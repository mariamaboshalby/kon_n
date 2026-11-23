<?php

declare(strict_types=1);

namespace Modules\Estate\Enums;

enum ListingType: string
{
    case Sale = 'sale';
    case Rent = 'rent';
    case FurnishedRent = 'furnished_rent';

    /**
     * Get the human-readable label for the enum value.
     */
    public function label(): string
    {
        return match($this) {
            self::Sale          => __('estate::estate.listing_type_sale'),
            self::Rent          => __('estate::estate.listing_type_rent'),
            self::FurnishedRent => __('estate::estate.listing_type_furnished_rent'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_column(
            array_map(fn(self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases()),
            'label',
            'value'
        );
    }
}
