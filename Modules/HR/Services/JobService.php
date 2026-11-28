<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\HR\Models\EmployeesJob;

class JobService
{
    /**
     * @return Collection<int, EmployeesJob>
     */
    public function list(?string $search = null, ?string $level = null): Collection
    {
        return EmployeesJob::query()
            ->when($search, fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->when($level, fn ($q) => $q->where('level', $level))
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array{title: string, description?: string|null, level?: string|null}  $data
     */
    public function create(array $data): EmployeesJob
    {
        return EmployeesJob::create($data);
    }

    /**
     * @param  array{title: string, description?: string|null, level?: string|null}  $data
     */
    public function update(EmployeesJob $job, array $data): EmployeesJob
    {
        $job->update($data);

        return $job->fresh();
    }

    public function delete(EmployeesJob $job): void
    {
        $job->delete();
    }
}
