@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.errands_management'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.errands_management')],
        ],
    ])

    <div class="errands-management" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrErrandAlert" class="alert d-none" role="alert"></div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.search') }}</label>
                        <input type="text" id="hrErrandSearch" class="form-control" placeholder="{{ __('hr::hr.search_errands') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.employee') }}</label>
                        <select id="hrErrandFilterEmployee" class="form-select js-searchable-filter">
                            <option value="">{{ __('hr::hr.all_employees') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary flex-fill" id="hrErrandClearFilters">
                            <i class="las la-filter me-1"></i>{{ __('hr::hr.clear') }}
                        </button>
                        @can('create Errands')
                            <button type="button" class="btn btn-main flex-fill" id="hrErrandCreateBtn">
                                <i class="las la-plus me-1"></i>{{ __('hr::hr.add_new_errand') }}
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-striped mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="font-hold fw-bold">#</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.title') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.employee') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.start_date') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.end_date') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.approved_by') }}</th>
                            @canany(['edit Errands', 'delete Errands', 'approve Errands'])
                                <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody id="hrErrandTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrErrandModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrErrandModalTitle">{{ __('hr::hr.add_new_errand') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrErrandForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.employee') }} <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select js-searchable-filter" required>
                                <option value="">{{ __('hr::hr.select_employee') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.title') }}</label>
                            <input type="text" name="title" class="form-control">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.details') }}</label>
                            <textarea name="details" class="form-control" rows="3"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.start_date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.end_date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrErrandSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrErrandsConfig = {
        routes: {
            data: @json(route('errands.data')),
            store: @json(route('errands.store')),
            update: @json(route('errands.update', ['errand' => '__ID__'])),
            destroy: @json(route('errands.destroy', ['errand' => '__ID__'])),
            approve: @json(route('errands.approve', ['errand' => '__ID__']))
        },
        canEdit: @json(auth()->user()->can('edit Errands')),
        canDelete: @json(auth()->user()->can('delete Errands')),
        canApprove: @json(auth()->user()->can('approve Errands')),
        csrfToken: @json(csrf_token()),
        trans: {
            add: @json(__('hr::hr.add_new_errand')),
            editTitle: @json(__('hr::hr.edit_errand')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            empty: @json(__('hr::hr.no_errands_found')),
            confirmDelete: @json(__('hr::hr.confirm_delete_errand_message')),
            confirmApprove: @json(__('hr::hr.confirm_approve_errand')),
            pending: @json(__('hr::hr.pending')),
            loadError: @json(__('hr::hr.error_occurred')),
            saveError: @json(__('hr::hr.error_occurred'))
        }
    };
</script>
<script src="{{ asset('js/hr-errands.js') }}"></script>
@endpush
