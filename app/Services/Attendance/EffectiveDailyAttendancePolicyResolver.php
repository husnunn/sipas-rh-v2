<?php

namespace App\Services\Attendance;

use App\Models\AttendanceDayOverride;
use App\Models\AttendanceSite;
use Carbon\CarbonInterface;

class EffectiveDailyAttendancePolicyResolver
{
    /**
     * @return array{
     *   site: AttendanceSite|null,
     *   policy: array{
     *     check_in_open_at: string,
     *     check_in_on_time_until: string,
     *     check_in_close_at: string,
     *     check_out_open_at: string,
     *     check_out_close_at: string
     *   }
     * }
     */
    public function resolveForSite(?AttendanceSite $site, ?AttendanceDayOverride $override = null): array
    {
        $policy = $this->basePolicyFromSite($site);

        if ($override?->override_attendance_policy) {
            $policy = [
                'check_in_open_at' => (string) ($override->check_in_open_at ?: $policy['check_in_open_at']),
                'check_in_on_time_until' => (string) ($override->check_in_on_time_until ?: $policy['check_in_on_time_until']),
                'check_in_close_at' => (string) ($override->check_in_close_at ?: $policy['check_in_close_at']),
                'check_out_open_at' => (string) ($override->check_out_open_at ?: $policy['check_out_open_at']),
                'check_out_close_at' => (string) ($override->check_out_close_at ?: $policy['check_out_close_at']),
            ];
        }

        return [
            'site' => $site,
            'policy' => $policy,
        ];
    }

    /**
     * @return array{
     *   site: AttendanceSite|null,
     *   policy: array{
     *     check_in_open_at: string,
     *     check_in_on_time_until: string,
     *     check_in_close_at: string,
     *     check_out_open_at: string,
     *     check_out_close_at: string
     *   }
     * }
     */
    public function resolveForDay(CarbonInterface $dayInSchoolTimezone, ?AttendanceSite $preferredSite = null): array
    {
        $override = AttendanceDayOverride::query()
            ->active()
            ->forDate($dayInSchoolTimezone)
            ->orderByDesc('override_attendance_policy')
            ->orderBy('id')
            ->first();

        $site = $preferredSite;
        if (! $site && $override?->attendance_site_id) {
            $site = AttendanceSite::query()->whereKey($override->attendance_site_id)->where('is_active', true)->first();
        }

        if (! $site) {
            $site = AttendanceSite::query()->forApiPicker()->orderBy('id')->first();
        }

        return $this->resolveForSite($site, $override);
    }

    /**
     * @return array{
     *   check_in_open_at: string,
     *   check_in_on_time_until: string,
     *   check_in_close_at: string,
     *   check_out_open_at: string,
     *   check_out_close_at: string
     * }
     */
    private function basePolicyFromSite(?AttendanceSite $site): array
    {
        return [
            'check_in_open_at' => (string) ($site?->check_in_open_at ?: config('school_daily_attendance.check_in.open')),
            'check_in_on_time_until' => (string) ($site?->check_in_on_time_until ?: config('school_daily_attendance.check_in.on_time_until')),
            'check_in_close_at' => (string) ($site?->check_in_close_at ?: config('school_daily_attendance.check_in.close')),
            'check_out_open_at' => (string) ($site?->check_out_open_at ?: config('school_daily_attendance.check_out.open')),
            'check_out_close_at' => (string) ($site?->check_out_close_at ?: config('school_daily_attendance.check_out.close')),
        ];
    }
}
