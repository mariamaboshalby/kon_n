<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\HR\Models\Cv;
use Modules\HR\Models\JobPosting;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CvController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view CVs')->only(['index', 'data', 'show', 'download']);
        $this->middleware('can:create CVs')->only(['store']);
        $this->middleware('can:edit CVs')->only(['update']);
        $this->middleware('can:delete CVs')->only(['destroy']);
    }

    public function index(): View
    {
        $jobPostings = JobPosting::query()
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('recruitment::cvs.manage', compact('jobPostings'));
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $gender = $request->string('gender')->toString();
        $marital = $request->string('marital_status')->toString();
        $nationality = $request->string('nationality')->toString();
        $status = $request->string('status')->toString();
        $jobPostingId = $request->string('job_posting_id')->toString();
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        $rows = Cv::query()
            ->with('jobPosting:id,title')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('job_title', 'like', '%'.$search.'%');
                });
            })
            ->when($gender !== '', fn ($q) => $q->where('gender', $gender))
            ->when($marital !== '', fn ($q) => $q->where('marital_status', $marital))
            ->when($nationality !== '', fn ($q) => $q->where('nationality', 'like', '%'.$nationality.'%'))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($jobPostingId !== '', fn ($q) => $q->where('job_posting_id', $jobPostingId))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->get()
            ->map(function (Cv $cv) {
                $media = $cv->getFirstMedia('HR_Cvs');

                return array_merge($this->fieldsFrom($cv), [
                    'has_file' => (bool) $media,
                    'file_name' => $media?->file_name,
                    'job_posting_title' => $cv->jobPosting?->title,
                ]);
            });

        return response()->json(['data' => $rows]);
    }

    public function selectList(): JsonResponse
    {
        $rows = Cv::query()
            ->latest()
            ->get(['id', 'name', 'phone'])
            ->map(fn (Cv $cv) => [
                'id'    => $cv->id,
                'name'  => $cv->name,
                'phone' => $cv->phone,
            ]);

        return response()->json(['data' => $rows]);
    }

    public function show($id): View
    {
        $cv = Cv::with(['branch', 'jobPosting', 'interviews', 'contract'])->findOrFail($id);

        return view('recruitment::cvs.show', compact('cv'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request);
        $validated['branch_id'] = $this->resolveBranchId();
        $cv = Cv::create($validated);
        $this->attachFile($request, $cv);

        return response()->json([
            'success' => true,
            'message' => __('recruitment.cv_created_successfully'),
        ]);
    }

    public function update(Request $request, Cv $cv): JsonResponse
    {
        $cv->update($this->validated($request));
        $this->attachFile($request, $cv, true);

        return response()->json([
            'success' => true,
            'message' => __('recruitment.cv_updated_successfully'),
        ]);
    }

    public function destroy(Cv $cv): JsonResponse
    {
        $cv->clearMediaCollection('HR_Cvs');
        $cv->delete();

        return response()->json([
            'success' => true,
            'message' => __('recruitment.cv_deleted_successfully'),
        ]);
    }

    public function download(Cv $cv): BinaryFileResponse|JsonResponse
    {
        $media = $cv->getFirstMedia('HR_Cvs');
        if (! $media) {
            return response()->json(['message' => __('recruitment.cv_file_not_found')], 404);
        }

        return response()->download($media->getPath(), $media->file_name);
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFrom(Cv $cv): array
    {
        return [
            'id' => $cv->id,
            'name' => $cv->name,
            'email' => $cv->email,
            'phone' => $cv->phone,
            'country' => $cv->country,
            'state' => $cv->state,
            'city' => $cv->city,
            'address' => $cv->address,
            'birth_date' => $cv->birth_date,
            'gender' => $cv->gender,
            'marital_status' => $cv->marital_status,
            'nationality' => $cv->nationality,
            'religion' => $cv->religion,
            'summary' => $cv->summary,
            'skills' => $cv->skills,
            'experience' => $cv->experience,
            'education' => $cv->education,
            'projects' => $cv->projects,
            'certifications' => $cv->certifications,
            'languages' => $cv->languages,
            'interests' => $cv->interests,
            'references' => $cv->references,
            'cover_letter' => $cv->cover_letter,
            'portfolio' => $cv->portfolio,
            'status' => $cv->status ?? 'pending',
            'job_title' => $cv->job_title,
            'job_posting_id' => $cv->job_posting_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'country' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'birth_date' => 'required|date',
            'gender' => 'required|string|in:male,female,other',
            'marital_status' => 'required|string|in:single,married,divorced,widowed',
            'nationality' => 'required|string|max:100',
            'religion' => 'required|string|max:100',
            'summary' => 'nullable|string|max:1000',
            'skills' => 'nullable|string|max:1000',
            'experience' => 'nullable|string|max:2000',
            'education' => 'nullable|string|max:2000',
            'projects' => 'nullable|string|max:2000',
            'certifications' => 'nullable|string|max:1000',
            'languages' => 'nullable|string|max:500',
            'interests' => 'nullable|string|max:500',
            'references' => 'nullable|string|max:1000',
            'cover_letter' => 'nullable|string|max:2000',
            'portfolio' => 'nullable|string|max:255',
            'cv_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'status' => 'nullable|string|in:pending,reviewed,accepted,rejected,on_hold',
            'job_title' => 'nullable|string|max:255',
            'job_posting_id' => 'nullable|integer|exists:job_postings,id',
        ]);
    }

    private function attachFile(Request $request, Cv $cv, bool $replace = false): void
    {
        if (! $request->hasFile('cv_file')) {
            return;
        }

        if ($replace) {
            $cv->clearMediaCollection('HR_Cvs');
        }

        $cv->addMediaFromRequest('cv_file')->toMediaCollection('HR_Cvs');
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
