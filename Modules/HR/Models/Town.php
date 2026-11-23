<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Town extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // Clear cache when town is saved or deleted
        static::saved(function () {
            Cache::forget('towns_list');
        });

        static::deleted(function () {
            Cache::forget('towns_list');
        });
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
