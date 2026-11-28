<?php

use Illuminate\Support\Facades\Route;
use Modules\HR\Http\Controllers\Api\Portal\PortalAttendanceController;
use Modules\HR\Http\Controllers\Api\Portal\PortalAuthController;
use Modules\HR\Http\Controllers\Api\Portal\PortalManagerController;
use Modules\HR\Http\Controllers\Api\Portal\PortalRequestController;
use Modules\HR\Http\Controllers\Api\Portal\PortalSelfServiceController;

// Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
//     Route::apiResource('hrs', HRController::class)->names('hr');
// });

// ===== بوابة الموظف (تطبيق الموبايل) — /api/v1/employee-portal/* =====
// Rate limiters (per tenant + employee): HRServiceProvider::registerPortalRateLimiters()
Route::prefix('v1/employee-portal')->name('employee-portal.')->group(function (): void {
    Route::get('ping', [PortalAuthController::class, 'ping'])->middleware('throttle:employee-portal-public')->name('ping');
    Route::get('branding/logo', [PortalAuthController::class, 'brandingLogo'])->middleware('throttle:employee-portal-public')->name('branding.logo');
    Route::post('auth/login', [PortalAuthController::class, 'login'])->middleware('throttle:employee-portal-login')->name('login');

    Route::middleware('employee.portal:allow_pending,allow_inactive')->group(function (): void {
        Route::get('me', [PortalAuthController::class, 'me'])->name('me');
        Route::post('auth/logout', [PortalAuthController::class, 'logout'])->name('logout');
    });

    Route::middleware('employee.portal:allow_pending')->group(function (): void {
        Route::post('me/password', [PortalAuthController::class, 'changePassword'])->middleware('throttle:employee-portal-write')->name('password');
        Route::post('me/fcm-token', [PortalAuthController::class, 'fcmToken'])->middleware('throttle:employee-portal-write')->name('fcm-token');
    });

    Route::middleware('employee.portal')->group(function (): void {
        Route::get('attendance/today', [PortalAttendanceController::class, 'today'])->name('attendance.today');
        Route::get('attendance/history', [PortalAttendanceController::class, 'history'])->name('attendance.history');
        Route::post('attendance', [PortalAttendanceController::class, 'store'])->middleware('throttle:employee-portal-punch')->name('attendance.store');

        Route::get('leave-types', [PortalRequestController::class, 'leaveTypes'])->name('leave-types');
        Route::get('leave-preview', [PortalRequestController::class, 'leavePreview'])->name('leave-preview');
        Route::get('requests', [PortalRequestController::class, 'index'])->name('requests.index');
        Route::post('requests/{type}', [PortalRequestController::class, 'store'])
            ->whereIn('type', ['leave', 'permission', 'errand', 'advance', 'attendance_correction', 'document'])
            ->middleware('throttle:employee-portal-write')
            ->name('requests.store');
        Route::get('requests/{id}', [PortalRequestController::class, 'show'])->whereNumber('id')->name('requests.show');
        Route::post('requests/{id}/cancel', [PortalRequestController::class, 'cancel'])->whereNumber('id')->middleware('throttle:employee-portal-write')->name('requests.cancel');
        Route::get('requests/{id}/document', [PortalRequestController::class, 'document'])->whereNumber('id')->name('requests.document');

        Route::get('balances', [PortalSelfServiceController::class, 'balances'])->name('balances');
        Route::get('finance', [PortalSelfServiceController::class, 'finance'])->name('finance');
        Route::get('payslips', [PortalSelfServiceController::class, 'payslips'])->name('payslips');
        Route::get('covenants', [PortalSelfServiceController::class, 'covenants'])->name('covenants');
        Route::get('me/instant-evaluations', [PortalSelfServiceController::class, 'instantEvaluations'])->name('me.instant-evaluations');
        Route::get('notifications', [PortalSelfServiceController::class, 'notifications'])->name('notifications');
        Route::post('notifications/read', [PortalSelfServiceController::class, 'markNotificationsRead'])->name('notifications.read');

        Route::get('team/today', [PortalManagerController::class, 'teamToday'])->name('team.today');
        Route::get('team/requests', [PortalManagerController::class, 'teamRequests'])->name('team.requests');
        Route::get('team/requests/{id}', [PortalManagerController::class, 'show'])->whereNumber('id')->name('team.requests.show');
        Route::post('team/requests/{id}/approve', [PortalManagerController::class, 'approve'])->whereNumber('id')->middleware('throttle:employee-portal-write')->name('team.requests.approve');
        Route::post('team/requests/{id}/reject', [PortalManagerController::class, 'reject'])->whereNumber('id')->middleware('throttle:employee-portal-write')->name('team.requests.reject');
    });
});
