@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.jobs'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.jobs')],
        ],
    ])

    <div class="jobs-management" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrCrudAlert" class="alert d-none" role="alert"></div>

        {{-- Filters Card --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.job_title') }}</label>
                        <input type="text" id="hrJobSearch" class="form-control font-hold"
                               placeholder="{{ __('hr::hr.search_by_title') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.job_level') }}</label>
                        <select id="hrJobLevelFilter" class="form-select font-hold">
                            <option value="">{{ __('hr::hr.all') }}</option>
                            @foreach ($jobLevels as $level)
                                <option value="{{ $level }}">{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5 d-flex gap-2 justify-content-end">
                        <button type="button" id="hrJobClearFilters" class="btn btn-outline-secondary d-none font-hold">
                            <i class="las la-sync-alt me-1"></i>{{ __('hr::hr.clear_filters') }}
                        </button>
                        @can('create Jobs')
                            <button type="button" class="btn btn-main font-hold fw-bold" id="hrJobCreateBtn">
                                <i class="las la-plus me-1"></i>{{ __('hr::hr.add_job') }}
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped mb-0 text-center">
                        <thead class="table-light align-middle">
                            <tr>
                                <th class="font-hold fw-bold">#</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.job_title') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.job_level') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.job_description') }}</th>
                                @canany(['edit Jobs', 'delete Jobs'])
                                    <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                                @endcanany
                            </tr>
                        </thead>
                        <tbody id="hrJobTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrJobModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrJobModalTitle">{{ __('hr::hr.add_job') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrJobForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.job_title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control font-hold fw-bold" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.job_level') }}</label>
                            <select name="level" id="hrJobLevelSelect" class="form-select font-hold fw-bold"
                                    data-placeholder="{{ __('hr::hr.job_level_type_or_select') }}">
                                <option value="">{{ __('hr::hr.job_level_type_or_select') }}</option>
                                @foreach ($jobLevels as $level)
                                    <option value="{{ $level }}">{{ $level }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.job_description') }}</label>
                            <input type="text" name="description" class="form-control font-hold fw-bold">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrJobSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrSimpleCrudConfig = {
        routes: {
            data: @json(route('jobs.data')),
            store: @json(route('jobs.store')),
            update: @json(route('jobs.update', ['job' => '__ID__'])),
            destroy: @json(route('jobs.destroy', ['job' => '__ID__']))
        },
        fields: [
            { name: 'title', type: 'text' },
            { name: 'level', type: 'select' },
            { name: 'description', type: 'text' }
        ],
        titleField: 'title',
        columnCount: {{ auth()->user()->canAny(['edit Jobs', 'delete Jobs']) ? 5 : 4 }},
        canEdit: @json(auth()->user()->can('edit Jobs')),
        canDelete: @json(auth()->user()->can('delete Jobs')),
        extraColumns: [{ key: 'level' }, { key: 'description' }],
        searchSelector: '#hrJobSearch',
        createBtnSelector: '#hrJobCreateBtn',
        tbodySelector: '#hrJobTableBody',
        modalSelector: '#hrJobModal',
        modalTitleSelector: '#hrJobModalTitle',
        formSelector: '#hrJobForm',
        submitBtnSelector: '#hrJobSubmitBtn',
        alertSelector: '#hrCrudAlert',
        csrfToken: @json(csrf_token()),
        trans: {
            add: @json(__('hr::hr.add_job')),
            editTitle: @json(__('hr::hr.edit_job')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            edit: @json(__('hr::hr.edit')),
            delete: @json(__('hr::hr.delete')),
            empty: @json(__('hr::hr.no_jobs_found')),
            confirmDelete: @json(__('hr::hr.confirm_delete_job')),
            saved: @json(__('hr::hr.job_created_successfully')),
            deleted: @json(__('hr::hr.job_deleted_successfully'))
        }
    };
</script>
<script src="{{ asset('js/hr-simple-crud.js') }}"></script>
<script>
(function ($) {
    'use strict';

    var levelTimer = null;

    function getExtraParams() {
        return { level: $('#hrJobLevelFilter').val() || '' };
    }

    function updateClearBtn() {
        var hasFilter = $('#hrJobLevelFilter').val() || $('#hrJobSearch').val();
        $('#hrJobClearFilters').toggleClass('d-none', !hasFilter);
    }

    // Patch _hrSimpleCrudConfig to include extra params by overriding the data route via $.ajaxSetup beforeSend
    // Simpler: override the internal loadList after the script runs by monkey-patching the search input trigger
    $(function () {
        // Intercept every AJAX call to the jobs data endpoint and inject the level param
        $(document).ajaxSend(function (event, jqxhr, settings) {
            if (settings.url && settings.url.indexOf(@json(route('jobs.data'))) !== -1) {
                var url = settings.url;
                var sep = url.indexOf('?') !== -1 ? '&' : '?';
                var level = $('#hrJobLevelFilter').val() || '';
                if (level) {
                    settings.url = url + sep + 'level=' + encodeURIComponent(level);
                }
            }
        });

        $('#hrJobLevelFilter').on('change', function () {
            updateClearBtn();
            // Trigger the search input change to re-run loadList in hr-simple-crud.js
            $('#hrJobSearch').trigger('input');
        });

        $('#hrJobSearch').on('input', function () {
            updateClearBtn();
        });

        $('#hrJobClearFilters').on('click', function () {
            $('#hrJobSearch').val('');
            $('#hrJobLevelFilter').val('');
            updateClearBtn();
            $('#hrJobSearch').trigger('input');
        });

        function ensureLevelOption($select, level) {
            if (!level) {
                return;
            }
            var exists = $select.find('option').filter(function () {
                return $(this).val() === level;
            }).length > 0;
            if (exists) {
                return;
            }
            $select.append($('<option>', { value: level, text: level }));
        }

        var $levelSelect = $('#hrJobLevelSelect');
        if ($levelSelect.length && typeof $.fn.select2 === 'function') {
            $levelSelect.select2({
                tags: true,
                allowClear: true,
                width: '100%',
                theme: 'bootstrap-5',
                dropdownParent: $('#hrJobModal'),
                placeholder: $levelSelect.data('placeholder') || ''
            });
        }

        $('#hrJobModal').on('shown.bs.modal', function () {
            var val = $levelSelect.val() || '';
            ensureLevelOption($levelSelect, val);
            $levelSelect.val(val).trigger('change');
        });

        $(document).ajaxSuccess(function (event, xhr, settings) {
            if (!settings.url || (settings.url.indexOf(@json(route('jobs.store'))) === -1 && settings.url.indexOf('/jobs/') === -1)) {
                return;
            }
            var payload = xhr.responseJSON;
            if (!payload || !payload.success) {
                return;
            }
            var level = $levelSelect.val();
            ensureLevelOption($('#hrJobLevelFilter'), level);
            ensureLevelOption($levelSelect, level);
        });
    });
})(jQuery);
</script>
@endpush
