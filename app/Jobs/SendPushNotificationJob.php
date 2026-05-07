<?php

namespace App\Jobs;

use App\Models\ScheduleNotificationLog;
use App\Models\UserDeviceToken;
use App\Services\Notifications\FcmPushNotificationSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendPushNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $logId,
        public int $recipientId,
        public string $title,
        public string $body,
        public array $payload
    ) {}

    public function handle(FcmPushNotificationSender $sender): void
    {
        $log = ScheduleNotificationLog::query()->find($this->logId);
        if (! $log || $log->status !== 'pending') {
            return;
        }

        $deviceTokens = UserDeviceToken::query()
            ->where('user_id', $this->recipientId)
            ->where('platform', 'android')
            ->where('is_active', true)
            ->whereNotNull('token')
            ->pluck('token');

        if ($deviceTokens->isEmpty()) {
            $log->update([
                'status' => 'skipped',
                'error_message' => 'No active Android device token found.',
            ]);

            return;
        }

        $anySuccess = false;
        $lastError = null;

        foreach ($deviceTokens as $token) {
            try {
                $sender->sendToToken($token, $this->title, $this->body, $this->payload);
                $anySuccess = true;
            } catch (Throwable $exception) {
                $lastError = $exception->getMessage();

                if ($this->isInvalidTokenError($lastError)) {
                    UserDeviceToken::query()
                        ->where('user_id', $this->recipientId)
                        ->where('token', $token)
                        ->update(['is_active' => false]);
                }
            }
        }

        if ($anySuccess) {
            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);

            return;
        }

        if ($lastError !== null) {
            $log->update([
                'status' => 'failed',
                'error_message' => $lastError,
            ]);
        }
    }

    private function isInvalidTokenError(string $errorMessage): bool
    {
        $lower = strtolower($errorMessage);

        return str_contains($errorMessage, 'InvalidRegistration')
            || str_contains($errorMessage, 'NotRegistered')
            || str_contains($errorMessage, 'registration-token-not-registered')
            || str_contains($lower, 'unregistered')
            || str_contains($lower, 'not_found')
            || str_contains($lower, 'requested entity was not found');
    }
}
