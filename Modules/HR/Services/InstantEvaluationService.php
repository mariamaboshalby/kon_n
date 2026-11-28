<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeInstantEvaluation;
use Modules\HR\Models\InstantEvaluationItem;

class InstantEvaluationService
{
    /**
     * @return \Illuminate\Database\Eloquent\Builder<Employee>
     */
    private function evaluableEmployeesQuery()
    {
        return Employee::query()
            ->whereNull('date_of_fire')
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', '!=', 'خارج الشركة');
            });
    }

    /**
     * @return Collection<int, Employee>
     */
    public function employeesForSelect(): Collection
    {
        return $this->evaluableEmployeesQuery()
            ->with(['job:id,title', 'department:id,title'])
            ->select('id', 'name', 'job_id', 'department_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Employee>
     */
    public function searchEmployees(string $query, int $limit = 20): Collection
    {
        $query = trim($query);
        $limit = max(1, min($limit, 50));

        $builder = $this->evaluableEmployeesQuery()
            ->with(['job:id,title', 'department:id,title'])
            ->select('id', 'name', 'job_id', 'department_id', 'finger_print_id', 'code');

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('finger_print_id', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%");
            });
        }

        return $builder->orderBy('name')->limit($limit)->get();
    }

    /**
     * @return LengthAwarePaginator<int, EmployeeInstantEvaluation>
     */
    public function list(?string $search = null, ?int $employeeId = null, ?string $polarity = null, int $perPage = 50, ?string $dateFrom = null, ?string $dateTo = null): LengthAwarePaginator
    {
        return EmployeeInstantEvaluation::query()
            ->with([
                'employee:id,name,job_id,department_id',
                'employee.job:id,title',
                'employee.department:id,title',
                'evaluator:id,name',
                'item:id,name,polarity',
            ])
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($polarity,   fn ($q) => $q->where('polarity', $polarity))
            ->when($dateFrom,   fn ($q) => $q->whereDate('evaluated_at', '>=', $dateFrom))
            ->when($dateTo,     fn ($q) => $q->whereDate('evaluated_at', '<=', $dateTo))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->whereHas('employee', fn ($eq) => $eq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('evaluator', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('item', fn ($iq) => $iq->where('name', 'like', "%{$search}%"))
                        ->orWhere('note', 'like', "%{$search}%");
                });
            })
            ->latest('evaluated_at')
            ->paginate($perPage);
    }

    /**
     * @return array{items: list<array<string, mixed>>, average_score: float|null, total: int, positive_total: int, negative_total: int}
     */
    public function forEmployee(int $employeeId, int $limit = 50): array
    {
        $rows = EmployeeInstantEvaluation::query()
            ->with(['evaluator:id,name', 'item:id,name,polarity'])
            ->where('employee_id', $employeeId)
            ->latest('evaluated_at')
            ->limit(max(1, min($limit, 100)))
            ->get();

        $average = $rows->isEmpty()
            ? null
            : round((float) $rows->avg('score'), 2);

        return [
            'items' => $rows->map(fn (EmployeeInstantEvaluation $row) => $this->toArray($row))->all(),
            'average_score' => $average,
            'total' => $rows->count(),
            'positive_total' => $rows->where('score', '>', 0)->sum('score'),
            'negative_total' => $rows->where('score', '<', 0)->sum('score'),
        ];
    }

    /**
     * @return array{
     *     totals: array{all: int, positive: int, negative: int, sum_positive: int, sum_negative: int},
     *     top_positive: list<array<string, mixed>>,
     *     top_negative: list<array<string, mixed>>,
     *     recent: list<array<string, mixed>>
     * }
     */
    public function dashboard(int $top = 10, int $recent = 20): array
    {
        $totals = [
            'all' => (int) EmployeeInstantEvaluation::query()->count(),
            'positive' => (int) EmployeeInstantEvaluation::query()->where('score', '>', 0)->count(),
            'negative' => (int) EmployeeInstantEvaluation::query()->where('score', '<', 0)->count(),
            'sum_positive' => (int) EmployeeInstantEvaluation::query()->where('score', '>', 0)->sum('score'),
            'sum_negative' => (int) EmployeeInstantEvaluation::query()->where('score', '<', 0)->sum('score'),
        ];

        $topPositive = EmployeeInstantEvaluation::query()
            ->select('employee_id', DB::raw('SUM(score) as score_sum'), DB::raw('COUNT(*) as evaluations_count'), DB::raw('AVG(score) as score_avg'))
            ->where('score', '>', 0)
            ->groupBy('employee_id')
            ->orderByDesc('score_sum')
            ->limit($top)
            ->with(['employee:id,name,job_id', 'employee.job:id,title'])
            ->get()
            ->map(fn ($row) => [
                'employee_id' => (int) $row->employee_id,
                'employee_name' => $row->employee?->name,
                'job_title' => $row->employee?->job?->title,
                'score_sum' => (int) $row->score_sum,
                'score_avg' => round((float) $row->score_avg, 2),
                'evaluations_count' => (int) $row->evaluations_count,
            ])->all();

        $topNegative = EmployeeInstantEvaluation::query()
            ->select('employee_id', DB::raw('SUM(score) as score_sum'), DB::raw('COUNT(*) as evaluations_count'), DB::raw('AVG(score) as score_avg'))
            ->where('score', '<', 0)
            ->groupBy('employee_id')
            ->orderBy('score_sum') // الأكثر سالبية أولاً
            ->limit($top)
            ->with(['employee:id,name,job_id', 'employee.job:id,title'])
            ->get()
            ->map(fn ($row) => [
                'employee_id' => (int) $row->employee_id,
                'employee_name' => $row->employee?->name,
                'job_title' => $row->employee?->job?->title,
                'score_sum' => (int) $row->score_sum,
                'score_avg' => round((float) $row->score_avg, 2),
                'evaluations_count' => (int) $row->evaluations_count,
            ])->all();

        $recentRows = EmployeeInstantEvaluation::query()
            ->with([
                'employee:id,name',
                'evaluator:id,name',
                'item:id,name,polarity',
            ])
            ->latest('evaluated_at')
            ->limit($recent)
            ->get();

        return [
            'totals' => $totals,
            'top_positive' => $topPositive,
            'top_negative' => $topNegative,
            'recent' => $recentRows->map(fn (EmployeeInstantEvaluation $row) => $this->toArray($row))->all(),
        ];
    }

    public function find(int $id): EmployeeInstantEvaluation
    {
        return EmployeeInstantEvaluation::with([
            'employee:id,name,job_id,department_id',
            'employee.job:id,title',
            'employee.department:id,title',
            'evaluator:id,name',
            'item:id,name,polarity',
        ])->findOrFail($id);
    }

    /**
     * @param  array{employee_id: int, polarity: string, score: int, item_id: int, note?: string|null, client_request_id?: string|null}  $data
     */
    public function create(array $data, User $evaluator): EmployeeInstantEvaluation
    {
        $polarity = (string) $data['polarity'];
        if (! in_array($polarity, [InstantEvaluationItem::POLARITY_POSITIVE, InstantEvaluationItem::POLARITY_NEGATIVE], true)) {
            throw ValidationException::withMessages([
                'polarity' => [__('hr::hr.instant_evaluation_polarity_invalid')],
            ]);
        }

        $magnitude = abs((int) $data['score']);
        if ($magnitude < 1 || $magnitude > 5) {
            throw ValidationException::withMessages([
                'score' => [__('hr::hr.instant_evaluation_score_invalid')],
            ]);
        }

        $signedScore = $polarity === InstantEvaluationItem::POLARITY_POSITIVE ? $magnitude : -$magnitude;

        $item = InstantEvaluationItem::query()->find((int) $data['item_id']);
        if ($item === null || ! $item->is_active) {
            throw ValidationException::withMessages([
                'item_id' => [__('hr::hr.instant_evaluation_item_required')],
            ]);
        }
        if ($item->polarity !== $polarity) {
            throw ValidationException::withMessages([
                'item_id' => [__('hr::hr.instant_evaluation_item_polarity_mismatch')],
            ]);
        }

        $employee = $this->evaluableEmployeesQuery()->find((int) $data['employee_id']);
        if ($employee === null) {
            throw ValidationException::withMessages([
                'employee_id' => [__('hr::hr.instant_evaluation_employee_inactive')],
            ]);
        }

        $clientRequestId = isset($data['client_request_id']) && is_string($data['client_request_id'])
            ? trim($data['client_request_id'])
            : null;
        if ($clientRequestId === '') {
            $clientRequestId = null;
        }

        if ($clientRequestId !== null) {
            $existing = EmployeeInstantEvaluation::query()
                ->where('client_request_id', $clientRequestId)
                ->first();
            if ($existing !== null) {
                return $existing->load(['employee:id,name', 'evaluator:id,name', 'item:id,name,polarity']);
            }
        }

        return DB::transaction(function () use ($data, $evaluator, $polarity, $signedScore, $clientRequestId, $item) {
            $evaluation = EmployeeInstantEvaluation::create([
                'employee_id' => (int) $data['employee_id'],
                'evaluator_user_id' => (int) $evaluator->id,
                'polarity' => $polarity,
                'item_id' => $item->id,
                'score' => $signedScore,
                'note' => isset($data['note']) ? (trim((string) $data['note']) ?: null) : null,
                'evaluated_at' => now(),
                'client_request_id' => $clientRequestId,
            ]);

            return $evaluation->load([
                'employee:id,name,job_id,department_id',
                'employee.job:id,title',
                'employee.department:id,title',
                'evaluator:id,name',
                'item:id,name,polarity',
            ]);
        });
    }

    public function delete(EmployeeInstantEvaluation $evaluation): void
    {
        $evaluation->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(EmployeeInstantEvaluation $evaluation): array
    {
        $score = (int) $evaluation->score;
        $polarity = $evaluation->polarity
            ?: ($score < 0 ? InstantEvaluationItem::POLARITY_NEGATIVE : InstantEvaluationItem::POLARITY_POSITIVE);

        return [
            'id' => $evaluation->id,
            'employee_id' => $evaluation->employee_id,
            'employee_name' => $evaluation->employee?->name,
            'job_title' => $evaluation->employee?->job?->title,
            'department' => $evaluation->employee?->department?->title,
            'evaluator_user_id' => $evaluation->evaluator_user_id,
            'evaluator_name' => $evaluation->evaluator?->name,
            'polarity' => $polarity,
            'item_id' => $evaluation->item_id,
            'item_name' => $evaluation->item?->name,
            'score' => $score,
            'score_abs' => abs($score),
            'note' => $evaluation->note,
            'evaluated_at' => optional($evaluation->evaluated_at)?->toIso8601String(),
            'evaluated_at_label' => optional($evaluation->evaluated_at)?->format('Y-m-d H:i'),
        ];
    }
}
