<?php

namespace Modules\HR\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\Branches\Models\Branch;

class Shift extends Model
{
    protected $table = 'shifts';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::addGlobalScope(new BranchScope);

        // Clear cache when shift is saved or deleted
        static::saved(function () {
            Cache::forget('shifts_list');
        });

        static::deleted(function () {
            Cache::forget('shifts_list');
        });
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
