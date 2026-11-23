<?php

declare(strict_types=1);

namespace Modules\Estate\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * EstatePermissionsSeeder - بذرة صلاحيات العقارات
 *
 * Seeds permissions for the Estate module
 */
class EstatePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define Estate module permissions
        $permissions = [
            'view Properties',
            'create Properties',
            'edit Properties',
            'delete Properties',
            'view PropertyTypes',
            'create PropertyTypes',
            'edit PropertyTypes',
            'delete PropertyTypes',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        // Assign all permissions to Admin role
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }

        $this->command->info('Estate module permissions seeded successfully!');
    }
}
