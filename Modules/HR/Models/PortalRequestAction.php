<?php

declare(strict_types=1);

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalRequestAction extends Model
{
    public const ACTOR_EMPLOYEE = 'employee';

    public const ACTOR_USER = 'user';

    public const UPDATED_AT = null;

    protected $table = 'portal_request_actions';

    protected $guarded = ['id'];

    public function portalRequest(): BelongsTo
    {
        return $this->belongsTo(PortalRequest::class);
    }
}
