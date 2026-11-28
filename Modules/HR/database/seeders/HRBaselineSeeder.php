<?php

declare(strict_types=1);

namespace Modules\HR\database\seeders;

use Illuminate\Database\Seeder;

final class HRBaselineSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            StateSeeder::class,
            CitySeeder::class,
            TownSeeder::class,
            DepartmentSeeder::class,
            EmployeesJobSeeder::class,
            ShiftSeeder::class,
            ContractTypeSeeder::class,
            LeaveTypeSeeder::class,
        ]);
    }
}
