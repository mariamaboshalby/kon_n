<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            [
                'name' => 'قطعه',
                'code' => 1,
            ],
            [
                'name' => 'كرتونة',
                'code' => 2,
            ],
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($units as $unit) {
            Unit::firstOrCreate(
                ['code' => $unit['code']],
                $unit
            );
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
