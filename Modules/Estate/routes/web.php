<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Estate\Http\Controllers\PropertyController;
use Modules\Estate\Http\Controllers\PropertyTypeController;
use Modules\Estate\Http\Controllers\EstateClientController;

Route::middleware(['auth', 'verified'])->prefix('estate')->group(function () {
    Route::get('dashboard', [\Modules\Estate\Http\Controllers\EstateDashboardController::class, 'index'])->name('estate.dashboard');
    Route::delete('properties/{property}/media/{media}', [\Modules\Estate\Http\Controllers\PropertyController::class, 'deleteMedia'])->name('properties.media.destroy');
    Route::resource('properties', PropertyController::class)->names('properties')->middleware('can:view Properties');
    Route::post('properties/search', [PropertyController::class, 'search'])->name('properties.search');

    Route::resource('property-types', PropertyTypeController::class)->names('property-types');

    Route::get('clients',                [EstateClientController::class, 'index'])->name('estate.clients.index');
    Route::get('clients/create',         [EstateClientController::class, 'createClient'])->name('estate.clients.create');
    Route::post('clients',               [EstateClientController::class, 'storeClient'])->name('estate.clients.store');
    Route::get('clients/{client}',       [EstateClientController::class, 'show'])->name('estate.clients.show');
    Route::get('contacts',               [EstateClientController::class, 'contacts'])->name('estate.contacts.index');
    Route::get('contacts/create',        [EstateClientController::class, 'createContact'])->name('estate.contacts.create');
    Route::get('leads',                  [EstateClientController::class, 'leads'])->name('estate.leads.index');
});
