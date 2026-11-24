<?php

use App\Http\Controllers\DiscountController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VaribalController;
use App\Http\Controllers\VaribalValueController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/locale/{locale}', function (string $locale) {
    if (! in_array($locale, ['ar', 'en', 'ur', 'hi'], true)) {
        abort(404);
    }
    session(['locale' => $locale]);

    return back();
})->name('locale.switch');

// Admin Dashboard
use App\Http\Controllers\DashboardController;

Route::get('/admin/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('admin.dashboard');

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// Gradient Theme Demo (Development Only)
Route::get('/gradient-theme-demo', function () {
    return view('examples.gradient-theme-demo');
})->name('gradient-theme-demo');

Route::get('dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {

    Route::redirect('my-settings', 'my-settings/profile');
    Volt::route('my-settings/profile', 'my-settings.profile')->name('my-settings.profile');
    Volt::route('my-settings/password', 'my-settings.password')->name('my-settings.password');
    Volt::route('my-settings/appearance', 'my-settings.appearance')->name('my-settings.appearance');

    // Font Settings
    Route::get('settings/fonts', \App\Livewire\FontSettings::class)->name('settings.fonts');

    // 📁 Items & Units & Prices & Notes
    Route::resource('varibals', VaribalController::class)->names('varibals')->middleware('can:view varibals');
    Route::get('varibalValues/{varibalId?}', [VaribalValueController::class, 'index'])->name('varibalValues.index')->middleware('can:view varibalsValues');

    // Items routes (statistics and management)
    Route::get('items/statistics', [ItemController::class, 'getStatistics'])->name('items.statistics');
    Route::get('items/statistics/refresh', [ItemController::class, 'refresh'])->name('items.statistics.refresh');
    Route::get('items/manage-prices', [ItemController::class, 'managePrices'])->name('items.manage-prices');
    Route::get('items/print', [ItemController::class, 'printItems'])->name('items.print');
    Route::get('items/{id}/json', [ItemController::class, 'getItemJson'])->name('items.json');
    Route::resource('items', ItemController::class)->names('items')->only('index', 'show', 'create', 'edit');

    // Units, Prices, Notes
    Route::resource('units', UnitController::class)->names('units')->only('index');
    Route::resource('prices', PriceController::class)->names('prices')->only('index');
    Route::resource('notes', NoteController::class)->names('notes')->only('index');
    Route::get('notes/{id}', [NoteController::class, 'noteDetails'])->name('notes.noteDetails');

    // 📁 User Management & Monitoring
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/login-history', [App\Http\Controllers\UserMonitoringController::class, 'loginHistory'])->name('login-history');
        Route::get('/active-sessions', [App\Http\Controllers\UserMonitoringController::class, 'activeSessions'])->name('active-sessions');
        Route::post('/terminate-session/{sessionId}', [App\Http\Controllers\UserMonitoringController::class, 'terminateSession'])->name('terminate-session');
        Route::get('/activity-log', [App\Http\Controllers\UserMonitoringController::class, 'activityLog'])->name('activity-log');
    });
    Route::get('users/{user}/copy', [UserController::class, 'copy'])->name('users.copy')->middleware('can:create Users');
    Route::resource('users', UserController::class)->names('users');

    // 📁 Discounts
    Route::get('/discounts/general-statistics', [DiscountController::class, 'generalStatistics'])->name('discounts.general-statistics');
    Route::resource('discounts', DiscountController::class)->names('discounts');

    // 📁 Home
    Route::get('home', [HomeController::class, 'index'])->name('home.index');

    // 📁 Module Routes
    require __DIR__.'/modules/magicals.php';
});

require __DIR__.'/auth.php';

// Client Portal (public - no auth required)
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('login', [\App\Http\Controllers\ClientPortalController::class, 'login'])->name('login');
    Route::post('login', [\App\Http\Controllers\ClientPortalController::class, 'authenticate'])->name('authenticate');
    Route::get('statement', [\App\Http\Controllers\ClientPortalController::class, 'statement'])->name('statement');
    Route::get('operation/{opId}', [\App\Http\Controllers\ClientPortalController::class, 'operationDetails'])->name('operation.details');
    Route::post('logout', [\App\Http\Controllers\ClientPortalController::class, 'logout'])->name('logout');
});

// Test Manufacturing Validation
Route::get('/test-validation', function () {
    return view('test-manufacturing-validation');
});
