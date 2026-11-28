<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Models\Cv;
use Modules\HR\Models\Contract;
use Modules\HR\Models\Interview;
use Modules\HR\Models\JobPosting;
use Modules\HR\Models\Termination;
use Modules\HR\Models\Onboarding;

class RecruitmentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $year     = (int) ($request->input('year', now()->year));
        $yearFrom = "{$year}-01-01";
        $yearTo   = "{$year}-12-31";

        // Basic Statistics
        $stats = [
            // Job Postings
            'total_job_postings'   => JobPosting::whereYear('created_at', $year)->count(),
            'active_job_postings'  => JobPosting::where('status', 'active')->whereYear('created_at', $year)->count(),
            'closed_job_postings'  => JobPosting::where('status', 'closed')->whereYear('created_at', $year)->count(),
            'expired_job_postings' => JobPosting::where('status', 'expired')->whereYear('created_at', $year)->count(),

            // CVs
            'total_cvs'       => Cv::whereYear('created_at', $year)->count(),
            'cvs_this_month'  => Cv::whereYear('created_at', $year)->whereMonth('created_at', now()->month)->count(),
            'cvs_last_month'  => Cv::whereYear('created_at', $year)->whereMonth('created_at', now()->subMonth()->month)->count(),

            // Interviews
            'total_interviews'     => Interview::whereYear('created_at', $year)->count(),
            'scheduled_interviews' => Interview::where('status', 'scheduled')->whereYear('created_at', $year)->count(),
            'completed_interviews' => Interview::where('status', 'completed')->whereYear('created_at', $year)->count(),
            'cancelled_interviews' => Interview::where('status', 'cancelled')->whereYear('created_at', $year)->count(),
            'accepted_interviews'  => Interview::where('result', 'accepted')->whereYear('created_at', $year)->count(),
            'rejected_interviews'  => Interview::where('result', 'rejected')->whereYear('created_at', $year)->count(),
            'pending_interviews'   => Interview::where(fn ($q) => $q->where('result', 'pending')->orWhereNull('result'))->whereYear('created_at', $year)->count(),

            // Contracts
            'total_contracts'      => Contract::whereYear('created_at', $year)->count(),
            'recent_contracts'     => Contract::whereDate('created_at', '>=', now()->subDays(30))->count(),
            'contracts_this_month' => Contract::whereYear('created_at', $year)->whereMonth('created_at', now()->month)->count(),

            // Onboardings
            'total_onboardings'                => Onboarding::whereYear('created_at', $year)->count(),
            'pending_onboardings'              => Onboarding::where('status', 'pending')->whereYear('created_at', $year)->count(),
            'in_progress_onboardings'          => Onboarding::where('status', 'in_progress')->whereYear('created_at', $year)->count(),
            'completed_onboardings'            => Onboarding::where('status', 'completed')->whereYear('created_at', $year)->count(),
            'cancelled_onboardings'            => Onboarding::where('status', 'cancelled')->whereYear('created_at', $year)->count(),
            'employees_created_from_onboarding'=> Onboarding::whereNotNull('employee_id')->whereYear('created_at', $year)->count(),

            // Terminations
            'total_terminations'     => Termination::whereYear('termination_date', $year)->count(),
            'pending_terminations'   => Termination::whereNull('approved_by')->whereYear('termination_date', $year)->count(),
            'terminations_this_month'=> Termination::whereYear('termination_date', $year)->whereMonth('termination_date', now()->month)->count(),

            // Conversion Rates
            'cv_to_interview_rate' => $this->calculateConversionRate(
                Cv::whereYear('created_at', $year)->count(),
                Interview::whereYear('created_at', $year)->count()
            ),
            'interview_to_contract_rate' => $this->calculateConversionRate(
                Interview::where('result', 'accepted')->whereYear('created_at', $year)->count(),
                Contract::whereYear('created_at', $year)->count()
            ),
            'onboarding_to_employee_rate' => $this->calculateConversionRate(
                Onboarding::where('status', 'completed')->whereYear('created_at', $year)->count(),
                Onboarding::whereNotNull('employee_id')->whereYear('created_at', $year)->count()
            ),
        ];

        // Monthly Statistics (12 months of selected year)
        $monthlyStats = $this->getMonthlyStatistics($year);

        // Status Distribution
        $statusDistribution = [
            'job_postings' => [
                'active'  => JobPosting::where('status', 'active')->whereYear('created_at', $year)->count(),
                'closed'  => JobPosting::where('status', 'closed')->whereYear('created_at', $year)->count(),
                'expired' => JobPosting::where('status', 'expired')->whereYear('created_at', $year)->count(),
            ],
            'interviews' => [
                'scheduled'   => Interview::where('status', 'scheduled')->whereYear('created_at', $year)->count(),
                'completed'   => Interview::where('status', 'completed')->whereYear('created_at', $year)->count(),
                'cancelled'   => Interview::where('status', 'cancelled')->whereYear('created_at', $year)->count(),
                'rescheduled' => Interview::where('status', 'rescheduled')->whereYear('created_at', $year)->count(),
            ],
            'onboardings' => [
                'pending'     => Onboarding::where('status', 'pending')->whereYear('created_at', $year)->count(),
                'in_progress' => Onboarding::where('status', 'in_progress')->whereYear('created_at', $year)->count(),
                'completed'   => Onboarding::where('status', 'completed')->whereYear('created_at', $year)->count(),
                'cancelled'   => Onboarding::where('status', 'cancelled')->whereYear('created_at', $year)->count(),
            ],
        ];

        // Interview Results Distribution
        $interviewResults = [
            'accepted' => Interview::where('result', 'accepted')->whereYear('created_at', $year)->count(),
            'rejected' => Interview::where('result', 'rejected')->whereYear('created_at', $year)->count(),
            'pending'  => Interview::where(fn ($q) => $q->where('result', 'pending')->orWhereNull('result'))->whereYear('created_at', $year)->count(),
            'on_hold'  => Interview::where('result', 'on_hold')->whereYear('created_at', $year)->count(),
        ];

        // Termination Types Distribution
        $terminationTypes = [
            'resignation' => Termination::where('termination_type', 'resignation')->whereYear('termination_date', $year)->count(),
            'dismissal'   => Termination::where('termination_type', 'dismissal')->whereYear('termination_date', $year)->count(),
            'death'       => Termination::where('termination_type', 'death')->whereYear('termination_date', $year)->count(),
            'retirement'  => Termination::where('termination_type', 'retirement')->whereYear('termination_date', $year)->count(),
        ];

        // Recent Data
        $upcomingInterviews = Interview::where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->limit(5)
            ->with(['cv', 'jobPosting', 'interviewer'])
            ->get();

        $recentJobPostings = JobPosting::where('status', 'active')
            ->whereYear('created_at', $year)
            ->orderByDesc('created_at')
            ->limit(5)
            ->with('job')
            ->get();

        $recentOnboardings = Onboarding::whereYear('created_at', $year)
            ->orderByDesc('created_at')
            ->limit(5)
            ->with(['cv', 'contract', 'employee'])
            ->get();

        $recentContracts = Contract::whereYear('created_at', $year)
            ->orderByDesc('created_at')
            ->limit(5)
            ->with(['employee', 'contract_type'])
            ->get();

        // Recent Applicants (latest CVs with their interview result as status)
        $recentApplicants = Cv::with([
                'jobPosting',
                'jobPosting.job',
                'interviews' => fn ($q) => $q->latest()->limit(1),
            ])
            ->whereYear('created_at', $year)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Available years for filter dropdown
        $availableYears = collect(range(now()->year, max(now()->year - 4, 2020)))->values();

        return view('recruitment::dashboard.index', compact(
            'stats',
            'monthlyStats',
            'statusDistribution',
            'interviewResults',
            'terminationTypes',
            'upcomingInterviews',
            'recentJobPostings',
            'recentOnboardings',
            'recentContracts',
            'recentApplicants',
            'year',
            'availableYears'
        ));
    }

    private function calculateConversionRate(int $total, int $converted): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($converted / $total) * 100, 2);
    }

    private function getMonthlyStatistics(int $year): array
    {
        $data = [];

        for ($m = 1; $m <= 12; $m++) {
            $label = \Carbon\Carbon::createFromDate($year, $m, 1)->format('M Y');
            $data[] = [
                'month'       => $label,
                'cvs'         => Cv::whereYear('created_at', $year)->whereMonth('created_at', $m)->count(),
                'interviews'  => Interview::whereYear('created_at', $year)->whereMonth('created_at', $m)->count(),
                'contracts'   => Contract::whereYear('created_at', $year)->whereMonth('created_at', $m)->count(),
                'onboardings' => Onboarding::whereYear('created_at', $year)->whereMonth('created_at', $m)->count(),
            ];
        }

        return $data;
    }
}
