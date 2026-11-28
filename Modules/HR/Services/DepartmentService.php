<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;
use Modules\HR\Models\HRSetting;

class DepartmentService
{
    /**
     * @return Collection<int, Department>
     */
    public function list(?string $search = null): Collection
    {
        return Department::query()
            ->with(['parent:id,title', 'director:id,name', 'deputyDirector:id,name'])
            ->when($search, fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, Employee>
     */
    public function employeesForSelect(): Collection
    {
        return Employee::query()
            ->with('job:id,title')
            ->orderByDesc('id')
            ->get(['id', 'name', 'job_id']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Department
    {
        $this->assertLeavePercentage($data['max_leave_percentage'] ?? null);

        return Department::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Department $department, array $data): Department
    {
        $this->assertLeavePercentage($data['max_leave_percentage'] ?? null);
        $department->update($data);

        return $department->fresh(['parent', 'director', 'deputyDirector']);
    }

    public function delete(Department $department): void
    {
        $department->delete();
    }

    public function hierarchy(Department $department): Department
    {
        return $department->load(['parent', 'childrenRecursive']);
    }

    private function assertLeavePercentage(mixed $percentage): void
    {
        if ($percentage === null || $percentage === '' || (float) $percentage <= 0) {
            return;
        }

        $value = (float) $percentage;
        $companyMaxPercentage = HRSetting::getCompanyMaxLeavePercentage();

        if (is_null($companyMaxPercentage)) {
            throw ValidationException::withMessages([
                'max_leave_percentage' => [__('hr::hr.department_percentage_requires_company_percentage')],
            ]);
        }

        if ($value > $companyMaxPercentage) {
            throw ValidationException::withMessages([
                'max_leave_percentage' => [__('hr::hr.department_percentage_exceeds_company', [
                    'department_percentage' => number_format($value, 2),
                    'company_percentage' => number_format($companyMaxPercentage, 2),
                ])],
            ]);
        }
    }
}
