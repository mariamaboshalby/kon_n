<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers\Api\Portal;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\HR\Http\Requests\Portal\PortalStoreRequest;
use Modules\HR\Models\EmployeeDocumentRequest;
use Modules\HR\Models\PortalRequest;
use Modules\HR\Services\LeaveRequestService;
use Modules\HR\Services\Portal\PortalException;
use Modules\HR\Services\Portal\PortalRequestService;
use Modules\HR\Services\Portal\PortalSelfService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalRequestController extends PortalBaseController
{
    public function __construct(
        private readonly PortalRequestService $requests,
        private readonly PortalSelfService $selfService,
        private readonly LeaveRequestService $leaveRequestService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected,cancelled'],
            'type' => ['nullable', 'in:'.implode(',', PortalRequest::TYPES)],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json(array_merge(
            ['success' => true],
            $this->requests->listForEmployee($this->employee($request), $data, (int) ($data['page'] ?? 1))
        ));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $portalRequest = $this->ownRequest($request, $id);

        return response()->json([
            'success' => true,
            'data' => $this->requests->detail($portalRequest),
        ]);
    }

    public function store(PortalStoreRequest $request, string $type): JsonResponse
    {
        $data = $request->validated();

        $portalRequest = $this->requests->create(
            $this->employee($request),
            $type,
            $data,
            $data['client_request_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => __('hr::portal.request_submitted'),
            'data' => $this->requests->serialize($portalRequest),
        ], 201);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $portalRequest = $this->requests->cancel($this->employee($request), $this->ownRequest($request, $id));

        return response()->json([
            'success' => true,
            'message' => __('hr::portal.request_cancelled'),
            'data' => $this->requests->serialize($portalRequest),
        ]);
    }

    public function document(Request $request, int $id): StreamedResponse
    {
        $portalRequest = $this->ownRequest($request, $id);
        $source = $portalRequest->requestable;

        if (! $source instanceof EmployeeDocumentRequest || ! $source->hasFile() || $source->status !== 'approved'
            || ! Storage::disk('local')->exists((string) $source->file_path)) {
            throw new PortalException(__('hr::portal.document_not_available'), 'document_not_available', 404);
        }

        return Storage::disk('local')->download((string) $source->file_path, $source->file_name ?: basename((string) $source->file_path));
    }

    public function leaveTypes(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'types' => $this->selfService->leaveTypes(),
            'balances' => $this->selfService->leaveBalances($this->employee($request)),
        ]);
    }

    public function leavePreview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'leave_type_id' => ['required', 'integer'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->leaveRequestService->preview(array_merge($data, [
                'employee_id' => $this->employee($request)->id,
            ])),
        ]);
    }

    private function ownRequest(Request $request, int $id): PortalRequest
    {
        $portalRequest = PortalRequest::query()
            ->where('employee_id', $this->employee($request)->id)
            ->find($id);

        if (! $portalRequest) {
            throw new PortalException(__('hr::portal.request_not_found'), 'not_found', 404);
        }

        return $portalRequest;
    }
}
