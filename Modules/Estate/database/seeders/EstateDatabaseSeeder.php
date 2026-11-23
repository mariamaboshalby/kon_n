<?php

namespace Modules\Estate\Database\Seeders;

use Illuminate\Database\Seeder;

class EstateDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            EstatePermissionsSeeder::class,
            PropertyTypesSeeder::class,
            PropertySeeder::class,
        ]);
    }
}
