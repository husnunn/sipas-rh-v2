<?php

namespace App\Services\Notifications;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FcmPushNotificationSender
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function sendToToken(string $deviceToken, string $title, string $body, array $data): void
    {
        $projectId = (string) config('services.fcm.project_id', '');
        $credentialsPath = (string) config('services.fcm.credentials', '');

        if ($projectId === '' || $credentialsPath === '' || ! is_readable($credentialsPath)) {
            throw new RuntimeException('Firebase Cloud Messaging is not configured (FIREBASE_PROJECT_ID / FIREBASE_CREDENTIALS).');
        }

        $endpointFormat = (string) config(
            'services.fcm.endpoint',
            'https://fcm.googleapis.com/v1/projects/%s/messages:send'
        );

        $url = sprintf($endpointFormat, $projectId);
        $accessToken = $this->resolveAccessToken($credentialsPath);

        $message = $this->buildV1Message($deviceToken, $title, $body, $data);

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->post($url, [
                'message' => $message,
            ]);

        $this->ensureSuccessfulV1($response);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildV1Message(string $deviceToken, string $title, string $body, array $data): array
    {
        $stringData = [];
        foreach ($data as $key => $value) {
            if (! is_string($key)) {
                continue;
            }
            $stringData[$key] = $this->stringDataValue($value);
        }

        return [
            'token' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $stringData,
            'android' => [
                'priority' => 'HIGH',
                'notification' => [
                    'channel_id' => 'schedule_reminder_channel',
                    'sound' => 'default',
                ],
            ],
        ];
    }

    protected function resolveAccessToken(string $credentialsPath): string
    {
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
        $credentials = new ServiceAccountCredentials($scopes, $credentialsPath);
        $token = $credentials->fetchAuthToken();

        if (empty($token['access_token']) || ! is_string($token['access_token'])) {
            throw new RuntimeException('Failed to obtain Firebase OAuth access token.');
        }

        return $token['access_token'];
    }

    private function stringDataValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if ($value === null) {
            return '';
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private function ensureSuccessfulV1(Response $response): void
    {
        $body = $response->body();

        if (! $response->successful()) {
            throw new RuntimeException('FCM HTTP v1 failed with status '.$response->status().': '.$body);
        }

        $json = $response->json();
        if (is_array($json) && isset($json['error'])) {
            $msg = is_string($json['error']) ? $json['error'] : ($json['error']['message'] ?? json_encode($json['error'], JSON_THROW_ON_ERROR));
            throw new RuntimeException('FCM rejected request: '.$msg);
        }
    }
}
