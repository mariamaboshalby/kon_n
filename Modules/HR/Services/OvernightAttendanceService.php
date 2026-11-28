<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\HR\Models\Shift;

/**
 * إعادة نسب بصمات الانصراف التي تقع بعد منتصف الليل ليوم العمل الصحيح.
 *
 * نفس قاعدة SalaryCalculationService::processAttendanceData(): في الورديات
 * الليلية (end_time < start_time) البصمة check_out المُخزَّنة بتاريخ اليوم
 * التالي تتنقل لمجموعة يوم الشغل (تاريخ الـ check_in) لو وقتها داخل نافذة
 * نهاية الوردية + هامش ساعتين للأضافي، عشان التقارير اليومية ما تُعرضش
 * "صف شبح" فيه انصراف يتيم يوم راحة/غياب.
 */
class OvernightAttendanceService
{
    /**
     * تجميع بصمات موظف واحد بتاريخ يوم العمل (Y-m-d) مع إعادة نسب انصراف
     * ما بعد منتصف الليل لليوم السابق.
     *
     * @param  Collection  $attendances  بصمات موظف واحد مرتبة بتاريخها
     * @return array<string, Collection>  map تاريخ يوم العمل => بصماته
     */
    public static function groupByWorkday(Collection $attendances, ?Shift $shift): array
    {
        // نفس ملاحظة SalaryCalculationService: الـ cast يرجع Carbon؛ استخدم
        // toDateString مباشرة وتفادَ Carbon::parse عليه ليلا من إعادة تفسير التوقيت.
        $byDate = $attendances->groupBy(function ($attendance) {
            $date = $attendance->date;

            return $date instanceof \Carbon\Date
                ? $date->toDateString()
                : Carbon::parse($date)->toDateString();
        })->map(fn ($group) => collect($group)->all())->all();

        if ($shift && $shift->isOvernightShift() && $shift->end_time) {
            $shiftEnd = Carbon::parse('2000-01-01 '.$shift->end_time);

            // المرور بالترتيب الزمني حتى تستقر كل بصمة على يوم شغل نهائي
            // مش متعالج مرة تانية (يمنع القفز لأكثر من يوم واحد لورا).
            ksort($byDate);

            foreach (array_keys($byDate) as $dateStr) {
                $prevDate = Carbon::parse($dateStr)->subDay()->format('Y-m-d');

                // يوم الشغل السابق لازم يكون موجود وفيه check_in فعلي،
                // وإلا الانصراف هيقفز على يوم راحة/غياب فاضي (صف شبح).
                $prevIsWorkday = isset($byDate[$prevDate])
                    && collect($byDate[$prevDate])->contains(fn ($r) => $r->type === 'check_in');
                if (! $prevIsWorkday) {
                    continue;
                }

                foreach ($byDate[$dateStr] as $record) {
                    if ($record->type !== 'check_out') {
                        continue;
                    }

                    $checkOutTime = Carbon::parse('2000-01-01 '.substr((string) $record->time, 0, 8));

                    // انصراف داخل نافذة نهاية الوردية الليلية (+ ساعتان إضافي) →
                    // هو نهاية وردية الأمس مش بصمة اليوم ده.
                    if ($checkOutTime->lte($shiftEnd->copy()->addHours(2))) {
                        $byDate[$prevDate][] = $record;
                        $byDate[$dateStr] = collect($byDate[$dateStr])
                            ->reject(fn ($r) => $r->id === $record->id)
                            ->all();
                    }
                }
            }
        }

        //ارجاع كل مجموعة كـ Collection عشان المستدعي يستخدم where/sortBy عليها.
        return array_map(fn ($group) => collect($group), $byDate);
    }
}
