<?php

declare(strict_types=1);

namespace Modules\Estate\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyType extends Model
{
    protected $table = 'property_types';

    protected $fillable = [
        'parent_id',
        'name_ar',
        'name_en',
        'slug',
        'icon',
        'attributes',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function activeChildren(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'property_type_id');
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function isLeaf(): bool
    {
        return $this->children()->doesntExist();
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLeaves(Builder $query): Builder
    {
        return $query->whereDoesntHave('children');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Get all available attribute keys for this type (merged with parent).
     *
     * @return array<string>
     */
    public function getAvailableAttributes(): array
    {
        $attrs = $this->attributes['attributes'] ?? [];

        if ($this->parent) {
            $attrs = array_merge($this->parent->getAvailableAttributes(), $attrs);
        }

        return array_unique($attrs);
    }

    /**
     * Build a flat tree for select options.
     *
     * @return Collection<int, self>
     */
    public static function flatTree(): Collection
    {
        $all = self::with('children')->active()->roots()->orderBy('sort_order')->get();

        $flat = new Collection;
        foreach ($all as $root) {
            $flat->push($root);
            foreach ($root->activeChildren as $child) {
                $flat->push($child);
            }
        }

        return $flat;
    }
}
