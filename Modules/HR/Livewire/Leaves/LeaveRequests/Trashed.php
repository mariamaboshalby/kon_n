<?php

declare(strict_types=1);

namespace Modules\HR\Livewire\Leaves\LeaveRequests;

use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\LeaveType;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('الطلبات المحذوفة')]
class Trashed extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $selectedEmployee = '';

    public string $selectedLeaveType = '';

    public string $selectedStatus = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    /** @var Collection<int, Employee> */
    public Collection $employees;

    /** @var Collection<int, LeaveType> */
    public Collection $leaveTypes;

    public function mount(): void
    {
        $this->employees = Employee::orderBy('name')->get();
        $this->leaveTypes = LeaveType::orderBy('name')->get();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedEmployee(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedLeaveType(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = LeaveRequest::onlyTrashed()
            ->with(['employee', 'leaveType', 'approver'])
            ->when($this->search, function ($query) {
                $query->whereHas('employee', function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->selectedEmployee, function ($query) {
                $query->where('employee_id', $this->selectedEmployee);
            })
            ->when($this->selectedLeaveType, function ($query) {
                $query->where('leave_type_id', $this->selectedLeaveType);
            })
            ->when($this->selectedStatus, function ($query) {
                $query->where('status', $this->selectedStatus);
            })
            ->when($this->dateFrom, function ($query) {
                $query->where('start_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->where('end_date', '<=', $this->dateTo);
            })
            ->orderBy('deleted_at', 'desc');

        $requests = $query->paginate(15);

        return view('hr::livewire.hr-management.leaves.leave-requests.trashed', [
            'requests' => $requests,
            'employees' => $this->employees,
            'leaveTypes' => $this->leaveTypes,
        ]);
    }

    public function restoreRequest(int $id): void
    {
        $request = LeaveRequest::onlyTrashed()->findOrFail($id);
        
        $this->authorize('restore', $request);
        
        $request->restore();

        session()->flash('message', 'تم استرجاع الطلب بنجاح.');
        $this->dispatch('show-message', message: 'تم استرجاع الطلب بنجاح.', type: 'success');
    }

    public function forceDeleteRequest(int $id): void
    {
        $request = LeaveRequest::onlyTrashed()->findOrFail($id);
        
        $this->authorize('forceDelete', $request);
        
        $request->forceDelete();

        session()->flash('message', 'تم حذف الطلب نهائياً.');
        $this->dispatch('show-message', message: 'تم حذف الطلب نهائياً.', type: 'success');
    }

    public function getStatusBadgeClass($status): string
    {
        return match ($status) {
            'draft' => 'bg-secondary',
            'submitted' => 'bg-warning',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'cancelled' => 'bg-dark',
            default => 'bg-secondary'
        };
    }

    public function getStatusText($status): string
    {
        return match ($status) {
            'draft' => 'مسودة',
            'submitted' => 'مقدم',
            'approved' => 'معتمد',
            'rejected' => 'مرفوض',
            'cancelled' => 'ملغي',
            default => 'غير محدد'
        };
    }
}
