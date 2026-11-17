<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\Admin\EmployeeController;
use App\Http\Controllers\Api\Admin\OfficeLocationController;
use App\Http\Controllers\Api\Admin\AttendanceReportController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\WorkScheduleController;
use App\Http\Controllers\Api\Admin\KMeansController;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/register-admin', [AuthController::class, 'registerAdmin'])->middleware('throttle:10,1');
Route::get('/attendance/attachment/{id}', [AttendanceController::class, 'getAttachment']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/profile', [ProfileController::class, 'profile']);
    Route::post('/profile', [ProfileController::class, 'updateProfile']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/offices', [OfficeLocationController::class, 'index']);
    Route::get('/work-schedule', [AttendanceController::class, 'getSchedule']);

    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);
    Route::post('/attendance/leave', [AttendanceController::class, 'requestLeave']);
    Route::get('/attendance/history', [AttendanceController::class, 'history']);
    
    Route::middleware('admin')->group(function () {
    
        // Manajemen Karyawan
        Route::get('/admin/employees', [EmployeeController::class, 'index']);
        Route::post('/admin/employees', [EmployeeController::class, 'store']);
        Route::get('/admin/employees/{employee}', [EmployeeController::class, 'show']);
        Route::put('/admin/employees/{employee}', [EmployeeController::class, 'update']);
        Route::delete('/admin/employees/{employee}', [EmployeeController::class, 'destroy']);

        // Manajemen Akun Admin
        Route::get('/admin/admins', [AdminUserController::class, 'index']);
        Route::post('/admin/admins', [AdminUserController::class, 'store']);
        Route::put('/admin/admins/{user}', [AdminUserController::class, 'update']);
        Route::delete('/admin/admins/{user}', [AdminUserController::class, 'destroy']);
        Route::get('/admin/users-overview', [AdminUserController::class, 'overview']);

        // Pengaturan Jam Kerja & Presensi
        Route::get('/admin/work-schedule', [WorkScheduleController::class, 'show']);
        Route::put('/admin/work-schedule', [WorkScheduleController::class, 'update']);

        // Lokasi Kantor CV Berkat Sentosa
        Route::get('/admin/offices', [OfficeLocationController::class, 'index']);
        Route::get('/admin/office', [OfficeLocationController::class, 'index']);
        Route::post('/admin/offices', [OfficeLocationController::class, 'store']);
        Route::put('/admin/offices/{office}', [OfficeLocationController::class, 'update']);
        Route::put('/admin/office', [OfficeLocationController::class, 'update']);

        // Rekap Absensi & Catat Izin/Sakit/Alpa Manual
        Route::get('/admin/attendances', [AttendanceReportController::class, 'index']);
        Route::post('/admin/attendances/record-status', [AttendanceReportController::class, 'recordManualStatus']);
        Route::get('/admin/dashboard-stats', [AttendanceReportController::class, 'stats']);

        // Alur Persetujuan Izin Karyawan (Leave Approval Workflow)
        Route::get('/admin/leave-requests', [AttendanceReportController::class, 'leaveRequests']);
        Route::post('/admin/leave-requests/{attendance}/approve', [AttendanceReportController::class, 'approveLeave']);
        Route::post('/admin/leave-requests/{attendance}/reject', [AttendanceReportController::class, 'rejectLeave']);

        // Machine Learning K-Means Clustering Analisis Kedisiplinan
        Route::get('/admin/kmeans-clustering', [KMeansController::class, 'index']);
    });

});