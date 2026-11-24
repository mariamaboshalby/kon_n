<?php

declare(strict_types=1);

namespace Modules\Authorization\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use LogsActivity;

    public const INQUIRY_RELATED_CATEGORIES = [
        'CRM',
        'HR',
        'Inquiries',
        'inquiries_management',
        'Progress',
        'Projects',
        'permissions',
        'Settings',
        'Users',
    ];

    protected $fillable = ['name', 'guard_name', 'category', 'option_type', 'description'];

    public function scopeRelevantToInquiries(Builder $query): Builder
    {
        return $query->whereIn('category', self::INQUIRY_RELATED_CATEGORIES);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'category', 'description'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "تم {$eventName} الصلاحية");
    }
}
