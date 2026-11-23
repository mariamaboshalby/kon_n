<?php

declare(strict_types=1);

namespace Modules\Estate\Enums;

enum PropertyType: string
{
    // Residential - سكني
    case Apartment = 'apartment';
    case Duplex = 'duplex';
    case Penthouse = 'penthouse';
    case Studio = 'studio';
    case StandaloneVilla = 'standalone_villa';
    case Townhouse = 'townhouse';
    case TwinHouse = 'twin_house';
    case Chalet = 'chalet';

    // Commercial - تجاري
    case Shop = 'shop';
    case Restaurant = 'restaurant';
    case Warehouse = 'warehouse';
    case Pharmacy = 'pharmacy';

    // Administrative - إداري
    case Office = 'office';
    case Clinic = 'clinic';
    case AdminBuilding = 'admin_building';

    // Land - أراضي
    case ResidentialLand = 'residential_land';
    case AgriculturalLand = 'agricultural_land';
    case IndustrialLand = 'industrial_land';

    public function label(): string
    {
        return match ($this) {
            self::Apartment => __('estate::estate.type_apartment'),
            self::Duplex => __('estate::estate.type_duplex'),
            self::Penthouse => __('estate::estate.type_penthouse'),
            self::Studio => __('estate::estate.type_studio'),
            self::StandaloneVilla => __('estate::estate.type_standalone_villa'),
            self::Townhouse => __('estate::estate.type_townhouse'),
            self::TwinHouse => __('estate::estate.type_twin_house'),
            self::Chalet => __('estate::estate.type_chalet'),
            self::Shop => __('estate::estate.type_shop'),
            self::Restaurant => __('estate::estate.type_restaurant'),
            self::Warehouse => __('estate::estate.type_warehouse'),
            self::Pharmacy => __('estate::estate.type_pharmacy'),
            self::Office => __('estate::estate.type_office'),
            self::Clinic => __('estate::estate.type_clinic'),
            self::AdminBuilding => __('estate::estate.type_admin_building'),
            self::ResidentialLand => __('estate::estate.type_residential_land'),
            self::AgriculturalLand => __('estate::estate.type_agricultural_land'),
            self::IndustrialLand => __('estate::estate.type_industrial_land'),
        };
    }

    public function category(): string
    {
        return match (true) {
            in_array($this, [
                self::Apartment, self::Duplex, self::Penthouse, self::Studio,
                self::StandaloneVilla, self::Townhouse, self::TwinHouse, self::Chalet,
            ]) => 'residential',
            in_array($this, [
                self::Shop, self::Restaurant, self::Warehouse, self::Pharmacy,
            ]) => 'commercial',
            in_array($this, [
                self::Office, self::Clinic, self::AdminBuilding,
            ]) => 'administrative',
            default => 'land',
        };
    }

    public function categoryLabel(): string
    {
        return match ($this->category()) {
            'residential' => __('estate::estate.category_residential'),
            'commercial' => __('estate::estate.category_commercial'),
            'administrative' => __('estate::estate.category_administrative'),
            default => __('estate::estate.category_land'),
        };
    }

    /** الـ attributes الخاصة بكل نوع */
    public function specificAttributes(): array
    {
        return match ($this->category()) {
            'residential' => match ($this) {
                self::Studio => ['floor_number', 'finishing_level', 'build_year', 'is_furnished', 'furnishing_percentage', 'neighbor_notes', 'reputation_notes'],
                self::StandaloneVilla, self::Townhouse, self::TwinHouse, self::Chalet => ['rooms_count', 'bathrooms_count', 'finishing_level', 'build_year', 'is_furnished', 'furnishing_percentage', 'garden_area', 'neighbor_notes', 'reputation_notes'],
                self::Penthouse => ['rooms_count', 'bathrooms_count', 'floor_number', 'finishing_level', 'build_year', 'is_furnished', 'furnishing_percentage', 'roof_area', 'neighbor_notes', 'reputation_notes'],
                default => ['rooms_count', 'bathrooms_count', 'floor_number', 'finishing_level', 'build_year', 'is_furnished', 'furnishing_percentage', 'neighbor_notes', 'reputation_notes'],
            },
            'commercial' => ['floor_number', 'finishing_level', 'build_year', 'facade_width', 'is_corner'],
            'administrative' => ['floor_number', 'finishing_level', 'build_year', 'rooms_count'],
            'land' => ['land_license_type', 'street_width', 'land_shape'],
        };
    }

    /** هل يظهر حقل معين لهذا النوع */
    public function hasAttribute(string $attribute): bool
    {
        return in_array($attribute, $this->specificAttributes());
    }

    /** @return array<string, string> */
    public static function groupedOptions(): array
    {
        $groups = [];
        foreach (self::cases() as $case) {
            $groups[$case->category()][] = ['value' => $case->value, 'label' => $case->label()];
        }

        return $groups;
    }
}
