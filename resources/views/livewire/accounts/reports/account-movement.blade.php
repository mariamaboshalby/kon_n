<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Models\AccHead;
use App\Models\OperHead;
use App\Models\JournalDetail;
use App\Enums\OperationTypeEnum;

new class extends Component {
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $accountId = null;
    public ?string $fromDate = null;
    public ?string $toDate = null;
    public string $accountName = '';
    public string $searchTerm = '';
    public int $highlightedIndex = -1;
    public bool $showDropdown = false;
    public string $sortField = 'crtime';
    public string $sortDirection = 'asc';

    public Collection $warehouses;

    public function mount($accountId = null): void
    {
        $this->warehouses = AccHead::where('is_stock', 1)->orderBy('id')->pluck('aname', 'id');
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->endOfMonth()->toDateString();
        if ($accountId) {
            $this->accountId = $accountId;
            $account = AccHead::with('currency')->find($accountId);
            if ($account) {
                $this->accountName = $account->aname;
                $this->searchTerm = $account->aname;
            }
        }
    }

    public function updatedSearchTerm(): void
    {
        $this->highlightedIndex = -1;
        $this->showDropdown = true;
        if (empty($this->searchTerm)) {
            $this->accountId = null;
            $this->accountName = '';
        }
    }

    public function getSearchResultsProperty()
    {
        if (strlen($this->searchTerm) < 2 || $this->searchTerm === $this->accountName) {
            return collect();
        }

        return AccHead::where('is_basic', 0)
            ->where('aname', 'like', '%' . $this->searchTerm . '%')
            ->select('id', 'aname')
            ->limit(7)
            ->get();
    }

    public function selectAccount(int $id, string $name): void
    {
        $this->accountId = $id;
        $this->accountName = $name;
        $this->searchTerm = $name;
        $this->highlightedIndex = -1;
        $this->showDropdown = false;
    }

    public function arrowDown(): void
    {
        $resultsCount = count($this->searchResults);
        if ($resultsCount > 0) {
            $this->highlightedIndex = ($this->highlightedIndex + 1) % $resultsCount;
        }
    }

    public function arrowUp(): void
    {
        $resultsCount = count($this->searchResults);
        if ($resultsCount > 0) {
            $this->highlightedIndex = ($this->highlightedIndex - 1 + $resultsCount) % $resultsCount;
        }
    }

    public function selectHighlightedItem(): void
    {
        $results = $this->searchResults;
        if ($this->highlightedIndex >= 0 && isset($results[$this->highlightedIndex])) {
            $account = $results[$this->highlightedIndex];
            $this->selectAccount($account->id, $account->aname);
        }
    }

    public function showResults(): void
    {
        $this->showDropdown = true;
    }

    public function hideDropdown(): void
    {
        $this->showDropdown = false;
    }

    public function getArabicReferenceName(int $referenceId): string
    {
        $operationType = OperationTypeEnum::fromValue($referenceId);
        return $operationType?->getArabicName() ?? 'عملية غير محددة';
    }

    public function with(): array
    {
        $account = $this->accountId
            ? AccHead::with('currency')->find($this->accountId)
            : null;

        $defaultCurrency = \Modules\Settings\Models\Currency::where('is_default', true)->first();

        return [
            'movements'       => $this->getMovements(),
            'summary'         => $this->getSummary(),
            'account'         => $account,
            'currencySymbol'  => $account?->currency?->symbol ?? $account?->currency?->code ?? '',
            'currencyName'    => $account?->currency?->name ?? '',
            'runningBalance'  => $this->getRunningBalanceProperty(),
            'defaultCurrency' => $defaultCurrency,
        ];
    }

    public function getMovements()
    {
        if (!$this->accountId) {
            return collect();
        }

        return JournalDetail::where('account_id', $this->accountId)
            ->when($this->fromDate, fn($q) => $q->whereDate('crtime', '>=', $this->fromDate))
            ->when($this->toDate, fn($q) => $q->whereDate('crtime', '<=', $this->toDate))
            ->with([
                'operHead',
                'operHead.acc1Head:id,aname',
                'operHead.acc2Head:id,aname',
                'head',
                'foreignCurrency:id,symbol,code,name',
            ])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(100);
    }

    /**
     * ملخص الفترة: رصيد افتتاحي، إجمالي مدين، دائن، رصيد ختامي
     */
    public function getSummary(): array
    {
        if (!$this->accountId) {
            return [];
        }

        $openingDebit = JournalDetail::where('account_id', $this->accountId)
            ->when($this->fromDate, fn($q) => $q->whereDate('crtime', '<', $this->fromDate))
            ->sum('debit');

        $openingCredit = JournalDetail::where('account_id', $this->accountId)
            ->when($this->fromDate, fn($q) => $q->whereDate('crtime', '<', $this->fromDate))
            ->sum('credit');

        $periodDebit = JournalDetail::where('account_id', $this->accountId)
            ->when($this->fromDate, fn($q) => $q->whereDate('crtime', '>=', $this->fromDate))
            ->when($this->toDate, fn($q) => $q->whereDate('crtime', '<=', $this->toDate))
            ->sum('debit');

        $periodCredit = JournalDetail::where('account_id', $this->accountId)
            ->when($this->fromDate, fn($q) => $q->whereDate('crtime', '>=', $this->fromDate))
            ->when($this->toDate, fn($q) => $q->whereDate('crtime', '<=', $this->toDate))
            ->sum('credit');

        $openingBalance = $openingDebit - $openingCredit;
        $closingBalance = $openingBalance + $periodDebit - $periodCredit;

        return compact('openingBalance', 'periodDebit', 'periodCredit', 'closingBalance');
    }

    public function updated($property): void
    {
        if (in_array($property, ['accountId', 'fromDate', 'toDate'])) {
            $this->resetPage();
        }
    }

    public function getRunningBalanceProperty(): float
    {
        if (!$this->accountId) {
            return 0;
        }
        return (float) DB::table('acc_head')->where('id', $this->accountId)->value('balance');
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
};
?>

<div>
    {{-- ===== فلاتر البحث ===== --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                {{-- بحث الحساب --}}
                <div class="col-md-5">
                    <label class="form-label font-hold fw-bold small mb-1">{{ __('reports::reports.account') }}</label>
                    <div class="position-relative" wire:click.outside="hideDropdown">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="las la-search text-muted"></i>
                            </span>
                            <input type="text"
                                   class="form-control border-start-0 font-hold"
                                   placeholder="{{ __('reports::reports.search_for_account') }}"
                                   wire:model.live.debounce.300ms="searchTerm"
                                   wire:keydown.arrow-down.prevent="arrowDown"
                                   wire:keydown.arrow-up.prevent="arrowUp"
                                   wire:keydown.enter.prevent="selectHighlightedItem"
                                   wire:focus="showResults"
                                   onclick="this.select()">
                        </div>
                        @if ($showDropdown && $this->searchResults->isNotEmpty())
                            <ul class="dropdown-menu show shadow-sm border-0 w-100" style="z-index:1050;">
                                @foreach ($this->searchResults as $index => $acc)
                                    <li>
                                        <a class="dropdown-item font-hold py-2 {{ $highlightedIndex === $index ? 'active' : '' }}"
                                           href="#"
                                           wire:click.prevent="selectAccount({{ $acc->id }}, '{{ addslashes($acc->aname) }}')">
                                            {{ $acc->aname }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @elseif($showDropdown && strlen($searchTerm) >= 2 && $searchTerm !== $accountName)
                            <ul class="dropdown-menu show shadow-sm border-0 w-100" style="z-index:1050;">
                                <li>
                                    <span class="dropdown-item-text font-hold text-muted small">
                                        <i class="las la-exclamation-circle me-1"></i>{{ __('reports::reports.no_results_found_for_search') }}
                                    </span>
                                </li>
                            </ul>
                        @endif
                    </div>
                </div>

                {{-- من تاريخ --}}
                <div class="col-md-3">
                    <label class="form-label font-hold fw-bold small mb-1">{{ __('reports::reports.from_date') }}</label>
                    <input type="date" wire:model.live="fromDate" class="form-control font-hold">
                </div>

                {{-- إلى تاريخ --}}
                <div class="col-md-3">
                    <label class="form-label font-hold fw-bold small mb-1">{{ __('reports::reports.to_date') }}</label>
                    <input type="date" wire:model.live="toDate" class="form-control font-hold">
                </div>

                {{-- مؤشر التحميل --}}
                <div class="col-md-1 text-center">
                    <div wire:loading wire:target="fromDate,toDate,searchTerm,sortBy" class="mt-3">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">{{ __('reports::reports.please_wait') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($accountId && $account)
        @php
            $sym = $currencySymbol;
            $currName = $currencyName;
        @endphp

        {{-- ===== بطاقات الملخص العلوية ===== --}}
        <div class="row g-3 mb-3">
            {{-- الرصيد الحالي --}}
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        @php $currentBal = $runningBalance; @endphp
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0
                            {{ $currentBal < 0 ? 'bg-danger' : 'bg-primary' }} bg-opacity-10"
                            style="width:48px;height:48px;">
                            <i class="las la-wallet fs-4 {{ $currentBal < 0 ? 'text-danger' : 'text-primary' }}"></i>
                        </div>
                        <div class="overflow-hidden">
                            <div class="text-muted small font-hold">{{ __('reports::reports.current_balance') }}</div>
                            <div class="fw-bold font-hold fs-5 text-truncate {{ $currentBal < 0 ? 'text-danger' : 'text-primary' }}">
                                {{ number_format($currentBal, 2) }}
                                @if($sym) <small class="fs-6">{{ $sym }}</small> @endif
                            </div>
                            @if($currName)
                                <div class="text-muted" style="font-size:11px;">{{ $currName }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- الرصيد الافتتاحي --}}
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 bg-secondary bg-opacity-10"
                            style="width:48px;height:48px;">
                            <i class="las la-history fs-4 text-secondary"></i>
                        </div>
                        <div>
                            <div class="text-muted small font-hold">{{ __('reports::reports.opening_balance') }}</div>
                            <div class="fw-bold font-hold fs-5 {{ ($summary['openingBalance'] ?? 0) < 0 ? 'text-danger' : 'text-dark' }}">
                                {{ number_format($summary['openingBalance'] ?? 0, 2) }}
                                @if($sym) <small class="fs-6 text-muted">{{ $sym }}</small> @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- إجمالي المدين --}}
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 bg-success bg-opacity-10"
                            style="width:48px;height:48px;">
                            <i class="las la-arrow-circle-down fs-4 text-success"></i>
                        </div>
                        <div>
                            <div class="text-muted small font-hold">{{ __('reports::reports.total_debit') }}</div>
                            <div class="fw-bold font-hold fs-5 text-success">
                                {{ number_format($summary['periodDebit'] ?? 0, 2) }}
                                @if($sym) <small class="fs-6 text-muted">{{ $sym }}</small> @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- إجمالي الدائن --}}
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 bg-warning bg-opacity-10"
                            style="width:48px;height:48px;">
                            <i class="las la-arrow-circle-up fs-4 text-warning"></i>
                        </div>
                        <div>
                            <div class="text-muted small font-hold">{{ __('reports::reports.total_credit') }}</div>
                            <div class="fw-bold font-hold fs-5 text-warning">
                                {{ number_format($summary['periodCredit'] ?? 0, 2) }}
                                @if($sym) <small class="fs-6 text-muted">{{ $sym }}</small> @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== جدول الحركات ===== --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="las la-list-alt text-primary fs-5"></i>
                    <span class="fw-bold font-hold">{{ $accountName }}</span>
                    @if($currName)
                        <span class="badge bg-primary bg-opacity-10 text-primary font-hold fw-normal">
                            <i class="las la-coins me-1"></i>{{ $currName }}
                            @if($sym && $sym !== $currName) ({{ $sym }}) @endif
                        </span>
                    @endif
                </div>
                <small class="text-muted font-hold">
                    {{ $fromDate }} — {{ $toDate }}
                </small>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="font-hold fw-bold ps-3" style="cursor:pointer;white-space:nowrap;" wire:click="sortBy('crtime')">
                                    {{ __('reports::reports.date') }}
                                    <i class="las la-sort{{ $sortField === 'crtime' ? ($sortDirection === 'asc' ? '-up text-primary' : '-down text-primary') : ' text-muted' }} ms-1"></i>
                                </th>
                                <th class="font-hold fw-bold" style="cursor:pointer;" wire:click="sortBy('op_id')">
                                    {{ __('reports::reports.operation_source') }}
                                    <i class="las la-sort{{ $sortField === 'op_id' ? ($sortDirection === 'asc' ? '-up text-primary' : '-down text-primary') : ' text-muted' }} ms-1"></i>
                                </th>
                                <th class="font-hold fw-bold">{{ __('reports::reports.description') }}</th>
                                <th class="font-hold fw-bold">{{ __('reports::reports.account_name') }}</th>
                                <th class="font-hold fw-bold text-end" style="cursor:pointer;" wire:click="sortBy('debit')">
                                    {{ __('reports::reports.debit') }}
                                    @if($sym) <small class="text-muted fw-normal">({{ $sym }})</small> @endif
                                    <i class="las la-sort{{ $sortField === 'debit' ? ($sortDirection === 'asc' ? '-up text-primary' : '-down text-primary') : ' text-muted' }} ms-1"></i>
                                </th>
                                <th class="font-hold fw-bold text-end" style="cursor:pointer;" wire:click="sortBy('credit')">
                                    {{ __('reports::reports.credit') }}
                                    @if($sym) <small class="text-muted fw-normal">({{ $sym }})</small> @endif
                                    <i class="las la-sort{{ $sortField === 'credit' ? ($sortDirection === 'asc' ? '-up text-primary' : '-down text-primary') : ' text-muted' }} ms-1"></i>
                                </th>
                                {{-- عمود القيمة بالعملة الافتراضية (يظهر فقط إذا كانت العملة مختلفة) --}}
                                @if($sym && $defaultCurrency && $defaultCurrency->symbol !== $sym && $defaultCurrency->code !== $sym)
                                <th class="font-hold fw-bold text-end">
                                    {{ __('reports::reports.amount') }}
                                    <small class="text-muted fw-normal">({{ $defaultCurrency->symbol ?: $defaultCurrency->code }})</small>
                                </th>
                                @endif
                                <th class="font-hold fw-bold text-end">
                                    {{ __('reports::reports.balance_after') }}
                                    @if($sym) <small class="text-muted fw-normal">({{ $sym }})</small> @endif
                                </th>
                                <th class="font-hold fw-bold text-center">{{ __('reports::reports.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $balanceBefore = JournalDetail::where('account_id', $this->accountId)
                                    ->when($this->fromDate, fn($q) => $q->whereDate('crtime', '<', $this->fromDate))
                                    ->selectRaw('SUM(foreign_amount * CASE WHEN debit > 0 THEN 1 ELSE -1 END) as bal')
                                    ->value('bal') ?? 0;
                                // إذا لم يكن هناك foreign_amount نستخدم debit - credit
                                if ($balanceBefore == 0) {
                                    $balanceBefore = JournalDetail::where('account_id', $this->accountId)
                                        ->when($this->fromDate, fn($q) => $q->whereDate('crtime', '<', $this->fromDate))
                                        ->selectRaw('SUM(debit) - SUM(credit) as bal')
                                        ->value('bal') ?? 0;
                                }
                                $runningBal = $balanceBefore;
                            @endphp

                            @forelse($movements as $movement)
                                @php
                                    $fDebit  = $movement->foreign_amount > 0 && $movement->debit > 0  ? $movement->foreign_amount : $movement->debit;
                                    $fCredit = $movement->foreign_amount > 0 && $movement->credit > 0 ? $movement->foreign_amount : $movement->credit;
                                    $runningBal += $fDebit - $fCredit;
                                    $operation = $movement->operHead;
                                    // الحساب المقابل
                                    $contraAccount = null;
                                    if ($operation) {
                                        $contraAccount = ($operation->acc1 == $this->accountId)
                                            ? $operation->acc2Head
                                            : $operation->acc1Head;
                                    }
                                    // البيان
                                    $description = $movement->head?->details
                                        ?? $operation?->info
                                        ?? $operation?->info2
                                        ?? null;
                                    // هل العملة مختلفة عن الافتراضية
                                    $showDefaultCol = $sym && $defaultCurrency
                                        && $defaultCurrency->symbol !== $sym
                                        && $defaultCurrency->code !== $sym;
                                @endphp
                                <tr>
                                    <td class="font-hold ps-3" style="white-space:nowrap;">
                                        {{ $movement->crtime->format('Y-m-d') }}
                                    </td>
                                    <td class="font-hold">
                                        @if($operation)
                                            <span class="badge bg-light text-dark border me-1">#{{ $movement->op_id }}</span>
                                            {{ $this->getArabicReferenceName($operation->pro_type) }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    {{-- البيان --}}
                                    <td class="font-hold text-muted" style="max-width:180px;">
                                        @if($description)
                                            <span title="{{ $description }}" style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:180px;">
                                                {{ $description }}
                                            </span>
                                        @else
                                            <span class="text-muted opacity-50">—</span>
                                        @endif
                                    </td>
                                    {{-- الحساب المقابل --}}
                                    <td class="font-hold" style="max-width:160px;">
                                        @if($contraAccount)
                                            <span class="text-dark" title="{{ $contraAccount->aname }}" style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:160px;">
                                                {{ $contraAccount->aname }}
                                            </span>
                                        @else
                                            <span class="text-muted opacity-50">—</span>
                                        @endif
                                    </td>
                                    {{-- مدين بعملة الحساب --}}
                                    <td class="font-hold text-end">
                                        @if ($movement->debit > 0)
                                            @php $fAmt = $movement->foreign_amount > 0 ? $movement->foreign_amount : $movement->debit; @endphp
                                            <span class="text-success fw-semibold">{{ number_format($fAmt, 2) }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    {{-- دائن بعملة الحساب --}}
                                    <td class="font-hold text-end">
                                        @if ($movement->credit > 0)
                                            @php $fAmt = $movement->foreign_amount > 0 ? $movement->foreign_amount : $movement->credit; @endphp
                                            <span class="text-danger fw-semibold">{{ number_format($fAmt, 2) }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    {{-- القيمة بالعملة الافتراضية --}}
                                    @if($showDefaultCol)
                                    <td class="font-hold text-end text-muted small">
                                        @php
                                            $defaultVal = $movement->debit > 0 ? $movement->debit : $movement->credit;
                                        @endphp
                                        @if($defaultVal > 0)
                                            {{ number_format($defaultVal, 2) }}
                                            <small>{{ $defaultCurrency->symbol ?: $defaultCurrency->code }}</small>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    @endif
                                    {{-- الرصيد بعد --}}
                                    <td class="font-hold text-end fw-bold {{ $runningBal < 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($runningBal, 2) }}
                                    </td>
                                    <td class="text-center">
                                        @if ($operation && in_array($operation->pro_type, [10, 11, 12, 13]))
                                            <a href="{{ route('invoice.view', $movement->op_id) }}"
                                               class="btn btn-sm btn-outline-info py-0 px-2"
                                               target="_blank"
                                               title="{{ __('reports::reports.view_operation') }}">
                                                <i class="las la-eye"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted font-hold">
                                        <i class="las la-inbox fs-1 d-block mb-2 opacity-50"></i>
                                        {{ __('reports::reports.no_movements_found_for_the_selected_criteria') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ===== ملخص مجمع في الأسفل ===== --}}
            @if($movements->count() > 0)
                <div class="card-footer bg-light border-top">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex flex-wrap gap-4">
                                <div class="text-center">
                                    <div class="text-muted small font-hold">{{ __('reports::reports.opening_balance') }}</div>
                                    <div class="fw-bold font-hold {{ ($summary['openingBalance'] ?? 0) < 0 ? 'text-danger' : 'text-dark' }}">
                                        {{ number_format($summary['openingBalance'] ?? 0, 2) }}
                                        @if($sym) <small class="text-muted">{{ $sym }}</small> @endif
                                    </div>
                                </div>
                                <div class="text-muted align-self-center">+</div>
                                <div class="text-center">
                                    <div class="text-muted small font-hold">{{ __('reports::reports.total_debit') }}</div>
                                    <div class="fw-bold font-hold text-success">
                                        {{ number_format($summary['periodDebit'] ?? 0, 2) }}
                                        @if($sym) <small class="text-muted">{{ $sym }}</small> @endif
                                    </div>
                                </div>
                                <div class="text-muted align-self-center">−</div>
                                <div class="text-center">
                                    <div class="text-muted small font-hold">{{ __('reports::reports.total_credit') }}</div>
                                    <div class="fw-bold font-hold text-danger">
                                        {{ number_format($summary['periodCredit'] ?? 0, 2) }}
                                        @if($sym) <small class="text-muted">{{ $sym }}</small> @endif
                                    </div>
                                </div>
                                <div class="text-muted align-self-center">=</div>
                                <div class="text-center">
                                    <div class="text-muted small font-hold">{{ __('reports::reports.closing_balance') }}</div>
                                    <div class="fw-bold font-hold fs-5 {{ ($summary['closingBalance'] ?? 0) < 0 ? 'text-danger' : 'text-primary' }}">
                                        {{ number_format($summary['closingBalance'] ?? 0, 2) }}
                                        @if($sym) <small class="fs-6 text-muted">{{ $sym }}</small> @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex justify-content-end align-items-center">
                            <small class="text-muted font-hold me-3">
                                {{ $movements->total() }} {{ __('reports::reports.operations_count') }}
                            </small>
                            {{ $movements->links() }}
                        </div>
                    </div>
                </div>
            @else
                <div class="card-footer bg-white d-flex justify-content-center">
                    {{ $movements->links() }}
                </div>
            @endif
        </div>

    @elseif(!$accountId)
        {{-- حالة عدم اختيار حساب --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="las la-search fs-1 d-block mb-3 opacity-50"></i>
                <p class="font-hold mb-0">{{ __('reports::reports.select_account_to_view_movements') }}</p>
            </div>
        </div>
    @endif
</div>
