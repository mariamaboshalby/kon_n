<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\Covenant;
use Modules\HR\Services\CovenantService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CovenantController extends Controller
{
    public function __construct(
        private readonly CovenantService $covenantService
    ) {
        $this->middleware('can:view Covenants')->only(['index', 'data', 'show', 'downloadImage', 'media']);
        $this->middleware('can:create Covenants')->only(['store']);
        $this->middleware('can:edit Covenants')->only(['update', 'assign']);
        $this->middleware('can:delete Covenants')->only(['destroy']);
    }

    public function index(): View
    {
        return view('hr::hr-management.covenants.manage-covenant', [
            'employees' => $this->covenantService->employeesForSelect(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $items = $this->covenantService->list(
            $request->string('search')->toString() ?: null,
            $request->filled('employee_id') ? (int) $request->input('employee_id') : null,
            $request->string('assignment_status')->toString() ?: null
        );

        return response()->json(['data' => $items]);
    }

    public function show(Covenant $covenant): JsonResponse
    {
        $covenant->load(['employee', 'media']);
        $covenant->setAttribute('image_url', $covenant->image_url);
        $covenant->setAttribute('acknowledgment_url', $covenant->acknowledgment_url);
        $covenant->setAttribute('discharge_url', $covenant->discharge_url);

        return response()->json(['data' => $covenant]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:returned,maintenance',
            'handover_status' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:2048',
            'acknowledgment_image' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:2048',
            'discharge_image' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $this->covenantService->create(
            $validated,
            $request->file('image'),
            $request->file('acknowledgment_image'),
            $request->file('discharge_image')
        );

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.covenant_created_successfully'),
        ]);
    }

    public function update(Request $request, Covenant $covenant): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:returned,maintenance',
            'handover_status' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:2048',
            'acknowledgment_image' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:2048',
            'discharge_image' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $this->covenantService->update(
            $covenant,
            $validated,
            $request->file('image'),
            $request->file('acknowledgment_image'),
            $request->file('discharge_image')
        );

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.covenant_updated_successfully'),
        ]);
    }

    public function destroy(Covenant $covenant): JsonResponse
    {
        $this->covenantService->delete($covenant);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.covenant_deleted_successfully'),
        ]);
    }

    public function assign(Request $request, Covenant $covenant): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'nullable|integer|exists:employees,id',
        ]);

        $employeeId = isset($validated['employee_id']) && (int) $validated['employee_id'] > 0
            ? (int) $validated['employee_id']
            : null;

        $this->covenantService->assign($covenant, $employeeId);

        return response()->json([
            'success' => true,
            'message' => $employeeId
                ? __('hr::hr.covenant_assigned_successfully')
                : __('hr::hr.covenant_unassigned_successfully'),
        ]);
    }

    public function downloadImage(Covenant $covenant): BinaryFileResponse|JsonResponse
    {
        $media = $covenant->getFirstMedia('HR_Covenants');

        if (! $media) {
            return response()->json([
                'success' => false,
                'message' => __('hr::hr.covenant_image_not_found'),
            ], 404);
        }

        return response()->download($media->getPath(), $media->file_name);
    }

    /**
     * Stream a covenant media file inline for display.
     * Reads via Spatie's absolute path so it resolves the correct tenant storage.
     */
    public function media(Covenant $covenant, string $collection): \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
    {
        $map = [
            'image' => 'HR_Covenants',
            'acknowledgment' => 'HR_Covenant_Acknowledgment',
            'discharge' => 'HR_Covenant_Discharge',
        ];

        if (! isset($map[$collection])) {
            abort(404);
        }

        $media = $covenant->getFirstMedia($map[$collection]);

        if (! $media) {
            return response()->json([
                'success' => false,
                'message' => __('hr::hr.covenant_image_not_found'),
            ], 404);
        }

        return response()->file($media->getPath(), [
            'Content-Type' => $media->mime_type,
        ]);
    }
}
