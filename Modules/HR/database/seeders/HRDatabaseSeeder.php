<?php

namespace Modules\HR\database\seeders;

use Illuminate\Database\Seeder;

class HRDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            HRBaselineSeeder::class,
            HRPermissionsSeeder::class,
            GiveAdminHRPermissionsSeeder::class,
            InstantEvaluationItemsSeeder::class,
        ]);
    }
}
