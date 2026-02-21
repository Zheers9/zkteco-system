<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\ZktecoController;
use App\Http\Controllers\UniversityUserController;
use App\Http\Controllers\AdmsController;

// ADMS / BioTime Communication Routes
Route::any('/iclock/cdata', [AdmsController::class, 'cdata']);
Route::any('/iclock/getrequest', [AdmsController::class, 'getrequest']);
Route::any('/iclock/devicecmd', [AdmsController::class, 'devicecmd']);

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Devices Management
// Transfer Data (Must be before resource to avoid ID conflict)
Route::get('/devices/transfer', [DeviceController::class, 'transferIndex'])->name('devices.transfer.index');
Route::get('/devices/{device}/fetch-users', [DeviceController::class, 'fetchDeviceUsers'])->name('devices.transfer.fetch');
Route::post('/devices/transfer', [DeviceController::class, 'transfer'])->name('devices.transfer.store');

Route::resource('devices', DeviceController::class);
Route::get('/devices/{device}/users', [DeviceController::class, 'users'])->name('devices.users');
Route::get('/devices/{device}/attendance', [DeviceController::class, 'attendance'])->name('devices.attendance');

Route::post('/devices/{device}/connect', [DeviceController::class, 'testConnection'])->name('devices.connect');
Route::post('/devices/{device}/sync-users', [DeviceController::class, 'syncUsers'])->name('devices.sync-users');
Route::post('/devices/{device}/sync-attendance', [DeviceController::class, 'syncAttendance'])->name('devices.sync-attendance');
Route::get('/devices/{device}/sync-progress', [DeviceController::class, 'syncProgress'])->name('devices.sync-progress');

// Sync All Devices
Route::get('/sync-all', [DeviceController::class, 'syncAllPage'])->name('devices.sync-all');
Route::post('/sync-all/ping', [DeviceController::class, 'pingAll'])->name('devices.ping-all');
Route::post('/sync-all/dispatch', [DeviceController::class, 'dispatchAll'])->name('devices.dispatch-all');



// Attendance Global
Route::get('/attendance', [App\Http\Controllers\AttendanceController::class, 'index'])->name('attendance.index');
Route::get('/attendance/report', [App\Http\Controllers\AttendanceController::class, 'report'])->name('attendance.report');
Route::get('/attendance/analytics', [App\Http\Controllers\AttendanceController::class, 'analytics'])->name('attendance.analytics');
Route::get('/attendance/analytics/{user}', [App\Http\Controllers\AttendanceController::class, 'analyticsDetails'])->name('attendance.analytics.details');
Route::get('/attendance/payroll', [App\Http\Controllers\AttendanceController::class, 'payroll'])->name('attendance.payroll');
Route::post('/attendance/settings', [App\Http\Controllers\AttendanceController::class, 'updateSettings'])->name('attendance.settings.update');
Route::get('/attendance/export', [App\Http\Controllers\AttendanceController::class, 'export'])->name('attendance.export');

// Schedules Management
Route::get('/schedules', [App\Http\Controllers\ScheduleController::class, 'index'])->name('schedules.index');
Route::post('/schedules/update-all', [App\Http\Controllers\ScheduleController::class, 'update'])->name('schedules.update_all');
Route::post('/schedules/period', [App\Http\Controllers\ScheduleController::class, 'storePeriod'])->name('schedules.periods.store');
Route::delete('/schedules/period', [App\Http\Controllers\ScheduleController::class, 'destroyPeriod'])->name('schedules.periods.destroy');

// Device Users - List all users from all devices
Route::get('/device-users', [DeviceController::class, 'allUsers'])->name('device-users.index');

// University Users Management
Route::get('/university-users', [UniversityUserController::class, 'index'])->name('university-users.index');
Route::post('/university-users', [UniversityUserController::class, 'store'])->name('university-users.store');
Route::post('/university-users/import', [UniversityUserController::class, 'import'])->name('university-users.import');
Route::post('/university-users/{universityUser}/assign', [UniversityUserController::class, 'assign'])->name('university-users.assign');
Route::delete('/university-users/{universityUser}', [UniversityUserController::class, 'destroy'])->name('university-users.destroy');

// Permissions Management
Route::resource('permissions', App\Http\Controllers\PermissionController::class)->only(['index', 'create', 'store', 'destroy']);

// Departments Management
Route::resource('departments', App\Http\Controllers\DepartmentController::class);
Route::post('/departments/{id}/users', [App\Http\Controllers\DepartmentController::class, 'addUsers'])->name('departments.users.add');
Route::delete('/departments/{id}/users/{userId}', [App\Http\Controllers\DepartmentController::class, 'removeUser'])->name('departments.users.remove');

// Legacy routes (optional, kept for reference if needed)
Route::get('/legacy', [ZktecoController::class, 'index'])->name('zkteco.legacy');

// Locale Switcher
Route::get('locale/{lang}', function ($lang) {
    if (in_array($lang, ['en', 'ar'])) {
        session(['locale' => $lang]);
    }
    return back();
})->name('locale');
