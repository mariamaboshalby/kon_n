<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\HR\Models\Employee;
use Modules\HR\Models\WorkPermission;

class WorkPermissionService
{
    /**
     * @return Collection<int, Employee>
     */
    public function employeesForSelect(): Collection
    {
        return Employee::query()->select('id', 'name')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, WorkPermission>
     */
    public function list(
        ?string $search = null,
        ?int $employeeId = null,
        ?string $status = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): Collection {
        return WorkPermission::query()
            ->with(['employee:id,name', 'created_by:id,name', 'approved_by:id,name'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('employee', fn ($emp) => $emp->where('name', 'like', "%{$search}%"));
            })
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($startDate, fn ($q) => $q->where('date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('date', '<=', $endDate))
            ->latest()
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): WorkPermission
    {
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? 'pending';

        $workPermission = WorkPermission::create($data);

        return $workPermission->fresh(['employee', 'created_by', 'approved_by']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(WorkPermission $workPermission, array $data): WorkPermission
    {
        $data['updated_by'] = Auth::id();
        $workPermission->update($data);

        return $workPermission->fresh(['employee', 'created_by', 'approved_by', 'updated_by']);
    }

    public function delete(WorkPermission $workPermission): void
    {
        $workPermission->delete();
    }

    public function approve(WorkPermission $workPermission): WorkPermission
    {
        $workPermission->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $workPermission->fresh(['employee', 'created_by', 'approved_by']);
    }

    public function reject(WorkPermission $workPermission): WorkPermission
    {
        $workPermission->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $workPermission->fresh(['employee', 'created_by', 'approved_by']);
    }
}
