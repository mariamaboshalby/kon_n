<?php

use Illuminate\Support\Facades\Route;
use Modules\HR\Http\Controllers\HRController;
use Modules\HR\Http\Controllers\KpiController;
use Modules\HR\Http\Controllers\CityController;
use Modules\HR\Http\Controllers\ErrandController;
use Modules\HR\Http\Controllers\CountryController;
use Modules\HR\Http\Controllers\CovenantController;
use Modules\HR\Http\Controllers\EmployeeController;
use Modules\HR\Http\Controllers\EmployeeEvaluationController;
use Modules\HR\Http\Controllers\InstantEvaluationController;
use Modules\HR\Http\Controllers\InstantEvaluationItemController;
use Modules\HR\Http\Controllers\AttendanceController;
use Modules\HR\Http\Controllers\DepartmentController;
use Modules\HR\Http\Controllers\EmployeeAuthController;
use Modules\HR\Http\Controllers\EmployeePortalAdminController;
use Modules\HR\Http\Controllers\EmployeesJobController;
use Modules\HR\Http\Controllers\StateController;
use Modules\HR\Http\Controllers\ShiftController;
use Modules\HR\Http\Controllers\WeeklyShiftBoardController;
use Modules\HR\Http\Controllers\WorkPermissionController;
use Modules\HR\Http\Controllers\MobileAttendanceController;
use Modules\HR\Http\Controllers\TownController;
use Modules\HR\Http\Controllers\AddressQuickStoreController;
use Modules\HR\Http\Controllers\LeaveTypeController;
use Modules\HR\Http\Controllers\LeaveBalanceController;
use Modules\HR\Http\Controllers\LeaveRequestController;
use Modules\HR\Http\Controllers\HrSettingController;
use Modules\HR\Http\Controllers\RecruitmentDashboardController;
use Modules\HR\Http\Controllers\ContractTypeController;
use Modules\HR\Http\Controllers\CvController;
use Modules\HR\Http\Controllers\ContractController;
use Modules\HR\Http\Controllers\JobPostingController;
use Modules\HR\Http\Controllers\InterviewController;
use Modules\HR\Http\Controllers\TerminationController;
use Modules\HR\Http\Controllers\OnboardingController;
use Modules\HR\Http\Controllers\ApplicantController;
use Modules\HR\Http\Controllers\LatestApplicantController;
use Modules\HR\Http\Controllers\JobApplicantController;


// Quick address create (Accounts forms) — auth only, no module.access:hr
Route::middleware(['auth', 'verified'])->prefix('api/addresses')->name('api.addresses.')->group(function () {
    Route::post('countries', [AddressQuickStoreController::class, 'storeCountry'])->name('countries.store');
    Route::post('states', [AddressQuickStoreController::class, 'storeState'])->name('states.store');
    Route::post('cities', [AddressQuickStoreController::class, 'storeCity'])->name('cities.store');
    Route::post('towns', [AddressQuickStoreController::class, 'storeTown'])->name('towns.store');
});

