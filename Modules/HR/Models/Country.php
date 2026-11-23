<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Country extends Model
{
    protected $table = 'countries';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // Clear cache when country is saved or deleted
        static::saved(function () {
            Cache::forget('countries_list');
        });

        static::deleted(function () {
            Cache::forget('countries_list');
        });
    }

    public function states()
    {
        return $this->hasMany(State::class);
    }
}
