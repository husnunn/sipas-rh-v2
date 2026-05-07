<?php

namespace App\Services;

use App\Jobs\SendPushNotificationJob;
use App\Models\ClassRoom;
use App\Models\Schedule;
use App\Models\ScheduleNotificationLog;
use App\Models\SchoolYear;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;

class ScheduleNotificationService
{
    public function dispatchForCurrentMinute(?CarbonImmutable $now = null): void
    {
        $timeZone = (string) config('app.school_timezone', config('app.timezone'));
        $current = $now?->setTimezone($timeZone) ?? CarbonImmutable::now($timeZone);

        $activeSchoolYear = SchoolYear::query()->active()->first();
        if (! $activeSchoolYear) {
            return;
        }

        $dayOfWeek = $current->dayOfWeekIso;
        if ($dayOfWeek > 6) {
            return;
        }

        $windowStart = $current->startOfMinute();
        $windowEnd = $current->endOfMinute();

        $this->dispatchTeacherNotifications($activeSchoolYear->id, $dayOfWeek, $windowStart, $windowEnd);
        $this->dispatchStudentFirstScheduleNotifications($activeSchoolYear->id, $dayOfWeek, $windowStart, $windowEnd);
    }

    private function dispatchTeacherNotifications(
        int $schoolYearId,
        int $dayOfWeek,
        CarbonImmutable $windowStart,
        CarbonImmutable $windowEnd
    ): void {
        $schedules = Schedule::query()
            ->where('school_year_id', $schoolYearId)
            ->where('day_of_week', $dayOfWeek)
            ->active()
            ->with(['subject:id,name', 'teacherProfile.user:id,roles,is_active'])
            ->get();

        foreach ($schedules as $schedule) {
            $teacherUser = $schedule->teacherProfile?->user;
            if (! $teacherUser || ! $teacherUser->isTeacher() || ! $teacherUser->is_active) {
                continue;
            }

            $events = [
                'teacher_schedule_start_reminder' => CarbonImmutable::parse($schedule->start_time)->subMinutes(15),
                'teacher_schedule_end_reminder' => CarbonImmutable::parse($schedule->end_time)->subMinutes(15),
                'teacher_schedule_ended' => CarbonImmutable::parse($schedule->end_time),
            ];

            foreach ($events as $eventType => $triggerTime) {
                $triggerAt = $windowStart->setTime($triggerTime->hour, $triggerTime->minute);
                if (! $triggerAt->betweenIncluded($windowStart, $windowEnd)) {
                    continue;
                }

                $title = $this->teacherTitleForEvent($eventType);
                $subjectName = (string) ($schedule->subject?->name ?? '-');
                $body = match ($eventType) {
                    'teacher_schedule_start_reminder' => "Anda akan mengajar {$subjectName} pukul {$schedule->start_time} - {$schedule->end_time}.",
                    'teacher_schedule_end_reminder' => "Mata pelajaran {$subjectName} akan selesai pukul {$schedule->end_time}.",
                    default => "Waktu mengajar {$subjectName} telah selesai.",
                };

                $payload = [
                    'type' => $eventType,
                    'title' => $title,
                    'body' => $body,
                    'schedule_id' => (string) $schedule->id,
                    'subject_name' => $subjectName,
                    'start_time' => substr((string) $schedule->start_time, 0, 5),
                    'end_time' => substr((string) $schedule->end_time, 0, 5),
                ];

                $notificationKey = "{$eventType}:{$schedule->id}:{$teacherUser->id}:{$windowStart->toDateString()}";

                $this->reserveAndDispatch(
                    notificationKey: $notificationKey,
                    eventType: $eventType,
                    targetRole: 'teacher',
                    recipientId: $teacherUser->id,
                    scheduleId: $schedule->id,
                    classId: $schedule->class_id,
                    scheduledAt: $triggerAt,
                    title: $title,
                    body: $body,
                    payload: $payload,
                );
            }
        }
    }

