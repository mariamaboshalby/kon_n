<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_paid',
        'requires_approval',
        'max_per_request_days',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'requires_approval' => 'boolean',
        'max_per_request_days' => 'integer',
    ];

    protected static function booted(): void
    {
        // Clear cache when leave type is created, updated, or deleted
        static::saved(function () {
            Cache::forget('leave_types_list');
        });

        static::deleted(function () {
            Cache::forget('leave_types_list');
        });
    }

    public function employeeLeaveBalances(): HasMany
    {
        return $this->hasMany(EmployeeLeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    // Helper methods
    public function isPaid(): bool
    {
        return $this->is_paid;
    }

    public function requiresApproval(): bool
    {
        return $this->requires_approval;
    }
}
