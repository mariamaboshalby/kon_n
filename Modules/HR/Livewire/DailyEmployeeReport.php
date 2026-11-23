<?php

declare(strict_types=1);

namespace Modules\HR\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Modules\HR\Models\Department;
use Modules\HR\Services\DailyEmployeeReportService;

class DailyEmployeeReport extends Component
{
    public string $selectedDate = '';

    public ?int $selectedDepartment = null;

    public string $activeTab = 'present';

    /** @var array<string, mixed> */
    public array $reportData = [];

    public bool $isLoading = false;

    public bool $reportGenerated = false;

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function updatedSelectedDate(): void
    {
        if ($this->reportGenerated) {
            $this->generateReport();
        }
    }

    public function updatedSelectedDepartment(): void
    {
        if ($this->reportGenerated) {
            $this->generateReport();
        }
    }

    public function generateReport(): void
    {
        $this->validate([
            'selectedDate' => 'required|date',
        ]);

        $this->isLoading = true;

        try {
            /** @var DailyEmployeeReportService $service */
            $service = app(DailyEmployeeReportService::class);

            $this->reportData = $service->generateDailyReport(
                $this->selectedDate,
                $this->selectedDepartment
            );

            $this->reportGenerated = true;
        } catch (\Exception $e) {
            session()->flash('error', __('hr.error_generating_report') . ': ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Get departments for filter dropdown
     */
    public function getDepartmentsProperty()
    {
        return Department::orderBy('title')->get();
    }

    public function render()
    {
        return view('hr::livewire.hr-management.attendances.reports.daily-employee-report', [
            'departments' => $this->departments,
        ]);
    }
}
