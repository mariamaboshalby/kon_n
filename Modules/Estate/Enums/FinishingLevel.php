<?php

declare(strict_types=1);

namespace Modules\Estate\Enums;

enum FinishingLevel: string
{
    case Shell      = 'shell';
    case SemiFinished = 'semi_finished';
    case Finished   = 'finished';
    case Luxury     = 'luxury';

    public function label(): string
    {
        return match($this) {
            self::Shell         => __('estate::estate.finishing_shell'),
            self::SemiFinished  => __('estate::estate.finishing_semi_finished'),
            self::Finished      => __('estate::estate.finishing_finished'),
            self::Luxury        => __('estate::estate.finishing_luxury'),
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
