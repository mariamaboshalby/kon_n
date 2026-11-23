<?php

namespace App\Models;

use Modules\Branches\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Modules\Accounts\Models\AccHead;

class JournalDetail extends Model
{
    protected $table = 'journal_details';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = [
        'crtime'         => 'datetime',
        'debit'          => 'decimal:4',
        'credit'         => 'decimal:4',
        'foreign_amount' => 'decimal:4',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new \App\Models\Scopes\BranchScope);
    }

    public function accHead()
    {
        return $this->belongsTo(AccHead::class, 'account_id');
    }
    public function head()
    {
        return $this->belongsTo(JournalHead::class, 'journal_id', 'journal_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center');
    }

    public function operHead()
    {
        return $this->belongsTo(OperHead::class, 'op_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * العملة الأجنبية المرتبطة بهذا القيد
     */
    public function foreignCurrency()
    {
        return $this->belongsTo(\Modules\Settings\Models\Currency::class, 'foreign_currency_id');
    }

    /**
     * هل هذا القيد بعملة أجنبية؟
     */
    public function hasForeignCurrency(): bool
    {
        return ! is_null($this->foreign_currency_id) && ! is_null($this->foreign_amount);
    }
}
