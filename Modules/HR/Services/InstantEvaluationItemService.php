<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\HR\Models\InstantEvaluationItem;

class InstantEvaluationItemService
{
    /**
     * @return Collection<int, InstantEvaluationItem>
     */
    public function list(?string $search = null, ?string $polarity = null): Collection
    {
        return InstantEvaluationItem::query()
            ->when($polarity, fn ($q) => $q->where('polarity', $polarity))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('polarity')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, InstantEvaluationItem>
     */
    public function activeByPolarity(string $polarity): Collection
    {
        return InstantEvaluationItem::query()
            ->where('polarity', $polarity)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): InstantEvaluationItem
    {
        return InstantEvaluationItem::query()->findOrFail($id);
    }

    /**
     * @param  array{name: string, description?: string|null, polarity: string, is_active?: bool, sort_order?: int}  $data
     */
    public function create(array $data): InstantEvaluationItem
    {
        return InstantEvaluationItem::create([
            'name' => trim($data['name']),
            'description' => isset($data['description']) ? (trim((string) $data['description']) ?: null) : null,
            'polarity' => $data['polarity'],
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    /**
     * @param  array{name: string, description?: string|null, polarity: string, is_active?: bool, sort_order?: int}  $data
     */
    public function update(InstantEvaluationItem $item, array $data): InstantEvaluationItem
    {
        $item->update([
            'name' => trim($data['name']),
            'description' => isset($data['description']) ? (trim((string) $data['description']) ?: null) : null,
            'polarity' => $data['polarity'],
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $item->is_active,
            'sort_order' => (int) ($data['sort_order'] ?? $item->sort_order),
        ]);

        return $item->fresh();
    }

    public function delete(InstantEvaluationItem $item): void
    {
        $item->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(InstantEvaluationItem $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'polarity' => $item->polarity,
            'is_active' => (bool) $item->is_active,
            'sort_order' => (int) $item->sort_order,
        ];
    }
}
