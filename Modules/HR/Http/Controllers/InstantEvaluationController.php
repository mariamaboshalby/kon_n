<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\HR\Models\InstantEvaluationItem;
use Modules\HR\Services\InstantEvaluationItemService;
use Modules\HR\Services\InstantEvaluationService;

class InstantEvaluationController extends Controller
{
    public function __construct(
        private readonly InstantEvaluationService $service,
        private readonly InstantEvaluationItemService $items
    ) {
        $this->middleware('can:view Instant Evaluations')->only(['index', 'data', 'dashboard', 'itemsByPolarity']);
        $this->middleware('can:create Instant Evaluations')->only(['store']);
        $this->middleware('can:delete Instant Evaluations')->only(['destroy']);
    }

    public function index(): View
    {
        return view('hr::hr-management.instant-evaluations.index', [
            'employees' => $this->service->employeesForSelect(),
            'dashboard' => $this->service->dashboard(10, 15),
            'positiveItems' => $this->items->activeByPolarity(InstantEvaluationItem::POLARITY_POSITIVE),
            'negativeItems' => $this->items->activeByPolarity(InstantEvaluationItem::POLARITY_NEGATIVE),
        ]);
    }

    public function dashboard(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->dashboard(10, 20),
        ]);
    }

    public function itemsByPolarity(string $polarity): JsonResponse
    {
        if (! in_array($polarity, ['positive', 'negative'], true)) {
            return response()->json(['data' => []]);
        }

        $rows = $this->items->activeByPolarity($polarity);

        return response()->json([
            'data' => $rows->map(fn ($row) => $this->items->toArray($row))->values(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $polarity = $request->string('polarity')->toString() ?: null;
        if ($polarity !== null && ! in_array($polarity, ['positive', 'negative'], true)) {
            $polarity = null;
        }

        $paginator = $this->service->list(
            $request->string('search')->toString() ?: null,
            $request->filled('employee_id') ? (int) $request->input('employee_id') : null,
            $polarity,
            50,
            $request->string('date_from')->toString() ?: null,
            $request->string('date_to')->toString()   ?: null,
        );

        return response()->json([
            'data' => collect($paginator->items())->map(fn ($row) => $this->service->toArray($row))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'polarity' => ['required', Rule::in([InstantEvaluationItem::POLARITY_POSITIVE, InstantEvaluationItem::POLARITY_NEGATIVE])],
            'score' => 'required|integer|min:1|max:5',
            'item_id' => 'required|exists:instant_evaluation_items,id',
            'note' => 'nullable|string|max:1000',
        ]);

        $evaluation = $this->service->create($validated, $request->user());

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.instant_evaluation_saved'),
            'data' => $this->service->toArray($evaluation),
        ]);
    }

    public function destroy(int $evaluation): JsonResponse
    {
        $model = $this->service->find($evaluation);
        $this->service->delete($model);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.instant_evaluation_deleted'),
        ]);
    }
}
