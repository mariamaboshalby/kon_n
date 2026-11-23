<?php

declare(strict_types=1);

namespace Modules\Currencies\Database\Factories;

use Modules\Settings\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'code' => strtoupper($this->faker->unique()->bothify('???')),
            'symbol' => $this->faker->randomElement(['$', '€', '£', '¥', '₹']),
            'decimal_places' => $this->faker->numberBetween(0, 8),
            'is_default' => false,
            'is_active' => true,
            'rate_mode' => $this->faker->randomElement(['automatic', 'manual']),
        ];
    }

    public function default(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
