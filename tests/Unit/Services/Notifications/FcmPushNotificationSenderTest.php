<?php

namespace Tests\Unit\Services\Notifications;

use App\Services\Notifications\FcmPushNotificationSender;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class FcmPushNotificationSenderTest extends TestCase
{
    #[Test]
    public function test_throws_when_firebase_is_not_configured(): void
    {
        Config::set('services.fcm.project_id', '');
        Config::set('services.fcm.credentials', '');

        $sender = new FcmPushNotificationSender;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Firebase Cloud Messaging is not configured');

        $sender->sendToToken('token', 'T', 'B', ['type' => 'x']);
    }

    #[Test]
    public function test_posts_http_v1_payload_and_succeeds(): void
    {
        Config::set('services.fcm.project_id', 'unit-test-project');
        Config::set('services.fcm.credentials', __FILE__);

        Http::fake([
            'https://fcm.googleapis.com/v1/projects/unit-test-project/messages:send' => Http::response([
                'name' => 'projects/unit-test-project/messages/0',
            ], 200),
        ]);

        $sender = new class extends FcmPushNotificationSender
        {
            protected function resolveAccessToken(string $credentialsPath): string
            {
                return 'oauth-unit-test-token';
            }
        };

        $sender->sendToToken('device-fcm-token', 'Judul', 'Isi', [
            'type' => 'teacher_schedule_start_reminder',
            'schedule_id' => '99',
            'subject_name' => 'PJOK',
            'start_time' => '07:00',
            'end_time' => '09:15',
        ]);

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/v1/projects/unit-test-project/messages:send')) {
                return false;
            }

            $authorization = $request->header('Authorization');
            if ($authorization === null || ($authorization[0] ?? '') !== 'Bearer oauth-unit-test-token') {
                return false;
            }

            $body = $request->data();
            if (! is_array($body) || ! isset($body['message'])) {
                return false;
            }

            $message = $body['message'];
            if (($message['token'] ?? null) !== 'device-fcm-token') {
                return false;
            }
            if (($message['notification']['title'] ?? null) !== 'Judul') {
                return false;
            }
            if (($message['android']['priority'] ?? null) !== 'HIGH') {
                return false;
            }
            if (($message['android']['notification']['channel_id'] ?? null) !== 'schedule_reminder_channel') {
                return false;
            }

            return ($message['data']['type'] ?? null) === 'teacher_schedule_start_reminder'
                && ($message['data']['schedule_id'] ?? null) === '99';
        });
    }

    #[Test]
    public function test_throws_with_firebase_error_body_on_http_failure(): void
    {
        Config::set('services.fcm.project_id', 'unit-test-project');
        Config::set('services.fcm.credentials', __FILE__);

        Http::fake([
            'https://fcm.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 404,
                    'message' => 'Requested entity was not found.',
                    'status' => 'NOT_FOUND',
                ],
            ], 404),
        ]);

        $sender = new class extends FcmPushNotificationSender
        {
            protected function resolveAccessToken(string $credentialsPath): string
            {
                return 'oauth-unit-test-token';
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('FCM HTTP v1 failed with status 404');

        $sender->sendToToken('invalid-token', 'T', 'B', ['type' => 'x']);
    }
}
