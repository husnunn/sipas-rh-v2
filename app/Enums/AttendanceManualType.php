<?php

namespace App\Enums;

enum AttendanceManualType: string
{
    case Excused = 'excused';
    case Sick = 'sick';
    case Dispensation = 'dispensation';
}
