<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\Cv;
use Modules\HR\Models\JobPosting;

/**
 * أحدث المتقدمين — CRUD على جدول cvs مع عرض الحالة والإعلان الوظيفي
 * نفس نمط InterviewController في الربط بـ cvs
 */
class LatestApplicantController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view CVs')->only(['index', 'data']);
        $this->middleware('can:edit CVs')->only(['updateStatus']);
    }

    public function index(): View
    {
        $jobPostings = JobPosting::query()->orderBy('title')->get(['id', 'title', 'status']);

        return view('recruitment::latest-applicants.manage', compact('jobPostings'));
    }

    public function data(Request $request): JsonResponse
    {
        $search       = $request->string('search')->toString();
        $status       = $request->string('status')->toString();
        $jobPostingId = $request->string('job_posting_id')->toString();
        $dateFrom     = $request->string('date_from')->toString();
        $dateTo       = $request->string('date_to')->toString();

        $rows = Cv::query()
            ->with('jobPosting:id,title')
            ->when($search !== '', fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', '%' . $search . '%')
                      ->orWhere('phone', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
            }))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($jobPostingId !== '', fn ($q) => $q->where('job_posting_id', $jobPostingId))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->limit(50)                // أحدث 50 متقدم
            ->get()
            ->map(function (Cv $cv): array {
                $media = $cv->getFirstMedia('HR_Cvs');

                return [
                    'id'                => $cv->id,
                    'name'              => $cv->name,
                    'phone'             => $cv->phone,
                    'email'             => $cv->email,
                    'job_posting_id'    => $cv->job_posting_id,
                    'job_posting_title' => $cv->jobPosting?->title,
                    'status'            => $cv->status ?? 'pending',
                    'has_file'          => (bool) $media,
                    'file_name'         => $media?->file_name,
                    'created_at'        => $cv->created_at?->format('Y-m-d'),
                ];
            });

        return response()->json(['data' => $rows]);
    }

    /** تحديث حالة المتقدم فقط */
    public function updateStatus(Request $request, Cv $cv): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,reviewed,accepted,rejected,on_hold',
        ]);

        $cv->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => __('recruitment.applicant_updated_successfully'),
        ]);
    }

    /** تحميل ملف CV */
    public function download(Cv $cv): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        $media = $cv->getFirstMedia('HR_Cvs');

        if (! $media) {
            return response()->json(['message' => __('recruitment.cv_file_not_found')], 404);
        }

        return response()->download($media->getPath(), $media->file_name);
    }
}
