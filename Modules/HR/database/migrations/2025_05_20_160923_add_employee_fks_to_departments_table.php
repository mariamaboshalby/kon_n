<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('departments') || ! Schema::hasTable('employees')) {
            return;
        }

        $columns = array_filter(
            ['director_id', 'deputy_director_id'],
            fn (string $column): bool => ! $this->foreignKeyExists('departments', $column)
        );

        if ($columns === []) {
            return;
        }

        Schema::table('departments', function (Blueprint $table) use ($columns): void {
            if (in_array('director_id', $columns, true)) {
                $table->foreign('director_id')
                    ->references('id')->on('employees')->nullOnDelete();
            }

            if (in_array('deputy_director_id', $columns, true)) {
                $table->foreign('deputy_director_id')
                    ->references('id')->on('employees')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('departments')) {
            return;
        }

        Schema::table('departments', function (Blueprint $table): void {
            if ($this->foreignKeyExists('departments', 'director_id')) {
                $table->dropForeign(['director_id']);
            }

            if ($this->foreignKeyExists('departments', 'deputy_director_id')) {
                $table->dropForeign(['deputy_director_id']);
            }
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
