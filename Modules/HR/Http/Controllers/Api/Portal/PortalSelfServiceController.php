<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers\Api\Portal;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HR\Services\InstantEvaluationService;
use Modules\HR\Services\Portal\PortalException;
use Modules\HR\Services\Portal\PortalSelfService;
use Modules\HR\Services\Portal\PortalSettings;

class PortalSelfServiceController extends PortalBaseController
{
    public function __construct(
        private readonly PortalSelfService $selfService,
        private readonly InstantEvaluationService $instantEvaluations,
    ) {
    }

    public function balances(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->selfService->leaveBalances($this->employee($request)),
        ]);
    }

    public function finance(Request $request): JsonResponse
    {
        $year = (int) ($request->validate(['year' => ['nullable', 'integer', 'min:2000', 'max:2100']])['year'] ?? 0)
            ?: (int) now()->year;

        return response()->json(array_merge(
            ['success' => true],
            $this->selfService->finance($this->employee($request), $year)
        ));
    }

    public function payslips(Request $request): JsonResponse
    {
        if (! PortalSettings::current()->showPayslip()) {
            throw new PortalException(__('hr::portal.payslip_disabled'), 'payslip_disabled', 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->selfService->payslips($this->employee($request)),
        ]);
    }

    public function covenants(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->selfService->covenants($this->employee($request)),
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $page = (int) ($request->validate(['page' => ['nullable', 'integer', 'min:1']])['page'] ?? 1);

        return response()->json(array_merge(
            ['success' => true],
            $this->selfService->notifications($this->employee($request), $page)
        ));
    }

    public function markNotificationsRead(Request $request): JsonResponse
    {
        $id = $request->validate(['id' => ['nullable', 'integer', 'min:1']])['id'] ?? null;

        $this->selfService->markRead($this->employee($request), $id !== null ? (int) $id : null);

        return response()->json([
            'success' => true,
            'unread' => $this->selfService->unreadCount($this->employee($request)),
        ]);
    }

    public function instantEvaluations(Request $request): JsonResponse
    {
        $payload = $this->instantEvaluations->forEmployee((int) $this->employee($request)->id, 50);

        return response()->json([
            'success' => true,
            'data' => $payload['items'],
            'average_score' => $payload['average_score'],
            'total' => $payload['total'],
        ]);
    }
}