Route::middleware(['auth', 'module.access:hr'])->group(function () {

    // 📁 HR Management
    // 📁 Departments
    Route::get('departments/data', [DepartmentController::class, 'data'])->name('departments.data');
    Route::get('departments/{department}/hierarchy', [DepartmentController::class, 'hierarchy'])->name('departments.hierarchy');
    Route::resource('departments', DepartmentController::class)->names('departments')->only(['index', 'store', 'update', 'destroy']);

    // 📁 Jobs
    Route::get('jobs/data', [EmployeesJobController::class, 'data'])->name('jobs.data');
    Route::resource('jobs', EmployeesJobController::class)->names('jobs')->only(['index', 'store', 'update', 'destroy']);

    // 📁 Addresses
    Route::get('countries/data', [CountryController::class, 'data'])->name('countries.data');
    Route::resource('countries', CountryController::class)->names('countries')->only(['index', 'store', 'update', 'destroy']);
    Route::get('states/data', [StateController::class, 'data'])->name('states.data');
    Route::resource('states', StateController::class)->names('states')->only(['index', 'store', 'update', 'destroy']);
    Route::get('cities/data', [CityController::class, 'data'])->name('cities.data');
    Route::resource('cities', CityController::class)->names('cities')->only(['index', 'store', 'update', 'destroy']);
    Route::get('towns/data', [TownController::class, 'data'])->name('towns.data');
    Route::resource('towns', TownController::class)->names('towns')->only(['index', 'store', 'update', 'destroy']);

    // 📁 Shifts
    Route::get('shifts/weekly/data', [WeeklyShiftBoardController::class, 'data'])->name('shifts.weekly.data');
    Route::post('shifts/weekly/save', [WeeklyShiftBoardController::class, 'save'])->name('shifts.weekly.save');
    Route::post('shifts/weekly/settings', [WeeklyShiftBoardController::class, 'settings'])->name('shifts.weekly.settings');
    Route::post('shifts/weekly/assign', [WeeklyShiftBoardController::class, 'assign'])->name('shifts.weekly.assign');
    Route::delete('shifts/weekly/assign', [WeeklyShiftBoardController::class, 'unassign'])->name('shifts.weekly.unassign');
    Route::get('shifts/weekly', [WeeklyShiftBoardController::class, 'index'])->name('shifts.weekly.index');
    Route::get('shifts/data', [ShiftController::class, 'data'])->name('shifts.data');
    Route::resource('shifts', ShiftController::class)->names('shifts')->only(['index', 'store', 'update', 'destroy']);

    // 📁 Employees
    Route::get('employees/data', [EmployeeController::class, 'data'])->name('employees.data');
    Route::get('employees/cascade/states/{country}', [EmployeeController::class, 'statesByCountry'])->name('employees.cascade.states');
    Route::get('employees/cascade/cities/{state}', [EmployeeController::class, 'citiesByState'])->name('employees.cascade.cities');
    Route::get('employees/cascade/towns/{city}', [EmployeeController::class, 'townsByCity'])->name('employees.cascade.towns');
    Route::resource('employees', EmployeeController::class)->names('employees');

    // 📁 KPIs
    Route::get('kpis/data', [KpiController::class, 'data'])->name('kpis.data');
    Route::get('kpis/employee-evaluation', [EmployeeEvaluationController::class, 'index'])->name('kpis.employeeEvaluation');
    Route::resource('kpis', KpiController::class)->names('kpis')->only(['index', 'store', 'update', 'destroy']);

    // 📁 Employee Evaluations
    Route::get('employee-evaluations/data', [EmployeeEvaluationController::class, 'data'])->name('employee-evaluations.data');
    Route::get('employee-evaluations/employees/{employee}/kpis', [EmployeeEvaluationController::class, 'employeeKpis'])->name('employee-evaluations.employee-kpis');
    Route::get('employee-evaluations/{evaluation}', [EmployeeEvaluationController::class, 'show'])->name('employee-evaluations.show')->whereNumber('evaluation');
    Route::post('employee-evaluations', [EmployeeEvaluationController::class, 'store'])->name('employee-evaluations.store');
    Route::put('employee-evaluations/{evaluation}', [EmployeeEvaluationController::class, 'update'])->name('employee-evaluations.update')->whereNumber('evaluation');
    Route::delete('employee-evaluations/{evaluation}', [EmployeeEvaluationController::class, 'destroy'])->name('employee-evaluations.destroy')->whereNumber('evaluation');

    // 📁 Instant Evaluation Items (بنود)
    Route::get('instant-evaluation-items', [InstantEvaluationItemController::class, 'index'])->name('instant-evaluation-items.index');
    Route::get('instant-evaluation-items/data', [InstantEvaluationItemController::class, 'data'])->name('instant-evaluation-items.data');
    Route::post('instant-evaluation-items', [InstantEvaluationItemController::class, 'store'])->name('instant-evaluation-items.store');
    Route::put('instant-evaluation-items/{item}', [InstantEvaluationItemController::class, 'update'])->name('instant-evaluation-items.update')->whereNumber('item');
    Route::delete('instant-evaluation-items/{item}', [InstantEvaluationItemController::class, 'destroy'])->name('instant-evaluation-items.destroy')->whereNumber('item');

    // 📁 Instant Evaluations
    Route::get('instant-evaluations', [InstantEvaluationController::class, 'index'])->name('instant-evaluations.index');
    Route::get('instant-evaluations/dashboard', [InstantEvaluationController::class, 'dashboard'])->name('instant-evaluations.dashboard');
    Route::get('instant-evaluations/items/{polarity}', [InstantEvaluationController::class, 'itemsByPolarity'])
        ->whereIn('polarity', ['positive', 'negative'])
        ->name('instant-evaluations.items');
    Route::get('instant-evaluations/data', [InstantEvaluationController::class, 'data'])->name('instant-evaluations.data');
    Route::post('instant-evaluations', [InstantEvaluationController::class, 'store'])->name('instant-evaluations.store');
    Route::delete('instant-evaluations/{evaluation}', [InstantEvaluationController::class, 'destroy'])->name('instant-evaluations.destroy')->whereNumber('evaluation');

    // 📁 Attendances
    Route::resource('attendances', AttendanceController::class)->names('attendances')->only('index');

    // 📁 Leave Management
    Route::prefix('hr/leaves')->group(function () {
        // Leave Balances
        Route::get('/balances/data', [LeaveBalanceController::class, 'data'])->name('hr.leaves.balances.data');
        Route::get('/balances', [LeaveBalanceController::class, 'index'])->name('hr.leaves.balances.index');
        Route::get('/balances/create', [LeaveBalanceController::class, 'create'])->name('hr.leaves.balances.create');
        Route::post('/balances', [LeaveBalanceController::class, 'store'])->name('hr.leaves.balances.store');
        Route::get('/balances/{balance}/edit', [LeaveBalanceController::class, 'edit'])->name('hr.leaves.balances.edit');
        Route::put('/balances/{balance}', [LeaveBalanceController::class, 'update'])->name('hr.leaves.balances.update');
        Route::delete('/balances/{balance}', [LeaveBalanceController::class, 'destroy'])->name('hr.leaves.balances.destroy');

        // Leave Requests
        Route::get('/requests/data', [LeaveRequestController::class, 'data'])->name('hr.leaves.requests.data');
        Route::get('/requests/preview', [LeaveRequestController::class, 'preview'])->name('hr.leaves.requests.preview');
        Route::get('/requests/trashed/data', [LeaveRequestController::class, 'trashedData'])->name('hr.leaves.requests.trashed.data');
        Route::get('/requests/trashed', [LeaveRequestController::class, 'trashed'])->name('hr.leaves.requests.trashed');
        Route::get('/requests/create', [LeaveRequestController::class, 'create'])->name('hr.leaves.requests.create');
        Route::get('/requests', [LeaveRequestController::class, 'index'])->name('hr.leaves.requests.index');
        Route::post('/requests', [LeaveRequestController::class, 'store'])->name('hr.leaves.requests.store');
        Route::post('/requests/{leaveRequest}/submit', [LeaveRequestController::class, 'submit'])->name('hr.leaves.requests.submit');
        Route::post('/requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('hr.leaves.requests.approve');
        Route::post('/requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('hr.leaves.requests.reject');
        Route::post('/requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])->name('hr.leaves.requests.cancel');
        Route::post('/requests/{leaveRequest}/restore', [LeaveRequestController::class, 'restore'])->withTrashed()->name('hr.leaves.requests.restore');
        Route::delete('/requests/{leaveRequest}/force-destroy', [LeaveRequestController::class, 'forceDestroy'])->withTrashed()->name('hr.leaves.requests.force-destroy');
        Route::get('/requests/{leaveRequest}/edit', [LeaveRequestController::class, 'edit'])->name('hr.leaves.requests.edit');
        Route::get('/requests/{leaveRequest}', [LeaveRequestController::class, 'show'])->name('hr.leaves.requests.show');
        Route::put('/requests/{leaveRequest}', [LeaveRequestController::class, 'update'])->name('hr.leaves.requests.update');
        Route::delete('/requests/{leaveRequest}', [LeaveRequestController::class, 'destroy'])->name('hr.leaves.requests.destroy');

        // Leave Types
        Route::get('/leave-types/data', [LeaveTypeController::class, 'data'])->name('hr.leaves.types.data');
        Route::get('/leave-types', [LeaveTypeController::class, 'index'])->name('hr.leaves.types.manage');
        Route::post('/leave-types', [LeaveTypeController::class, 'store'])->name('hr.leaves.types.store');
        Route::put('/leave-types/{leaveType}', [LeaveTypeController::class, 'update'])->name('hr.leaves.types.update');
        Route::delete('/leave-types/{leaveType}', [LeaveTypeController::class, 'destroy'])->name('hr.leaves.types.destroy');
    });

    // 📁 HR Settings
    Route::prefix('hr/settings')->group(function () {
        Route::get('/', [HrSettingController::class, 'index'])->name('hr.settings.index');
        Route::get('/edit', [HrSettingController::class, 'edit'])->name('hr.settings.edit');
        Route::put('/', [HrSettingController::class, 'update'])->name('hr.settings.update');
    });

    // 📁 Covenants
    Route::get('covenants/data', [CovenantController::class, 'data'])->name('covenants.data');
    Route::get('covenants/{covenant}/download-image', [CovenantController::class, 'downloadImage'])->name('covenants.download-image');
    Route::get('covenants/{covenant}/media/{collection}', [CovenantController::class, 'media'])->name('covenants.media');
    Route::post('covenants/{covenant}/assign', [CovenantController::class, 'assign'])->name('covenants.assign');
    Route::get('covenants/{covenant}', [CovenantController::class, 'show'])->name('covenants.show');
    Route::resource('covenants', CovenantController::class)->names('covenants')->only(['index', 'store', 'update', 'destroy']);

    // 📁 Errands
    Route::get('errands/data', [ErrandController::class, 'data'])->name('errands.data');
    Route::post('errands/{errand}/approve', [ErrandController::class, 'approve'])->name('errands.approve');
    Route::get('errands/{errand}', [ErrandController::class, 'show'])->name('errands.show');
    Route::resource('errands', ErrandController::class)->names('errands')->only(['index', 'store', 'update', 'destroy']);

    // 📁 Work Permissions
    Route::get('work-permissions/data', [WorkPermissionController::class, 'data'])->name('work-permissions.data');
    Route::post('work-permissions/{work_permission}/approve', [WorkPermissionController::class, 'approve'])->name('work-permissions.approve');
    Route::post('work-permissions/{work_permission}/reject', [WorkPermissionController::class, 'reject'])->name('work-permissions.reject');
    Route::get('work-permissions/{work_permission}', [WorkPermissionController::class, 'show'])->name('work-permissions.show');
    Route::resource('work-permissions', WorkPermissionController::class)->names('work-permissions')->only(['index', 'store', 'update', 'destroy']);

    // 📁 Employee Portal (إدارة بوابة الموظف)
    Route::prefix('hr/employee-portal')->name('hr.employee-portal.')->group(function () {
        Route::get('/', [EmployeePortalAdminController::class, 'index'])->name('index');
        Route::get('/requests/data', [EmployeePortalAdminController::class, 'requestsData'])->name('requests.data');
        Route::get('/requests/{portalRequest}', [EmployeePortalAdminController::class, 'requestShow'])->whereNumber('portalRequest')->name('requests.show');
        Route::post('/requests/{portalRequest}/approve', [EmployeePortalAdminController::class, 'requestApprove'])->whereNumber('portalRequest')->name('requests.approve');
        Route::post('/requests/{portalRequest}/reject', [EmployeePortalAdminController::class, 'requestReject'])->whereNumber('portalRequest')->name('requests.reject');
        Route::get('/devices/data', [EmployeePortalAdminController::class, 'devicesData'])->name('devices.data');
        Route::post('/devices/{device}/approve', [EmployeePortalAdminController::class, 'deviceApprove'])->whereNumber('device')->name('devices.approve');
        Route::post('/devices/{device}/revoke', [EmployeePortalAdminController::class, 'deviceRevoke'])->whereNumber('device')->name('devices.revoke');
        Route::get('/employees/data', [EmployeePortalAdminController::class, 'employeesData'])->name('employees.data');
        Route::post('/employees/bulk', [EmployeePortalAdminController::class, 'employeesBulk'])->name('employees.bulk');
        Route::post('/employees/{employee}/toggle', [EmployeePortalAdminController::class, 'employeeToggle'])->whereNumber('employee')->name('employees.toggle');
        Route::post('/employees/{employee}/password', [EmployeePortalAdminController::class, 'employeePassword'])->whereNumber('employee')->name('employees.password');
        Route::get('/suspicious/data', [EmployeePortalAdminController::class, 'suspiciousData'])->name('suspicious.data');
        Route::get('/attendance/{attendance}/selfie', [EmployeePortalAdminController::class, 'selfie'])->whereNumber('attendance')->name('attendance.selfie');
        Route::put('/settings', [EmployeePortalAdminController::class, 'settingsUpdate'])->name('settings.update');
    });

    require __DIR__ . '/attendance.php';

    // 📁 Recruitment (merged into HR — keep recruitment.* route names)
    Route::prefix('recruitment')->name('recruitment.')->group(function () {
        Route::get('/dashboard', [RecruitmentDashboardController::class, 'index'])->name('dashboard');

        Route::get('contract-types/data', [ContractTypeController::class, 'data'])->name('contract-types.data');
        Route::resource('contract-types', ContractTypeController::class)->names('contract-types')->only(['index', 'store', 'update', 'destroy']);

        Route::get('cvs/data', [CvController::class, 'data'])->name('cvs.data');
        Route::get('cvs/select-list', [CvController::class, 'selectList'])->name('cvs.select-list');
        Route::get('cvs/{cv}/download', [CvController::class, 'download'])->name('cvs.download');
        Route::resource('cvs', CvController::class)->names('cvs')->only(['index', 'show', 'store', 'update', 'destroy']);

        Route::get('contracts/data', [ContractController::class, 'data'])->name('contracts.data');
        Route::resource('contracts', ContractController::class)->names('contracts')->only(['index', 'show', 'store', 'update', 'destroy']);

        Route::get('job-postings/data', [JobPostingController::class, 'data'])->name('job-postings.data');
        Route::resource('job-postings', JobPostingController::class)->names('job-postings')->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::get('interviews/data', [InterviewController::class, 'data'])->name('interviews.data');
        Route::get('interviews/calendar/data', [InterviewController::class, 'calendarData'])->name('interviews.calendar.data');
        Route::get('interviews/calendar', [InterviewController::class, 'calendar'])->name('interviews.calendar');
        Route::resource('interviews', InterviewController::class)->names('interviews')->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::get('terminations/data', [TerminationController::class, 'data'])->name('terminations.data');
        Route::resource('terminations', TerminationController::class)->names('terminations')->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::get('onboardings/data', [OnboardingController::class, 'data'])->name('onboardings.data');
        Route::get('onboardings/{onboarding}/documents/{mediaId}/download', [OnboardingController::class, 'downloadDocument'])->name('onboardings.documents.download');
        Route::delete('onboardings/{onboarding}/documents/{mediaId}', [OnboardingController::class, 'destroyDocument'])->name('onboardings.documents.destroy');
        Route::resource('onboardings', OnboardingController::class)->names('onboardings')->only(['index', 'show', 'store', 'update', 'destroy']);

        // 📁 Applicants (أحدث المتقدمين) — مربوط بـ cvs
        Route::get('applicants/posting-stats', [ApplicantController::class, 'postingStats'])->name('applicants.posting-stats');
        Route::get('applicants/data', [ApplicantController::class, 'data'])->name('applicants.data');
        Route::get('applicants/{applicant}/download', [ApplicantController::class, 'download'])->name('applicants.download');
        Route::resource('applicants', ApplicantController::class)->names('applicants')->only(['index', 'store', 'update', 'destroy']);

        // 📁 Latest Applicants (أحدث المتقدمين من cvs)
        Route::get('latest-applicants/data', [LatestApplicantController::class, 'data'])->name('latest-applicants.data');
        Route::get('latest-applicants/{cv}/download', [LatestApplicantController::class, 'download'])->name('latest-applicants.download');
        Route::patch('latest-applicants/{cv}/status', [LatestApplicantController::class, 'updateStatus'])->name('latest-applicants.status');
        Route::get('latest-applicants', [LatestApplicantController::class, 'index'])->name('latest-applicants.index');

        // 📁 Job Applicants (المتقدمون)
        Route::get('job-applicants/data', [JobApplicantController::class, 'data'])->name('job-applicants.data');
        Route::get('job-applicants/posting-stats', [JobApplicantController::class, 'postingStats'])->name('job-applicants.posting-stats');
        Route::get('job-applicants/{applicant}/download', [JobApplicantController::class, 'download'])->name('job-applicants.download');
        Route::resource('job-applicants', JobApplicantController::class, ['parameters' => ['job-applicants' => 'applicant']])->names('job-applicants')->only(['index', 'store', 'update', 'destroy']);
    });

}); // end module.access:hr

    
// ===== Employee Mobile Routes (خارج auth middleware) =====
// Employee Login Routes
Route::get('/mobile/employee-login', function () {
    return view('hr::mobile.employee-login');
})->name('mobile.employee-login');

// Employee Auth API Routes
Route::post('/api/employee/login', [EmployeeAuthController::class, 'login'])->name('api.employee.login');
Route::post('/api/employee/logout', [EmployeeAuthController::class, 'logout'])->name('api.employee.logout');
Route::get('/api/employee/check-auth', [EmployeeAuthController::class, 'checkAuth'])->name('api.employee.check-auth');
Route::get('/api/employee/current', [EmployeeAuthController::class, 'getCurrentEmployee'])->name('api.employee.current');

// Mobile Attendance Routes
Route::get('/mobile/attendance', [MobileAttendanceController::class, 'showAttendancePage'])->middleware(['employee.auth'])->name('mobile.attendance');

// Mobile Attendance API Routes
Route::post('/api/attendance/record', [MobileAttendanceController::class, 'recordAttendance'])->middleware(['employee.auth'])->name('api.attendance.record');
Route::get('/api/attendance/last', [MobileAttendanceController::class, 'getLastAttendance'])->middleware(['employee.auth'])->name('api.attendance.last');
Route::get('/api/attendance/history', [MobileAttendanceController::class, 'getAttendanceHistory'])->middleware(['employee.auth'])->name('api.attendance.history');
Route::get('/api/attendance/stats', [MobileAttendanceController::class, 'getAttendanceStats'])->middleware(['employee.auth'])->name('api.attendance.stats');
Route::get('/api/attendance/can-record', [MobileAttendanceController::class, 'canRecordAttendance'])->middleware(['employee.auth'])->name('api.attendance.can-record');
