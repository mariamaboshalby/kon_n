<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\EmployeesJob;
use Modules\HR\Services\JobService;

class EmployeesJobController extends Controller
{
    public function __construct(
        private readonly JobService $jobService
    ) {
        $this->middleware('can:view Jobs')->only(['index', 'data']);
        $this->middleware('can:create Jobs')->only(['store']);
        $this->middleware('can:edit Jobs')->only(['update']);
        $this->middleware('can:delete Jobs')->only(['destroy']);
    }

    public function index(): View
    {
        $defaultLevels = [
            __('hr::hr.job_level_beginner'),
            __('hr::hr.job_level_intermediate'),
            __('hr::hr.job_level_professional'),
        ];

        $savedLevels = EmployeesJob::query()
            ->whereNotNull('level')
            ->where('level', '!=', '')
            ->distinct()
            ->orderBy('level')
            ->pluck('level')
            ->all();

        $jobLevels = collect($defaultLevels)
            ->merge($savedLevels)
            ->unique()
            ->values();

        return view('hr::hr-management.jobs.manage-jobs', [
            'jobLevels' => $jobLevels,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->jobService->list(
                $request->string('search')->toString() ?: null,
                $request->string('level')->toString() ?: null,
            ),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:255',
        ]);

        $this->jobService->create($validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.job_created_successfully'),
        ]);
    }

    public function update(Request $request, EmployeesJob $job): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:255',
        ]);

        $this->jobService->update($job, $validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.job_updated_successfully'),
        ]);
    }

    public function destroy(EmployeesJob $job): JsonResponse
    {
        $this->jobService->delete($job);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.job_deleted_successfully'),
        ]);
    }
}
