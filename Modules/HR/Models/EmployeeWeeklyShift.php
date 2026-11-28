<?php

declare(strict_types=1);

namespace Modules\HR\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Branches\Models\Branch;

class EmployeeWeeklyShift extends Model
{
    /** @var list<string> */
    public const WEEKDAYS = [
        'saturday',
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
    ];

    protected $table = 'employee_weekly_shifts';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::addGlobalScope(new BranchScope);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
