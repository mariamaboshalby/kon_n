<?php

declare(strict_types=1);

namespace Modules\Fleet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'phone'         => ['required', 'string', 'max:20'],
            'vehicle_type'  => ['nullable', 'string', 'max:100'],
            'license_number'=> ['nullable', 'string', 'max:100'],
            'is_available'  => ['nullable', 'boolean'],
            'notes'         => ['nullable', 'string'],
            'branch_id'     => ['nullable', 'exists:branches,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => __('fleet::fleet.validation.driver_name_required'),
            'phone.required' => __('fleet::fleet.validation.driver_phone_required'),
        ];
    }
}
