<?php

declare(strict_types=1);

namespace Modules\HR\Services\Portal;

use Modules\HR\Models\Employee;
use Modules\HR\Models\HRSetting;
use Modules\Progress\Models\ProjectProgress;

final class GeofenceService
{
    private const DEFAULT_RADIUS = 200;

    public function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * إحداثيات الشركة: hr_settings أولاً ثم جدول settings (المصدر القديم).
     *
     * @return array{latitude: float, longitude: float, radius: int, label: string, source: string}|null
     */
    public function companyLocation(): ?array
    {
        $hr = HRSetting::getCompanyDefault();

        if ($hr && $hr->company_latitude !== null && $hr->company_longitude !== null) {
            return [
                'latitude' => (float) $hr->company_latitude,
                'longitude' => (float) $hr->company_longitude,
                'radius' => (int) ($hr->company_location_radius ?: self::DEFAULT_RADIUS),
                'label' => __('hr::portal.company_location'),
                'source' => 'company',
            ];
        }

        $legacy = HRSetting::getCompanyLocation();
        if ($legacy === null) {
            return null;
        }

        return [
            'latitude' => (float) $legacy['latitude'],
            'longitude' => (float) $legacy['longitude'],
            'radius' => (int) ($legacy['radius'] ?: self::DEFAULT_RADIUS),
            'label' => __('hr::portal.company_location'),
            'source' => 'company',
        ];
    }

    /**
     * @return array{latitude: float, longitude: float, radius: int, label: string, source: string}|null
     */
    public function projectLocation(int $projectId): ?array
    {
        // قبول مشاريع وحدة المشاريع (is_progress=0) ومشاريع لوحة Progress معًا
        $project = ProjectProgress::query()->withoutGlobalScope('progressOnly')->find($projectId);
        if (! $project || ! $project->hasLocation()) {
            return null;
        }

        $data = $project->getLocationData();

        return [
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'radius' => (int) ($data['radius'] ?: self::DEFAULT_RADIUS),
            'label' => (string) $project->name,
            'source' => 'project',
        ];
    }

    /**
     * @return array{latitude: float, longitude: float, radius: int, label: string, source: string}|null
     */
    public function resolveTarget(Employee $employee, ?int $projectId): ?array
    {
        if ($employee->location_type === 'project') {
            return $projectId ? $this->projectLocation($projectId) : null;
        }

        return $this->companyLocation();
    }

    /**
     * مشاريع لها إحداثيات — للموظف ذو نوع موقع «مشروع».
     *
     * @return list<array{id: int, name: string, code: string|null}>
     */
    public function projectsWithLocation(Employee $employee): array
    {
        $query = ProjectProgress::query()
            ->withoutGlobalScope('progressOnly')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        $accountId = $employee->account?->id;
        if ($accountId) {
            $own = (clone $query)->where('account_id', $accountId)->get(['id', 'name', 'project_code']);
            if ($own->isNotEmpty()) {
                return $own->map(fn ($p) => [
                    'id' => (int) $p->id,
                    'name' => (string) $p->name,
                    'code' => $p->project_code,
                ])->values()->all();
            }
        }

        return $query->orderBy('name')->limit(200)->get(['id', 'name', 'project_code'])
            ->map(fn ($p) => [
                'id' => (int) $p->id,
                'name' => (string) $p->name,
                'code' => $p->project_code,
            ])->values()->all();
    }
}
