<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherDenomination extends Model
{
    protected $table = 'voucher_denominations';

    protected $fillable = [
        'operhead_id',
        'denomination',
        'count',
    ];

    protected function casts(): array
    {
        return [
            'denomination' => 'integer',
            'count' => 'integer',
            'subtotal' => 'decimal:2',
        ];
    }

    /** الفئات المتاحة */
    public const ALLOWED_DENOMINATIONS = [5, 10, 20, 50, 100, 200];

    public function operHead(): BelongsTo
    {
        return $this->belongsTo(OperHead::class, 'operhead_id');
    }
}
