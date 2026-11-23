<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class City extends Model
{
    protected $table = 'cities';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // Clear cache when city is saved or deleted
        static::saved(function () {
            Cache::forget('cities_list');
        });

        static::deleted(function () {
            Cache::forget('cities_list');
        });
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function towns()
    {
        return $this->hasMany(Town::class);
    }
}
