<?php

declare(strict_types=1);

namespace Modules\Estate\Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Estate\Enums\FinishingLevel;
use Modules\Estate\Enums\ListingType;
use Modules\Estate\Enums\PaymentMethod;
use Modules\Estate\Enums\PropertyStatus;
use Modules\Estate\Models\Property;

/**
 * PropertyFactory - مصنع بيانات العقارات
 * 
 * Generates fake property data for testing and seeding
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    /**
     * Define the model's default state
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cities = ['الرياض', 'جدة', 'الدمام', 'مكة', 'المدينة', 'الخبر', 'الطائف'];
        $districts = ['النخيل', 'الملقا', 'العليا', 'الربوة', 'السلامة', 'الفيصلية', 'الورود'];
        
        return [
            'title' => $this->faker->sentence(3),
            'sku' => 'PROP-' . $this->faker->unique()->numberBetween(10000, 99999),
            'listing_type' => $this->faker->randomElement(ListingType::cases()),
            'status' => $this->faker->randomElement(PropertyStatus::cases()),
            'owner_id' => Client::factory(),
            'tenant_buyer_id' => $this->faker->boolean(30) ? Client::factory() : null,
            'city' => $this->faker->randomElement($cities),
            'district' => $this->faker->randomElement($districts),
            'address_details' => $this->faker->address(),
            'latitude' => $this->faker->latitude(21, 32),
            'longitude' => $this->faker->longitude(34, 56),
            'landmark' => $this->faker->optional()->streetName(),
            'area' => $this->faker->randomFloat(2, 50, 500),
            'floor_number' => $this->faker->numberBetween(0, 20),
            'rooms_count' => $this->faker->numberBetween(1, 8),
            'bathrooms_count' => $this->faker->numberBetween(1, 5),
            'finishing_level' => $this->faker->randomElement(FinishingLevel::cases()),
            'build_year' => $this->faker->year(),
            'is_furnished' => $this->faker->boolean(40),
            'furnishing_percentage' => $this->faker->numberBetween(0, 100),
            'neighbor_notes' => $this->faker->optional()->sentence(),
            'reputation_notes' => $this->faker->optional()->sentence(),
            'price' => $this->faker->randomFloat(2, 100000, 5000000),
            'payment_method' => $this->faker->randomElement(PaymentMethod::cases()),
            'installment_period_months' => $this->faker->optional()->numberBetween(12, 120),
            'commission' => $this->faker->randomFloat(2, 1000, 50000),
        ];
    }

    /**
     * State for available properties
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PropertyStatus::Available,
        ]);
    }

    /**
     * State for sale properties
     */
    public function forSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'listing_type' => ListingType::Sale,
        ]);
    }

    /**
     * State for rent properties
     */
    public function forRent(): static
    {
        return $this->state(fn (array $attributes) => [
            'listing_type' => ListingType::Rent,
        ]);
    }

    /**
     * State for furnished properties
     */
    public function furnished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_furnished' => true,
            'furnishing_percentage' => $this->faker->numberBetween(70, 100),
        ]);
    }
}
