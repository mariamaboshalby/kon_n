<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers\Api\Portal;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HR\Http\Requests\Portal\PortalAttendanceRequest;
use Modules\HR\Services\Portal\PortalAttendanceService;

class PortalAttendanceController extends PortalBaseController
{
    public function __construct(private readonly PortalAttendanceService $attendance)
    {
    }

    public function today(Request $request): JsonResponse
    {
        return response()->json(array_merge(
            ['success' => true],
            $this->attendance->today($this->employee($request))
        ));
    }

    public function store(PortalAttendanceRequest $request): JsonResponse
    {
        $result = $this->attendance->record(
            $this->employee($request),
            $this->device($request),
            $request->validated(),
            $request->file('selfie')
        );

        $record = $this->attendance->serialize($result['attendance']);

        return response()->json([
            'success' => true,
            'duplicate' => $result['duplicate'],
            'message' => $record['type'] === 'check_in'
                ? __('hr::portal.check_in_success', ['time' => $record['time']])
                : __('hr::portal.check_out_success', ['time' => $record['time']]),
            'data' => $record,
        ], $result['duplicate'] ? 200 : 201);
    }

    public function history(Request $request): JsonResponse
    {
        $data = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json(array_merge(
            ['success' => true],
            $this->attendance->history(
                $this->employee($request),
                (string) ($data['month'] ?? now()->format('Y-m')),
                (int) ($data['page'] ?? 1)
            )
        ));
    }
}
