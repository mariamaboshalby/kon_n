<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Estate\Http\Controllers\Api\PropertyApiController;

// Public read-only endpoints - no auth required
Route::prefix('estate')->group(function () {
    Route::get('properties', [PropertyApiController::class, 'index'])->name('api.properties.index');
    Route::get('properties/statistics', [PropertyApiController::class, 'statistics'])->name('api.properties.statistics');
    Route::get('properties/{id}', [PropertyApiController::class, 'show'])->name('api.properties.show');
});

// Protected write endpoints - require Sanctum token
Route::middleware(['auth:sanctum'])->prefix('estate')->group(function () {
    Route::post('properties', [PropertyApiController::class, 'store'])->name('api.properties.store');
    Route::put('properties/{id}', [PropertyApiController::class, 'update'])->name('api.properties.update');
    Route::delete('properties/{id}', [PropertyApiController::class, 'destroy'])->name('api.properties.destroy');
});
