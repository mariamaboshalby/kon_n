<?php

declare(strict_types=1);

namespace Modules\Estate\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Estate\Models\PropertyType;

class PropertyTypesSeeder extends Seeder
{
    public function run(): void
    {
        // Common attributes shared by all types
        $commonAttrs = ['finishing_level', 'build_year'];

        $tree = [
            [
                'name_ar' => 'سكني',
                'name_en' => 'Residential',
                'slug' => 'residential',
                'icon' => 'las la-home',
                'attributes' => ['rooms_count', 'bathrooms_count', 'floor_number', 'finishing_level', 'build_year', 'is_furnished', 'furnishing_percentage', 'neighbor_notes', 'reputation_notes'],
                'sort_order' => 1,
                'children' => [
                    ['name_ar' => 'شقة',           'name_en' => 'Apartment',       'slug' => 'apartment',       'icon' => 'las la-building',      'sort_order' => 1, 'attributes' => ['rooms_count', 'bathrooms_count', 'floor_number', 'finishing_level', 'build_year', 'is_furnished', 'furnishing_percentage', 'neighbor_notes', 'reputation_notes']],
                    ['name_ar' => 'دوبلكس',         'name_en' => 'Duplex',          'slug' => 'duplex',          'icon' => 'las la-layer-group',   'sort_order' => 2, 'attributes' => ['rooms_count', 'bathrooms_count', 'floor_number', 'finishing_level', 'build_year', 'is_furnished', 'furnishing_percentage', 'neighbor_notes', 'reputation_notes']],
                    ['name_ar' => 'بنتهاوس',        'name_en' => 'Penthouse',       'slug' => 'penthouse',       'icon' => 'las la-star',          'sort_order' => 3, 'attributes' => ['rooms_count', 'bathrooms_count', 'floor_number', 'roof_area', 'finishing_level', 'build_year', 'is_furnished', 'furnishing_percentage', 'neighbor_notes', 'reputation_notes']],
                    ['name_ar' => 'ستوديو',         'name_en' => 'Studio',          'slug' => 'studio',          'icon' => 'las la-door-open',     'sort_order' => 4, 'attributes' => ['floor_number', 'finishing_level', 'build_year', 'is_furnished', 'furnishing_percentage', 'neighbor_notes', 'reputation_notes']],
                    ['name_ar' => 'فيلا مستقلة',    'name_en' => 'Standalone Villa', 'slug' => 'standalone_villa', 'icon' => 'las la-house-damage', 'sort_order' => 5, 'attributes' => ['rooms_count', 'bathrooms_count', 'garden_area', 'finishing_level', 'build_year', 'is_furnished', 'furnishing_percentage', 'neighbor_notes', 'reputation_notes']],
                    ['name_ar' => 'تاون هاوس',      'name_en' => 'Townhouse',       'slug' => 'townhouse',       'icon' => 'las la-city',          'sort_order' => 6, 'attributes' => ['rooms_count', 'bathrooms_count', 'garden_area', 'finishing_level', 'build_year', 'is_furnished', 'furnishing_percentage', 'neighbor_notes', 'reputation_notes']],
                    ['name_ar' => 'توين هاوس',      'name_en' => 'Twin House',      'slug' => 'twin_house',      'icon' => 'las la-clone',         'sort_order' => 7, 'attributes' => ['rooms_count', 'bathrooms_count', 'garden_area', 'finishing_level', 'build_year', 'is_furnished', 'furnishing_percentage', 'neighbor_notes', 'reputation_notes']],
                    ['name_ar' => 'شاليه',          'name_en' => 'Chalet',          'slug' => 'chalet',          'icon' => 'las la-umbrella-beach', 'sort_order' => 8, 'attributes' => ['rooms_count', 'bathrooms_count', 'garden_area', 'finishing_level', 'build_year', 'is_furnished', 'furnishing_percentage', 'neighbor_notes', 'reputation_notes']],
                ],
            ],
            [
                'name_ar' => 'تجاري',
                'name_en' => 'Commercial',
                'slug' => 'commercial',
                'icon' => 'las la-store',
                'attributes' => ['floor_number', 'finishing_level', 'build_year', 'facade_width', 'is_corner'],
                'sort_order' => 2,
                'children' => [
                    ['name_ar' => 'محل تجاري',      'name_en' => 'Retail Shop',     'slug' => 'shop',       'icon' => 'las la-shopping-bag',  'sort_order' => 1, 'attributes' => ['floor_number', 'finishing_level', 'build_year', 'facade_width', 'is_corner']],
                    ['name_ar' => 'مطعم / كافيه',   'name_en' => 'Restaurant/Cafe', 'slug' => 'restaurant', 'icon' => 'las la-utensils',      'sort_order' => 2, 'attributes' => ['floor_number', 'finishing_level', 'build_year', 'facade_width', 'is_corner']],
                    ['name_ar' => 'مخزن / مستودع',  'name_en' => 'Warehouse',       'slug' => 'warehouse',  'icon' => 'las la-warehouse',     'sort_order' => 3, 'attributes' => ['finishing_level', 'build_year', 'facade_width']],
                    ['name_ar' => 'صيدلية',         'name_en' => 'Pharmacy',        'slug' => 'pharmacy',   'icon' => 'las la-pills',         'sort_order' => 4, 'attributes' => ['floor_number', 'finishing_level', 'build_year', 'facade_width', 'is_corner']],
                ],
            ],
            [
                'name_ar' => 'إداري',
                'name_en' => 'Administrative',
                'slug' => 'administrative',
                'icon' => 'las la-briefcase',
                'attributes' => ['floor_number', 'rooms_count', 'finishing_level', 'build_year'],
                'sort_order' => 3,
                'children' => [
                    ['name_ar' => 'مكتب إداري',     'name_en' => 'Office',                  'slug' => 'office',         'icon' => 'las la-chair',         'sort_order' => 1, 'attributes' => ['floor_number', 'rooms_count', 'finishing_level', 'build_year']],
                    ['name_ar' => 'عيادة',          'name_en' => 'Clinic',                  'slug' => 'clinic',         'icon' => 'las la-stethoscope',   'sort_order' => 2, 'attributes' => ['floor_number', 'rooms_count', 'finishing_level', 'build_year']],
                    ['name_ar' => 'مبنى إداري',     'name_en' => 'Administrative Building', 'slug' => 'admin_building', 'icon' => 'las la-landmark',      'sort_order' => 3, 'attributes' => ['floor_number', 'rooms_count', 'finishing_level', 'build_year']],
                ],
            ],
            [
                'name_ar' => 'أراضي',
                'name_en' => 'Land',
                'slug' => 'land',
                'icon' => 'las la-map',
                'attributes' => ['land_license_type', 'street_width', 'land_shape'],
                'sort_order' => 4,
                'children' => [
                    ['name_ar' => 'أرض سكنية',      'name_en' => 'Residential Land',  'slug' => 'residential_land',  'icon' => 'las la-map-marked',    'sort_order' => 1, 'attributes' => ['land_license_type', 'street_width', 'land_shape']],
                    ['name_ar' => 'أرض زراعية',     'name_en' => 'Agricultural Land', 'slug' => 'agricultural_land', 'icon' => 'las la-seedling',      'sort_order' => 2, 'attributes' => ['land_license_type', 'street_width', 'land_shape']],
                    ['name_ar' => 'أرض صناعية',     'name_en' => 'Industrial Land',   'slug' => 'industrial_land',   'icon' => 'las la-industry',      'sort_order' => 3, 'attributes' => ['land_license_type', 'street_width', 'land_shape']],
                ],
            ],
        ];

        foreach ($tree as $rootData) {
            $children = $rootData['children'] ?? [];
            unset($rootData['children']);

            $root = PropertyType::updateOrCreate(
                ['slug' => $rootData['slug']],
                array_merge($rootData, ['is_active' => true])
            );

            foreach ($children as $childData) {
                PropertyType::updateOrCreate(
                    ['slug' => $childData['slug']],
                    array_merge($childData, ['parent_id' => $root->id, 'is_active' => true])
                );
            }
        }
    }
}
