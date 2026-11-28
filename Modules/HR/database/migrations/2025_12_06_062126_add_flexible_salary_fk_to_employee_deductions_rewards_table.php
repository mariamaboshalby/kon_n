<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONSTRAINT = 'emp_ded_rew_flex_sal_proc_fk';

    public function up(): void
    {
        if (! Schema::hasTable('employee_deductions_rewards') || ! Schema::hasTable('flexible_salary_processings')) {
            return;
        }

        if ($this->foreignKeyExists('employee_deductions_rewards', 'flexible_salary_processing_id')) {
            return;
        }

        Schema::table('employee_deductions_rewards', function (Blueprint $table): void {
            $table->foreign('flexible_salary_processing_id', self::CONSTRAINT)
                ->references('id')->on('flexible_salary_processings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('employee_deductions_rewards')) {
            return;
        }

        if (! $this->foreignKeyExists('employee_deductions_rewards', 'flexible_salary_processing_id')) {
            return;
        }

        Schema::table('employee_deductions_rewards', function (Blueprint $table): void {
            $table->dropForeign(self::CONSTRAINT);
        });
    }

    private function foreignKeyExists(string $table, string $column): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $rows = $connection->select(
            'SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
             AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [$database, $table, $column]
        );

        return $rows !== [];
    }
};
