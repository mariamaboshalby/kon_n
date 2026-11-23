<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class State extends Model
{
    protected $table = 'states';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // Clear cache when state is saved or deleted
        static::saved(function () {
            Cache::forget('states_list');
        });

        static::deleted(function () {
            Cache::forget('states_list');
        });
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function cities()
    {
        return $this->hasMany(City::class);
    }
}
