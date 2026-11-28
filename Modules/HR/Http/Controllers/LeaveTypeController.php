<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\LeaveType;

class LeaveTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Leave Types')->only(['index', 'data']);
        $this->middleware('can:create Leave Types')->only(['store']);
        $this->middleware('can:edit Leave Types')->only(['update']);
        $this->middleware('can:delete Leave Types')->only(['destroy']);
    }

    public function index(): View
    {
        return view('hr::hr-management.leaves.leave-types.manage-leave-types');
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString() ?: null;
        $items = LeaveType::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateLeaveType($request);
        LeaveType::create($validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.leave_type_created_successfully'),
        ]);
    }

    public function update(Request $request, LeaveType $leaveType): JsonResponse
    {
        $validated = $this->validateLeaveType($request, $leaveType->id);
        $leaveType->update($validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.leave_type_updated_successfully'),
        ]);
    }

    public function destroy(LeaveType $leaveType): JsonResponse
    {
        $leaveType->delete();

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.leave_type_deleted_successfully'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateLeaveType(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:leave_types,name,'.($ignoreId ?? 'NULL').',id',
            'code' => 'required|string|max:255|unique:leave_types,code,'.($ignoreId ?? 'NULL').',id',
            'is_paid' => 'required|boolean',
            'requires_approval' => 'required|boolean',
            'max_per_request_days' => 'required|integer|min:0',
        ]);

        $validated['is_paid'] = (bool) $request->boolean('is_paid');
        $validated['requires_approval'] = (bool) $request->boolean('requires_approval');

        return $validated;
    }
}
