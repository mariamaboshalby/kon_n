<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\HR\Models\Applicant;
use Modules\HR\Models\Cv;
use Modules\HR\Models\JobPosting;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApplicantController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Applicants')->only(['index', 'data', 'postingStats']);
        $this->middleware('can:create Applicants')->only(['store']);
        $this->middleware('can:edit Applicants')->only(['update']);
        $this->middleware('can:delete Applicants')->only(['destroy']);
    }

    public function index(): View
    {
        $jobPostings = JobPosting::query()
            ->withCount('applicants')
            ->orderBy('title')
            ->get(['id', 'title', 'status']);

        return view('recruitment::applicants.manage', compact('jobPostings'));
    }

    public function postingStats(): JsonResponse
    {
        $stats = JobPosting::query()
            ->withCount('applicants')
            ->orderByDesc('applicants_count')
            ->get(['id', 'title', 'status'])
            ->map(fn (JobPosting $jp) => [
                'id'               => $jp->id,
                'title'            => $jp->title,
                'status'           => $jp->status,
                'applicants_count' => $jp->applicants_count,
            ]);

        return response()->json(['data' => $stats]);
    }

    public function data(Request $request): JsonResponse
    {
        $search       = $request->string('search')->toString();
        $status       = $request->string('status')->toString();
        $jobPostingId = $request->string('job_posting_id')->toString();
        $dateFrom     = $request->string('date_from')->toString();
        $dateTo       = $request->string('date_to')->toString();

        $rows = Applicant::query()
            ->with('jobPosting:id,title')
            ->when($search !== '', fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', '%' . $search . '%')
                      ->orWhere('job_title', 'like', '%' . $search . '%')
                      ->orWhereHas('jobPosting', fn ($jp) => $jp->where('title', 'like', '%' . $search . '%'));
            }))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($jobPostingId !== '', fn ($q) => $q->where('job_posting_id', $jobPostingId))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->get()
            ->map(function (Applicant $applicant): array {
                $media = $applicant->getFirstMedia('applicant_cvs');

                return [
                    'id'               => $applicant->id,
                    'name'             => $applicant->name,
                    'job_title'        => $applicant->job_title,
                    'job_posting_id'   => $applicant->job_posting_id,
                    'job_posting_title'=> $applicant->jobPosting?->title,
                    'status'           => $applicant->status,
                    'has_file'         => (bool) $media,
                    'file_name'        => $media?->file_name,
                    'created_at'       => $applicant->created_at?->format('Y-m-d'),
                ];
            });

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request);
        $validated['branch_id'] = $this->resolveBranchId();

        $applicant = Applicant::create($validated);

        // لو اختار CV موجود — انسخ ملفه للـ applicant
        if ($request->filled('cv_id')) {
            $cv = Cv::find($request->input('cv_id'));
            if ($cv) {
                $media = $cv->getFirstMedia('HR_Cvs');
                if ($media) {
                    $applicant->clearMediaCollection('applicant_cvs');
                    $media->copy($applicant, 'applicant_cvs');
                }
            }
        } else {
            $this->attachFile($request, $applicant);
        }

        return response()->json([
            'success' => true,
            'message' => __('recruitment.applicant_created_successfully'),
        ]);
    }

    public function update(Request $request, Applicant $applicant): JsonResponse
    {
        $applicant->update($this->validated($request));
        $this->attachFile($request, $applicant, replace: true);

        return response()->json([
            'success' => true,
            'message' => __('recruitment.applicant_updated_successfully'),
        ]);
    }

    public function destroy(Applicant $applicant): JsonResponse
    {
        $applicant->clearMediaCollection('applicant_cvs');
        $applicant->delete();

        return response()->json([
            'success' => true,
            'message' => __('recruitment.applicant_deleted_successfully'),
        ]);
    }

    public function download(Applicant $applicant): BinaryFileResponse|JsonResponse
    {
        $media = $applicant->getFirstMedia('applicant_cvs');

        if (! $media) {
            return response()->json(['message' => __('recruitment.cv_file_not_found')], 404);
        }

        return response()->download($media->getPath(), $media->file_name);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name'           => 'required|string|max:255',
            'job_title'      => 'nullable|string|max:255',
            'job_posting_id' => 'nullable|integer|exists:job_postings,id',
            'status'         => 'required|string|in:pending,reviewed,accepted,rejected,on_hold',
            'cv_file'        => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);
    }

    private function attachFile(Request $request, Applicant $applicant, bool $replace = false): void
    {
        if (! $request->hasFile('cv_file')) {
            return;
        }
        if ($replace) {
            $applicant->clearMediaCollection('applicant_cvs');
        }
        $applicant->addMediaFromRequest('cv_file')->toMediaCollection('applicant_cvs');
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
