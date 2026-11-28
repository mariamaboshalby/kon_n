<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\HR\Http\Requests\BulkAttendanceActionRequest;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\Employee;
use Modules\HR\Services\AttendanceBulkActionService;

class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Attendances')->only(['index', 'data']);
        $this->middleware('can:create Attendances')->only(['store']);
        $this->middleware('can:edit Attendances')->only(['update']);
        $this->middleware('can:delete Attendances')->only(['destroy']);
    }

    public function index(): View
    {
        return view('hr::hr-management.attendances.attendance.index', [
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->input('per_page', 50)));

        $paginator = Attendance::query()
            ->with('employee:id,name,code')
            ->when($request->filled('search_employee_name'), function ($q) use ($request) {
                $q->whereHas('employee', fn ($emp) => $emp->where('name', 'like', '%'.$request->string('search_employee_name').'%'));
            })
            ->when($request->filled('search_employee_id'), fn ($q) => $q->where('employee_id', $request->integer('search_employee_id')))
            ->when($request->filled('search_fingerprint_name'), fn ($q) => $q->where('employee_attendance_finger_print_name', 'like', '%'.$request->string('search_fingerprint_name').'%'))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('date', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('date', '<=', $request->string('date_to')))
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate($perPage);

        $rows = collect($paginator->items())->map(static function (Attendance $item): array {
            $time = $item->time ? substr((string) $item->time, 0, 5) : null;

            // parse location JSON stored by mobile app
            $locationRaw = $item->location ?? null;
            $locationData = null;
            if ($locationRaw) {
                if (is_string($locationRaw)) {
                    $decoded = json_decode($locationRaw, true);
                    $locationData = is_array($decoded) ? $decoded : null;
                } elseif (is_array($locationRaw)) {
                    $locationData = $locationRaw;
                }
            }

            $locationOut = null;
            if ($locationData) {
                $lat = isset($locationData['latitude'])  ? (float) $locationData['latitude']  : null;
                $lng = isset($locationData['longitude']) ? (float) $locationData['longitude'] : null;
                $locationOut = [
                    'latitude'  => $lat,
                    'longitude' => $lng,
                    'accuracy'  => isset($locationData['accuracy'])  ? (float) $locationData['accuracy']  : null,
                    'address'   => $locationData['address'] ?? null,
                    'maps_url'  => ($lat && $lng) ? "https://www.google.com/maps?q={$lat},{$lng}" : null,
                ];
            }

            return [
                'id'                => $item->id,
                'employee_id'       => $item->employee_id,
                'employee'          => $item->employee
                    ? ['id' => $item->employee->id, 'name' => $item->employee->name, 'code' => $item->employee->code]
                    : null,
                'finger_print_id'   => $item->employee_attendance_finger_print_id,
                'finger_print_name' => $item->employee_attendance_finger_print_name,
                'type'              => $item->type,
                'date'              => $item->date?->format('Y-m-d'),
                'time'              => $time,
                'status'            => $item->status,
                'notes'             => $item->notes,
                'location'          => $locationOut,
                'device_type'       => $item->device_type ?? null,
                'project_code'      => $item->project_code ?? null,
            ];
        })->values();

        return response()->json([
            'data' => $rows,
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'has_more' => $paginator->hasMorePages(),
        ]);
    }

    public function bulkActions(BulkAttendanceActionRequest $request, AttendanceBulkActionService $bulkActionService): JsonResponse
    {
        $action = (string) $request->validated('action');
        $user = $request->user();

        if ($action === 'delete') {
            abort_unless($user !== null && $user->can('delete Attendances'), 403);
        } else {
            abort_unless($user !== null && $user->can('edit Attendances'), 403);
        }

        $result = $bulkActionService->execute($action, $request->getIds());

        if ($result['processed'] === 0) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'processed' => 0,
                'skipped' => $result['skipped'],
                'errors' => $result['errors'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'processed' => $result['processed'],
            'skipped' => $result['skipped'],
            'errors' => $result['errors'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateAttendance($request);
        $data['user_id'] = Auth::id();
        Attendance::create($data);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.attendance_created_successfully'),
        ]);
    }

    public function update(Request $request, Attendance $attendance): JsonResponse
    {
        $attendance->update($this->validateAttendance($request));

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.attendance_updated_successfully'),
        ]);
    }

    public function destroy(Attendance $attendance): JsonResponse
    {
        $attendance->delete();

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.attendance_deleted_successfully'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAttendance(Request $request): array
    {
        return $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'employee_attendance_finger_print_id' => 'nullable|integer',
            'employee_attendance_finger_print_name' => 'nullable|string|max:255',
            'type' => 'required|in:check_in,check_out',
            'date' => 'required|date',
            'time' => 'required',
            'status' => 'required|in:pending,approved,rejected',
            'notes' => 'nullable|string',
            'device_type' => 'nullable|string|max:50',
        ]);
    }
}
