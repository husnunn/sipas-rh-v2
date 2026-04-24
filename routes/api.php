<?php

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

// === API GURU ===
Route::prefix('v1/teacher')->name('api.teacher.')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\Teacher\TeacherAuthController::class, 'login'])
        ->name('login');

    Route::middleware(['auth:sanctum', 'role:teacher'])->group(function () {
        Route::get('/me', [\App\Http\Controllers\Api\Teacher\TeacherProfileController::class, 'me'])
            ->name('me');
        Route::get('/schedule', [\App\Http\Controllers\Api\Teacher\TeacherScheduleController::class, 'index'])
            ->name('schedule.index');
        Route::post('/change-password', [\App\Http\Controllers\Api\Teacher\TeacherPasswordController::class, 'change'])
            ->name('password.change');
        Route::post('/logout', [\App\Http\Controllers\Api\Teacher\TeacherAuthController::class, 'logout'])
            ->name('logout');
    });
});

// === API SISWA ===
Route::prefix('v1/student')->name('api.student.')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\Student\StudentAuthController::class, 'login'])
        ->name('login');

    Route::middleware(['auth:sanctum', 'role:student'])->group(function () {
        Route::get('/me', [\App\Http\Controllers\Api\Student\StudentProfileController::class, 'me'])
            ->name('me');
        Route::get('/schedule', [\App\Http\Controllers\Api\Student\StudentScheduleController::class, 'index'])
            ->name('schedule.index');
        Route::post('/change-password', [\App\Http\Controllers\Api\Student\StudentPasswordController::class, 'change'])
            ->name('password.change');
        Route::post('/logout', [\App\Http\Controllers\Api\Student\StudentAuthController::class, 'logout'])
            ->name('logout');
    });
});
