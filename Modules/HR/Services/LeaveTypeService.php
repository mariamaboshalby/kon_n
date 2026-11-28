<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\HR\Models\LeaveType;

class LeaveTypeService
{
    /**
     * @return Collection<int, LeaveType>
     */
    public function list(?string $search = null): Collection
    {
        return LeaveType::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): LeaveType
    {
        return LeaveType::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(LeaveType $leaveType, array $data): LeaveType
    {
        $leaveType->update($data);

        return $leaveType->fresh();
    }

    public function delete(LeaveType $leaveType): void
    {
        $leaveType->delete();
    }
}
