<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\HR\Models\Shift;

class ShiftService
{
    /**
     * @return Collection<int, Shift>
     */
    public function list(?string $search = null): Collection
    {
        $items = Shift::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->get();

        foreach ($items as $shift) {
            if (is_string($shift->days)) {
                $decoded = json_decode($shift->days, true);
                $shift->setAttribute('days', is_array($decoded) ? $decoded : []);
            }
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Shift
    {
        $data['days'] = json_encode(array_values($data['days'] ?? []));

        return Shift::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Shift $shift, array $data): Shift
    {
        $data['days'] = json_encode(array_values($data['days'] ?? []));
        $shift->update($data);

        return $shift->fresh();
    }

    public function delete(Shift $shift): void
    {
        $shift->delete();
    }
}
