<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\HR\Models\Shift;
use Modules\HR\Services\ShiftService;

class ShiftController extends Controller
{
    public function __construct(
        private readonly ShiftService $shiftService
    ) {
        $this->middleware('can:view Shifts')->only(['index', 'data']);
        $this->middleware('can:create Shifts')->only(['store']);
        $this->middleware('can:edit Shifts')->only(['update']);
        $this->middleware('can:delete Shifts')->only(['destroy']);
    }

    public function index(): View
    {
        return view('hr::hr-management.shifts.manage-shifts', [
            'shiftTypes' => [
                'morning' => __('hr::hr.morning'),
                'evening' => __('hr::hr.evening'),
                'night' => __('hr::hr.night'),
            ],
            'weekDays' => [
                'saturday' => __('hr::hr.saturday'),
                'sunday' => __('hr::hr.sunday'),
                'monday' => __('hr::hr.monday'),
                'tuesday' => __('hr::hr.tuesday'),
                'wednesday' => __('hr::hr.wednesday'),
                'thursday' => __('hr::hr.thursday'),
                'friday' => __('hr::hr.friday'),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->shiftService->list($request->string('search')->toString() ?: null),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateShift($request);

        $this->shiftService->create($validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.shift_created_successfully'),
        ]);
    }

    public function update(Request $request, Shift $shift): JsonResponse
    {
        $validated = $this->validateShift($request, $shift->id);

        $this->shiftService->update($shift, $validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.shift_updated_successfully'),
        ]);
    }

    public function destroy(Shift $shift): JsonResponse
    {
        $this->shiftService->delete($shift);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.shift_deleted_successfully'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateShift(Request $request, ?int $shiftId = null): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:60|unique:shifts,name,'.($shiftId ?? 'NULL'),
            'start_time' => 'required',
            'end_time' => 'required',
            'beginning_check_in' => 'nullable',
            'ending_check_in' => 'nullable',
            'beginning_check_out' => 'nullable',
            'ending_check_out' => 'nullable',
            'allowed_late_minutes' => 'nullable|integer|min:0',
            'allowed_early_leave_minutes' => 'nullable|integer|min:0',
            'shift_type' => 'required|in:morning,evening,night',
            'days' => 'required|array|min:1',
            'days.*' => 'string|in:saturday,sunday,monday,tuesday,wednesday,thursday,friday',
            'notes' => 'nullable|string',
        ]);

        // Overnight shifts (end_time < start_time, e.g. 12:00 PM → 12:00 AM) are
        // valid and handled by Shift::isOvernightShift(); only an identical
        // start/end is ambiguous and must be rejected.
        $start = Carbon::parse($validated['start_time'])->format('H:i:s');
        $end = Carbon::parse($validated['end_time'])->format('H:i:s');
        if ($start === $end) {
            throw ValidationException::withMessages([
                'end_time' => __('hr::hr.end_time_cannot_equal_start_time'),
            ]);
        }

        return $validated;
    }
}
