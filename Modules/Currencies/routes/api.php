<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Currencies\Http\Controllers\Api\CurrencyConversionController;
use Modules\Currencies\Http\Controllers\Api\CashBoxRevaluationController;

Route::middleware(['auth:sanctum'])->prefix('currencies')->group(function () {
    // Currency Conversion
    Route::post('convert', [CurrencyConversionController::class, 'convert'])->name('currencies.convert');
    Route::get('rate', [CurrencyConversionController::class, 'getRate'])->name('currencies.get-rate');
    Route::get('active', [CurrencyConversionController::class, 'getActiveCurrencies'])->name('currencies.active');
    Route::post('rate/update', [CurrencyConversionController::class, 'updateRate'])->name('currencies.update-rate');
    Route::get('{currency}/rate-history', [CurrencyConversionController::class, 'getRateHistory'])->name('currencies.rate-history');

    // Cash Box Revaluation
    Route::post('revalue', [CashBoxRevaluationController::class, 'revalue'])->name('currencies.revalue');
    Route::get('{currency}/revaluation-history', [CashBoxRevaluationController::class, 'history'])->name('currencies.revaluation-history');
});
