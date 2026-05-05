<?php

namespace Tests\Unit\Casts;

use App\Models\DailyAttendance;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UtcDatetimeCastTest extends TestCase
{
    #[Test]
    public function test_store_and_hydrate_daily_attendance_check_in_independent_of_app_timezone(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);

        $incoming = Carbon::parse('2026-04-30 08:47:39', 'Asia/Jakarta');
        $model = new DailyAttendance;
        $model->check_in_at = $incoming;

        $this->assertSame('2026-04-30 01:47:39', $model->getAttributes()['check_in_at']);
        $this->assertSame(
            '2026-04-30 08:47:39',
            $model->check_in_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        );
        $this->assertSame(
            Carbon::parse('2026-04-30 01:47:39', 'UTC')->getTimestamp(),
            $model->check_in_at->getTimestamp(),
        );
    }
}
