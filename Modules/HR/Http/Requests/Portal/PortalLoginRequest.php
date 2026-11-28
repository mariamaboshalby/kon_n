<?php

declare(strict_types=1);

namespace Modules\HR\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class PortalLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:100'],
            'device' => ['required', 'array'],
            'device.uuid' => ['required', 'string', 'min:8', 'max:100'],
            'device.platform' => ['nullable', 'string', 'in:android,ios,web'],
            'device.model' => ['nullable', 'string', 'max:120'],
            'device.os_version' => ['nullable', 'string', 'max:40'],
            'device.app_version' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'login' => __('hr::portal.field_login'),
            'password' => __('hr::portal.field_password'),
        ];
    }
}
