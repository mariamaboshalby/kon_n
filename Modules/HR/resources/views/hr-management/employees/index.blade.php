@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('hr::hr-management.employees.partials.statistics', ['employeeStats' => $employeeStats])

    <div class="hr-employees-index" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrEmployeesAlert" class="alert d-none" role="alert"></div>

        <div class="card" style="overflow: visible;">
            <div class="card-header hr-employees-header">
                <div class="hr-employees-header__row">
                    @can('create Employees')
                        <a href="{{ route('employees.create') }}" class="btn btn-main font-hold fw-bold hr-employees-header__add">
                            {{ __('hr::hr.add_employee') }}
                            <i class="fas fa-plus me-2"></i>
                        </a>
                    @endcan

                    <input type="text" id="hrEmployeesSearch" class="form-control hr-employees-header__search"
                           placeholder="{{ __('hr::hr.search_by_name') }}"
                           aria-label="{{ __('hr::hr.search_by_name') }}">

                    <div class="hr-employees-header__filters">
                        <div class="hr-filter-field">
                            <select id="hrEmployeesJobFilter" class="form-select form-select-sm hr-filter-field__select"
                                    aria-label="{{ __('hr::hr.job') }}">
                                <option value="">{{ __('hr::hr.job') }}: {{ __('hr::hr.all') }}</option>
                                @foreach ($jobs as $job)
                                    <option value="{{ $job->id }}">{{ $job->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="hr-filter-field hr-filter-field--date">
                            <div class="d-flex align-items-center gap-1">
                                <input type="date" id="hrEmployeesDateFrom" class="form-control form-control-sm hr-filter-date"
                                       aria-label="{{ __('hr::hr.from_date') }}">
                                <span class="hr-filter-date__sep">—</span>
                                <input type="date" id="hrEmployeesDateTo" class="form-control form-control-sm hr-filter-date"
                                       aria-label="{{ __('hr::hr.to_date') }}">
                            </div>
                        </div>

                        <span id="hrEmployeesFiltersCount" class="hr-filter-count d-none"></span>

                        <button type="button" id="hrEmployeesClearFilters" class="btn btn-sm hr-filter-clear d-none">
                            <i class="las la-sync-alt me-1"></i>{{ __('hr::hr.clear_filters') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body pt-3">
                <div class="d-none d-md-block">
                    <div class="table-responsive">
                        <x-table-export-actions table-id="hr-employee-table" filename="employee-table"
                            excel-label="{{ __('hr::hr.export_excel') }}"
                            pdf-label="{{ __('hr::hr.export_pdf') }}"
                            print-label="{{ __('hr::hr.print') }}" />

                        <table id="hr-employee-table" class="table table-striped text-center mb-0">
                            <thead class="table-light align-middle">
                                <tr>
                                    <th class="font-hold fw-bold">#</th>
                                    <th class="font-hold fw-bold">{{ __('hr::hr.name') }}</th>
                                    <th class="font-hold fw-bold d-none d-lg-table-cell">{{ __('hr::hr.nickname') }}</th>
                                    <th class="font-hold fw-bold d-none d-lg-table-cell">{{ __('hr::hr.email') }}</th>
                                    <th class="font-hold fw-bold">{{ __('hr::hr.phone') }}</th>
                                    <th class="font-hold fw-bold">{{ __('hr::hr.department') }}</th>
                                    <th class="font-hold fw-bold d-none d-lg-table-cell">{{ __('hr::hr.job') }}</th>
                                    <th class="font-hold fw-bold">{{ __('hr::hr.employee_code') }}</th>
                                    <th class="font-hold fw-bold d-none d-xl-table-cell">{{ __('hr::hr.projects') }}</th>
                                    <th class="font-hold fw-bold">{{ __('hr::hr.status') }}</th>
                                    @canany(['edit Employees', 'delete Employees', 'view Employees'])
                                        <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                                    @endcanany
                                </tr>
                            </thead>
                            <tbody id="hrEmployeesTableBody">
                                <tr>
                                    <td colspan="11" class="text-center py-4 text-muted">
                                        <i class="fas fa-spinner fa-spin me-2"></i>{{ __('hr::hr.loading') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-md-none" id="hrEmployeesMobileList"></div>

                <div class="mt-3 d-flex justify-content-center" id="hrEmployeesPager"></div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .hr-employees-index .hr-employees-header__row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .75rem;
        }

        .hr-employees-index .hr-employees-header__search {
            flex: 1 1 260px;
            min-width: 220px;
            border-radius: .6rem;
        }

        .hr-employees-index .hr-employees-header__filters {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
        }

        .hr-employees-index .hr-filter-field {
            display: flex;
            flex-direction: column;
            gap: .25rem;
            min-width: 150px;
        }

        .hr-employees-index .hr-filter-field--date {
            min-width: auto;
        }

        .hr-employees-index .hr-filter-field__select {
            border-radius: .6rem;
            background-color: var(--masar-card, #fff);
            white-space: nowrap;
        }

        .hr-employees-index .hr-filter-date {
            border-radius: .6rem;
            background-color: var(--masar-card, #fff);
            min-width: 0;
            width: 130px;
        }

        .hr-employees-index .hr-filter-date__sep {
            color: var(--masar-text-muted, #6c757d);
            font-weight: 600;
            flex-shrink: 0;
        }

        .hr-employees-index .hr-filter-count {
            display: inline-flex;
            align-items: center;
            background: var(--masar-mint, #34d3a3);
            color: #fff;
            font-weight: 700;
            font-size: .75rem;
            line-height: 1;
            padding: .4rem .7rem;
            border-radius: 2rem;
            white-space: nowrap;
        }

        .hr-employees-index .hr-filter-clear {
            border: 1px solid color-mix(in srgb, var(--masar-swal-danger, #ef4444) 45%, transparent);
            color: var(--masar-swal-danger, #ef4444);
            background: transparent;
            border-radius: .6rem;
            font-weight: 600;
            white-space: nowrap;
            transition: all .2s ease;
        }

        .hr-employees-index .hr-filter-clear:hover {
            background: color-mix(in srgb, var(--masar-swal-danger, #ef4444) 10%, transparent);
            color: var(--masar-swal-danger, #ef4444);
        }

        @media (max-width: 991.98px) {
            .hr-employees-index .hr-employees-header__add {
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .hr-employees-index .hr-employees-header__filters {
                width: 100%;
            }

            .hr-employees-index .hr-employees-header__filters .hr-filter-field {
                flex: 1 1 auto;
            }

            .hr-employees-index .hr-employees-header__filters .hr-filter-field--date {
                width: 100%;
            }

            .hr-employees-index .hr-filter-date {
                flex: 1 1 auto;
                width: auto;
            }

            .hr-employees-index .hr-filter-clear {
                width: 100%;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        window._hrEmployeesStatsConfig = {
            byStatus: @json($employeeStats['by_status']),
            byDepartment: @json($employeeStats['by_department']),
            monthly: @json($employeeStats['monthly']),
            lang: {
                hires: @json(__('hr::hr.stats_hires')),
                exits: @json(__('hr::hr.stats_exits')),
                count: @json(__('hr::hr.stats_count')),
            }
        };
        window._hrEmployeesIndexConfig = {
            dataUrl: @json(route('employees.data')),
            destroyUrlTemplate: @json(url('employees/__ID__')),
            showUrlTemplate: @json(url('employees/__ID__')),
            editUrlTemplate: @json(url('employees/__ID__/edit')),
            csrfToken: @json(csrf_token()),
            canView: @json(auth()->user()->can('view Employees')),
            canEdit: @json(auth()->user()->can('edit Employees')),
            canDelete: @json(auth()->user()->can('delete Employees')),
            perPage: 20,
            initialFilters: {
                job_id: @json(request('job_id', '')),
                date_from: @json(request('date_from', '')),
                date_to: @json(request('date_to', '')),
            },
            lang: {
                loading: @json(__('hr::hr.loading')),
                noEmployees: @json(__('hr::hr.no_employees_found')),
                confirmDelete: @json(__('hr::hr.confirm_delete_employee')),
                view: @json(__('hr::hr.view')),
                edit: @json(__('hr::hr.edit')),
                delete: @json(__('hr::hr.delete')),
                error: @json(__('hr::hr.error_occurred')),
            }
        };
    </script>
    <script src="{{ asset('js/hr-employees-statistics.js') }}?v={{ @filemtime(public_path('js/hr-employees-statistics.js')) }}" defer></script>
    <script src="{{ asset('js/hr-employees-index.js') }}?v={{ @filemtime(public_path('js/hr-employees-index.js')) }}" defer></script>
@endpush
