<?php

declare(strict_types=1);

namespace Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkAttendanceActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && ($user->can('edit Attendances') || $user->can('delete Attendances'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:2000'],
            'ids.*' => ['required', 'integer', 'min:1'],
            'action' => ['required', 'string', 'in:approve,reject,delete'],
        ];
    }

    /**
     * @return list<int>
     */
    public function getIds(): array
    {
        $ids = $this->validated('ids') ?? [];

        return array_values(array_unique(array_map('intval', $ids)));
    }
}
