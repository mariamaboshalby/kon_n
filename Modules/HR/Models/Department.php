<?php

namespace Modules\HR\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Modules\Branches\Models\Branch;

class Department extends Model
{
    protected $table = 'departments';

    protected $guarded = ['id'];

    protected $casts = [
        'max_leave_percentage' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BranchScope);

        // Clear cache when department is saved or deleted
        static::saved(function () {
            Cache::forget('departments_list');
        });

        static::deleted(function () {
            Cache::forget('departments_list');
        });
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function director(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'director_id');
    }

    public function deputyDirector(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'deputy_director_id');
    }
}
