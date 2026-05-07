<?php

use App\Http\Controllers\Api\Mobile\MobileDeviceTokenController;
use App\Http\Controllers\Api\Mobile\MobileProfileController;
use App\Http\Controllers\Api\Student\StudentAttendanceController;
use App\Http\Controllers\Api\Student\StudentAuthController;
use App\Http\Controllers\Api\Student\StudentDailyAttendanceController;
use App\Http\Controllers\Api\Student\StudentPasswordController;
use App\Http\Controllers\Api\Student\StudentProfileController;
use App\Http\Controllers\Api\Student\StudentScheduleController;
use App\Http\Controllers\Api\Teacher\TeacherAttendanceController;
use App\Http\Controllers\Api\Teacher\TeacherAuthController;
use App\Http\Controllers\Api\Teacher\TeacherPasswordController;
use App\Http\Controllers\Api\Teacher\TeacherProfileController;
use App\Http\Controllers\Api\Teacher\TeacherScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| API routes for teacher and student mobile applications.
| All routes are prefixed with /api automatically.
|
*/

Route::prefix('mobile')->middleware(['auth:sanctum', 'mobile_profile'])->group(function () {
    Route::post('/device-token', [MobileDeviceTokenController::class, 'store']);
    Route::get('/profile', [MobileProfileController::class, 'show']);
    Route::post('/profile/photo', [MobileProfileController::class, 'updatePhoto']);
    Route::post('/profile/password', [MobileProfileController::class, 'updatePassword']);
});

// === API GURU ===
Route::prefix('v1/teacher')->name('api.teacher.')->group(function () {
    Route::post('/login', [TeacherAuthController::class, 'login'])
        ->name('login');

    Route::middleware(['auth:sanctum', 'role:teacher'])->group(function () {
        Route::get('/me', [TeacherProfileController::class, 'me'])
            ->name('me');
        Route::get('/schedule/by-day', [TeacherScheduleController::class, 'byDay'])
            ->name('schedule.by-day');
        Route::get('/schedule', [TeacherScheduleController::class, 'index'])
            ->name('schedule.index');
        Route::post('/attendance/check-in', [TeacherAttendanceController::class, 'checkIn'])
            ->name('attendance.check-in');
        Route::post('/attendance/check-out', [TeacherAttendanceController::class, 'checkOut'])
            ->name('attendance.check-out');
        Route::get('/attendance/today', [TeacherAttendanceController::class, 'today'])
            ->name('attendance.today');
        Route::post('/change-password', [TeacherPasswordController::class, 'change'])
            ->name('password.change');
        Route::post('/logout', [TeacherAuthController::class, 'logout'])
            ->name('logout');
    });
});

// === API SISWA ===
Route::prefix('v1/student')->name('api.student.')->group(function () {
    Route::post('/login', [StudentAuthController::class, 'login'])
        ->name('login');

    Route::middleware(['auth:sanctum', 'role:student'])->group(function () {
        Route::get('/me', [StudentProfileController::class, 'me'])
            ->name('me');
        Route::get('/schedule', [StudentScheduleController::class, 'index'])
            ->name('schedule.index');
        Route::post('/attendance/check-in', [StudentAttendanceController::class, 'checkIn'])
            ->name('attendance.check-in');
        Route::post('/attendance/check-out', [StudentAttendanceController::class, 'checkOut'])
            ->name('attendance.check-out');
        Route::get('/attendance/today', [StudentAttendanceController::class, 'today'])
            ->name('attendance.today');
        Route::post('/daily-attendance/check-in', [StudentDailyAttendanceController::class, 'checkIn'])
            ->name('daily-attendance.check-in');
        Route::post('/daily-attendance/check-out', [StudentDailyAttendanceController::class, 'checkOut'])
            ->name('daily-attendance.check-out');
        Route::get('/daily-attendance/today', [StudentDailyAttendanceController::class, 'today'])
            ->name('daily-attendance.today');
        Route::post('/change-password', [StudentPasswordController::class, 'change'])
            ->name('password.change');
        Route::post('/logout', [StudentAuthController::class, 'logout'])
            ->name('logout');
    });
});
