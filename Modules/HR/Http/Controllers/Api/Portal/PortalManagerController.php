<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers\Api\Portal;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HR\Models\PortalRequest;
use Modules\HR\Services\Portal\PortalException;
use Modules\HR\Services\Portal\PortalRequestService;
use Modules\HR\Services\Portal\PortalRequestWorkflow;
use Modules\HR\Services\Portal\PortalSelfService;

class PortalManagerController extends PortalBaseController
{
    public function __construct(
        private readonly PortalSelfService $selfService,
        private readonly PortalRequestService $requests,
        private readonly PortalRequestWorkflow $workflow
    ) {
    }

    public function teamToday(Request $request): JsonResponse
    {
        return response()->json(array_merge(
            ['success' => true],
            $this->selfService->teamToday($this->employee($request))
        ));
    }

    public function teamRequests(Request $request): JsonResponse
    {
        $page = (int) ($request->validate(['page' => ['nullable', 'integer', 'min:1']])['page'] ?? 1);

        return response()->json(array_merge(
            ['success' => true],
            $this->requests->listForManager($this->employee($request), $page)
        ));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->requests->detail($this->teamRequest($request, $id), true),
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $note = $request->validate(['note' => ['nullable', 'string', 'max:1000']])['note'] ?? null;

        $portalRequest = $this->workflow->managerDecide($this->employee($request), $this->teamRequest($request, $id), true, $note);

        return response()->json([
            'success' => true,
            'message' => __('hr::portal.manager_approved'),
            'data' => $this->requests->serialize($portalRequest, true),
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $note = $request->validate(['note' => ['required', 'string', 'max:1000']], [], [
            'note' => __('hr::portal.field_reject_reason'),
        ])['note'];

        $portalRequest = $this->workflow->managerDecide($this->employee($request), $this->teamRequest($request, $id), false, $note);

        return response()->json([
            'success' => true,
            'message' => __('hr::portal.manager_rejected'),
            'data' => $this->requests->serialize($portalRequest, true),
        ]);
    }

    private function teamRequest(Request $request, int $id): PortalRequest
    {
        $portalRequest = PortalRequest::query()
            ->where('manager_employee_id', $this->employee($request)->id)
            ->find($id);

        if (! $portalRequest) {
            throw new PortalException(__('hr::portal.request_not_found'), 'not_found', 404);
        }

        return $portalRequest;
    }
}
