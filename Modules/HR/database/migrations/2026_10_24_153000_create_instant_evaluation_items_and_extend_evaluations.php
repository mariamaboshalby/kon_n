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
        if (! Schema::hasTable('instant_evaluation_items')) {
            Schema::create('instant_evaluation_items', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('polarity', 16); // positive | negative
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['polarity', 'is_active'], 'iei_polarity_active_idx');
            });
        }

        if (Schema::hasTable('employee_instant_evaluations')) {
            Schema::table('employee_instant_evaluations', function (Blueprint $table) {
                if (! Schema::hasColumn('employee_instant_evaluations', 'polarity')) {
                    $table->string('polarity', 16)->nullable()->after('evaluator_user_id');
                }
                if (! Schema::hasColumn('employee_instant_evaluations', 'item_id')) {
                    $table->unsignedBigInteger('item_id')->nullable()->after('polarity');
                }
            });

            // ترحيل الصفوف القديمة: موجبة + بدون بند
            DB::table('employee_instant_evaluations')
                ->whereNull('polarity')
                ->update(['polarity' => 'positive']);

            // السماح بدرجة سالبة (MySQL signed tinyint)
            DB::statement('ALTER TABLE `employee_instant_evaluations` MODIFY `score` TINYINT NOT NULL');

            if (! $this->foreignExists('employee_instant_evaluations', 'eie_item_id_fk')) {
                Schema::table('employee_instant_evaluations', function (Blueprint $table) {
                    $table->foreign('item_id', 'eie_item_id_fk')
                        ->references('id')
                        ->on('instant_evaluation_items')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_instant_evaluations')) {
            Schema::table('employee_instant_evaluations', function (Blueprint $table) {
                if ($this->foreignExists('employee_instant_evaluations', 'eie_item_id_fk')) {
                    $table->dropForeign('eie_item_id_fk');
                }
                if (Schema::hasColumn('employee_instant_evaluations', 'item_id')) {
                    $table->dropColumn('item_id');
                }
                if (Schema::hasColumn('employee_instant_evaluations', 'polarity')) {
                    $table->dropColumn('polarity');
                }
            });

            DB::statement('ALTER TABLE `employee_instant_evaluations` MODIFY `score` TINYINT UNSIGNED NOT NULL');
        }

        Schema::dropIfExists('instant_evaluation_items');
    }

    private function foreignExists(string $table, string $name): bool
    {
        $database = Schema::getConnection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.table_constraints
             WHERE constraint_schema = ? AND table_name = ? AND constraint_name = ? AND constraint_type = ?
             LIMIT 1',
            [$database, $table, $name, 'FOREIGN KEY']
        );

        return $row !== null;
    }
};
