<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    private static int $codeCounter = 1000;

    public function definition(): array
    {
        return [
            'name'               => $this->faker->unique()->words(3, true),
            'code'               => ++self::$codeCounter,
            'info'               => $this->faker->sentence(),
            'tenant'             => 1,
            'branch_id'          => null,
            'average_cost'       => $this->faker->randomFloat(2, 5, 200),
            'is_active'          => 1,
            'isdeleted'          => 0,
            'type'               => 1,
            'min_order_quantity' => 1,
            'max_order_quantity' => 100,
        ];
    }

    /** صنف مطعم بسعر واقعي */
    public function restaurant(float $cost = null, float $price = null): static
    {
        $cost ??= $this->faker->randomFloat(2, 5, 80);

        return $this->state([
            'average_cost' => $cost,
        ]);
    }
}
