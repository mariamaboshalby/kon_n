<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class Kpi extends Model
{
    protected $table = 'kpis';

    protected $fillable = ['name', 'description'];

    protected static function booted(): void
    {
        // Clear cache when KPI is saved or deleted
        static::saved(function () {
            Cache::forget('kpis_list');
        });

        static::deleted(function () {
            Cache::forget('kpis_list');
        });
    }

    public function evaluations(): BelongsToMany
    {
        return $this->belongsToMany(Employee_Evaluation::class, 'employeeEvaluation_kpis', 'kpi_id', 'employee_evaluation_id')->withPivot('score', 'notes');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_kpis', 'kpi_id', 'employee_id')->withPivot('weight_percentage');
    }
}
