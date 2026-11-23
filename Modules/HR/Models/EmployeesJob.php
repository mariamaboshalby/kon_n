<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class EmployeesJob extends Model
{
    protected $table = 'employees_jobs';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // Clear cache when job is saved or deleted
        static::saved(function () {
            Cache::forget('jobs_list');
        });

        static::deleted(function () {
            Cache::forget('jobs_list');
        });
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
