@php
    $isEdit = $isEdit ?? false;
    $p = $p ?? [];
    $tabs = [
        'personal' => ['icon' => 'fa-user', 'label' => __('hr::hr.personal_information')],
        'location' => ['icon' => 'fa-map-marker-alt', 'label' => __('hr::hr.location')],
        'job' => ['icon' => 'fa-briefcase', 'label' => __('hr::hr.job')],
        'salary' => ['icon' => 'fa-money-bill-wave', 'label' => __('hr::hr.salary')],
        'attendance' => ['icon' => 'fa-clock', 'label' => __('hr::hr.attendance')],
        'kpi' => ['icon' => 'fa-chart-line', 'label' => __('hr::hr.kpis')],
        'Accounting' => ['icon' => 'fa-calculator', 'label' => __('hr::hr.accounting')],
        'leaveBalances' => ['icon' => 'fa-calendar-check', 'label' => __('hr::hr.leave_balances')],
        'documents' => ['icon' => 'fa-id-badge', 'label' => __('hr::hr.documents_tab')],
    ];
@endphp

<ul class="nav nav-tabs mb-3 employee-form-tabs" role="tablist" id="employeeFormTabs">
    @foreach ($tabs as $tabKey => $tab)
        <li class="nav-item" role="presentation">
            <button class="nav-link font-hold fw-bold {{ $loop->first ? 'active' : '' }}"
                    id="{{ $tabKey }}-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#{{ $tabKey }}-content"
                    type="button"
                    role="tab"
                    data-hr-tab="{{ $tabKey }}"
                    aria-controls="{{ $tabKey }}-content"
                    aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                <i class="fas {{ $tab['icon'] }} me-2"></i>
                <span>{{ $tab['label'] }}</span>
                <span class="badge bg-danger ms-2 rounded-pill d-none hr-tab-error-badge">0</span>
            </button>
        </li>
    @endforeach
</ul>

<div class="tab-content" id="employeeFormTabsContent">
    @foreach ($tabs as $tabKey => $tab)
        @php
            $tabFileName = match ($tabKey) {
                'Accounting' => 'accounting',
                'leaveBalances' => 'leave-balances',
                'documents' => 'documents',
                default => strtolower($tabKey),
            };
        @endphp
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
             id="{{ $tabKey }}-content"
             role="tabpanel"
             aria-labelledby="{{ $tabKey }}-tab"
             tabindex="0">
            @include("hr::hr-management.employees.partials.form.tabs.{$tabFileName}-tab", [
                'isEdit' => $isEdit,
                'p' => $p,
            ])
        </div>
    @endforeach
</div>
