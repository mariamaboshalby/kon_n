<?php

use Illuminate\Support\Facades\Route;
use Modules\Reports\Http\Controllers\salesReportController;
use Modules\Reports\Http\Controllers\PosReportController;

Route::middleware(['auth'])->group(function () {
    Route::get('reports/general-sales-items', [salesReportController::class, 'generalSalesItemsReport'])
        ->name('reports.general.sales.items');

    Route::get('/reports/general-sales-total', [salesReportController::class, 'generalSalesTotalReport'])
        ->name('reports.general-sales-total-report');

    Route::get('/reports/general-sales-report', [salesReportController::class, 'generalSalesReport'])
        ->name('reports.general-sales-report');

    Route::get('/reports/sales/comprehensive', [salesReportController::class, 'comprehensiveSalesReport'])
        ->name('reports.sales.comprehensive');

    Route::get('/reports/general-sales-report-by-address', [salesReportController::class, 'salesReportByAddress'])
        ->name('reports.general-sales-report-by-address');

    Route::get('/reports/sales/by-representative', [salesReportController::class, 'salesByRepresentativeReport'])
        ->name('reports.sales.representative');

    // تقرير المبيعات أصناف
    Route::get('/reports/sales/items', [salesReportController::class, 'generalSalesItemsReport'])
        ->name('reports.sales.items');

    // تقرير المبيعات إجماليات
    Route::get('/reports/sales/total', [salesReportController::class, 'generalSalesTotalReport'])
        ->name('reports.sales.total');

    // تقرير مبيعات صنف
    Route::get('/reports/item-sales', [salesReportController::class, 'manageItemSales'])
        ->name('reports.item-sales');

    // ── تقارير POS التحليلية ──────────────────────────────────────────────
    Route::get('/reports/pos/cashier-performance', [salesReportController::class, 'cashierPerformanceReport'])
        ->name('reports.pos.cashier-performance');

    Route::get('/reports/pos/restaurant-analytics', [salesReportController::class, 'restaurantAnalyticsReport'])
        ->name('reports.pos.restaurant-analytics');

    Route::get('/reports/pos/channel-comparison', [salesReportController::class, 'channelComparisonReport'])
        ->name('reports.pos.channel-comparison');

    Route::get('/reports/pos/slow-movers', [salesReportController::class, 'posSlowMoversReport'])
        ->name('reports.pos.slow-movers');

    Route::get('/reports/pos/discounts-returns', [salesReportController::class, 'discountsAndReturnsReport'])
        ->name('reports.pos.discounts-returns');

    Route::get('/reports/pos/peak-hours', [salesReportController::class, 'peakHoursReport'])
        ->name('reports.pos.peak-hours');

    Route::get('/reports/pos/peak-hours-calender', [PosReportController::class, 'peakHours'])
        ->name('reports.pos.peak-hours-calender');

    Route::get('/reports/pos/peak-hours-calender/day', [PosReportController::class, 'peakHoursDay'])
        ->name('reports.pos.peak-hours-calender-day');

});
