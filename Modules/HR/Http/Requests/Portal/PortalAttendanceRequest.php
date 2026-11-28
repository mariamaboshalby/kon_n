<?php

declare(strict_types=1);

namespace Modules\HR\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class PortalAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_mock')) {
            $this->merge(['is_mock' => filter_var($this->input('is_mock'), FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) config('hr.portal.max_attachment_kb', 5120);

        return [
            'client_request_id' => ['required', 'string', 'min:8', 'max:64'],
            'type' => ['nullable', 'in:check_in,check_out'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'is_mock' => ['nullable', 'boolean'],
            'project_id' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:250'],
            'selfie' => ['nullable', 'image', 'max:'.$maxKb],
        ];
    }
}
