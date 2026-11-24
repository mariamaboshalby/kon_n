<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HR\database\seeders\HRDatabaseSeeder;
use Modules\App\database\seeders\AppDatabaseSeeder;
use Modules\CRM\Database\Seeders\CRMDatabaseSeeder;
use Modules\Branches\database\seeders\BranchesDatabaseSeeder;
use Modules\Progress\database\seeders\ProgressDatabaseSeeder;
use Modules\Settings\Database\Seeders\SettingsDatabaseSeeder;
use Modules\Inquiries\database\seeders\InquiriesDatabaseSeeder;
use Modules\Notifications\Database\Seeders\NotificationsDatabaseSeeder;
use Modules\Projects\database\seeders\ProjectsDatabaseSeeder;
use Modules\Authorization\Database\Seeders\RoleAndPermissionDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(BranchesDatabaseSeeder::class);
        $this->call(AppDatabaseSeeder::class);
        $this->call(RoleAndPermissionDatabaseSeeder::class);
        $this->call(CRMDatabaseSeeder::class);
        $this->call(InquiriesDatabaseSeeder::class);
        $this->call(SettingsDatabaseSeeder::class);
        $this->call(ProgressDatabaseSeeder::class);
        $this->call(HRDatabaseSeeder::class);
        $this->call(NotificationsDatabaseSeeder::class);
        $this->call(ProjectsDatabaseSeeder::class);

        $this->call([
            UpdateAccHeadAccTypeSeeder::class,
            ProTypesSeeder::class,
            NoteSeeder::class,
            NoteDetailsSeeder::class,
            UnitSeeder::class,
            PriceSeeder::class,
            VaribalSeeder::class,
            GiveAllPermissionsToAdminSeeder::class,
        ]);
    }
}
