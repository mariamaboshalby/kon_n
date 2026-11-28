<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Modules\HR\Models\Covenant;
use Modules\HR\Models\Employee;

class CovenantService
{
    /**
     * @return Collection<int, Employee>
     */
    public function employeesForSelect(): Collection
    {
        return Employee::query()->select('id', 'name')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Covenant>
     */
    public function list(?string $search = null, ?int $employeeId = null, ?string $assignmentStatus = null): Collection
    {
        return Covenant::query()
            ->with(['employee:id,name', 'media'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('employee', fn ($emp) => $emp->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($assignmentStatus === 'assigned', fn ($q) => $q->whereNotNull('employee_id'))
            ->when($assignmentStatus === 'unassigned', fn ($q) => $q->whereNull('employee_id'))
            ->latest()
            ->get()
            ->each(function (Covenant $covenant) {
                $covenant->setAttribute('image_url', $covenant->image_url);
                $covenant->setAttribute('acknowledgment_url', $covenant->acknowledgment_url);
                $covenant->setAttribute('discharge_url', $covenant->discharge_url);
            });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $image = null, ?UploadedFile $acknowledgment = null, ?UploadedFile $discharge = null): Covenant
    {
        $covenant = Covenant::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? null,
            'handover_status' => $data['handover_status'] ?? null,
            'cost' => isset($data['cost']) && $data['cost'] !== '' ? $data['cost'] : null,
        ]);

        if ($image) {
            $this->storeMedia($covenant, $image, 'HR_Covenants');
        }

        if ($acknowledgment) {
            $this->storeMedia($covenant, $acknowledgment, 'HR_Covenant_Acknowledgment');
        }

        if ($discharge) {
            $this->storeMedia($covenant, $discharge, 'HR_Covenant_Discharge');
        }

        return $covenant->load(['employee', 'media']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Covenant $covenant, array $data, ?UploadedFile $image = null, ?UploadedFile $acknowledgment = null, ?UploadedFile $discharge = null): Covenant
    {
        $covenant->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? null,
            'handover_status' => $data['handover_status'] ?? null,
            'cost' => isset($data['cost']) && $data['cost'] !== '' ? $data['cost'] : null,
        ]);

        if ($image) {
            $covenant->clearMediaCollection('HR_Covenants');
            $this->storeMedia($covenant, $image, 'HR_Covenants');
        }

        if ($acknowledgment) {
            $covenant->clearMediaCollection('HR_Covenant_Acknowledgment');
            $this->storeMedia($covenant, $acknowledgment, 'HR_Covenant_Acknowledgment');
        }

        if ($discharge) {
            $covenant->clearMediaCollection('HR_Covenant_Discharge');
            $this->storeMedia($covenant, $discharge, 'HR_Covenant_Discharge');
        }

        return $covenant->fresh(['employee', 'media']);
    }

    public function delete(Covenant $covenant): void
    {
        $covenant->clearMediaCollection('HR_Covenants');
        $covenant->clearMediaCollection('HR_Covenant_Acknowledgment');
        $covenant->clearMediaCollection('HR_Covenant_Discharge');
        $covenant->delete();
    }

    public function assign(Covenant $covenant, ?int $employeeId): Covenant
    {
        $covenant->update(['employee_id' => $employeeId]);

        return $covenant->fresh(['employee', 'media']);
    }

    private function storeMedia(Covenant $covenant, UploadedFile $file, string $collection): void
    {
        $covenant->addMedia($file)
            ->usingName($file->getClientOriginalName())
            ->usingFileName($file->getClientOriginalName())
            ->toMediaCollection($collection);
    }
}
