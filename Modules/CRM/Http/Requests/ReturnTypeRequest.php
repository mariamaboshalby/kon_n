<?php

declare(strict_types=1);

namespace Modules\CRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReturnTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255']];
    }

    public function attributes(): array
    {
        return ['title' => __('crm::crm.title')];
    }

    public function authorize(): bool
    {
        return true;
    }
}
