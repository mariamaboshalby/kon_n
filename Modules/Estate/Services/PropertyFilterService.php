<?php

declare(strict_types=1);

namespace Modules\Estate\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Estate\Enums\ListingType;
use Modules\Estate\Enums\PropertyStatus;
use Modules\Estate\Models\Property;

/**
 * PropertyFilterService - خدمة تصفية العقارات
 * 
 * Handles complex filtering logic for property searches
 * Implements clean separation of concerns and reusable business logic
 */
class PropertyFilterService
{
    /**
     * Filter properties based on provided criteria
     * تصفية العقارات بناءً على المعايير المحددة
     * 
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function filter(array $filters): LengthAwarePaginator
    {
        $query = Property::query()
            ->with(['stakeholders.stakeholder', 'clients', 'employees']);

        // Filter by city - التصفية حسب المدينة
        if (!empty($filters['city'])) {
            $query->inCity($filters['city']);
        }

        // Filter by district - التصفية حسب الحي
        if (!empty($filters['district'])) {
            $query->where('district', 'like', "%{$filters['district']}%");
        }

        // Filter by price range - التصفية حسب نطاق السعر
        if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
            $query->priceRange(
                !empty($filters['min_price']) ? (float) $filters['min_price'] : null,
                !empty($filters['max_price']) ? (float) $filters['max_price'] : null
            );
        }

        // Filter by rooms count - التصفية حسب عدد الغرف
        if (!empty($filters['rooms_count'])) {
            $query->withRooms((int) $filters['rooms_count']);
        }

        // Filter by bathrooms count - التصفية حسب عدد دورات المياه
        if (!empty($filters['bathrooms_count'])) {
            $query->where('bathrooms_count', (int) $filters['bathrooms_count']);
        }

        // Filter by listing type - التصفية حسب نوع العرض
        if (!empty($filters['listing_type'])) {
            $listingType = ListingType::tryFrom($filters['listing_type']);
            if ($listingType) {
                $query->ofType($listingType);
            }
        }

        // Filter by status - التصفية حسب الحالة
        if (!empty($filters['status'])) {
            $status = PropertyStatus::tryFrom($filters['status']);
            if ($status) {
                $query->where('status', $status);
            }
        }

        // Filter by furnished status - التصفية حسب حالة الأثاث
        if (isset($filters['is_furnished'])) {
            $query->where('is_furnished', (bool) $filters['is_furnished']);
        }

        // Filter by area range - التصفية حسب نطاق المساحة
        if (!empty($filters['min_area'])) {
            $query->where('area', '>=', (float) $filters['min_area']);
        }
        if (!empty($filters['max_area'])) {
            $query->where('area', '<=', (float) $filters['max_area']);
        }

        // Search in title - البحث في العنوان
        if (!empty($filters['search'])) {
            $query->where('title', 'like', "%{$filters['search']}%");
        }

        // Sorting - الترتيب
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        $allowedSortFields = ['price', 'area', 'rooms_count', 'created_at', 'title'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Pagination - الترقيم
        $perPage = !empty($filters['per_page']) ? (int) $filters['per_page'] : 15;
        
        return $query->paginate($perPage);
    }
}
