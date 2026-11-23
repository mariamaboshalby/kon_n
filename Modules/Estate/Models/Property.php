<?php

declare(strict_types=1);

namespace Modules\Estate\Models;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Modules\Estate\Enums\FinishingLevel;
use Modules\HR\Models\Employee;
use Modules\Estate\Enums\ListingType;
use Modules\Estate\Enums\PaymentMethod;
use Modules\Estate\Enums\PropertyStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Property Model - نموذج العقار
 * 
 * Manages real estate properties with comprehensive details including
 * location, specifications, financials, and media gallery.
 */
class Property extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'properties';

    protected $fillable = [
        'title',
        'sku',
        'property_type_id',
        'listing_type',
        'status',
        'city',
        'district',
        'address_details',
        'latitude',
        'longitude',
        'landmark',
        'area',
        'roof_area',
        'garden_area',
        'floor_number',
        'rooms_count',
        'bathrooms_count',
        'finishing_level',
        'build_year',
        'is_furnished',
        'furnishing_percentage',
        'facade_width',
        'is_corner',
        'land_license_type',
        'street_width',
        'land_shape',
        'neighbor_notes',
        'reputation_notes',
        'price',
        'payment_method',
        'installment_period_months',
        'commission',
    ];

    /**
     * Cast attributes to native types and enums
     */
    protected function casts(): array
    {
        return [
            'listing_type'   => ListingType::class,
            'status'         => PropertyStatus::class,
            'finishing_level' => FinishingLevel::class,
            'payment_method' => PaymentMethod::class,
            'is_furnished'   => 'boolean',
            'is_corner'      => 'boolean',
            'latitude'       => 'decimal:8',
            'longitude'      => 'decimal:8',
            'area'           => 'decimal:2',
            'roof_area'      => 'decimal:2',
            'garden_area'    => 'decimal:2',
            'facade_width'   => 'decimal:2',
            'street_width'   => 'decimal:2',
            'price'          => 'decimal:2',
            'commission'     => 'decimal:2',
            'furnishing_percentage'    => 'integer',
            'rooms_count'              => 'integer',
            'bathrooms_count'          => 'integer',
            'floor_number'             => 'integer',
            'installment_period_months' => 'integer',
        ];
    }

    /**
     * Relationship: Property type
     */
    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    /**
     * Relationship: All stakeholders (pivot table)
     * جميع أطراف العقار
     */
    public function stakeholders(): HasMany
    {
        return $this->hasMany(PropertyStakeholder::class);
    }

    /**
     * Relationship: Property has many Clients (Many-to-Many)
     * العلاقة مع العملاء
     */
    public function clients(): MorphToMany
    {
        return $this->morphedByMany(Client::class, 'stakeholder', 'property_stakeholders')
            ->withPivot(['role', 'notes', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Relationship: Property has many Employees (Many-to-Many)
     * العلاقة مع الموظفين
     */
    public function employees(): MorphToMany
    {
        return $this->morphedByMany(Employee::class, 'stakeholder', 'property_stakeholders')
            ->withPivot(['role', 'notes', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Relationship: Property has many Users (Many-to-Many)
     * العلاقة مع المستخدمين
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'stakeholder', 'property_stakeholders')
            ->withPivot(['role', 'notes', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Get owners (clients with 'owner' role)
     * الحصول على المالكين
     */
    public function owners()
    {
        return $this->clients()->wherePivot('role', 'owner');
    }

    /**
     * Get primary owner
     * الحصول على المالك الرئيسي
     */
    public function primaryOwner()
    {
        return $this->clients()
            ->wherePivot('role', 'owner')
            ->wherePivot('is_primary', true)
            ->first();
    }

    /**
     * Get tenants (clients with 'tenant' role)
     * الحصول على المستأجرين
     */
    public function tenants()
    {
        return $this->clients()->wherePivot('role', 'tenant');
    }

    /**
     * Get buyers (clients with 'buyer' role)
     * الحصول على المشترين
     */
    public function buyers()
    {
        return $this->clients()->wherePivot('role', 'buyer');
    }

    /**
     * Get agents (employees with 'agent' role)
     * الحصول على الوكلاء
     */
    public function agents()
    {
        return $this->employees()->wherePivot('role', 'agent');
    }

    /**
     * Scope: Filter properties by price range
     * 
     * @param Builder $query
     * @param float|null $minPrice
     * @param float|null $maxPrice
     * @return Builder
     */
    public function scopePriceRange(Builder $query, ?float $minPrice = null, ?float $maxPrice = null): Builder
    {
        return $query->when($minPrice, fn($q) => $q->where('price', '>=', $minPrice))
                     ->when($maxPrice, fn($q) => $q->where('price', '<=', $maxPrice));
    }

    /**
     * Scope: Filter only available properties
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', PropertyStatus::Available);
    }

    /**
     * Scope: Filter by city
     * 
     * @param Builder $query
     * @param string $city
     * @return Builder
     */
    public function scopeInCity(Builder $query, string $city): Builder
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    /**
     * Scope: Filter by number of rooms
     * 
     * @param Builder $query
     * @param int $rooms
     * @return Builder
     */
    public function scopeWithRooms(Builder $query, int $rooms): Builder
    {
        return $query->where('rooms_count', $rooms);
    }

    /**
     * Scope: Filter by listing type
     * 
     * @param Builder $query
     * @param ListingType $type
     * @return Builder
     */
    public function scopeOfType(Builder $query, ListingType $type): Builder
    {
        return $query->where('listing_type', $type);
    }

    /**
     * Check if property is available for sale or rent
     */
    public function isAvailable(): bool
    {
        return $this->status === PropertyStatus::Available;
    }

    /**
     * Check if property is furnished
     */
    public function isFurnished(): bool
    {
        return $this->is_furnished;
    }

    /**
     * Get formatted price with currency
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format((float) $this->price, 2) . ' ' . __('estate::estate.currency');
    }

    /**
     * Register media collections for property images
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('property_images')
            ->useDisk('public');
    }
}
