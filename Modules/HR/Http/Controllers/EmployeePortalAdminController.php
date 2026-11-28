<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeDevice;
use Modules\HR\Models\EmployeePortalToken;
use Modules\HR\Models\HRSetting;
use Modules\HR\Models\PortalRequest;
use Modules\HR\Services\Portal\PortalAuthService;
use Modules\HR\Services\Portal\PortalException;
use Modules\HR\Services\Portal\PortalRequestService;
use Modules\HR\Services\Portal\PortalRequestWorkflow;
use Modules\HR\Services\Portal\PortalSettings;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * إدارة بوابة الموظف من لوحة HR — صندوق الطلبات، الأجهزة، تفعيل الموظفين، البصمات المشبوهة، الإعدادات.
 */
class EmployeePortalAdminController extends Controller
{
    public function __construct(
        private readonly PortalRequestService $requests,
        private readonly PortalRequestWorkflow $workflow,
        private readonly PortalAuthService $auth
    ) {
        $this->middleware('permission:view Employee Portal|view Portal Requests|view Employee Devices')->only(['index']);
        $this->middleware('can:view Portal Requests')->only(['requestsData', 'requestShow']);
        $this->middleware('can:edit Portal Requests')->only(['requestApprove', 'requestReject']);
        $this->middleware('can:view Employee Devices')->only(['devicesData']);
        $this->middleware('can:edit Employee Devices')->only(['deviceApprove', 'deviceRevoke']);
        $this->middleware('can:view Employee Portal')->only(['employeesData', 'suspiciousData', 'selfie']);
        $this->middleware('can:edit Employee Portal')->only(['employeeToggle', 'employeePassword', 'employeesBulk', 'settingsUpdate']);
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $setting = HRSetting::getCompanyDefault() ?? HRSetting::create(['company_max_leave_percentage' => 7.00]);

        $tabs = array_values(array_filter([
            $user->can('view Portal Requests') ? 'requests' : null,
            $user->can('view Employee Devices') ? 'devices' : null,
            $user->can('view Employee Portal') ? 'employees' : null,
            $user->can('view Employee Portal') ? 'suspicious' : null,
            $user->can('view Employee Portal') ? 'settings' : null,
        ]));

        $activeTab = in_array((string) $request->query('tab'), $tabs, true) ? (string) $request->query('tab') : ($tabs[0] ?? 'requests');

        return view('hr::hr-management.employee-portal.index', [
            'setting' => $setting,
            'settings' => new PortalSettings($setting),
            'tabs' => $tabs,
            'activeTab' => $activeTab,
            'departments' => Department::query()->orderBy('title')->get(['id', 'title']),
            'pendingDevices' => EmployeeDevice::query()->where('status', EmployeeDevice::STATUS_PENDING)->count(),
            'pendingRequests' => PortalRequest::query()->where('status', PortalRequest::STATUS_PENDING)->count(),
        ]);
    }

    public function requestsData(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected,cancelled'],
            'stage' => ['nullable', 'in:manager,hr,done'],
            'type' => ['nullable', Rule::in(PortalRequest::TYPES)],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json(array_merge(
            ['success' => true],
            $this->requests->listForHr($filters, (int) ($filters['page'] ?? 1))
        ));
    }

