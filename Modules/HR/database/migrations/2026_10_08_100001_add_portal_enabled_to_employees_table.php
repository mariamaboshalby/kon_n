<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees', 'portal_enabled')) {
                $table->boolean('portal_enabled')->default(false);
            }
            if (! Schema::hasColumn('employees', 'portal_password_changed_at')) {
                $table->timestamp('portal_password_changed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $cols = array_values(array_filter(
                ['portal_enabled', 'portal_password_changed_at'],
                fn (string $c) => Schema::hasColumn('employees', $c)
            ));

            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
