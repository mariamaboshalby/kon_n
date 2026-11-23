<?php

declare(strict_types=1);

namespace Modules\Estate\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Estate\Models\Property;

/**
 * PropertySeeder - بذرة بيانات العقارات
 * 
 * Seeds the database with sample property data
 */
class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds
     */
    public function run(): void
    {
        // Create 50 sample properties with various states
        Property::factory()
            ->count(20)
            ->available()
            ->forSale()
            ->create();

        Property::factory()
            ->count(15)
            ->available()
            ->forRent()
            ->create();

        Property::factory()
            ->count(10)
            ->available()
            ->furnished()
            ->create();

        Property::factory()
            ->count(5)
            ->create(['status' => \Modules\Estate\Enums\PropertyStatus::Reserved]);
    }
}
