<?php

declare(strict_types=1);

namespace Modules\App\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Authorization\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AppPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // App permissions removed - category disabled
    }
}
