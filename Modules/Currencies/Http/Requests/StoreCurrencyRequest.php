<?php

declare(strict_types=1);

namespace Modules\Currencies\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCurrencyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:currencies,name'],
            'code' => ['required', 'string', 'size:3', 'uppercase', 'unique:currencies,code'],
            'symbol' => ['nullable', 'string', 'max:10'],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:8'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'rate_mode' => ['required', 'in:automatic,manual'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('currencies.name_required'),
            'name.unique' => __('currencies.name_unique'),
            'code.required' => __('currencies.code_required'),
            'code.size' => __('currencies.code_size'),
            'code.unique' => __('currencies.code_unique'),
            'decimal_places.required' => __('currencies.decimal_places_required'),
            'rate_mode.required' => __('currencies.rate_mode_required'),
        ];
    }
}
