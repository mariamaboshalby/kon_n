<?php

declare(strict_types=1);

namespace Modules\HR\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocumentRequest extends Model
{
    public const DOCUMENT_TYPES = [
        'salary_certificate',
        'experience_certificate',
        'employment_letter',
        'other',
    ];

    protected $table = 'employee_document_requests';

    protected $guarded = ['id'];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    protected $hidden = ['file_path'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function hasFile(): bool
    {
        return $this->file_path !== null && $this->file_path !== '';
    }
}
