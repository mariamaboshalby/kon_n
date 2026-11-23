<?php

declare(strict_types=1);

namespace Modules\POS\app\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\POS\app\Events\TransactionSaved;
use Modules\POS\app\Jobs\PrintKitchenOrderJob;
use Modules\POS\app\Models\PrintJob;

class PrintOrderListener
{
    public function handle(TransactionSaved $event): void
    {
        try {
            // Outbox Pattern: جلب PrintJob records المُنشأة مسبقاً داخل الـ transaction
            $pendingJobs = PrintJob::where('transaction_id', $event->transaction->id)
                ->where('status', 'queued')
                ->with('printerStation')
                ->get();

            foreach ($pendingJobs as $printJob) {
                PrintKitchenOrderJob::dispatch($printJob);
            }
        } catch (\Exception $e) {
            Log::error('Failed to queue kitchen print jobs', [
                'transaction_id' => $event->transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
