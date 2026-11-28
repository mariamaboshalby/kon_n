<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\HR\Models\Contract;
use Modules\HR\Models\Cv;
use Modules\HR\Models\Interview;
use Modules\HR\Models\Onboarding;
use Modules\HR\Services\OnboardingService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
    ) {
        $this->middleware('can:view Onboardings')->only(['index', 'data', 'show', 'downloadDocument']);
        $this->middleware('can:create Onboardings')->only(['store']);
        $this->middleware('can:edit Onboardings')->only(['update', 'destroyDocument']);
        $this->middleware('can:delete Onboardings')->only(['destroy']);
    }

    public function index(): View
    {
        $contracts = Contract::query()->latest()->get(['id', 'name']);
        $interviews = Interview::query()->where('result', 'accepted')->latest()->get(['id', 'cv_id']);
        $cvs = Cv::query()->orderBy('name')->get(['id', 'name']);

        return view('recruitment::onboardings.manage', compact('contracts', 'interviews', 'cvs'));
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $contractId = $request->input('contract_id');
        $cvId = $request->input('cv_id');
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        $rows = Onboarding::query()
            ->with(['employee:id,name', 'contract:id,name', 'cv:id,name', 'interview:id'])
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('cv', fn ($c) => $c->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('employee', fn ($e) => $e->where('name', 'like', '%'.$search.'%'));
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($contractId, fn ($q) => $q->where('contract_id', $contractId))
            ->when($cvId, fn ($q) => $q->where('cv_id', $cvId))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('start_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('start_date', '<=', $dateTo))
            ->latest()
            ->get()
            ->map(function (Onboarding $row) {
                $docs = $row->getMedia('onboarding_documents')->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->file_name,
                ])->values();

                return [
                    'id' => $row->id,
                    'contract_id' => $row->contract_id,
                    'interview_id' => $row->interview_id,
                    'cv_id' => $row->cv_id,
                    'cv_name' => $row->cv?->name,
                    'employee_id' => $row->employee_id,
                    'employee_name' => $row->employee?->name,
                    'contract_name' => $row->contract?->name,
                    'status' => $row->status,
                    'start_date' => $row->start_date?->format('Y-m-d'),
                    'completion_date' => $row->completion_date?->format('Y-m-d'),
                    'notes' => $row->notes,
                    'checklist' => $row->checklist ?? [],
                    'documents' => $docs,
                ];
            });

        return response()->json(['data' => $rows]);
    }

    public function show($id): View
    {
        $onboarding = Onboarding::with(['contract', 'branch', 'cv', 'employee', 'interview'])->findOrFail($id);

        return view('recruitment::onboardings.show', compact('onboarding'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request);
        $messages = [];

        DB::transaction(function () use ($validated, $request, &$messages) {
            $validated['created_by'] = Auth::id();
            $validated['branch_id'] = $this->resolveBranchId();
            $onboarding = Onboarding::create($validated);
            $this->attachDocuments($request, $onboarding);

            if ($validated['status'] === 'completed') {
                $result = $this->onboardingService->createEmployeeIfNeeded($onboarding->fresh(['cv']));
                if (! empty($result['message'])) {
                    $messages[] = $result['message'];
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => $messages[0] ?? __('recruitment.onboarding_created_successfully'),
        ]);
    }

    public function update(Request $request, Onboarding $onboarding): JsonResponse
    {
        $validated = $this->validated($request);
        $messages = [];

        DB::transaction(function () use ($validated, $request, $onboarding, &$messages) {
            $oldStatus = $onboarding->status;
            $onboarding->update($validated);
            $this->attachDocuments($request, $onboarding);

            if ($validated['status'] === 'completed' && $oldStatus !== 'completed') {
                $result = $this->onboardingService->createEmployeeIfNeeded($onboarding->fresh(['cv']));
                if (! empty($result['message'])) {
                    $messages[] = $result['message'];
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => $messages[0] ?? __('recruitment.onboarding_updated_successfully'),
        ]);
    }

    public function destroy(Onboarding $onboarding): JsonResponse
    {
        $onboarding->clearMediaCollection('onboarding_documents');
        $onboarding->delete();

        return response()->json([
            'success' => true,
            'message' => __('recruitment.onboarding_deleted_successfully'),
        ]);
    }

    public function downloadDocument(Onboarding $onboarding, int $mediaId): BinaryFileResponse|JsonResponse
    {
        $media = $onboarding->getMedia('onboarding_documents')->where('id', $mediaId)->first();
        if (! $media) {
            return response()->json(['message' => __('recruitment.cv_file_not_found')], 404);
        }

        return response()->download($media->getPath(), $media->file_name);
    }

    public function destroyDocument(Onboarding $onboarding, int $mediaId): JsonResponse
    {
        $media = $onboarding->getMedia('onboarding_documents')->where('id', $mediaId)->first();
        if ($media) {
            $media->delete();
        }

        return response()->json([
            'success' => true,
            'message' => __('recruitment.document_deleted_successfully'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'contract_id' => 'nullable|exists:contracts,id',
            'interview_id' => 'nullable|exists:interviews,id',
            'cv_id' => 'required|exists:cvs,id',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'start_date' => 'nullable|date',
            'completion_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'checklist' => 'nullable|array',
            'checklist.*.item' => 'nullable|string|max:255',
            'checklist.*.completed' => 'nullable|boolean',
            'documents' => 'nullable|array',
            'documents.*' => 'nullable|file|max:10240',
        ]);

        $checklist = [];
        foreach ($validated['checklist'] ?? [] as $item) {
            if (! empty($item['item'])) {
                $checklist[] = [
                    'item' => $item['item'],
                    'completed' => ! empty($item['completed']),
                ];
            }
        }
        $validated['checklist'] = $checklist;
        unset($validated['documents']);

        return $validated;
    }

    private function attachDocuments(Request $request, Onboarding $onboarding): void
    {
        if (! $request->hasFile('documents')) {
            return;
        }

        foreach ($request->file('documents') as $file) {
            if ($file && $file->isValid()) {
                $onboarding->addMedia($file)->toMediaCollection('onboarding_documents');
            }
        }
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
