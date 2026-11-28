<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\HR\Models\EmployeesJob;
use Modules\HR\Models\JobPosting;

class JobPostingController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Job Postings')->only(['index', 'data', 'show']);
        $this->middleware('can:create Job Postings')->only(['store']);
        $this->middleware('can:edit Job Postings')->only(['update']);
        $this->middleware('can:delete Job Postings')->only(['destroy']);
    }

    public function index(): View
    {
        $jobs = EmployeesJob::query()->orderBy('title')->get(['id', 'title']);

        return view('recruitment::job-postings.manage', compact('jobs'));
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $jobId = $request->string('job_id')->toString();
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        $rows = JobPosting::query()
            ->with(['job:id,title'])
            ->withCount('cvs')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($jobId !== '', fn ($q) => $q->where('job_id', $jobId))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('start_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('start_date', '<=', $dateTo))
            ->latest()
            ->get()
            ->map(fn (JobPosting $row) => [
                'id' => $row->id,
                'title' => $row->title,
                'description' => $row->description,
                'job_id' => $row->job_id,
                'job_title' => $row->job?->title,
                'qualification_type' => $row->qualification_type,
                'status' => $row->status,
                'start_date' => $row->start_date?->format('Y-m-d'),
                'end_date' => $row->end_date?->format('Y-m-d'),
                'requirements' => $row->requirements,
                'benefits' => $row->benefits,
                'cvs_count' => $row->cvs_count,
            ]);

        return response()->json(['data' => $rows]);
    }

    public function show($id): View
    {
        $jobPosting = JobPosting::with(['branch', 'job'])->findOrFail($id);

        return view('recruitment::job-postings.show', compact('jobPosting'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request);
        $validated['created_by'] = Auth::id();
        $validated['branch_id'] = $this->resolveBranchId();

        JobPosting::create($validated);

        return response()->json([
            'success' => true,
            'message' => __('recruitment.job_posting_created_successfully'),
        ]);
    }

    public function update(Request $request, JobPosting $job_posting): JsonResponse
    {
        $job_posting->update($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => __('recruitment.job_posting_updated_successfully'),
        ]);
    }

    public function destroy(JobPosting $job_posting): JsonResponse
    {
        $job_posting->delete();

        return response()->json([
            'success' => true,
            'message' => __('recruitment.job_posting_deleted_successfully'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'job_id' => 'nullable|exists:employees_jobs,id',
            'qualification_type' => 'nullable|string|max:100',
            'status' => 'required|in:active,closed,expired',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'requirements' => 'nullable|string',
            'benefits' => 'nullable|string',
        ]);
    }

    private function resolveBranchId(): ?int
    {
        $branchId = optional(Auth::user())
            ->branches()
            ->where('branches.is_active', 1)
            ->value('branches.id');

        return $branchId !== null ? (int) $branchId : null;
    }
}
