<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\HR\Models\Cv;
use Modules\HR\Models\Interview;
use Modules\HR\Models\JobPosting;

class InterviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Interviews')->only(['index', 'data', 'show', 'calendar', 'calendarData']);
        $this->middleware('can:create Interviews')->only(['store']);
        $this->middleware('can:edit Interviews')->only(['update']);
        $this->middleware('can:delete Interviews')->only(['destroy']);
    }

    public function index(): View
    {
        $cvs = Cv::query()->orderBy('name')->get(['id', 'name']);
        $jobPostings = JobPosting::query()->where('status', 'active')->orderBy('title')->get(['id', 'title']);
        $interviewers = User::query()->orderBy('name')->get(['id', 'name']);

        return view('recruitment::interviews.manage', compact('cvs', 'jobPostings', 'interviewers'));
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $type = $request->string('type')->toString();
        $result = $request->string('result')->toString();
        $jobPostingId = $request->integer('job_posting_id') ?: null;
        $interviewerId = $request->integer('interviewer_id') ?: null;
        $dateFrom = $this->normalizeDate($request->string('date_from')->toString());
        $dateTo = $this->normalizeDate($request->string('date_to')->toString());

        $rows = Interview::query()
            ->with(['cv:id,name', 'jobPosting:id,title', 'interviewer:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->whereHas('cv', fn ($c) => $c->where('name', 'like', $like))
                        ->orWhereHas('jobPosting', fn ($j) => $j->where('title', 'like', $like))
                        ->orWhereHas('interviewer', fn ($i) => $i->where('name', 'like', $like))
                        ->orWhere('location', 'like', $like)
                        ->orWhere('notes', 'like', $like);
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($type !== '', fn ($q) => $q->where('interview_type', $type))
            ->when($result !== '', fn ($q) => $q->where('result', $result))
            ->when($jobPostingId, fn ($q) => $q->where('job_posting_id', $jobPostingId))
            ->when($interviewerId, fn ($q) => $q->where('interviewer_id', $interviewerId))
            ->when($dateFrom, fn ($q) => $q->whereDate('scheduled_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('scheduled_at', '<=', $dateTo))
            ->latest('scheduled_at')
            ->get()
            ->map(fn (Interview $row) => $this->serialize($row));

        return response()->json(['data' => $rows]);
    }

    /**
     * Validate a Y-m-d date string; return it or null.
     */
    private function normalizeDate(string $value): ?string
    {
        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $parsed = \DateTime::createFromFormat('Y-m-d', $value);

        return ($parsed && $parsed->format('Y-m-d') === $value) ? $value : null;
    }

    public function show(Request $request, $id): View|JsonResponse
    {
        $interview = Interview::with(['cv', 'branch', 'jobPosting', 'interviewer'])->findOrFail($id);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'interview' => $this->serialize($interview),
            ]);
        }

        return view('recruitment::interviews.show', compact('interview'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request);
        $validated['created_by'] = Auth::id();
        $validated['branch_id'] = $this->resolveBranchId();

        Interview::create($validated);

        return response()->json([
            'success' => true,
            'message' => __('recruitment.interview_created_successfully'),
        ]);
    }

    public function update(Request $request, Interview $interview): JsonResponse
    {
        $interview->update($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => __('recruitment.interview_updated_successfully'),
        ]);
    }

    public function destroy(Interview $interview): JsonResponse
    {
        $interview->delete();

        return response()->json([
            'success' => true,
            'message' => __('recruitment.interview_deleted_successfully'),
        ]);
    }

    public function calendar(): View
    {
        return view('recruitment::interviews.calendar');
    }

    public function calendarData(Request $request): JsonResponse
    {
        $month = $request->string('month')->toString() ?: now()->format('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $type   = $request->string('type')->toString() ?: null;
        $status = $request->string('status')->toString() ?: null;
        if ($type !== null && ! in_array($type, ['phone', 'video', 'in_person', 'panel'], true)) {
            $type = null;
        }
        if ($status !== null && ! in_array($status, ['scheduled', 'completed', 'cancelled', 'rescheduled'], true)) {
            $status = null;
        }

        $start = $month.'-01 00:00:00';
        $end = date('Y-m-t 23:59:59', strtotime($start));

        $interviews = Interview::query()
            ->with(['cv:id,name', 'jobPosting:id,title', 'interviewer:id,name'])
            ->whereBetween('scheduled_at', [$start, $end])
            ->when($type,   fn ($q) => $q->where('interview_type', $type))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (Interview $row) => [
                'id'             => $row->id,
                'date'           => $row->scheduled_at?->format('Y-m-d'),
                'time'           => $row->scheduled_at?->format('H:i'),
                'cv_name'        => $row->cv?->name,
                'interview_type' => $row->interview_type,
                'status'         => $row->status,
            ]);

        return response()->json([
            'month'      => $month,
            'interviews' => $interviews,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Interview $row): array
    {
        return [
            'id' => $row->id,
            'cv_id' => $row->cv_id,
            'cv_name' => $row->cv?->name,
            'job_posting_id' => $row->job_posting_id,
            'job_posting_title' => $row->jobPosting?->title,
            'interview_type' => $row->interview_type,
            'status' => $row->status,
            'scheduled_at' => $row->scheduled_at?->format('Y-m-d\TH:i'),
            'scheduled_at_display' => $row->scheduled_at?->format('Y-m-d H:i'),
            'scheduled_at_iso' => $row->scheduled_at?->toIso8601String(),
            'duration' => $row->duration,
            'location' => $row->location,
            'notes' => $row->notes,
            'interviewer_id' => $row->interviewer_id,
            'interviewer_name' => $row->interviewer?->name,
            'result' => $row->result,
            'feedback' => $row->feedback,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'cv_id' => 'required|exists:cvs,id',
            'job_posting_id' => 'nullable|exists:job_postings,id',
            'interview_type' => 'required|in:phone,video,in_person,panel',
            'status' => 'required|in:scheduled,completed,cancelled,rescheduled',
            'scheduled_at' => 'required|date',
            'duration' => 'nullable|integer|min:15|max:480',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'interviewer_id' => 'nullable|exists:users,id',
            'result' => 'nullable|in:pending,accepted,rejected,on_hold',
            'feedback' => 'nullable|string',
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
