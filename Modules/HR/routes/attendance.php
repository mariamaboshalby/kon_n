<?php

use Illuminate\Support\Facades\Route;
use Modules\HR\Http\Controllers\AttendanceController;
use Modules\HR\Http\Controllers\AttendanceProcessingController;
use Modules\HR\Http\Controllers\DailyReportController;
use Modules\HR\Http\Controllers\EmployeeAdvanceController;
use Modules\HR\Http\Controllers\EmployeeDeductionRewardController;
use Modules\HR\Http\Controllers\EmployeeFingerprintsReportController;
use Modules\HR\Http\Controllers\FlexibleSalaryProcessingController;
use Modules\HR\Http\Controllers\MonthlyActivityReportController;
use Modules\HR\Http\Controllers\ProjectAttendanceReportController;

Route::middleware(['auth'])->group(function () {
    Route::get('/attendance/processing/data', [AttendanceProcessingController::class, 'data'])
        ->name('hr.attendance.processing.data');
    Route::get('/attendance/processing/{processing}', [AttendanceProcessingController::class, 'show'])
        ->name('hr.attendance.processing.show');
    Route::post('/attendance/processing', [AttendanceProcessingController::class, 'process'])
        ->name('hr.attendance.processing.process');
    Route::post('/attendance/processing/{processing}/approve', [AttendanceProcessingController::class, 'approve'])
        ->name('hr.attendance.processing.approve');
    Route::post('/attendance/processing/{processing}/reject', [AttendanceProcessingController::class, 'reject'])
        ->name('hr.attendance.processing.reject');
    Route::delete('/attendance/processing/{processing}', [AttendanceProcessingController::class, 'destroy'])
        ->name('hr.attendance.processing.destroy');
    Route::get('/attendance/processing', [AttendanceProcessingController::class, 'index'])
        ->name('hr.attendance.processing');

    Route::prefix('attendances/flexible-salary')->name('hr.flexible-salary.processing.')->group(function () {
        Route::get('/data', [FlexibleSalaryProcessingController::class, 'data'])->name('data');
        Route::get('/employees', [FlexibleSalaryProcessingController::class, 'employees'])->name('employees');
        Route::get('/', [FlexibleSalaryProcessingController::class, 'index'])->name('index');
        Route::get('/create', [FlexibleSalaryProcessingController::class, 'create'])->name('create');
        Route::post('/', [FlexibleSalaryProcessingController::class, 'store'])->name('store');
        Route::get('/{processing}/edit', [FlexibleSalaryProcessingController::class, 'edit'])->name('edit');
        Route::put('/{processing}', [FlexibleSalaryProcessingController::class, 'update'])->name('update');
        Route::post('/{processing}/approve', [FlexibleSalaryProcessingController::class, 'approve'])->name('approve');
        Route::post('/{processing}/reject', [FlexibleSalaryProcessingController::class, 'reject'])->name('reject');
    });

    Route::get('/employee-advances/data', [EmployeeAdvanceController::class, 'data'])->name('hr.employee-advances.data');
    Route::get('/employee-advances', [EmployeeAdvanceController::class, 'index'])->name('hr.employee-advances.index');
    Route::post('/employee-advances', [EmployeeAdvanceController::class, 'store'])->name('hr.employee-advances.store');
    Route::put('/employee-advances/{advance}', [EmployeeAdvanceController::class, 'update'])->name('hr.employee-advances.update');
    Route::post('/employee-advances/{advance}/approve', [EmployeeAdvanceController::class, 'approve'])->name('hr.employee-advances.approve');
    Route::post('/employee-advances/{advance}/reject', [EmployeeAdvanceController::class, 'reject'])->name('hr.employee-advances.reject');
    Route::delete('/employee-advances/{advance}', [EmployeeAdvanceController::class, 'destroy'])->name('hr.employee-advances.destroy');

    Route::get('/employee-deductions-rewards/data', [EmployeeDeductionRewardController::class, 'data'])->name('hr.employee-deductions-rewards.data');
    Route::get('/employee-deductions-rewards', [EmployeeDeductionRewardController::class, 'index'])->name('hr.employee-deductions-rewards.index');
    Route::post('/employee-deductions-rewards', [EmployeeDeductionRewardController::class, 'store'])->name('hr.employee-deductions-rewards.store');
    Route::put('/employee-deductions-rewards/{item}', [EmployeeDeductionRewardController::class, 'update'])->name('hr.employee-deductions-rewards.update');
    Route::post('/employee-deductions-rewards/{item}/approve', [EmployeeDeductionRewardController::class, 'approve'])->name('hr.employee-deductions-rewards.approve');
    Route::delete('/employee-deductions-rewards/{item}', [EmployeeDeductionRewardController::class, 'destroy'])->name('hr.employee-deductions-rewards.destroy');

    Route::get('/attendance/data', [AttendanceController::class, 'data'])->name('hr.attendance.data');
    Route::post('/attendance/bulk-actions', [AttendanceController::class, 'bulkActions'])->name('hr.attendance.bulk-actions');
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('hr.attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('hr.attendance.store');
    Route::put('/attendance/{attendance}', [AttendanceController::class, 'update'])->name('hr.attendance.update');
    Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy'])->name('hr.attendance.destroy');

    Route::get('/attendance/reports/project-attendance', [ProjectAttendanceReportController::class, 'index'])
        ->name('hr.attendance.reports.project');

    Route::get('/attendance/reports/daily', [DailyReportController::class, 'index'])
        ->name('hr.attendance.reports.daily')->middleware('can:view Attendances');

    Route::get('/attendance/reports/daily/employee-comprehensive/{employee}', [DailyReportController::class, 'employeeComprehensiveReport'])
        ->name('hr.attendance.reports.daily.employee-comprehensive')->middleware('can:view Attendances');

    Route::get('/attendance/reports/monthly-activity', [MonthlyActivityReportController::class, 'index'])
        ->name('hr.attendance.reports.monthly-activity')->middleware('can:view Attendances');

    Route::get('/attendance/reports/employee-fingerprints', [EmployeeFingerprintsReportController::class, 'index'])
        ->name('hr.attendance.reports.employee-fingerprints')->middleware('can:view Attendances');
});
