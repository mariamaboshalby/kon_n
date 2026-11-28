{{-- Employee View Partial with Bootstrap Tabs --}}
@php
    $viewEmployee = $viewEmployee ?? $employee ?? null;
@endphp
<div class="container-fluid employee-view-container" style="direction: rtl;">
    @if ($viewEmployee)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm bg-gradient-primary text-white">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <div class="employee-avatar me-3">
                                    @php
                                        $employeeImage = $viewEmployee->image_url;
                                        $hasImage = $viewEmployee->hasMedia('employee_images') && $employeeImage;
                                    @endphp
                                    @if ($hasImage)
                                        <img src="{{ $employeeImage }}"
                                             alt="{{ e($viewEmployee->name) }}"
                                             class="rounded-circle border-3 border-white shadow-lg"
                                             style="width: 80px; height: 80px; object-fit: cover; cursor: pointer;"
                                             loading="lazy"
                                             onclick="openLightbox({{ json_encode($employeeImage) }})"
                                             onerror="this.src='{{ asset('assets/images/avatar-placeholder.svg') }}'">
                                    @else
                                        <img src="{{ asset('assets/images/avatar-placeholder.svg') }}"
                                             alt="{{ e($viewEmployee->name) }}"
                                             class="rounded-circle border-3 border-white shadow-lg"
                                             style="width: 80px; height: 80px; object-fit: cover;">
                                    @endif
                                </div>
                                <div>
                                    <h4 class="mb-1 fw-bold font-hold">
                                        <i class="fas fa-user me-2"></i>{{ e($viewEmployee->display_name) }}
                                    </h4>
                                    <p class="mb-0 opacity-75">
                                        <i class="fas fa-briefcase me-2"></i>{{ $viewEmployee->job?->title ?? __('hr::hr.not_specified') }}
                                        <span class="mx-2">|</span>
                                        <i class="fas fa-building me-2"></i>{{ $viewEmployee->department?->title ?? __('hr::hr.not_specified') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-3" role="tablist" id="employeeViewTabs">
            @foreach ([
                'personal' => ['icon' => 'fa-user', 'label' => __('hr::hr.personal_information')],
                'location' => ['icon' => 'fa-map-marker-alt', 'label' => __('hr::hr.location')],
                'job' => ['icon' => 'fa-briefcase', 'label' => __('hr::hr.job')],
                'attendance' => ['icon' => 'fa-clock', 'label' => __('hr::hr.attendance')],
                'accounting' => ['icon' => 'fa-calculator', 'label' => __('hr::hr.accounting')],
                'kpi' => ['icon' => 'fa-chart-line', 'label' => __('hr::hr.kpis')],
                'leaveBalances' => ['icon' => 'fa-calendar-check', 'label' => __('hr::hr.leave_balances')],
                'otherDetails' => ['icon' => 'fa-info-circle', 'label' => __('hr::hr.other_details')],
            ] as $tabKey => $tab)
                <li class="nav-item" role="presentation">
                    <button class="nav-link font-hold fw-bold {{ $loop->first ? 'active' : '' }}"
                            id="{{ $tabKey }}-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#{{ $tabKey }}-view-content"
                            type="button"
                            role="tab"
                            aria-controls="{{ $tabKey }}-view-content"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                        <i class="fas {{ $tab['icon'] }} me-2"></i>{{ $tab['label'] }}
                    </button>
                </li>
            @endforeach
        </ul>

        <div class="tab-content" id="employeeViewTabsContent">
            @foreach ([
                'personal' => 'personal',
                'location' => 'location',
                'job' => 'job',
                'attendance' => 'attendance',
                'accounting' => 'accounting',
                'kpi' => 'kpi',
                'leaveBalances' => 'leave-balances',
                'otherDetails' => 'other-details',
            ] as $tabKey => $file)
                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                     id="{{ $tabKey }}-view-content"
                     role="tabpanel"
                     aria-labelledby="{{ $tabKey }}-tab"
                     tabindex="0">
                    @include("hr::hr-management.employees.partials.view.tabs.{$file}-tab", ['viewEmployee' => $viewEmployee])
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-warning text-center">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ __('hr::hr.no_employee_data_loaded') }}
        </div>
    @endif
</div>
