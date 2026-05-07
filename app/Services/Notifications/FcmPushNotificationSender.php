<?php

namespace App\Services\Notifications;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FcmPushNotificationSender
{
    public function sendToToken(string $deviceToken, string $title, string $body, array $data): void
    {
        $serverKey = (string) config('services.fcm.server_key', '');
        $endpoint = (string) config('services.fcm.endpoint', 'https://fcm.googleapis.com/fcm/send');

        if ($serverKey === '') {
            throw new RuntimeException('FCM server key is not configured.');
        }

        $response = Http::withToken($serverKey)
            ->acceptJson()
            ->post($endpoint, [
                'to' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
                'priority' => 'high',
            ]);

        $this->ensureSuccessfulDelivery($response);
    }

    private function ensureSuccessfulDelivery(Response $response): void
    {
        if (! $response->successful()) {
            throw new RuntimeException('FCM request failed with status '.$response->status().'.');
        }

        $result = $response->json();

        if (($result['failure'] ?? 0) > 0) {
            $error = $result['results'][0]['error'] ?? 'Unknown FCM error.';
            throw new RuntimeException('FCM rejected token: '.$error);
        }
    }
}
