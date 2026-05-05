<?php

namespace App\Enums;

enum AttendanceManualRecordStatus: string
{
    case Approved = 'approved';
    case Cancelled = 'cancelled';
}
