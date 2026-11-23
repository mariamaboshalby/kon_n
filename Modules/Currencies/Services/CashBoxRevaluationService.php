<?php

declare(strict_types=1);

namespace Modules\Currencies\Services;

use Illuminate\Support\Facades\DB;
use Modules\Settings\Models\Currency;
use Modules\Settings\Models\ExchangeRate;

class CashBoxRevaluationService
{
    /**
     * Revalue cash box balances for all currencies.
     *
     * @param string|null $rateDate
     * @return array
     * @throws \Exception
     */
    public function revaluateCashBox(?string $rateDate = null): array
    {
        $rateDate = $rateDate ?? now()->format('Y-m-d');
        $defaultCurrency = Currency::where('is_default', true)->firstOrFail();

        $results = [];
        $totalGainLoss = 0;

        DB::beginTransaction();

        try {
            // Get all active non-default currencies
            $currencies = Currency::where('is_active', true)
                ->where('id', '!=', $defaultCurrency->id)
                ->get();

            foreach ($currencies as $currency) {
                $revaluation = $this->revaluateCurrency(
                    $currency,
                    $defaultCurrency,
                    $rateDate
                );

                $results[] = $revaluation;
                $totalGainLoss += $revaluation['gain_loss'];
            }

            DB::commit();

            return [
                'success' => true,
                'revaluation_date' => $rateDate,
                'default_currency' => [
                    'id' => $defaultCurrency->id,
                    'name' => $defaultCurrency->name,
                    'code' => $defaultCurrency->code,
                ],
                'revaluations' => $results,
                'total_gain_loss' => round($totalGainLoss, 8),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Revalue a specific currency.
     *
     * @param Currency $currency
     * @param Currency $defaultCurrency
     * @param string $rateDate
     * @return array
     */
    private function revaluateCurrency(
        Currency $currency,
        Currency $defaultCurrency,
        string $rateDate
    ): array {
        // Get current rate
        $currentRate = $this->getRate($currency->id, $rateDate);
        $previousRate = $this->getPreviousRate($currency->id, $rateDate);

        // Get cash balance in this currency (from acc_head table)
        $cashBalance = $this->getCashBalance($currency->id);

        // Calculate gain/loss
        $gainLoss = $this->calculateGainLoss(
            $cashBalance,
            $currentRate,
            $previousRate
        );

        return [
            'currency_id' => $currency->id,
            'currency_name' => $currency->name,
            'currency_code' => $currency->code,
            'cash_balance' => round($cashBalance, 8),
            'previous_rate' => round($previousRate, 8),
            'current_rate' => round($currentRate, 8),
            'rate_change' => round($currentRate - $previousRate, 8),
            'gain_loss' => round($gainLoss, 8),
            'gain_loss_type' => $gainLoss > 0 ? 'gain' : ($gainLoss < 0 ? 'loss' : 'neutral'),
        ];
    }

    /**
     * Get current exchange rate for a currency.
     *
     * @param int $currencyId
     * @param string $rateDate
     * @return float
     */
    private function getRate(int $currencyId, string $rateDate): float
    {
        $rate = ExchangeRate::where('currency_id', '=', $currencyId)
            ->where('rate_date', '<=', $rateDate)
            ->orderBy('rate_date', 'desc')
            ->first();

        return $rate ? (float) $rate->rate : 1.0;
    }

    /**
     * Get previous exchange rate for a currency.
     *
     * @param int $currencyId
     * @param string $rateDate
     * @return float
     */
    private function getPreviousRate(int $currencyId, string $rateDate): float
    {
        $rate = ExchangeRate::where('currency_id', '=', $currencyId)
            ->where('rate_date', '<', $rateDate)
            ->orderBy('rate_date', 'desc')
            ->first();

        return $rate ? (float) $rate->rate : 1.0;
    }

    /**
     * Get cash balance for a currency.
     *
     * @param int $currencyId
     * @return float
     */
    private function getCashBalance(int $currencyId): float
    {
        // This assumes cash accounts are marked with a specific type
        // Adjust the query based on your actual cash account structure
        $balance = DB::table('acc_head')
            ->where('currency_id', $currencyId)
            ->where('type', 'cash') // Adjust based on your schema
            ->sum('balance');

        return (float) $balance;
    }

    /**
     * Calculate gain/loss from exchange rate change.
     *
     * @param float $balance
     * @param float $currentRate
     * @param float $previousRate
     * @return float
     */
    private function calculateGainLoss(
        float $balance,
        float $currentRate,
        float $previousRate
    ): float {
        if ($previousRate == 0) {
            return 0;
        }

        // Gain/Loss = Balance * (Current Rate - Previous Rate)
        return $balance * ($currentRate - $previousRate);
    }

    /**
     * Get revaluation history.
     *
     * @param int $currencyId
     * @param int $days
     * @return array
     */
    public function getRevaluationHistory(int $currencyId, int $days = 30): array
    {
        $rates = ExchangeRate::where('currency_id', $currencyId)
            ->where('rate_date', '>=', now()->subDays($days))
            ->orderBy('rate_date', 'asc')
            ->get();

        $history = [];
        $previousRate = null;

        foreach ($rates as $rate) {
            if ($previousRate !== null) {
                $rateChange = $rate->rate - $previousRate;
                $history[] = [
                    'date' => $rate->rate_date->format('Y-m-d'),
                    'rate' => (float) $rate->rate,
                    'rate_change' => round($rateChange, 8),
                    'change_percentage' => round(($rateChange / $previousRate) * 100, 2),
                ];
            }

            $previousRate = (float) $rate->rate;
        }

        return $history;
    }
}
