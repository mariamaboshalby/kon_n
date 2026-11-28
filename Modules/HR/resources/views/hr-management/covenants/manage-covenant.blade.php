@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.covenants_management'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.covenants_management')],
        ],
    ])

    <div class="covenants-management" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrCovenantAlert" class="alert d-none" role="alert"></div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <input type="text" id="hrCovenantSearch" class="form-control font-hold"
                               placeholder="{{ __('hr::hr.search_covenants') }}">
                    </div>
                    <div class="col-md-3">
                        <select id="hrCovenantFilterEmployee" class="form-select font-hold js-searchable-filter">
                            <option value="">{{ __('hr::hr.all_employees') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="hrCovenantFilterAssignment" class="form-select font-hold">
                            <option value="">{{ __('hr::hr.all_covenants') }}</option>
                            <option value="assigned">{{ __('hr::hr.assigned_covenants') }}</option>
                            <option value="unassigned">{{ __('hr::hr.unassigned_covenants') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary flex-grow-1" id="hrCovenantClearFilters">
                            <i class="las la-filter me-1"></i>{{ __('hr::hr.clear') }}
                        </button>
                        @can('create Covenants')
                            <button type="button" class="btn btn-main flex-grow-1" id="hrCovenantCreateBtn">
                                <i class="las la-plus me-1"></i>{{ __('hr::hr.add_new_covenant') }}
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-striped text-center mb-0">
                    <thead class="table-light align-middle">
                        <tr>
                            <th class="font-hold fw-bold">#</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.name') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.description') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.employee') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.covenant_status') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.image') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.created_at') }}</th>
                            @canany(['view Covenants', 'edit Covenants', 'delete Covenants'])
                                <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody id="hrCovenantTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        #hrCovenantModal .modal-dialog,
        #hrCovenantViewModal .modal-dialog {
            max-height: calc(100vh - 30px);
            margin: 15px auto;
            display: flex;
        }
        #hrCovenantModal .modal-content,
        #hrCovenantViewModal .modal-content {
            width: 100%;
            max-height: calc(100vh - 30px);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        #hrCovenantModal form#hrCovenantForm {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
        }
        #hrCovenantModal .modal-header,
        #hrCovenantModal .modal-footer,
        #hrCovenantViewModal .modal-header,
        #hrCovenantViewModal .modal-footer {
            flex: 0 0 auto;
        }
        #hrCovenantModal .modal-body,
        #hrCovenantViewModal .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
    </style>

    <div class="modal fade" id="hrCovenantModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrCovenantModalTitle">{{ __('hr::hr.add_new_covenant') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrCovenantForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control font-hold" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.description') }}</label>
                            <textarea name="description" class="form-control font-hold" rows="3"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.covenant_status') }}</label>
                                <select name="status" id="hrCovenantStatusInput" class="form-select font-hold">
                                    <option value="">{{ __('hr::hr.status_unspecified') }}</option>
                                    <option value="returned">{{ __('hr::hr.status_returned') }}</option>
                                    <option value="maintenance">{{ __('hr::hr.status_maintenance') }}</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.handover_status') }}</label>
                                <input type="text" name="handover_status" class="form-control font-hold">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.covenant_cost') }}</label>
                            <input type="number" name="cost" class="form-control font-hold" min="0" step="0.01" placeholder="0.00">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.image') }}</label>
                            <div id="hrCovenantCurrentImage" class="mb-2 d-none"></div>
                            <input type="file" name="image" id="hrCovenantImageInput" class="form-control" accept="image/*">
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">{{ __('hr::hr.image_upload_hint') }}</small>
                            <div id="hrCovenantImagePreview" class="mt-2 d-none"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.acknowledgment_image') }}</label>
                            <div id="hrCovenantCurrentAcknowledgment" class="mb-2 d-none"></div>
                            <input type="file" name="acknowledgment_image" id="hrCovenantAcknowledgmentInput" class="form-control" accept="image/*">
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">{{ __('hr::hr.image_upload_hint') }}</small>
                            <div id="hrCovenantAcknowledgmentPreview" class="mt-2 d-none"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.discharge_image') }}</label>
                            <div id="hrCovenantCurrentDischarge" class="mb-2 d-none"></div>
                            <input type="file" name="discharge_image" id="hrCovenantDischargeInput" class="form-control" accept="image/*">
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">{{ __('hr::hr.image_upload_hint') }}</small>
                            <div id="hrCovenantDischargePreview" class="mt-2 d-none"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrCovenantSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrCovenantViewModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold">{{ __('hr::hr.covenant_details') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="hrCovenantViewBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrCovenantAssignModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold">{{ __('hr::hr.assign_covenant') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrCovenantAssignForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.select_employee') }}</label>
                            <select name="employee_id" id="hrCovenantAssignEmployee" class="form-select font-hold js-searchable-filter">
                                <option value="0">{{ __('hr::hr.not_assigned') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">{{ __('hr::hr.assign_covenant_hint') }}</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-success" id="hrCovenantAssignSubmitBtn">{{ __('hr::hr.assign') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrCovenantsConfig = {
        routes: {
            data: @json(route('covenants.data')),
            store: @json(route('covenants.store')),
            update: @json(route('covenants.update', ['covenant' => '__ID__'])),
            destroy: @json(route('covenants.destroy', ['covenant' => '__ID__'])),
            show: @json(route('covenants.show', ['covenant' => '__ID__'])),
            assign: @json(route('covenants.assign', ['covenant' => '__ID__'])),
            download: @json(route('covenants.download-image', ['covenant' => '__ID__']))
        },
        canView: @json(auth()->user()->can('view Covenants')),
        canEdit: @json(auth()->user()->can('edit Covenants')),
        canDelete: @json(auth()->user()->can('delete Covenants')),
        csrfToken: @json(csrf_token()),
        trans: {
            add: @json(__('hr::hr.add_new_covenant')),
            editTitle: @json(__('hr::hr.edit_covenant')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            empty: @json(__('hr::hr.no_covenants_found')),
            confirmDelete: @json(__('hr::hr.confirm_delete_covenant_message')),
            noDescription: @json(__('hr::hr.no_description')),
            noEmployee: @json(__('hr::hr.no_employee')),
            noFile: @json(__('hr::hr.no_file')),
            currentImage: @json(__('hr::hr.current_image')),
            name: @json(__('hr::hr.name')),
            description: @json(__('hr::hr.description')),
            employee: @json(__('hr::hr.employee')),
            image: @json(__('hr::hr.image')),
            covenantStatus: @json(__('hr::hr.covenant_status')),
            handoverStatus: @json(__('hr::hr.handover_status')),
            acknowledgmentImage: @json(__('hr::hr.acknowledgment_image')),
            dischargeImage: @json(__('hr::hr.discharge_image')),
            covenantCost: @json(__('hr::hr.covenant_cost')),
            statusLabels: {
                returned: @json(__('hr::hr.status_returned')),
                maintenance: @json(__('hr::hr.status_maintenance'))
            },
            createdAt: @json(__('hr::hr.created_at')),
            updatedAt: @json(__('hr::hr.updated_at')),
            downloadImage: @json(__('hr::hr.download_image')),
            noImage: @json(__('hr::hr.no_image_uploaded')),
            view: @json(__('hr::hr.view')),
            edit: @json(__('hr::hr.edit')),
            delete: @json(__('hr::hr.delete')),
            assign: @json(__('hr::hr.assign_covenant'))
        }
    };
</script>
<script src="{{ asset('js/hr-covenants.js') }}?v={{ filemtime(public_path('js/hr-covenants.js')) }}"></script>
@endpush
