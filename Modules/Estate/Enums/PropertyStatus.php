<?php

declare(strict_types=1);

namespace Modules\Estate\Enums;

enum PropertyStatus: string
{
    case Available = 'available';
    case Reserved  = 'reserved';
    case Sold      = 'sold';

    public function label(): string
    {
        return match($this) {
            self::Available => __('estate::estate.status_available'),
            self::Reserved  => __('estate::estate.status_reserved'),
            self::Sold      => __('estate::estate.status_sold'),
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
