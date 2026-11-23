<?php

declare(strict_types=1);

namespace Modules\Currencies\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Settings\Models\Currency;
use Modules\Settings\Models\ExchangeRate;
use Modules\Currencies\Services\CurrencyConversionService;

class CurrencyConversionController extends Controller
{
    public function __construct(private CurrencyConversionService $conversionService) {}

    /**
     * Convert amount from one currency to another.
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'from_currency_id' => ['required', 'exists:currencies,id'],
            'to_currency_id' => ['required', 'exists:currencies,id'],
            'rate_date' => ['nullable', 'date'],
        ]);

        try {
            $result = $this->conversionService->convert(
                amount: (float) $validated['amount'],
                fromCurrencyId: (int) $validated['from_currency_id'],
                toCurrencyId: (int) $validated['to_currency_id'],
                rateDate: $validated['rate_date'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get exchange rate between two currencies.
     */
    public function getRate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_currency_id' => ['required', 'exists:currencies,id'],
            'to_currency_id' => ['required', 'exists:currencies,id'],
            'rate_date' => ['nullable', 'date'],
        ]);

        try {
            $rate = $this->conversionService->getExchangeRate(
                fromCurrencyId: (int) $validated['from_currency_id'],
                toCurrencyId: (int) $validated['to_currency_id'],
                rateDate: $validated['rate_date'] ?? null
            );

            return response()->json([
                'success' => true,
                'rate' => $rate,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get all active currencies for selection.
     */
    public function getActiveCurrencies(): JsonResponse
    {
        $currencies = Currency::active()
            ->with('latestRate')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get()
            ->map(fn ($currency) => [
                'id' => $currency->id,
                'name' => $currency->name,
                'code' => $currency->code,
                'symbol' => $currency->symbol,
                'is_default' => $currency->is_default,
                'rate' => $currency->latestRate?->rate ?? 1,
            ]);

        return response()->json([
            'success' => true,
            'data' => $currencies,
        ]);
    }

    /**
     * Update exchange rate for a currency.
     */
    public function updateRate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency_id' => ['required', 'exists:currencies,id'],
            'rate' => ['required', 'numeric', 'min:0.00000001'],
            'rate_date' => ['required', 'date'],
        ]);

        try {
            $exchangeRate = ExchangeRate::updateOrCreate(
                [
                    'currency_id' => $validated['currency_id'],
                    'rate_date' => $validated['rate_date'],
                ],
                ['rate' => $validated['rate']]
            );

            return response()->json([
                'success' => true,
                'message' => __('currencies.rate_updated_successfully'),
                'data' => $exchangeRate,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get exchange rate history for a currency.
     */
    public function getRateHistory(Currency $currency, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $days = $validated['days'] ?? 30;

        $history = $currency->exchangeRates()
            ->where('rate_date', '>=', now()->subDays($days))
            ->orderBy('rate_date', 'desc')
            ->get()
            ->map(fn ($rate) => [
                'date' => $rate->rate_date->format('Y-m-d'),
                'rate' => $rate->rate,
            ]);

        return response()->json([
            'success' => true,
            'currency' => [
                'id' => $currency->id,
                'name' => $currency->name,
                'code' => $currency->code,
            ],
            'history' => $history,
        ]);
    }
}
