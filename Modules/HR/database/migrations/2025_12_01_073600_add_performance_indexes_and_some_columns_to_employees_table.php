<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        // Branches core migration may have run before employees existed (optional HR install).
        if (! Schema::hasColumn('employees', 'branch_id') && Schema::hasTable('branches')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->foreignId('branch_id')->nullable()->default(1)->constrained('branches')->nullOnDelete();
            });
        }

        Schema::table('employees', function (Blueprint $table): void {
            if (Schema::hasColumn('employees', 'salary_type')) {
                $table->enum('salary_type', ['ساعات عمل فقط', 'ساعات عمل و إضافي يومى', 'ساعات عمل و إضافي للمده', 'حضور فقط', 'إنتاج فقط', 'ثابت + ساعات عمل مرن'])->nullable()->change();
            }

            if (! Schema::hasColumn('employees', 'allowed_permission_days')) {
                $table->unsignedInteger('allowed_permission_days')->default(0);
            }

            if (! Schema::hasColumn('employees', 'allowed_late_days')) {
                $table->unsignedInteger('allowed_late_days')->default(0);
            }

            if (! Schema::hasColumn('employees', 'allowed_absent_days')) {
                $table->unsignedInteger('allowed_absent_days')->default(0);
            }

            if (! Schema::hasColumn('employees', 'is_errand_allowed')) {
                $table->boolean('is_errand_allowed')->default(false);
            }

            if (! Schema::hasColumn('employees', 'allowed_errand_days')) {
                $table->unsignedInteger('allowed_errand_days')->default(0);
            }

            if (! Schema::hasColumn('employees', 'line_manager_id')) {
                $table->foreignId('line_manager_id')->nullable()->constrained('employees')->onDelete('set null');
            }

            if (! Schema::hasColumn('employees', 'flexible_hourly_wage')) {
                $table->decimal('flexible_hourly_wage', 10, 2)->default(0);
            }
        });

        Schema::table('employees', function (Blueprint $table): void {
            if (
                Schema::hasColumn('employees', 'branch_id')
                && Schema::hasColumn('employees', 'name')
                && ! $this->indexExists('employees', 'employees_branch_id_name_index')
            ) {
                $table->index(['branch_id', 'name'], 'employees_branch_id_name_index');
            }

            if (
                Schema::hasColumn('employees', 'status')
                && ! $this->indexExists('employees', 'employees_status_index')
            ) {
                $table->index('status', 'employees_status_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table): void {
            if ($this->indexExists('employees', 'employees_branch_id_name_index')) {
                $table->dropIndex('employees_branch_id_name_index');
            }

            if ($this->indexExists('employees', 'employees_status_index')) {
                $table->dropIndex('employees_status_index');
            }
        });

        Schema::table('employees', function (Blueprint $table): void {
            $columnsToDrop = array_values(array_filter([
                Schema::hasColumn('employees', 'allowed_permission_days') ? 'allowed_permission_days' : null,
                Schema::hasColumn('employees', 'allowed_late_days') ? 'allowed_late_days' : null,
                Schema::hasColumn('employees', 'allowed_absent_days') ? 'allowed_absent_days' : null,
                Schema::hasColumn('employees', 'is_errand_allowed') ? 'is_errand_allowed' : null,
                Schema::hasColumn('employees', 'allowed_errand_days') ? 'allowed_errand_days' : null,
                Schema::hasColumn('employees', 'line_manager_id') ? 'line_manager_id' : null,
                Schema::hasColumn('employees', 'flexible_hourly_wage') ? 'flexible_hourly_wage' : null,
            ]));

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }

            if (Schema::hasColumn('employees', 'salary_type')) {
                $table->enum('salary_type', ['ساعات عمل فقط', 'ساعات عمل و إضافي يومى', 'ساعات عمل و إضافي للمده', 'حضور فقط', 'إنتاج فقط'])->change();
            }
        });
    }

    /**
     * Check if index exists (database-agnostic)
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        try {
            if ($driver === 'sqlite') {
                $indexes = $connection->select("PRAGMA index_list({$table})");
                foreach ($indexes as $idx) {
                    if ($idx->name === $index) {
                        return true;
                    }
                }

                return false;
            }

            if ($driver === 'mysql') {
                $databaseName = $connection->getDatabaseName();
                $result = $connection->select(
                    'SELECT COUNT(*) as count 
                     FROM information_schema.statistics 
                     WHERE table_schema = ? 
                     AND table_name = ? 
                     AND index_name = ?',
                    [$databaseName, $table, $index]
                );

                return $result[0]->count > 0;
            }

            return false;
        } catch (\Exception) {
            return false;
        }
    }
};
