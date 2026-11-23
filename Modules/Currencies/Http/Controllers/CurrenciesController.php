<?php

declare(strict_types=1);

namespace Modules\Currencies\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Settings\Models\Currency;
use Modules\Currencies\Http\Requests\StoreCurrencyRequest;
use Modules\Currencies\Http\Requests\UpdateCurrencyRequest;

class CurrenciesController extends Controller
{
    /**
     * Display a listing of currencies.
     */
    public function index(): View
    {
        $currencies = Currency::with('latestRate')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return view('currencies::index', compact('currencies'));
    }

    /**
     * Show the form for creating a new currency.
     */
    public function create(): View
    {
        return view('currencies::create');
    }

    /**
     * Store a newly created currency in storage.
     */
    public function store(StoreCurrencyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Currency::create($validated);

        return redirect()
            ->route('currencies.index')
            ->with('success', __('currencies::currencies.created_successfully'));
    }

    /**
     * Show the specified currency.
     */
    public function show(Currency $currency): View
    {
        $currency->load('exchangeRates');

        return view('currencies::show', compact('currency'));
    }

    /**
     * Show the form for editing the specified currency.
     */
    public function edit(Currency $currency): View
    {
        return view('currencies::edit', compact('currency'));
    }

    /**
     * Update the specified currency in storage.
     */
    public function update(UpdateCurrencyRequest $request, Currency $currency): RedirectResponse
    {
        $validated = $request->validated();

        $currency->update($validated);

        return redirect()
            ->route('currencies.index')
            ->with('success', __('currencies::currencies.updated_successfully'));
    }

    /**
     * Remove the specified currency from storage.
     */
    public function destroy(Currency $currency): RedirectResponse
    {
        try {
            $currency->delete();

            return redirect()
                ->route('currencies.index')
                ->with('success', __('currencies::currencies.deleted_successfully'));
        } catch (\Throwable $e) {
            return redirect()
                ->route('currencies.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show currency conversion form.
     */
    public function convert(): View
    {
        $currencies = Currency::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('currencies::convert', compact('currencies'));
    }

    /**
     * Perform currency conversion.
     */
    public function performConvert(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from_currency_id' => ['required', 'exists:currencies,id'],
            'to_currency_id' => ['required', 'exists:currencies,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $fromCurrency = Currency::findOrFail($validated['from_currency_id']);
        $toCurrency = Currency::findOrFail($validated['to_currency_id']);

        if (!$fromCurrency->is_active || !$toCurrency->is_active) {
            return redirect()
                ->back()
                ->with('error', __('currencies::currencies.inactive_currency'));
        }

        $fromRate = (float) ($fromCurrency->latestRate?->rate ?? 1);
        $toRate = (float) ($toCurrency->latestRate?->rate ?? 1);
        $amount = (float) $validated['amount'];

        $convertedAmount = ($amount * $toRate) / $fromRate;

        return redirect()
            ->back()
            ->with([
                'success' => __('currencies::currencies.conversion_rate'),
                'conversion_result' => [
                    'from_currency' => $fromCurrency->name,
                    'to_currency' => $toCurrency->name,
                    'amount' => $amount,
                    'converted_amount' => $convertedAmount,
                    'rate' => $toRate / $fromRate,
                ],
            ]);
    }

    /**
     * Show cash box revaluation form.
     */
    public function revaluation(): View
    {
        $currencies = Currency::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('currencies::revaluation', compact('currencies'));
    }

    /**
     * Perform cash box revaluation.
     */
    public function performRevaluation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency_id' => ['required', 'exists:currencies,id'],
            'revaluation_date' => ['required', 'date'],
        ]);

        $currency = Currency::findOrFail($validated['currency_id']);

        if (!$currency->is_active) {
            return redirect()
                ->back()
                ->with('error', __('currencies::currencies.inactive_currency'));
        }

        // TODO: Implement revaluation logic
        // This would typically involve:
        // 1. Getting cash balance for the currency
        // 2. Calculating gain/loss based on rate change
        // 3. Recording the revaluation entry

        return redirect()
            ->back()
            ->with('success', __('currencies::currencies.rate_updated_successfully'));
    }
}
