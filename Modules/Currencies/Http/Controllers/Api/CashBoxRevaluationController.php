<?php

declare(strict_types=1);

namespace Modules\Currencies\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Settings\Models\Currency;
use Modules\Currencies\Services\CashBoxRevaluationService;

class CashBoxRevaluationController extends Controller
{
    public function __construct(private CashBoxRevaluationService $revaluationService) {}

    /**
     * Revalue cash box for all currencies.
     */
    public function revalue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'revaluation_date' => ['nullable', 'date'],
        ]);

        try {
            $result = $this->revaluationService->revaluateCashBox(
                $validated['revaluation_date'] ?? null
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get revaluation history for a currency.
     */
    public function history(Currency $currency, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        try {
            $history = $this->revaluationService->getRevaluationHistory(
                $currency->id,
                $validated['days'] ?? 30
            );

            return response()->json([
                'success' => true,
                'currency' => [
                    'id' => $currency->id,
                    'name' => $currency->name,
                    'code' => $currency->code,
                ],
                'history' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
