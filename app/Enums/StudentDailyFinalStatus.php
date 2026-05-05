<?php

namespace App\Enums;

enum StudentDailyFinalStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case Excused = 'excused';
    case Sick = 'sick';
    case Dispensation = 'dispensation';
    case Absent = 'absent';
    case Holiday = 'holiday';
}
