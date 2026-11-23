<?php

declare(strict_types=1);

namespace Modules\CRM\Models;

use App\Traits\WithSorting;
use Illuminate\Database\Eloquent\Model;

class ReturnType extends Model
{
    use WithSorting;

    protected $table = 'crm_return_types';

    protected $fillable = ['title'];
}
