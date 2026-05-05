<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Daily school attendance windows (wall clock in school timezone)
    |--------------------------------------------------------------------------
    |
    | Used for student daily check-in/check-out (not schedule-based).
    | Times are H:i or H:i:s interpreted in config('app.school_timezone').
    |
    */

    'check_in' => [
        'open' => env('DAILY_CHECK_IN_OPEN', '06:00'),
        'on_time_until' => env('DAILY_CHECK_IN_ON_TIME_UNTIL', '07:00'),
        'close' => env('DAILY_CHECK_IN_CLOSE', '09:00'),
    ],

    'check_out' => [
        'open' => env('DAILY_CHECK_OUT_OPEN', '13:00'),
        'close' => env('DAILY_CHECK_OUT_CLOSE', '18:00'),
    ],

];
