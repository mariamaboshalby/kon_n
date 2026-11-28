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
        if (! Schema::hasTable('employee_weekly_shifts')) {
            return;
        }

        if (! Schema::hasColumn('employee_weekly_shifts', 'start_time')) {
            Schema::table('employee_weekly_shifts', function (Blueprint $table): void {
                $table->time('start_time')->nullable()->after('weekday');
                $table->time('end_time')->nullable()->after('start_time');
            });
        }

        if (Schema::hasColumn('employee_weekly_shifts', 'shift_id') && Schema::hasTable('shifts')) {
            DB::table('employee_weekly_shifts')
                ->whereNotNull('shift_id')
                ->whereNull('start_time')
                ->orderBy('id')
                ->chunkById(500, function ($rows): void {
                    $shiftIds = [];
                    foreach ($rows as $row) {
                        $shiftIds[] = $row->shift_id;
                    }

                    $shifts = DB::table('shifts')
                        ->whereIn('id', array_values(array_unique($shiftIds)))
                        ->get()
                        ->keyBy('id');

                    foreach ($rows as $row) {
                        $shift = $shifts->get($row->shift_id);
                        if ($shift === null) {
                            continue;
                        }

                        DB::table('employee_weekly_shifts')->where('id', $row->id)->update([
                            'start_time' => $shift->start_time,
                            'end_time' => $shift->end_time,
                        ]);
                    }
                });

            DB::table('employee_weekly_shifts')
                ->where(function ($query): void {
                    $query->whereNull('start_time')->orWhereNull('end_time');
                })
                ->delete();
        }

        if (Schema::hasColumn('employee_weekly_shifts', 'shift_id')) {
            foreach (Schema::getForeignKeys('employee_weekly_shifts') as $foreignKey) {
                if (in_array('shift_id', $foreignKey['columns'] ?? [], true)) {
                    Schema::table('employee_weekly_shifts', function (Blueprint $table) use ($foreignKey): void {
                        $table->dropForeign($foreignKey['name']);
                    });
                }
            }

            foreach (Schema::getIndexes('employee_weekly_shifts') as $index) {
                $indexName = (string) ($index['name'] ?? '');
                $columns = $index['columns'] ?? [];
                if ($indexName === '' || $indexName === 'primary' || ! in_array('shift_id', $columns, true)) {
                    continue;
                }

                Schema::table('employee_weekly_shifts', function (Blueprint $table) use ($indexName): void {
                    $table->dropIndex($indexName);
                });
            }

            if (! $this->indexExists('employee_weekly_shifts_employee_day_index')) {
                Schema::table('employee_weekly_shifts', function (Blueprint $table): void {
                    $table->index(['employee_id', 'weekday'], 'employee_weekly_shifts_employee_day_index');
                });
            }

            if ($this->indexExists('employee_weekly_shifts_employee_day_unique')) {
                Schema::table('employee_weekly_shifts', function (Blueprint $table): void {
                    $table->dropUnique('employee_weekly_shifts_employee_day_unique');
                });
            }

            Schema::table('employee_weekly_shifts', function (Blueprint $table): void {
                $table->dropColumn('shift_id');
            });
        }

        if (! $this->indexExists('employee_weekly_shifts_employee_day_index')) {
            Schema::table('employee_weekly_shifts', function (Blueprint $table): void {
                $table->index(['employee_id', 'weekday'], 'employee_weekly_shifts_employee_day_index');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('employee_weekly_shifts')) {
            return;
        }

        if ($this->indexExists('employee_weekly_shifts_employee_day_index')) {
            Schema::table('employee_weekly_shifts', function (Blueprint $table): void {
                $table->dropIndex('employee_weekly_shifts_employee_day_index');
            });
        }

        if (! Schema::hasColumn('employee_weekly_shifts', 'shift_id') && Schema::hasTable('shifts')) {
            Schema::table('employee_weekly_shifts', function (Blueprint $table): void {
                $table->foreignId('shift_id')->nullable()->after('employee_id')->constrained('shifts')->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('employee_weekly_shifts', 'start_time')) {
            Schema::table('employee_weekly_shifts', function (Blueprint $table): void {
                $table->dropColumn(['start_time', 'end_time']);
            });
        }
    }

    private function indexExists(string $name): bool
    {
        foreach (Schema::getIndexes('employee_weekly_shifts') as $index) {
            if (($index['name'] ?? '') === $name) {
                return true;
            }
        }

        return false;
    }
};
