@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.departments'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.departments')],
        ],
    ])

    <div class="departments-management" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrDeptAlert" class="alert d-none" role="alert"></div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                @can('create Departments')
                    <button type="button" class="btn btn-main font-hold fw-bold" id="hrDeptCreateBtn">
                        <i class="fas fa-plus me-2"></i>{{ __('hr::hr.add_new_department') }}
                    </button>
                @else
                    <span></span>
                @endcan
                <input type="text" id="hrDeptSearch" class="form-control w-auto" style="min-width: 200px;"
                       placeholder="{{ __('hr::hr.search_by_title') }}">
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-light text-center align-middle">
                        <tr>
                            <th class="font-hold fw-bold">#</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.department_name') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.parent') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.director') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.deputy_director') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.description') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.max_leave_percentage') }}</th>
                            @canany(['edit Departments', 'delete Departments'])
                                <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody id="hrDeptTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrDeptModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrDeptModalTitle">{{ __('hr::hr.add_department') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrDeptForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control font-hold fw-bold" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.parent') }}</label>
                            <select name="parent_id" id="hrDeptParentSelect" class="form-control font-hold fw-bold">
                                <option value="">{{ __('hr::hr.select_parent') }}</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.director') }}</label>
                            <select name="director_id" class="form-control font-hold fw-bold">
                                <option value="">{{ __('hr::hr.select_director') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}@if($employee->job) - {{ $employee->job->title }}@endif</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.deputy_director') }}</label>
                            <select name="deputy_director_id" class="form-control font-hold fw-bold">
                                <option value="">{{ __('hr::hr.select_deputy_director') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}@if($employee->job) - {{ $employee->job->title }}@endif</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.description') }}</label>
                            <input type="text" name="description" class="form-control font-hold fw-bold">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.max_leave_percentage') }} (%)</label>
                            <input type="text" name="max_leave_percentage" class="form-control font-hold fw-bold" inputmode="decimal"
                                   placeholder="{{ __('hr::hr.max_leave_percentage_placeholder') }}">
                            <small class="form-text text-muted font-hold">
                                {{ __('hr::hr.max_leave_percentage_help') }}
                                @if($companyMaxLeavePercentage)
                                    <br><strong class="text-info">{{ __('hr::hr.company_percentage_info', ['percentage' => number_format($companyMaxLeavePercentage, 2)]) }}</strong>
                                @else
                                    <br><strong class="text-danger">{{ __('hr::hr.company_percentage_not_set_warning') }}</strong>
                                @endif
                            </small>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrDeptSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrDeptHierarchyModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrDeptHierarchyTitle">{{ __('hr::hr.hierarchy') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="hrDeptHierarchyBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrDepartmentsConfig = {
        routes: {
            data: @json(route('departments.data')),
            store: @json(route('departments.store')),
            update: @json(route('departments.update', ['department' => '__ID__'])),
            destroy: @json(route('departments.destroy', ['department' => '__ID__'])),
            hierarchy: @json(route('departments.hierarchy', ['department' => '__ID__']))
        },
        canEdit: @json(auth()->user()->can('edit Departments')),
        canDelete: @json(auth()->user()->can('delete Departments')),
        csrfToken: @json(csrf_token()),
        trans: {
            add: @json(__('hr::hr.add_department')),
            editTitle: @json(__('hr::hr.edit_department')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            edit: @json(__('hr::hr.edit')),
            delete: @json(__('hr::hr.delete')),
            empty: @json(__('hr::hr.no_departments_found')),
            confirmDelete: @json(__('hr::hr.confirm_delete_department')),
            hierarchy: @json(__('hr::hr.hierarchy')),
            viewHierarchy: @json(__('hr::hr.view_hierarchy'))
        }
    };
</script>
<script src="{{ asset('js/hr-departments.js') }}"></script>
@endpush
