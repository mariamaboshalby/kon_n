<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_instant_evaluations')) {
            // أعمدة فقط — بدون indexes تلقائية طويلة الاسم
            Schema::create('employee_instant_evaluations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('evaluator_user_id');
                $table->unsignedTinyInteger('score');
                $table->text('note')->nullable();
                $table->dateTime('evaluated_at');
                $table->string('client_request_id', 64)->nullable();
                $table->timestamps();
            });

            Schema::table('employee_instant_evaluations', function (Blueprint $table) {
                $table->foreign('employee_id', 'eie_employee_id_fk')
                    ->references('id')
                    ->on('employees')
                    ->cascadeOnDelete();
            });
        }

        $this->ensureUnique('eie_client_request_id_uq', ['client_request_id']);
        $this->ensureIndex('eie_employee_evaluated_idx', ['employee_id', 'evaluated_at']);
        $this->ensureIndex('eie_evaluator_evaluated_idx', ['evaluator_user_id', 'evaluated_at']);
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_instant_evaluations');
    }

    /**
     * @param  list<string>  $columns
     */
    private function ensureUnique(string $name, array $columns): void
    {
        if ($this->indexExists($name) || $this->indexExists('employee_instant_evaluations_client_request_id_unique')) {
            return;
        }

        $cols = collect($columns)->map(fn (string $c) => '`'.$c.'`')->implode(', ');
        DB::statement("ALTER TABLE `employee_instant_evaluations` ADD UNIQUE `{$name}` ({$cols})");
    }

    /**
     * @param  list<string>  $columns
     */
    private function ensureIndex(string $name, array $columns): void
    {
        if ($this->indexExists($name)) {
            return;
        }

        // أسماء Laravel التلقائية القديمة (لو اتنشأت على بعض السيرفرات)
        $legacy = 'employee_instant_evaluations_'.implode('_', $columns).'_index';
        if (strlen($legacy) <= 64 && $this->indexExists($legacy)) {
            return;
        }

        $cols = collect($columns)->map(fn (string $c) => '`'.$c.'`')->implode(', ');
        DB::statement("ALTER TABLE `employee_instant_evaluations` ADD INDEX `{$name}` ({$cols})");
    }

    private function indexExists(string $index): bool
    {
        $database = Schema::getConnection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?
             LIMIT 1',
            [$database, 'employee_instant_evaluations', $index]
        );

        return $row !== null;
    }
};
