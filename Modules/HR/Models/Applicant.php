<?php

declare(strict_types=1);

namespace Modules\HR\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Modules\Branches\Models\Branch;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Applicant extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = ['id'];

    protected $table = 'applicants';

    protected static function booted(): void
    {
        static::addGlobalScope(new BranchScope);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('applicant_cvs')
            ->useDisk('public')
            ->singleFile();
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function jobPosting(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }
}
