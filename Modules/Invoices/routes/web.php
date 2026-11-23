<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Http\Controllers\InvoiceController;
use Modules\Invoices\Http\Controllers\InvoiceFormController;
use Modules\Invoices\Http\Controllers\InvoiceTemplateController;
use Modules\Invoices\Http\Controllers\InvoiceWorkflowController;
use Modules\Invoices\Http\Controllers\BarcodeLabelController;

// 1. الراوت العام (Public) - يوضع هنا لضمان عدم خضوعه لـ Middleware الـ auth
Route::get('/invoice/portal', [InvoiceController::class, 'showPortal'])->name('invoice.portal');
// 2. باقي الراوتات (تأكدي أن هذه المجموعة تبدأ بـ Route::middleware(['auth'])->group(function () { )
Route::middleware(['auth'])->group(function () {

    // Invoice Form Routes
    Route::get('/invoices/form/create', [InvoiceFormController::class, 'create'])->name('invoices.form.create');
    Route::get('/invoices/form/{invoiceId}/edit', [InvoiceFormController::class, 'edit'])->name('invoices.form.edit');

    // Workflow - يجب أن تكون قبل resource لتجنب التعارض مع {invoice} parameter
    Route::get('/invoices/requests', [InvoiceWorkflowController::class, 'index'])->name('invoices.requests.index');
    Route::get('/invoices/track/search', [InvoiceWorkflowController::class, 'search'])->name('invoices.track.search');
    Route::get('/invoices/track/{id}', [InvoiceWorkflowController::class, 'show'])->name('invoices.track');
    Route::post('/invoices/{id}/confirm', [InvoiceWorkflowController::class, 'confirm'])->name('invoices.confirm');

    // Invoice Templates
    Route::resource('invoice-templates', InvoiceTemplateController::class)->parameters([
        'invoice-templates' => 'template'
    ]);

    Route::get('invoice-templates/{template}/data', [InvoiceTemplateController::class, 'getTemplateData'])
        ->name('invoice-templates.data');

    // Invoice Resource
    Route::resource('invoices', InvoiceController::class)->names('invoices');

    Route::post('invoice-templates/{template}/toggle-active', [InvoiceTemplateController::class, 'toggleActive'])
        ->name('invoice-templates.toggle-active');

    // --- Send Portal & Notifications ---
    // الراوت الذي طلبته لإرسال الإيميل للعميل
    Route::post('/invoices/{id}/send-portal', [InvoiceController::class, 'sendPortalLink'])->name('invoice.send-portal');

    // --- Invoice Templates ---
    // Statistics
    Route::get('/sales/statistics', [InvoiceController::class, 'salesStatistics'])->name('sales.statistics');
    Route::get('/purchases/statistics', [InvoiceController::class, 'purchasesStatistics'])->name('purchases.statistics');
    Route::get('/inventory/statistics', [InvoiceController::class, 'inventoryStatistics'])->name('inventory.statistics');

    // Print & View
    Route::get('/invoice/print/{operation_id}', [InvoiceController::class, 'print'])->name('invoice.print');
    Route::get('invoice/view/{operationId}', [InvoiceController::class, 'view'])->name('invoice.view');

    // Send portal link
    Route::post('/invoices/{id}/send-portal', [InvoiceController::class, 'sendPortalLink'])->name('invoice.send-portal');

    // Barcode Label Designer
    Route::get('/barcode-label', [BarcodeLabelController::class, 'index'])->name('barcode-label.index');
    Route::get('/barcode-label/config', [BarcodeLabelController::class, 'getConfig'])->name('barcode-label.config');
    Route::post('/barcode-label/config', [BarcodeLabelController::class, 'saveConfig'])->name('barcode-label.save');
    Route::post('/barcode-label/upload-image', [BarcodeLabelController::class, 'uploadImage'])->name('barcode-label.upload-image');
});
