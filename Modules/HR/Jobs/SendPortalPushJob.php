<?php

declare(strict_types=1);

namespace Modules\HR\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\HR\Services\Portal\FcmPushService;

/**
 * Tenant-aware عبر QueueTenancyBootstrapper — يُرسل من داخل سياق الـ tenant.
 */
class SendPortalPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 30;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public int $employeeId,
        public string $title,
        public string $body,
        public array $data = []
    ) {
    }

    public function handle(FcmPushService $fcm): void
    {
        $fcm->sendToEmployee($this->employeeId, $this->title, $this->body, $this->data);
    }
}
