<?php

use App\Http\Controllers\Admin\AcademicCalendarEventController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AttendanceDayOverrideController;
use App\Http\Controllers\Admin\AttendanceMonitoringController;
use App\Http\Controllers\Admin\AttendanceSiteController;
use App\Http\Controllers\Admin\ClassRoomController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\SchoolYearController;
use App\Http\Controllers\Admin\StudentAttendanceManualStatusController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\WilayahController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

// Admin area — requires auth + admin role
Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        // Master Data CRUD
        Route::delete('students/bulk-destroy', [StudentController::class, 'bulkDestroy'])
            ->name('students.bulk-destroy');
        Route::get('students/import', [StudentController::class, 'importForm'])->name('students.import');
        Route::post('students/import', [StudentController::class, 'import'])->name('students.import.store');
        Route::prefix('wilayah')->name('wilayah.')->group(function () {
            Route::get('/provinces', [WilayahController::class, 'provinces'])->name('provinces');
            Route::get('/regencies', [WilayahController::class, 'regencies'])->name('regencies');
            Route::get('/districts', [WilayahController::class, 'districts'])->name('districts');
            Route::get('/villages', [WilayahController::class, 'villages'])->name('villages');
            Route::get('/village-context', [WilayahController::class, 'villageContext'])->name('village-context');
        });

        Route::resource('students', StudentController::class);
        Route::get('/students/{student}/attendance-export', [StudentController::class, 'exportAttendance'])
            ->name('students.attendance-export');
        Route::post('/students/{student}/attendance-manual-statuses', [StudentAttendanceManualStatusController::class, 'store'])
            ->name('students.attendance-manual-statuses.store');
        Route::put('/students/{student}/attendance-manual-statuses/{manualStatus}', [StudentAttendanceManualStatusController::class, 'update'])
            ->name('students.attendance-manual-statuses.update');
        Route::patch('/students/{student}/attendance-manual-statuses/{manualStatus}/cancel', [StudentAttendanceManualStatusController::class, 'cancel'])
            ->name('students.attendance-manual-statuses.cancel');
        Route::delete('teachers/bulk-destroy', [TeacherController::class, 'bulkDestroy'])
            ->name('teachers.bulk-destroy');
        Route::resource('teachers', TeacherController::class);
        Route::delete('classes/bulk-destroy', [ClassRoomController::class, 'bulkDestroy'])
            ->name('classes.bulk-destroy');
        Route::resource('classes', ClassRoomController::class);
        Route::delete('subjects/bulk-destroy', [SubjectController::class, 'bulkDestroy'])
            ->name('subjects.bulk-destroy');
        Route::resource('subjects', SubjectController::class);

        // Jadwal (schedule management with conflict validation)
        Route::delete('schedules/bulk-destroy', [ScheduleController::class, 'bulkDestroy'])
            ->name('schedules.bulk-destroy');
        Route::resource('schedules', ScheduleController::class);

        // Tahun ajaran
        Route::patch('school-years/{school_year}/set-active', [SchoolYearController::class, 'setActive'])
            ->name('school-years.set-active');
        Route::resource('school-years', SchoolYearController::class);
        Route::resource('attendance-sites', AttendanceSiteController::class);
        Route::patch('/attendance-sites/{attendanceSite}/toggle-active', [AttendanceSiteController::class, 'toggleActive'])
            ->name('attendance-sites.toggle-active');
        Route::resource('attendance-day-overrides', AttendanceDayOverrideController::class);
        Route::patch('/attendance-day-overrides/{attendanceDayOverride}/toggle-active', [AttendanceDayOverrideController::class, 'toggleActive'])
            ->name('attendance-day-overrides.toggle-active');
        Route::patch('/attendance-day-overrides/{attendanceDayOverride}/cancel', [AttendanceDayOverrideController::class, 'cancel'])
            ->name('attendance-day-overrides.cancel');
        Route::get('/attendance-records', [AttendanceMonitoringController::class, 'index'])
            ->name('attendance-records.index');
        Route::get('/attendance-records/print', [AttendanceMonitoringController::class, 'printReport'])
            ->name('attendance-records.print');
        Route::get('/attendance-records/export', [AttendanceMonitoringController::class, 'exportCsv'])
            ->name('attendance-records.export');
        Route::resource('academic-calendar-events', AcademicCalendarEventController::class);

        // Manajemen Akun
        Route::prefix('accounts')->name('accounts.')->group(function () {
            Route::get('/', [AccountController::class, 'index'])->name('index');
            Route::get('/create', [AccountController::class, 'create'])->name('create');
            Route::delete('/bulk-destroy', [AccountController::class, 'bulkDestroy'])->name('bulk-destroy');
            Route::get('/{user}', [AccountController::class, 'show'])->name('show');
            Route::post('/', [AccountController::class, 'store'])->name('store');
            Route::post('/{user}/reset-password', [AccountController::class, 'resetPassword'])
                ->name('reset-password');
            Route::patch('/{user}/toggle-active', [AccountController::class, 'toggleActive'])
                ->name('toggle-active');
        });
    });

// Fortify settings routes
require __DIR__.'/settings.php';
