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

class InstantEvaluationItemController extends Controller
{
    public function __construct(
        private readonly InstantEvaluationItemService $service
    ) {
        $this->middleware('can:view Instant Evaluation Items')->only(['index', 'data']);
        $this->middleware('can:create Instant Evaluation Items')->only(['store']);
        $this->middleware('can:edit Instant Evaluation Items')->only(['update']);
        $this->middleware('can:delete Instant Evaluation Items')->only(['destroy']);
    }

    public function index(): View
    {
        return view('hr::hr-management.instant-evaluation-items.index');
    }

    public function data(Request $request): JsonResponse
    {
        $polarity = $request->string('polarity')->toString() ?: null;
        if ($polarity !== null && ! in_array($polarity, ['positive', 'negative'], true)) {
            $polarity = null;
        }

        $rows = $this->service->list(
            $request->string('search')->toString() ?: null,
            $polarity
        );

        return response()->json([
            'data' => $rows->map(fn ($row) => $this->service->toArray($row))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateItem($request);
        $item = $this->service->create($validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.instant_evaluation_item_saved'),
            'data' => $this->service->toArray($item),
        ]);
    }

    public function update(Request $request, int $item): JsonResponse
    {
        $model = $this->service->find($item);
        $validated = $this->validateItem($request);
        $updated = $this->service->update($model, $validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.instant_evaluation_item_updated'),
            'data' => $this->service->toArray($updated),
        ]);
    }

    public function destroy(int $item): JsonResponse
    {
        $model = $this->service->find($item);
        $this->service->delete($model);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.instant_evaluation_item_deleted'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateItem(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:1000',
            'polarity' => ['required', Rule::in([InstantEvaluationItem::POLARITY_POSITIVE, InstantEvaluationItem::POLARITY_NEGATIVE])],
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);
    }
}
