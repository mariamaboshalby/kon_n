<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\HR\Models\Kpi;

class KpiService
{
    /**
     * @return Collection<int, Kpi>
     */
    public function list(?string $search = null, ?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        return Kpi::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo,   fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Kpi
    {
        return Kpi::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Kpi $kpi, array $data): Kpi
    {
        $kpi->update($data);

        return $kpi->fresh();
    }

    public function delete(Kpi $kpi): void
    {
        $kpi->delete();
    }
}