    public function requestShow(PortalRequest $portalRequest): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->requests->detail($portalRequest, true),
        ]);
    }

    public function requestApprove(Request $request, PortalRequest $portalRequest): JsonResponse
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.(int) config('hr.portal.max_attachment_kb', 5120)],
        ]);

        try {
            $updated = $this->workflow->hrDecide($request->user(), $portalRequest, true, $data['note'] ?? null, $request->file('document'));
        } catch (PortalException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        }

        return response()->json([
            'success' => true,
            'message' => __('hr::portal.admin_request_approved'),
            'data' => $this->requests->serialize($updated, true),
        ]);
    }

    public function requestReject(Request $request, PortalRequest $portalRequest): JsonResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']], [], [
            'note' => __('hr::portal.field_reject_reason'),
        ]);

        try {
            $updated = $this->workflow->hrDecide($request->user(), $portalRequest, false, $data['note'], null);
        } catch (PortalException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        }

        return response()->json([
            'success' => true,
            'message' => __('hr::portal.admin_request_rejected'),
            'data' => $this->requests->serialize($updated, true),
        ]);
    }

    public function devicesData(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:pending,approved,revoked'],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $search = trim((string) ($data['search'] ?? ''));

        $paginator = EmployeeDevice::query()
            ->with('employee:id,name,finger_print_id')
            ->when($data['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($search !== '', fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('name', 'like', '%'.$search.'%')))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->simplePaginate(25, ['*'], 'page', (int) ($data['page'] ?? 1));

        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map(fn (EmployeeDevice $d) => [
                'id' => (int) $d->id,
                'employee' => $d->employee?->name,
                'finger_print_id' => $d->employee?->finger_print_id,
                'platform' => $d->platform,
                'model' => $d->model,
                'os_version' => $d->os_version,
                'app_version' => $d->app_version,
                'status' => $d->status,
                'status_label' => __('hr::portal.device_status_'.$d->status),
                'last_seen_at' => $d->last_seen_at?->format('Y-m-d H:i'),
                'created_at' => $d->created_at?->format('Y-m-d H:i'),
            ])->values(),
            'has_more' => $paginator->hasMorePages(),
            'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
        ]);
    }

    public function deviceApprove(Request $request, EmployeeDevice $device): JsonResponse
    {
        $max = PortalSettings::current()->maxDevices();

        DB::transaction(function () use ($request, $device, $max): void {
            $others = EmployeeDevice::query()
                ->where('employee_id', $device->employee_id)
                ->where('id', '!=', $device->id)
                ->where('status', EmployeeDevice::STATUS_APPROVED)
                ->orderByDesc('last_seen_at')
                ->get();

            $toRevoke = $others->slice(max(0, $max - 1));
            foreach ($toRevoke as $old) {
                $old->update(['status' => EmployeeDevice::STATUS_REVOKED]);
                EmployeePortalToken::query()->where('employee_device_id', $old->id)->delete();
            }

            $device->update([
                'status' => EmployeeDevice::STATUS_APPROVED,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);
        });

        return response()->json(['success' => true, 'message' => __('hr::portal.admin_device_approved')]);
    }

    public function deviceRevoke(EmployeeDevice $device): JsonResponse
    {
        DB::transaction(function () use ($device): void {
            $device->update(['status' => EmployeeDevice::STATUS_REVOKED]);
            EmployeePortalToken::query()->where('employee_device_id', $device->id)->delete();
        });

        return response()->json(['success' => true, 'message' => __('hr::portal.admin_device_revoked')]);
    }

    public function employeesData(Request $request): JsonResponse
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer'],
            'portal' => ['nullable', 'in:enabled,disabled'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $search = trim((string) ($data['search'] ?? ''));

        $paginator = Employee::query()
            ->with('department:id,title')
            ->withCount(['portalDevices as approved_devices_count' => fn ($q) => $q->where('status', EmployeeDevice::STATUS_APPROVED)])
            ->whereNull('date_of_fire')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.$search.'%')
                ->orWhere('finger_print_id', $search)))
            ->when($data['department_id'] ?? null, fn ($q, $v) => $q->where('department_id', (int) $v))
            ->when(($data['portal'] ?? null) === 'enabled', fn ($q) => $q->where('portal_enabled', true))
            ->when(($data['portal'] ?? null) === 'disabled', fn ($q) => $q->where('portal_enabled', false))
            ->orderBy('name')
            ->simplePaginate(30, ['id', 'name', 'phone', 'finger_print_id', 'department_id', 'portal_enabled', 'password', 'portal_password_changed_at'], 'page', (int) ($data['page'] ?? 1));

        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map(fn (Employee $e) => [
                'id' => (int) $e->id,
                'name' => (string) $e->name,
                'phone' => $e->phone,
                'finger_print_id' => $e->finger_print_id,
                'department' => $e->department?->title,
                'portal_enabled' => (bool) $e->portal_enabled,
                'has_password' => ! empty($e->getAttributes()['password'] ?? null),
                'password_changed' => $e->portal_password_changed_at !== null,
                'devices' => (int) $e->approved_devices_count,
            ])->values(),
            'has_more' => $paginator->hasMorePages(),
            'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
        ]);
    }

    public function employeeToggle(Request $request, Employee $employee): JsonResponse
    {
        $enabled = (bool) $request->validate(['enabled' => ['required', 'boolean']])['enabled'];

        $employee->forceFill(['portal_enabled' => $enabled])->save();
        if (! $enabled) {
            $this->auth->revokeAllTokens($employee);
        }

        return response()->json([
            'success' => true,
            'message' => $enabled ? __('hr::portal.admin_employee_enabled') : __('hr::portal.admin_employee_disabled'),
        ]);
    }

    public function employeePassword(Request $request, Employee $employee): JsonResponse
    {
        $data = $request->validate(['password' => ['required', 'string', 'min:6', 'max:100']], [], [
            'password' => __('hr::portal.field_new_password'),
        ]);

        DB::transaction(function () use ($employee, $data): void {
            $employee->forceFill([
                'password' => $data['password'],
                'portal_password_changed_at' => null,
            ])->save();
            $this->auth->revokeAllTokens($employee);
        });

        return response()->json(['success' => true, 'message' => __('hr::portal.admin_password_reset')]);
    }

    public function employeesBulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer', 'min:1'],
            'enabled' => ['required', 'boolean'],
        ]);

        $enabled = (bool) $data['enabled'];
        $count = Employee::query()->whereIn('id', $data['ids'])->update(['portal_enabled' => $enabled]);

        if (! $enabled) {
            EmployeePortalToken::query()->whereIn('employee_id', $data['ids'])->delete();
        }

        return response()->json([
            'success' => true,
            'message' => __('hr::portal.admin_bulk_updated', ['count' => $count]),
        ]);
    }

    public function suspiciousData(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'kind' => ['nullable', 'in:suspicious,remote'],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $search = trim((string) ($data['search'] ?? ''));
        $kind = $data['kind'] ?? null;

        $paginator = Attendance::query()
            ->with('employee:id,name')
            ->where(function ($q) use ($kind): void {
                if ($kind === 'remote') {
                    $q->where('is_remote', true);
                } elseif ($kind === 'suspicious') {
                    $q->where('is_suspicious', true);
                } else {
                    $q->where('is_suspicious', true)->orWhere('is_remote', true);
                }
            })
            ->when($data['date_from'] ?? null, fn ($q, $v) => $q->where('date', '>=', $v))
            ->when($data['date_to'] ?? null, fn ($q, $v) => $q->where('date', '<=', $v))
            ->when($search !== '', fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('name', 'like', '%'.$search.'%')))
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->simplePaginate(30, ['*'], 'page', (int) ($data['page'] ?? 1));

        $deviceModels = EmployeeDevice::query()
            ->whereIn('id', collect($paginator->items())->pluck('employee_device_id')->filter()->unique()->all())
            ->pluck('model', 'id');

        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map(fn (Attendance $a) => [
                'id' => (int) $a->id,
                'employee' => $a->employee?->name,
                'type' => $a->type,
                'type_label' => __('hr::portal.att_'.$a->type),
                'date' => $a->date?->format('Y-m-d'),
                'time' => substr((string) $a->time, 0, 5),
                'distance' => $a->distance_meters,
                'accuracy' => $a->gps_accuracy !== null ? (float) $a->gps_accuracy : null,
                'is_remote' => (bool) $a->is_remote,
                'reasons' => collect(explode(',', (string) $a->suspicious_reason))
                    ->filter()
                    ->map(fn (string $r) => __('hr::portal.suspicious_'.$r))
                    ->values(),
                'device' => $a->employee_device_id ? ($deviceModels[$a->employee_device_id] ?? null) : null,
                'has_selfie' => ! empty($a->selfie_path),
                'map_url' => is_array($a->location) && isset($a->location['latitude'], $a->location['longitude'])
                    ? 'https://www.google.com/maps?q='.$a->location['latitude'].','.$a->location['longitude']
                    : null,
            ])->values(),
            'has_more' => $paginator->hasMorePages(),
            'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
        ]);
    }

    public function selfie(Attendance $attendance): StreamedResponse
    {
        abort_if(empty($attendance->selfie_path) || ! Storage::disk('local')->exists((string) $attendance->selfie_path), 404);

        return Storage::disk('local')->response((string) $attendance->selfie_path);
    }

    public function settingsUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'portal_enabled' => ['required', 'boolean'],
            'portal_require_biometric' => ['required', 'boolean'],
            'portal_device_binding' => ['required', 'boolean'],
            'portal_max_devices_per_employee' => ['required', 'integer', 'min:1', 'max:5'],
            'portal_enforce_geofence' => ['required', 'boolean'],
            'portal_max_gps_accuracy_meters' => ['required', 'integer', 'min:5', 'max:1000'],
            'portal_block_mock_location' => ['required', 'boolean'],
            'portal_selfie_required' => ['required', 'boolean'],
            'portal_approval_flow' => ['required', Rule::in(PortalSettings::FLOWS)],
            'portal_show_payslip' => ['required', 'boolean'],
            'portal_checkin_reminder_minutes' => ['required', 'integer', 'min:0', 'max:240'],
        ]);

        $setting = HRSetting::getCompanyDefault() ?? HRSetting::create(['company_max_leave_percentage' => 7.00]);
        $setting->update($validated);

        return response()->json(['success' => true, 'message' => __('hr::portal.admin_settings_saved')]);
    }
}
