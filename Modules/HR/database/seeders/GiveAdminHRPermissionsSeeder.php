<?php

declare(strict_types=1);

namespace Modules\HR\database\seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Authorization\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final class GiveAdminHRPermissionsSeeder extends Seeder
{
    /** @var list<string> */
    private const CATEGORIES = ['HR'];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = User::find(1);

        if ($admin === null) {
            $this->command?->error('User #1 not found.');

            return;
        }

        $permissionNames = Permission::query()
            ->whereIn('category', self::CATEGORIES)
            ->pluck('name')
            ->all();

        if ($permissionNames === []) {
            return;
        }

        $admin->givePermissionTo($permissionNames);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
