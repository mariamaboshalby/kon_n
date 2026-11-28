<?php

declare(strict_types=1);

namespace Modules\HR\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeInstantEvaluation extends Model
{
    protected $table = 'employee_instant_evaluations';

    protected $fillable = [
        'employee_id',
        'evaluator_user_id',
        'polarity',
        'item_id',
        'score',
        'note',
        'evaluated_at',
        'client_request_id',
    ];

    protected $casts = [
        'score' => 'integer',
        'evaluated_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_user_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InstantEvaluationItem::class, 'item_id');
    }
}
