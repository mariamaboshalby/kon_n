<?php

namespace Modules\HR\Models;

use App\Models\Scopes\BranchScope;
use Carbon\Carbon;
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

    /**
     * Returns true when the shift crosses midnight (end_time < start_time).
     *
     * Examples:
     *   08:00 → 16:00  →  false  (same day)
     *   17:00 → 01:00  →  true   (next day)
     *   22:00 → 06:00  →  true   (next day)
     */
    public function isOvernightShift(): bool
    {
        if (! $this->start_time || ! $this->end_time) {
            return false;
        }

        // Compare as time-only values on a fixed reference date so Carbon doesn't
        // introduce today's date and accidentally flip the result near midnight.
        $start = Carbon::parse('2000-01-01 '.$this->start_time);
        $end   = Carbon::parse('2000-01-01 '.$this->end_time);

        return $end->lt($start);
    }

    /**
     * Given a reference date (the shift's START date), return the Carbon datetime
     * for the shift's official end, properly adjusted to the next day when overnight.
     */
    public function getShiftEndDatetime(Carbon $shiftStartDate): Carbon
    {
        $end = $shiftStartDate->copy()->setTimeFromTimeString($this->end_time);

        if ($this->isOvernightShift()) {
            $end->addDay();
        }

        return $end;
    }
}
