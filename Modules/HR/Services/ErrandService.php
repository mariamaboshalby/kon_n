<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Errand;

class ErrandService
{
    /**
     * @return Collection<int, Employee>
     */
    public function employeesForSelect(): Collection
    {
        return Employee::query()->select('id', 'name')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Errand>
     */
    public function list(
        ?string $search = null,
        ?int $employeeId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): Collection {
        return Errand::query()
            ->with(['employee:id,name', 'created_by:id,name', 'approved_by:id,name'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('details', 'like', "%{$search}%")
                        ->orWhereHas('employee', fn ($emp) => $emp->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($startDate, fn ($q) => $q->where('start_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('end_date', '<=', $endDate))
            ->latest()
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Errand
    {
        $data['created_by'] = Auth::id();

        return Errand::create($data)->load(['employee', 'created_by', 'approved_by']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Errand $errand, array $data): Errand
    {
        $data['updated_by'] = Auth::id();
        $errand->update($data);

        return $errand->fresh(['employee', 'created_by', 'approved_by', 'updated_by']);
    }

    public function delete(Errand $errand): void
    {
        $errand->delete();
    }

    public function approve(Errand $errand): Errand
    {
        $errand->update([
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $errand->fresh(['employee', 'created_by', 'approved_by']);
    }
}
