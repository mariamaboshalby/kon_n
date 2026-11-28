<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Employee_Evaluation;

class EmployeeEvaluationService
{
    /**
     * @return Collection<int, Employee>
     */
    public function employeesForSelect(): Collection
    {
        return Employee::query()
            ->with(['job:id,title', 'department:id,title'])
            ->select('id', 'name', 'job_id', 'department_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Employee_Evaluation>
     */
    public function list(
        ?string $search     = null,
        ?int    $employeeId = null,
        ?string $finalRating = null,
        ?string $dateFrom   = null,
        ?string $dateTo     = null
    ): Collection {
        return Employee_Evaluation::query()
            ->with(['employee.job', 'employee.department', 'kpis'])
            ->when($search,      fn ($q) => $q->whereHas('employee', fn ($s) => $s->where('name', 'like', "%{$search}%")))
            ->when($employeeId,  fn ($q) => $q->where('employee_id', $employeeId))
            ->when($finalRating, fn ($q) => $q->where('final_rating', $finalRating))
            ->when($dateFrom,    fn ($q) => $q->whereDate('evaluation_date', '>=', $dateFrom))
            ->when($dateTo,      fn ($q) => $q->whereDate('evaluation_date', '<=', $dateTo))
            ->latest()
            ->get();
    }

    public function find(int $id): Employee_Evaluation
    {
        return Employee_Evaluation::with(['employee.job', 'employee.department', 'employee.kpis', 'kpis'])
            ->findOrFail($id);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function employeeKpis(int $employeeId): array
    {
        $employee = Employee::with('kpis')->findOrFail($employeeId);

        return $employee->kpis->map(fn ($kpi) => [
            'id' => $kpi->id,
            'name' => $kpi->name,
            'description' => $kpi->description,
            'weight_percentage' => (float) ($kpi->pivot->weight_percentage ?? 0),
        ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Employee_Evaluation
    {
        return DB::transaction(function () use ($data) {
            $scores = $data['scores'] ?? [];
            $notes = $data['notes'] ?? [];
            unset($data['scores'], $data['notes']);

            $computed = $this->computeScoreAndRating((int) $data['employee_id'], $scores);
            $data['total_score'] = $computed['total_score'];
            $data['final_rating'] = $computed['final_rating'];

            $evaluation = Employee_Evaluation::create($data);
            $this->syncKpiScores($evaluation, $scores, $notes);

            return $evaluation->load(['employee.job', 'employee.department', 'kpis']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Employee_Evaluation $evaluation, array $data): Employee_Evaluation
    {
        return DB::transaction(function () use ($evaluation, $data) {
            $scores = $data['scores'] ?? [];
            $notes = $data['notes'] ?? [];
            unset($data['scores'], $data['notes']);

            $computed = $this->computeScoreAndRating((int) $data['employee_id'], $scores);
            $data['total_score'] = $computed['total_score'];
            $data['final_rating'] = $computed['final_rating'];

            $evaluation->update($data);
            $evaluation->kpis()->detach();
            $this->syncKpiScores($evaluation, $scores, $notes);

            return $evaluation->fresh(['employee.job', 'employee.department', 'kpis']);
        });
    }

    public function delete(Employee_Evaluation $evaluation): void
    {
        DB::transaction(function () use ($evaluation) {
            $evaluation->kpis()->detach();
            $evaluation->delete();
        });
    }

    /**
     * @param  array<int|string, mixed>  $scores
     * @return array{total_score: float, final_rating: string}
     */
    public function computeScoreAndRating(int $employeeId, array $scores): array
    {
        $employee = Employee::findOrFail($employeeId);
        $totalScore = 0.0;

        foreach ($scores as $kpiId => $score) {
            $kpi = $employee->kpis()->where('kpi_id', $kpiId)->first();
            if (! $kpi) {
                continue;
            }
            $weight = (float) $kpi->pivot->weight_percentage;
            $totalScore += ((float) $score / 100) * $weight;
        }

        $totalScore = round(min($totalScore, 100), 2);

        return [
            'total_score' => $totalScore,
            'final_rating' => $this->ratingLabel($totalScore),
        ];
    }

    private function ratingLabel(float $totalScore): string
    {
        return match (true) {
            $totalScore >= 85 => __('hr::hr.excellent'),
            $totalScore >= 75 => __('hr::hr.very_good'),
            $totalScore >= 60 => __('hr::hr.good'),
            $totalScore >= 50 => __('hr::hr.acceptable'),
            $totalScore >= 35 => __('hr::hr.weak'),
            $totalScore >= 20 => __('hr::hr.very_weak'),
            default => __('hr::hr.unacceptable'),
        };
    }

    /**
     * @param  array<int|string, mixed>  $scores
     * @param  array<int|string, mixed>  $notes
     */
    private function syncKpiScores(Employee_Evaluation $evaluation, array $scores, array $notes): void
    {
        foreach ($scores as $kpiId => $score) {
            $evaluation->kpis()->attach((int) $kpiId, [
                'score' => $score,
                'notes' => $notes[$kpiId] ?? '',
            ]);
        }
    }
}
