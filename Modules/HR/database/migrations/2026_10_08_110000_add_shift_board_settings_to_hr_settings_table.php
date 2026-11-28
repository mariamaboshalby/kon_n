<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @return array<string, callable(Blueprint): mixed> */
    private function columns(): array
    {
        return [
            'shift_day_start_hour' => fn (Blueprint $t) => $t->unsignedTinyInteger('shift_day_start_hour')->default(0),
            'shift_day_end_hour' => fn (Blueprint $t) => $t->unsignedTinyInteger('shift_day_end_hour')->default(24),
            'shift_week_start_day' => fn (Blueprint $t) => $t->string('shift_week_start_day', 16)->default('saturday'),
        ];
    }

    public function up(): void
    {
        if (! Schema::hasTable('hr_settings')) {
            return;
        }

        Schema::table('hr_settings', function (Blueprint $table): void {
            foreach ($this->columns() as $name => $definition) {
                if (! Schema::hasColumn('hr_settings', $name)) {
                    $definition($table);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('hr_settings')) {
            return;
        }

        Schema::table('hr_settings', function (Blueprint $table): void {
            $existing = array_values(array_filter(
                array_keys($this->columns()),
                fn (string $c) => Schema::hasColumn('hr_settings', $c)
            ));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
