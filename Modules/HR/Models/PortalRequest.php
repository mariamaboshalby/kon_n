<?php

declare(strict_types=1);

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * غلاف موحّد لطلبات بوابة الموظف — السجل الفعلي يبقى في جدوله (leave_requests, work_permissions, ...).
 */
class PortalRequest extends Model
{
    public const TYPE_LEAVE = 'leave';

    public const TYPE_PERMISSION = 'permission';

    public const TYPE_ERRAND = 'errand';

    public const TYPE_ADVANCE = 'advance';

    public const TYPE_ATTENDANCE_CORRECTION = 'attendance_correction';

    public const TYPE_DOCUMENT = 'document';

    public const TYPES = [
        self::TYPE_LEAVE,
        self::TYPE_PERMISSION,
        self::TYPE_ERRAND,
        self::TYPE_ADVANCE,
        self::TYPE_ATTENDANCE_CORRECTION,
        self::TYPE_DOCUMENT,
    ];

    public const STAGE_MANAGER = 'manager';

    public const STAGE_HR = 'hr';

    public const STAGE_DONE = 'done';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'portal_requests';

    protected $guarded = ['id'];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_employee_id');
    }

    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actions(): HasMany
    {
        return $this->hasMany(PortalRequestAction::class)->orderBy('id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
