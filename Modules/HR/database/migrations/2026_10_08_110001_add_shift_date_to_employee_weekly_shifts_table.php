<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const WEEKDAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    public function up(): void
    {
        if (! Schema::hasTable('employee_weekly_shifts')) {
            return;
        }

        if (! Schema::hasColumn('employee_weekly_shifts', 'shift_date')) {
            Schema::table('employee_weekly_shifts', function (Blueprint $table): void {
                $table->date('shift_date')->nullable()->after('weekday');
            });
        }

        if (! $this->indexExists('employee_weekly_shifts_date_employee_index')) {
            Schema::table('employee_weekly_shifts', function (Blueprint $table): void {
                $table->index(['shift_date', 'employee_id'], 'employee_weekly_shifts_date_employee_index');
            });
        }

        $dates = $this->currentWeekDates();

        DB::table('employee_weekly_shifts')
            ->whereNull('shift_date')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($dates): void {
                foreach ($rows as $row) {
                    $date = $dates[(string) $row->weekday] ?? null;
                    if ($date === null) {
                        continue;
                    }

                    DB::table('employee_weekly_shifts')->where('id', $row->id)->update(['shift_date' => $date]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('employee_weekly_shifts')) {
            return;
        }

        if ($this->indexExists('employee_weekly_shifts_date_employee_index')) {
            Schema::table('employee_weekly_shifts', function (Blueprint $table): void {
                $table->dropIndex('employee_weekly_shifts_date_employee_index');
            });
        }

        if (Schema::hasColumn('employee_weekly_shifts', 'shift_date')) {
            Schema::table('employee_weekly_shifts', function (Blueprint $table): void {
                $table->dropColumn('shift_date');
            });
        }
    }

    /** @return array<string, string> */
    private function currentWeekDates(): array
    {
        $startDay = 'saturday';
        if (Schema::hasTable('hr_settings') && Schema::hasColumn('hr_settings', 'shift_week_start_day')) {
            $value = DB::table('hr_settings')->orderBy('id')->value('shift_week_start_day');
            if (is_string($value) && in_array($value, self::WEEKDAYS, true)) {
                $startDay = $value;
            }
        }

        $weekStart = CarbonImmutable::today()->startOfWeek(array_search($startDay, self::WEEKDAYS, true));
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $weekStart->addDays($i);
            $dates[strtolower($day->englishDayOfWeek)] = $day->toDateString();
        }

        return $dates;
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
