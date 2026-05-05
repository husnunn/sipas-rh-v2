<?php

namespace App\Enums;

enum DailyAttendancePhysicalStatus: string
{
    case Present = 'present';
    case Late = 'late';
}
