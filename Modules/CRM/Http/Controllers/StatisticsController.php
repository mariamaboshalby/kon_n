<?php

namespace Modules\CRM\Http\Controllers;

use Carbon\Carbon;
use App\Models\{Client, User};
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Enums\{TaskStatusEnum, TaskPriorityEnum};
use Modules\CRM\Models\{Lead, Task, ChanceSource, LeadStatus, ClientContact, Activity, Ticket, ReturnOrder};

class StatisticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view CRM Statistics')->only(['index']);
    }

    public function index(Request $request)
    {
        $period = $request->get('period', 'this_month');
        $userId = $request->get('user_id');
        
        $dateRange = $this->getDateRange($period);
        $users = User::select('id', 'name')->get();
        
        $statistics = $this->getStatistics($dateRange, $userId);
        
        return view('crm::statistics.index', compact('statistics', 'users', 'period', 'userId'));
    }
    
    private function getDateRange($period)
    {
        $now = Carbon::now();
        
        return match($period) {
            'all' => [
                'start' => Carbon::createFromTimestamp(0),
                'end' => Carbon::now()->endOfDay(),
            ],
            'this_month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'this_quarter' => [
                'start' => $now->copy()->startOfQuarter(),
                'end' => $now->copy()->endOfQuarter(),
            ],
            'this_year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
        };
    }

    public function getStatistics($dateRange, $userId = null)
    {
        return [
            'clients' => $this->getClientStatistics($dateRange, $userId),
            'leads' => $this->getLeadStatistics($dateRange, $userId),
            'tasks' => $this->getTaskStatistics($dateRange, $userId),
            'sources' => $this->getSourceStatistics($dateRange, $userId),
            'contacts' => $this->getContactStatistics($dateRange, $userId),
            'activities' => $this->getActivityStatistics($dateRange, $userId),
            'tickets' => $this->getTicketStatistics($dateRange, $userId),
            'returns' => $this->getReturnStatistics($dateRange, $userId),
        ];
    }

    private function getClientStatistics($dateRange, $userId = null)
    {
        $clientStats = Client::leftJoin('client_types', 'clients.client_type_id', '=', 'client_types.id')
            ->selectRaw('
        COUNT(*) as total,
        SUM(CASE WHEN client_types.title = "person" THEN 1 ELSE 0 END) as person_count,
        SUM(CASE WHEN client_types.title = "company" THEN 1 ELSE 0 END) as company_count,
        SUM(CASE WHEN clients.created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_this_period
    ', [$dateRange['start'], $dateRange['end']])
            ->when($userId, fn($q) => $q->where('clients.assigned_user_id', $userId))
            ->first();

        $activeClients = Client::whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('leads')
                ->join('lead_statuses', 'leads.status_id', '=', 'lead_statuses.id')
                ->whereColumn('clients.id', 'leads.client_id')
                ->where('lead_statuses.name', '!=', 'مغلق');
        })->when($userId, fn($q) => $q->where('assigned_user_id', $userId))->count();

        return [
            'total' => $clientStats->total,
            'person' => $clientStats->person_count,
            'company' => $clientStats->company_count,
            'new_this_month' => $clientStats->new_this_period,
            'active' => $activeClients,
            'person_percentage' => $clientStats->total > 0 ? round(($clientStats->person_count / $clientStats->total) * 100, 1) : 0,
            'company_percentage' => $clientStats->total > 0 ? round(($clientStats->company_count / $clientStats->total) * 100, 1) : 0,
        ];
    }

    private function getLeadStatistics($dateRange, $userId = null)
    {
        $leadStats = Lead::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->when($userId, fn($q) => $q->where('assigned_to', $userId))
            ->selectRaw('
                COUNT(*) as total,
                COALESCE(SUM(amount), 0) as total_value
            ')
            ->first();

        $averageValue = $leadStats->total > 0 ? $leadStats->total_value / $leadStats->total : 0;

        $leadsByStatus = LeadStatus::leftJoin('leads', function ($join) use ($dateRange, $userId) {
                $join->on('lead_statuses.id', '=', 'leads.status_id')
                    ->whereBetween('leads.created_at', [$dateRange['start'], $dateRange['end']]);
                if ($userId) {
                    $join->where('leads.assigned_to', $userId);
                }
            })
            ->select(
                'lead_statuses.id',
                'lead_statuses.name',
                'lead_statuses.color',
                'lead_statuses.branch_id',
                DB::raw('COUNT(leads.id) as leads_count')
            )
            ->groupBy('lead_statuses.id', 'lead_statuses.name', 'lead_statuses.color', 'lead_statuses.branch_id')
            ->get();

        $wonLeads = Lead::join('lead_statuses', 'leads.status_id', '=', 'lead_statuses.id')
            ->where('lead_statuses.name', 'مغلق - فوز')
            ->whereBetween('leads.created_at', [$dateRange['start'], $dateRange['end']])
            ->when($userId, fn($q) => $q->where('leads.assigned_to', $userId))
            ->count();

        $successRate = $leadStats->total > 0 ? round(($wonLeads / $leadStats->total) * 100, 1) : 0;

        return [
            'total' => $leadStats->total,
            'total_value' => $leadStats->total_value,
            'average_value' => round($averageValue, 2),
            'new_this_month' => $leadStats->total,
            'won_leads' => $wonLeads,
            'success_rate' => $successRate,
            'by_status' => $leadsByStatus,
        ];
    }

    private function getTaskStatistics($dateRange, $userId = null)
    {
        $now = Carbon::now();
        $today = Carbon::today();

        $taskStats = Task::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->when($userId, fn($q) => $q->where(function($q) use ($userId) {
                $q->where('target_user_id', $userId)->orWhere('user_id', $userId);
            }))
            ->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN priority = ? THEN 1 ELSE 0 END) as urgent,
            SUM(CASE WHEN priority = ? THEN 1 ELSE 0 END) as medium,
            SUM(CASE WHEN priority = ? THEN 1 ELSE 0 END) as low,
            SUM(CASE WHEN due_date < ? AND status != ? THEN 1 ELSE 0 END) as overdue,
            SUM(CASE WHEN DATE(due_date) = ? AND status != ? THEN 1 ELSE 0 END) as due_today
        ', [
            TaskStatusEnum::PENDING->value,
            TaskStatusEnum::IN_PROGRESS->value,
            TaskStatusEnum::COMPLETED->value,
            TaskStatusEnum::CANCELLED->value,
            TaskPriorityEnum::HIGH->value,
            TaskPriorityEnum::MEDIUM->value,
            TaskPriorityEnum::LOW->value,
            $now,
            TaskStatusEnum::COMPLETED->value,
            $today->format('Y-m-d'),
            TaskStatusEnum::COMPLETED->value
        ])->first();

        $tasksByStatus = [];
        $statusCounts = [
            TaskStatusEnum::PENDING->value => $taskStats->pending,
            TaskStatusEnum::IN_PROGRESS->value => $taskStats->in_progress,
            TaskStatusEnum::COMPLETED->value => $taskStats->completed,
            TaskStatusEnum::CANCELLED->value => $taskStats->cancelled,
        ];

        foreach (TaskStatusEnum::cases() as $status) {
            $count = $statusCounts[$status->value] ?? 0;
            $tasksByStatus[] = [
                'status' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
                'count' => $count,
                'percentage' => $taskStats->total > 0 ? round(($count / $taskStats->total) * 100, 1) : 0
            ];
        }

        $tasksByPriority = [];
        $priorityCounts = [
            TaskPriorityEnum::HIGH->value => $taskStats->urgent,
            TaskPriorityEnum::MEDIUM->value => $taskStats->medium,
            TaskPriorityEnum::LOW->value => $taskStats->low,
        ];

        foreach (TaskPriorityEnum::cases() as $priority) {
            $count = $priorityCounts[$priority->value] ?? 0;
            $tasksByPriority[] = [
                'priority' => $priority->value,
                'label' => $priority->label(),
                'color' => $priority->color(),
                'count' => $count,
                'percentage' => $taskStats->total > 0 ? round(($count / $taskStats->total) * 100, 1) : 0
            ];
        }

        return [
            'total' => $taskStats->total,
            'by_status' => $tasksByStatus,
            'by_priority' => $tasksByPriority,
            'overdue' => $taskStats->overdue,
            'due_today' => $taskStats->due_today,
        ];
    }

    private function getSourceStatistics($dateRange, $userId = null)
    {
        $sources = ChanceSource::leftJoin('leads', function ($join) use ($dateRange, $userId) {
                $join->on('chance_sources.id', '=', 'leads.source_id')
                    ->whereBetween('leads.created_at', [$dateRange['start'], $dateRange['end']]);
                if ($userId) {
                    $join->where('leads.assigned_to', $userId);
                }
            })
            ->selectRaw('chance_sources.title, COUNT(leads.id) as leads_count')
            ->groupBy('chance_sources.id', 'chance_sources.title')
            ->orderBy('leads_count', 'desc')
            ->get();

        $totalLeads = $sources->sum('leads_count');

        $sourceStats = $sources->map(function ($source) use ($totalLeads) {
            return [
                'title' => $source->title,
                'count' => $source->leads_count,
                'percentage' => $totalLeads > 0 ? round(($source->leads_count / $totalLeads) * 100, 1) : 0
            ];
        });

        return [
            'sources' => $sourceStats,
            'most_productive' => $sourceStats->first(),
        ];
    }

    private function getContactStatistics($dateRange, $userId = null)
    {
        $contactStats = ClientContact::selectRaw('
            COUNT(*) as total_contacts,
            COUNT(DISTINCT client_id) as unique_clients
        ')->first();

        $averageContactsPerClient = $contactStats->unique_clients > 0
            ? round($contactStats->total_contacts / $contactStats->unique_clients, 1)
            : 0;

        return [
            'total' => $contactStats->total_contacts,
            'average_per_client' => $averageContactsPerClient,
        ];
    }
    
    private function getActivityStatistics($dateRange, $userId = null)
    {
        $query = Activity::whereBetween('activity_date', [$dateRange['start'], $dateRange['end']]);
        
        if ($userId) {
            $query->where('assigned_to', $userId);
        }
        
        $activities = $query->with(['client', 'assignedUser'])->get();
        
        $byType = $activities->groupBy(fn($a) => $a->type->label())->map(fn($items) => $items->count());
        $byUser = $activities->groupBy('assigned_to')->map(function($items) {
            return [
                'count' => $items->count(),
                'user' => $items->first()->assignedUser->name ?? 'غير محدد'
            ];
        });
        
        return [
            'total' => $activities->count(),
            'by_type' => $byType,
            'by_user' => $byUser,
            'list' => $activities->take(10)
        ];
    }
    
    private function getTicketStatistics($dateRange, $userId = null)
    {
        $query = Ticket::whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        
        if ($userId) {
            $query->where('assigned_to', $userId);
        }
        
        $tickets = $query->with(['client', 'assignedTo'])->get();
        
        $byStatus = $tickets->groupBy('status')->map(fn($items) => $items->count());
        $byPriority = $tickets->groupBy('priority')->map(fn($items) => $items->count());
        $byUser = $tickets->groupBy('assigned_to')->map(function($items) {
            return [
                'count' => $items->count(),
                'user' => $items->first()->assignedTo->name ?? 'غير محدد'
            ];
        });
        
        return [
            'total' => $tickets->count(),
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
            'by_user' => $byUser,
            'list' => $tickets->take(10)
        ];
    }
    
    private function getReturnStatistics($dateRange, $userId = null)
    {
        $query = ReturnOrder::whereBetween('return_date', [$dateRange['start'], $dateRange['end']]);
        
        if ($userId) {
            $query->where('created_by', $userId);
        }
        
        $returns = $query->with(['client', 'createdBy'])->get();
        
        $byStatus = $returns->groupBy('status')->map(fn($items) => $items->count());
        $byType = $returns->groupBy('return_type')->map(fn($items) => $items->count());
        $totalAmount = $returns->sum('total_amount');
        $totalRefund = $returns->sum('refund_amount');
        
        return [
            'total' => $returns->count(),
            'by_status' => $byStatus,
            'by_type' => $byType,
            'total_amount' => $totalAmount,
            'total_refund' => $totalRefund,
            'list' => $returns->take(10)
        ];
    }
}
