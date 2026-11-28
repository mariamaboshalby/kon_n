<?php

declare(strict_types=1);

namespace Modules\HR\database\seeders;

use Illuminate\Database\Seeder;

/**
 * Demo / staging HR data — not run on module install; use Super Admin demo button or tenants:seed-module-demo.
 */
final class HRDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EmployeeSeeder::class,
            AttendanceSeeder::class,
            KpiSeeder::class,
            CvSeeder::class,
            AttendanceProcessingTestSeeder::class,
        ]);
    }
}
