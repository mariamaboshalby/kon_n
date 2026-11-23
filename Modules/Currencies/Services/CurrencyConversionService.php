<?php

declare(strict_types=1);

namespace Modules\Currencies\Services;

use Modules\Settings\Models\Currency;
use Modules\Settings\Models\ExchangeRate;

class CurrencyConversionService
{
    /**
     * Convert amount from one currency to another.
     *
     * @param float $amount
     * @param int $fromCurrencyId
     * @param int $toCurrencyId
     * @param string|null $rateDate
     * @return array
     * @throws \Exception
     */
    public function convert(
        float $amount,
        int $fromCurrencyId,
        int $toCurrencyId,
        ?string $rateDate = null
    ): array {
        if ($fromCurrencyId === $toCurrencyId) {
            return [
                'original_amount' => $amount,
                'converted_amount' => $amount,
                'from_currency' => $this->getCurrencyData($fromCurrencyId),
                'to_currency' => $this->getCurrencyData($toCurrencyId),
                'rate' => 1.0,
                'rate_date' => $rateDate ?? now()->format('Y-m-d'),
            ];
        }

        $rate = $this->getExchangeRate($fromCurrencyId, $toCurrencyId, $rateDate);
        $convertedAmount = $amount * $rate;

        return [
            'original_amount' => $amount,
            'converted_amount' => round($convertedAmount, 8),
            'from_currency' => $this->getCurrencyData($fromCurrencyId),
            'to_currency' => $this->getCurrencyData($toCurrencyId),
            'rate' => $rate,
            'rate_date' => $rateDate ?? now()->format('Y-m-d'),
        ];
    }

    /**
     * Get exchange rate between two currencies.
     *
     * @param int $fromCurrencyId
     * @param int $toCurrencyId
     * @param string|null $rateDate
     * @return float
     * @throws \Exception
     */
    public function getExchangeRate(
        int $fromCurrencyId,
        int $toCurrencyId,
        ?string $rateDate = null
    ): float {
        if ($fromCurrencyId === $toCurrencyId) {
            return 1.0;
        }

        $fromCurrency = Currency::findOrFail($fromCurrencyId);
        $toCurrency = Currency::findOrFail($toCurrencyId);

        if (!$fromCurrency->is_active || !$toCurrency->is_active) {
            throw new \Exception(__('currencies.inactive_currency'));
        }

        $rateDate = $rateDate ?? now()->format('Y-m-d');

        // Get rates for both currencies
        $fromRate = $this->getCurrencyRate($fromCurrencyId, $rateDate);
        $toRate = $this->getCurrencyRate($toCurrencyId, $rateDate);

        if ($toRate == 0) {
            throw new \Exception(__('currencies.invalid_exchange_rate'));
        }

        // Formula: (amount * fromRate) / toRate
        return round($fromRate / $toRate, 8);
    }

    /**
     * Get currency rate for a specific date.
     *
     * @param int $currencyId
     * @param string $rateDate
     * @return float
     */
    private function getCurrencyRate(int $currencyId, string $rateDate): float
    {
        $currency = Currency::findOrFail($currencyId);

        if ($currency->is_default) {
            return 1.0;
        }

        // Try to get rate for the specific date
        $rate = ExchangeRate::where('currency_id', '=', $currencyId)
            ->where('rate_date', '=', $rateDate)
            ->first();

        if ($rate) {
            return (float) $rate->rate;
        }

        // If no rate for that date, get the latest rate before that date
        $rate = ExchangeRate::where('currency_id', '=', $currencyId)
            ->where('rate_date', '<=', $rateDate)
            ->orderBy('rate_date', 'desc')
            ->first();

        return $rate ? (float) $rate->rate : 1.0;
    }

    /**
     * Get currency data.
     *
     * @param int $currencyId
     * @return array
     */
    private function getCurrencyData(int $currencyId): array
    {
        $currency = Currency::findOrFail($currencyId);

        return [
            'id' => $currency->id,
            'name' => $currency->name,
            'code' => $currency->code,
            'symbol' => $currency->symbol,
            'decimal_places' => $currency->decimal_places,
        ];
    }
}
