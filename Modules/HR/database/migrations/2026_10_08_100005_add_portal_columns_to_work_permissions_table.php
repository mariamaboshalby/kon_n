<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_permissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('work_permissions', 'permission_type')) {
                $table->string('permission_type', 20)->nullable();
            }
            if (! Schema::hasColumn('work_permissions', 'from_time')) {
                $table->time('from_time')->nullable();
            }
            if (! Schema::hasColumn('work_permissions', 'to_time')) {
                $table->time('to_time')->nullable();
            }
            if (! Schema::hasColumn('work_permissions', 'reason')) {
                $table->text('reason')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('work_permissions', function (Blueprint $table): void {
            $cols = array_values(array_filter(
                ['permission_type', 'from_time', 'to_time', 'reason'],
                fn (string $c) => Schema::hasColumn('work_permissions', $c)
            ));

            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
