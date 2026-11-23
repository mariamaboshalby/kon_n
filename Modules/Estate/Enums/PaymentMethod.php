<?php

declare(strict_types=1);

namespace Modules\Estate\Enums;

enum PaymentMethod: string
{
    case Cash         = 'cash';
    case Installments = 'installments';
    case Both         = 'both';

    public function label(): string
    {
        return match($this) {
            self::Cash         => __('estate::estate.payment_cash'),
            self::Installments => __('estate::estate.payment_installments'),
            self::Both         => __('estate::estate.payment_both'),
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
