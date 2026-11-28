<?php

declare(strict_types=1);

namespace Modules\HR\Services;

/**
 * Calculates the Day-Off / Absent / Extra-Day breakdown for a period.
 *
 * Quota arithmetic only. The caller decides which days are missed.
 *   day_off_used = min(quota, missed days)
 *   extra_days   = unused quota — reported only, not paid
 * SalaryCalculationService spends the quota on working-day absences first, then weekly rest.
 */
class AttendanceImpactCalculator
{
    /**
     * @param  int  $dayOffQuota       $employee->days_off  (total allocated for this period)
     * @param  int  $missedWorkingDays Number of working-day slots with no attendance, leave, errand, or permission
     * @return array{
     *     day_off_used:  int,   // quota consumed to cover missed days
     *     absent_days:   int,   // missed days after quota ran out
     *     extra_days:    int,   // unused quota; not paid
     * }
     */
    public function calculate(int $dayOffQuota, int $missedWorkingDays): array
    {
        // How many missed days can be covered by the quota?
        $dayOffUsed = min($dayOffQuota, $missedWorkingDays);

        // Whatever is left after covering with quota → real absence
        $absentDays = max(0, $missedWorkingDays - $dayOffUsed);

        // Whatever quota was not needed → bonus / extra days
        $extraDays = max(0, $dayOffQuota - $dayOffUsed);

        return [
            'day_off_used' => $dayOffUsed,
            'absent_days'  => $absentDays,
            'extra_days'   => $extraDays,
        ];
    }

    /**
     * Legacy shim — kept so existing callers don't break while they migrate.
     * Returns the old single-integer "net impact" value.
     *
     * @deprecated  Use calculate() instead.
     */
    public function calculateLegacy(int $sdo, int $do, int $ad): int
    {
        return max($sdo, $do) - $ad;
    }
}
