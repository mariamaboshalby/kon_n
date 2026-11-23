<?php

declare(strict_types=1);

namespace Modules\Estate\Models;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\HR\Models\Employee;

/**
 * PropertyStakeholder Model - نموذج أطراف العقار
 * 
 * Manages the many-to-many polymorphic relationship between properties
 * and stakeholders (clients, employees, users)
 */
class PropertyStakeholder extends Model
{
    protected $table = 'property_stakeholders';

    protected $fillable = [
        'property_id',
        'stakeholder_type',
        'stakeholder_id',
        'role',
        'notes',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Relationship: Belongs to Property
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Polymorphic relationship to stakeholder (Client, Employee, or User)
     */
    public function stakeholder(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if stakeholder is a client
     */
    public function isClient(): bool
    {
        return $this->stakeholder_type === Client::class;
    }

    /**
     * Check if stakeholder is an employee
     */
    public function isEmployee(): bool
    {
        return $this->stakeholder_type === Employee::class;
    }

    /**
     * Check if stakeholder is a user
     */
    public function isUser(): bool
    {
        return $this->stakeholder_type === User::class;
    }

    /**
     * Check if this is the primary stakeholder for the role
     */
    public function isPrimary(): bool
    {
        return $this->is_primary;
    }
}