    private function dispatchStudentFirstScheduleNotifications(
        int $schoolYearId,
        int $dayOfWeek,
        CarbonImmutable $windowStart,
        CarbonImmutable $windowEnd
    ): void {
        $firstSchedules = Schedule::query()
            ->select('schedules.*')
            ->joinSub(
                Schedule::query()
                    ->selectRaw('class_id, MIN(start_time) as first_start_time')
                    ->where('school_year_id', $schoolYearId)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_active', true)
                    ->groupBy('class_id'),
                'first_schedules',
                function ($join): void {
                    $join->on('schedules.class_id', '=', 'first_schedules.class_id')
                        ->on('schedules.start_time', '=', 'first_schedules.first_start_time');
                }
            )
            ->where('schedules.school_year_id', $schoolYearId)
            ->where('schedules.day_of_week', $dayOfWeek)
            ->where('schedules.is_active', true)
            ->with(['classRoom:id,name', 'subject:id,name'])
            ->get();

        foreach ($firstSchedules as $schedule) {
            $triggerAt = $windowStart
                ->setTimeFromTimeString((string) $schedule->start_time)
                ->subMinutes(15);

            if (! $triggerAt->betweenIncluded($windowStart, $windowEnd)) {
                continue;
            }

            $classRoom = ClassRoom::query()
                ->with([
                    'students' => function ($query) use ($schoolYearId): void {
                        $query->wherePivot('school_year_id', $schoolYearId)
                            ->wherePivot('is_active', true)
                            ->with('user:id,roles,is_active');
                    },
                ])
                ->find($schedule->class_id);

            if (! $classRoom) {
                continue;
            }

            foreach ($classRoom->students as $student) {
                $studentUser = $student->user;
                if (! $studentUser || ! $studentUser->isStudent() || ! $studentUser->is_active) {
                    continue;
                }

                $title = 'Kegiatan Belajar Akan Dimulai';
                $startTime = substr((string) $schedule->start_time, 0, 5);
                $body = "Kegiatan belajar hari ini dimulai pukul {$startTime}. Persiapkan diri Anda.";
                $eventType = 'student_first_schedule_reminder';
                $notificationKey = "{$eventType}:{$schedule->class_id}:{$studentUser->id}:{$windowStart->toDateString()}";

                $payload = [
                    'type' => $eventType,
                    'title' => $title,
                    'body' => $body,
                    'class_id' => (string) $schedule->class_id,
                    'first_schedule_id' => (string) $schedule->id,
                    'start_time' => $startTime,
                ];

                $this->reserveAndDispatch(
                    notificationKey: $notificationKey,
                    eventType: $eventType,
                    targetRole: 'student',
                    recipientId: $studentUser->id,
                    scheduleId: $schedule->id,
                    classId: $schedule->class_id,
                    scheduledAt: $triggerAt,
                    title: $title,
                    body: $body,
                    payload: $payload,
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function reserveAndDispatch(
        string $notificationKey,
        string $eventType,
        string $targetRole,
        int $recipientId,
        int $scheduleId,
        int $classId,
        CarbonImmutable $scheduledAt,
        string $title,
        string $body,
        array $payload
    ): void {
        try {
            $log = ScheduleNotificationLog::query()->create([
                'notification_key' => $notificationKey,
                'event_type' => $eventType,
                'target_role' => $targetRole,
                'recipient_id' => $recipientId,
                'schedule_id' => $scheduleId,
                'class_id' => $classId,
                'scheduled_at' => $scheduledAt,
                'status' => 'pending',
            ]);
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                return;
            }

            throw $exception;
        }

        SendPushNotificationJob::dispatch(
            logId: $log->id,
            recipientId: $recipientId,
            title: $title,
            body: $body,
            payload: $payload,
        );
    }

    private function teacherTitleForEvent(string $eventType): string
    {
        return match ($eventType) {
            'teacher_schedule_start_reminder' => 'Jadwal Mengajar Akan Dimulai',
            'teacher_schedule_end_reminder' => 'Waktu Mengajar Hampir Selesai',
            default => 'Waktu Mengajar Selesai',
        };
    }
}
