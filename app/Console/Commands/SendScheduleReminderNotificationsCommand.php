<?php

namespace App\Console\Commands;

use App\Services\ScheduleNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendScheduleReminderNotificationsCommand extends Command
{
    protected $signature = 'notifications:send-schedule-reminders {--at=}';

    protected $description = 'Send schedule reminder notifications for teachers and students.';

    public function handle(ScheduleNotificationService $service): int
    {
        $timeZone = (string) config('app.school_timezone', config('app.timezone'));
        $at = $this->option('at');

        $moment = $at
            ? CarbonImmutable::parse((string) $at, $timeZone)
            : CarbonImmutable::now($timeZone);

        $service->dispatchForCurrentMinute($moment);

        $this->info('Schedule reminder notification dispatch completed.');

        return self::SUCCESS;
    }
}
