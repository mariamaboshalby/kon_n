<?php

declare(strict_types=1);

namespace Modules\HR\Models;

use Modules\HR\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Covenant extends Model implements HasMedia
{
    // عهده عمل
    use InteractsWithMedia;

    protected $guarded = ['id'];
    protected $table = 'covenants';

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }


    // registerMediaConversions
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('HR_Covenants')
            ->useDisk('public')
            ->singleFile();

        $this->addMediaCollection('HR_Covenant_Acknowledgment')
            ->useDisk('public')
            ->singleFile();

        $this->addMediaCollection('HR_Covenant_Discharge')
            ->useDisk('public')
            ->singleFile();
    }

    /**
     * Map of short media keys to their Spatie collection names.
     */
    private const MEDIA_COLLECTIONS = [
        'image' => 'HR_Covenants',
        'acknowledgment' => 'HR_Covenant_Acknowledgment',
        'discharge' => 'HR_Covenant_Discharge',
    ];

    /**
     * Resolve the display URL for a covenant media collection.
     * Served through a controller route (reads the file via Spatie's absolute
     * path) so it works for per-tenant storage where the static /storage
     * symlink points at the central folder instead of the tenant folder.
     */
    private function collectionUrl(string $key): ?string
    {
        $collection = self::MEDIA_COLLECTIONS[$key] ?? null;

        if (! $collection || ! $this->exists) {
            return null;
        }

        if (! $this->getFirstMedia($collection)) {
            return null;
        }

        return route('covenants.media', ['covenant' => $this->id, 'collection' => $key]);
    }

    /**
     * Get the covenant's image URL or null if no image exists
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->collectionUrl('image');
    }

    /**
     * صورة إقرار الاستلام
     */
    public function getAcknowledgmentUrlAttribute(): ?string
    {
        return $this->collectionUrl('acknowledgment');
    }

    /**
     * صورة إبراء الذمة
     */
    public function getDischargeUrlAttribute(): ?string
    {
        return $this->collectionUrl('discharge');
    }
}
