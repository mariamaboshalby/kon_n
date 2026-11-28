<?php

declare(strict_types=1);

namespace Modules\HR\Models;

use Modules\Branches\Models\Branch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\BranchScope;
use App\Models\User;

class Contract extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'trial_period' => 'integer',
    ];

    /**
     * Contract duration calculated from start and end dates (inclusive).
     */
    public function getContractDurationAttribute(): ?string
    {
        if (empty($this->contract_start_date) || empty($this->contract_end_date)) {
            return null;
        }

        $start = Carbon::parse($this->contract_start_date)->startOfDay();
        $end = Carbon::parse($this->contract_end_date)->startOfDay();

        if ($end->lessThan($start)) {
            return null;
        }

        $diff = $start->diff($end);
        $parts = [];

        if ($diff->y > 0) {
            $parts[] = $diff->y . ' ' . __($diff->y === 1 ? 'Year' : 'Years');
        }
        if ($diff->m > 0) {
            $parts[] = $diff->m . ' ' . __($diff->m === 1 ? 'Month' : 'Months');
        }
        if ($diff->d > 0 && empty($parts)) {
            $parts[] = ($diff->d + 1) . ' ' . __('Days');
        }

        return implode('، ', $parts) ?: ('1 ' . __('Days'));
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new BranchScope);
    }

    public function created_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contract_type(): BelongsTo
    {
        return $this->belongsTo(ContractType::class, 'contract_type_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(EmployeesJob::class, 'job_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contract_points(): HasMany
    {
        return $this->hasMany(ContractPoint::class);
    }

    public function salary_points(): HasMany
    {
        return $this->hasMany(SalaryPoint::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class);
    }

    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class, 'interview_id');
    }

    public function termination(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Termination::class);
    }
}
