<?php

declare(strict_types=1);

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstantEvaluationItem extends Model
{
    public const POLARITY_POSITIVE = 'positive';

    public const POLARITY_NEGATIVE = 'negative';

    protected $table = 'instant_evaluation_items';

    protected $fillable = [
        'name',
        'description',
        'polarity',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function evaluations(): HasMany
    {
        return $this->hasMany(EmployeeInstantEvaluation::class, 'item_id');
    }

    public function isPositive(): bool
    {
        return $this->polarity === self::POLARITY_POSITIVE;
    }

    public function isNegative(): bool
    {
        return $this->polarity === self::POLARITY_NEGATIVE;
    }
}
