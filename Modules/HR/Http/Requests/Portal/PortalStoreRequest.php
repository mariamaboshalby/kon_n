<?php

declare(strict_types=1);

namespace Modules\HR\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Modules\HR\Models\EmployeeDocumentRequest;
use Modules\HR\Models\PortalRequest;
use Modules\HR\Services\Portal\PortalRequestService;

class PortalStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array((string) $this->route('type'), PortalRequest::TYPES, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $today = now()->toDateString();

        $base = ['client_request_id' => ['nullable', 'string', 'min:8', 'max:64']];

        $byType = match ((string) $this->route('type')) {
            PortalRequest::TYPE_LEAVE => [
                'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
                'start_date' => ['required', 'date_format:Y-m-d'],
                'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
                'reason' => ['nullable', 'string', 'max:1000'],
            ],
            PortalRequest::TYPE_PERMISSION => [
                'date' => ['required', 'date_format:Y-m-d'],
                'permission_type' => ['required', 'in:'.implode(',', PortalRequestService::PERMISSION_TYPES)],
                'from_time' => ['nullable', 'date_format:H:i'],
                'to_time' => ['nullable', 'date_format:H:i', 'after:from_time'],
                'reason' => ['required', 'string', 'max:1000'],
            ],
            PortalRequest::TYPE_ERRAND => [
                'title' => ['required', 'string', 'max:191'],
                'details' => ['nullable', 'string', 'max:2000'],
                'start_date' => ['required', 'date_format:Y-m-d'],
                'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            ],
            PortalRequest::TYPE_ADVANCE => [
                'amount' => ['required', 'numeric', 'min:1', 'max:99999999'],
                'reason' => ['required', 'string', 'max:255'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            PortalRequest::TYPE_ATTENDANCE_CORRECTION => [
                'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today, 'after_or_equal:'.now()->subDays(30)->toDateString()],
                'time' => ['required', 'date_format:H:i'],
                'attendance_type' => ['required', 'in:check_in,check_out'],
                'reason' => ['required', 'string', 'max:1000'],
            ],
            PortalRequest::TYPE_DOCUMENT => [
                'document_type' => ['required', 'in:'.implode(',', EmployeeDocumentRequest::DOCUMENT_TYPES)],
                'purpose' => ['nullable', 'string', 'max:255'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            default => [],
        };

        return array_merge($base, $byType);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $keys = [
            'leave_type_id', 'start_date', 'end_date', 'reason', 'date', 'permission_type', 'from_time',
            'to_time', 'title', 'details', 'amount', 'notes', 'time', 'attendance_type', 'document_type', 'purpose',
        ];

        $attributes = [];
        foreach ($keys as $key) {
            $attributes[$key] = __('hr::portal.field_'.$key);
        }

        return $attributes;
    }
}
