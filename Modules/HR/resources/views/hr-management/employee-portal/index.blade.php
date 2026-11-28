@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@php
    $hrpLabel = 'form-label font-hold fw-bold small mb-1';
    $hrpLabelStyle = 'color: var(--main-color);';
@endphp

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::portal.page_title'),
        'breadcrumb_items' => [
            ['label' => __('hr::portal.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::portal.hr')],
            ['label' => __('hr::portal.nav_title')],
        ],
    ])

    <div id="hrpRoot" style="font-family: var(--masar-font-family);" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="pb-5 mb-5">
        <div id="hrpAlert" class="alert d-none" role="alert"></div>

        <ul class="nav nav-tabs mb-3" id="hrpTabs" role="tablist">
            @foreach ($tabs as $tab)
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link font-hold fw-bold {{ $activeTab === $tab ? 'active' : '' }}" data-hrp-tab="{{ $tab }}">
                        {{ __('hr::portal.tab_'.$tab) }}
                        @if ($tab === 'requests' && $pendingRequests > 0)
                            <span class="badge bg-warning text-dark ms-1">{{ $pendingRequests }}</span>
                        @elseif ($tab === 'devices' && $pendingDevices > 0)
                            <span class="badge bg-warning text-dark ms-1">{{ $pendingDevices }}</span>
                        @endif
                    </button>
                </li>
            @endforeach
        </ul>

        {{-- ===================== صندوق الطلبات ===================== --}}
        @if (in_array('requests', $tabs, true))
            <div class="hrp-pane {{ $activeTab === 'requests' ? '' : 'd-none' }}" data-hrp-pane="requests">
                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-main hrp-filter-toggle" data-target="#hrpReqFilter">
                        <i class="las la-filter me-1"></i> {{ __('hr::portal.filters') }}
                    </button>
                </div>
                <div id="hrpReqFilter" class="card mb-3" style="display:none;">
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.search') }}</label>
                                <input type="text" class="form-control form-control-sm hrp-filter" data-pane="requests" name="search">
                            </div>
                            <div class="col-md-2">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.status') }}</label>
                                <select class="form-select form-select-sm hrp-filter" data-pane="requests" name="status">
                                    <option value="">{{ __('hr::portal.all') }}</option>
                                    @foreach (['pending', 'approved', 'rejected', 'cancelled'] as $s)
                                        <option value="{{ $s }}" @selected($s === 'pending')>{{ __('hr::portal.status_'.$s) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.stage') }}</label>
                                <select class="form-select form-select-sm hrp-filter" data-pane="requests" name="stage">
                                    <option value="">{{ __('hr::portal.all') }}</option>
                                    @foreach (['manager', 'hr', 'done'] as $s)
                                        <option value="{{ $s }}">{{ __('hr::portal.stage_'.$s) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.type') }}</label>
                                <select class="form-select form-select-sm hrp-filter" data-pane="requests" name="type">
                                    <option value="">{{ __('hr::portal.all') }}</option>
                                    @foreach (\Modules\HR\Models\PortalRequest::TYPES as $t)
                                        <option value="{{ $t }}">{{ __('hr::portal.type_'.$t) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('hr::portal.employee') }}</th>
                                    <th>{{ __('hr::portal.type') }}</th>
                                    <th>{{ __('hr::portal.summary') }}</th>
                                    <th>{{ __('hr::portal.stage') }}</th>
                                    <th>{{ __('hr::portal.status') }}</th>
                                    <th>{{ __('hr::portal.created_at') }}</th>
                                    <th>{{ __('hr::portal.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody data-hrp-body="requests"></tbody>
                        </table>
                        <div class="text-center py-2 d-none" data-hrp-more="requests">
                            <button type="button" class="btn btn-outline-secondary btn-sm hrp-load-more" data-pane="requests">{{ __('hr::portal.load_more') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ===================== الأجهزة ===================== --}}
        @if (in_array('devices', $tabs, true))
            <div class="hrp-pane {{ $activeTab === 'devices' ? '' : 'd-none' }}" data-hrp-pane="devices">
                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-main hrp-filter-toggle" data-target="#hrpDevFilter">
                        <i class="las la-filter me-1"></i> {{ __('hr::portal.filters') }}
                    </button>
                </div>
                <div id="hrpDevFilter" class="card mb-3" style="display:none;">
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.employee') }}</label>
                                <input type="text" class="form-control form-control-sm hrp-filter" data-pane="devices" name="search">
                            </div>
                            <div class="col-md-2">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.status') }}</label>
                                <select class="form-select form-select-sm hrp-filter" data-pane="devices" name="status">
                                    <option value="">{{ __('hr::portal.all') }}</option>
                                    @foreach (['pending', 'approved', 'revoked'] as $s)
                                        <option value="{{ $s }}">{{ __('hr::portal.device_status_'.$s) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('hr::portal.employee') }}</th>
                                    <th>{{ __('hr::portal.device_model') }}</th>
                                    <th>{{ __('hr::portal.device_platform') }}</th>
                                    <th>{{ __('hr::portal.device_app_version') }}</th>
                                    <th>{{ __('hr::portal.status') }}</th>
                                    <th>{{ __('hr::portal.device_last_seen') }}</th>
                                    <th>{{ __('hr::portal.device_registered') }}</th>
                                    <th>{{ __('hr::portal.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody data-hrp-body="devices"></tbody>
                        </table>
                        <div class="text-center py-2 d-none" data-hrp-more="devices">
                            <button type="button" class="btn btn-outline-secondary btn-sm hrp-load-more" data-pane="devices">{{ __('hr::portal.load_more') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ===================== تفعيل الموظفين ===================== --}}
        @if (in_array('employees', $tabs, true))
            <div class="hrp-pane {{ $activeTab === 'employees' ? '' : 'd-none' }}" data-hrp-pane="employees">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button type="button" class="btn btn-main hrp-filter-toggle" data-target="#hrpEmpFilter">
                        <i class="las la-filter me-1"></i> {{ __('hr::portal.filters') }}
                    </button>
                    @can('edit Employee Portal')
                        <button type="button" class="btn btn-outline-success hrp-bulk" data-enabled="1">
                            <i class="las la-check-double me-1"></i> {{ __('hr::portal.enable_selected') }}
                        </button>
                        <button type="button" class="btn btn-outline-danger hrp-bulk" data-enabled="0">
                            <i class="las la-ban me-1"></i> {{ __('hr::portal.disable_selected') }}
                        </button>
                    @endcan
                </div>
                <div id="hrpEmpFilter" class="card mb-3" style="display:none;">
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.search') }}</label>
                                <input type="text" class="form-control form-control-sm hrp-filter" data-pane="employees" name="search">
                            </div>
                            <div class="col-md-3">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.department') }}</label>
                                <select class="form-select form-select-sm hrp-filter" data-pane="employees" name="department_id">
                                    <option value="">{{ __('hr::portal.all_departments') }}</option>
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.portal_access') }}</label>
                                <select class="form-select form-select-sm hrp-filter" data-pane="employees" name="portal">
                                    <option value="">{{ __('hr::portal.all') }}</option>
                                    <option value="enabled">{{ __('hr::portal.portal_enabled_filter') }}</option>
                                    <option value="disabled">{{ __('hr::portal.portal_disabled_filter') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:32px;"><input type="checkbox" class="form-check-input" id="hrpEmpCheckAll"></th>
                                    <th>#</th>
                                    <th>{{ __('hr::portal.employee') }}</th>
                                    <th>{{ __('hr::portal.finger_print_id') }}</th>
                                    <th>{{ __('hr::portal.phone') }}</th>
                                    <th>{{ __('hr::portal.department') }}</th>
                                    <th>{{ __('hr::portal.portal_access') }}</th>
                                    <th>{{ __('hr::portal.password_status') }}</th>
                                    <th>{{ __('hr::portal.devices_count') }}</th>
                                    <th>{{ __('hr::portal.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody data-hrp-body="employees"></tbody>
                        </table>
                        <div class="text-center py-2 d-none" data-hrp-more="employees">
                            <button type="button" class="btn btn-outline-secondary btn-sm hrp-load-more" data-pane="employees">{{ __('hr::portal.load_more') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ===================== بصمات تحتاج مراجعة ===================== --}}
        @if (in_array('suspicious', $tabs, true))
            <div class="hrp-pane {{ $activeTab === 'suspicious' ? '' : 'd-none' }}" data-hrp-pane="suspicious">
                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-main hrp-filter-toggle" data-target="#hrpSusFilter">
                        <i class="las la-filter me-1"></i> {{ __('hr::portal.filters') }}
                    </button>
                </div>
                <div id="hrpSusFilter" class="card mb-3" style="display:none;">
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.date_from') }}</label>
                                <input type="date" class="form-control form-control-sm hrp-filter" data-pane="suspicious" name="date_from">
                            </div>
                            <div class="col-md-2">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.date_to') }}</label>
                                <input type="date" class="form-control form-control-sm hrp-filter" data-pane="suspicious" name="date_to">
                            </div>
                            <div class="col-md-3">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.employee') }}</label>
                                <input type="text" class="form-control form-control-sm hrp-filter" data-pane="suspicious" name="search">
                            </div>
                            <div class="col-md-2">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.kind') }}</label>
                                <select class="form-select form-select-sm hrp-filter" data-pane="suspicious" name="kind">
                                    <option value="">{{ __('hr::portal.all') }}</option>
                                    <option value="suspicious">{{ __('hr::portal.kind_suspicious') }}</option>
                                    <option value="remote">{{ __('hr::portal.kind_remote') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('hr::portal.employee') }}</th>
                                    <th>{{ __('hr::portal.type') }}</th>
                                    <th>{{ __('hr::portal.date') }}</th>
                                    <th>{{ __('hr::portal.time') }}</th>
                                    <th>{{ __('hr::portal.distance') }}</th>
                                    <th>{{ __('hr::portal.accuracy') }}</th>
                                    <th>{{ __('hr::portal.reasons') }}</th>
                                    <th>{{ __('hr::portal.device_model') }}</th>
                                    <th>{{ __('hr::portal.selfie') }}</th>
                                    <th>{{ __('hr::portal.map') }}</th>
                                </tr>
                            </thead>
                            <tbody data-hrp-body="suspicious"></tbody>
                        </table>
                        <div class="text-center py-2 d-none" data-hrp-more="suspicious">
                            <button type="button" class="btn btn-outline-secondary btn-sm hrp-load-more" data-pane="suspicious">{{ __('hr::portal.load_more') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ===================== الإعدادات ===================== --}}
        @if (in_array('settings', $tabs, true))
            <div class="hrp-pane {{ $activeTab === 'settings' ? '' : 'd-none' }}" data-hrp-pane="settings">
                <form id="hrpSettingsForm" class="card" novalidate>
                    <div class="card-body">
                        <div class="alert alert-info small mb-3">
                            <i class="las la-info-circle"></i> {{ __('hr::portal.settings_location_hint') }}
                            <br><i class="las la-mobile"></i> {{ __('hr::portal.app_download_hint') }}
                        </div>

                        <h6 class="font-hold fw-bold mb-3" style="color: var(--main-color);">{{ __('hr::portal.settings_general') }}</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="portal_enabled" id="hrp_portal_enabled" @checked($settings->enabled())>
                                    <label class="form-check-label font-hold" for="hrp_portal_enabled">{{ __('hr::portal.setting_portal_enabled') }}</label>
                                </div>
                            </div>
                        </div>

                        <h6 class="font-hold fw-bold mb-3" style="color: var(--main-color);">{{ __('hr::portal.settings_security') }}</h6>
                        <div class="row g-3 mb-4">
                            @foreach ([
                                'portal_require_biometric' => $settings->requireBiometric(),
                                'portal_device_binding' => $settings->deviceBinding(),
                                'portal_enforce_geofence' => $settings->enforceGeofence(),
                                'portal_block_mock_location' => $settings->blockMockLocation(),
                                'portal_selfie_required' => $settings->selfieRequired(),
                            ] as $key => $checked)
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="{{ $key }}" id="hrp_{{ $key }}" @checked($checked)>
                                        <label class="form-check-label font-hold" for="hrp_{{ $key }}">{{ __('hr::portal.setting_'.$key) }}</label>
                                    </div>
                                </div>
                            @endforeach
                            <div class="col-md-3">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.setting_portal_max_devices_per_employee') }}</label>
                                <input type="text" inputmode="numeric" class="form-control form-control-sm" name="portal_max_devices_per_employee" value="{{ $settings->maxDevices() }}">
                            </div>
                            <div class="col-md-3">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.setting_portal_max_gps_accuracy_meters') }}</label>
                                <input type="text" inputmode="numeric" class="form-control form-control-sm" name="portal_max_gps_accuracy_meters" value="{{ $settings->maxGpsAccuracy() }}">
                            </div>
                        </div>

                        <h6 class="font-hold fw-bold mb-3" style="color: var(--main-color);">{{ __('hr::portal.settings_requests') }}</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.setting_portal_approval_flow') }}</label>
                                <select class="form-select form-select-sm" name="portal_approval_flow">
                                    @foreach (\Modules\HR\Services\Portal\PortalSettings::FLOWS as $flow)
                                        <option value="{{ $flow }}" @selected($settings->approvalFlow() === $flow)>{{ __('hr::portal.flow_'.$flow) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.setting_portal_checkin_reminder_minutes') }}</label>
                                <input type="text" inputmode="numeric" class="form-control form-control-sm" name="portal_checkin_reminder_minutes" value="{{ $settings->checkinReminderMinutes() }}">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="portal_show_payslip" id="hrp_portal_show_payslip" @checked($settings->showPayslip())>
                                    <label class="form-check-label font-hold" for="hrp_portal_show_payslip">{{ __('hr::portal.setting_portal_show_payslip') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    @can('edit Employee Portal')
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-main" id="hrpSettingsSave">
                                <i class="las la-save me-1"></i> {{ __('hr::portal.save') }}
                            </button>
                        </div>
                    @endcan
                </form>
            </div>
        @endif
    </div>

    {{-- شريط الإجمالي الثابت --}}
    <div class="position-fixed bottom-0 start-0 end-0 bg-white border-top shadow-sm py-2 px-3" style="z-index: 1020;">
        <div class="d-flex flex-wrap justify-content-center gap-4 small font-hold">
            <span>{{ __('hr::portal.records_loaded') }}: <strong id="hrpFooterLoaded">0</strong></span>
            <span>{{ __('hr::portal.pending_requests') }}: <strong id="hrpFooterReq">{{ $pendingRequests }}</strong></span>
            <span>{{ __('hr::portal.pending_devices') }}: <strong id="hrpFooterDev">{{ $pendingDevices }}</strong></span>
        </div>
    </div>

    {{-- Modal: تفاصيل الطلب + القرار --}}
    <div class="modal fade" id="hrpRequestModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title font-hold fw-bold">{{ __('hr::portal.request_details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="hrpReqDetails"></div>
                    <h6 class="font-hold fw-bold mt-3" style="color: var(--main-color);">{{ __('hr::portal.timeline') }}</h6>
                    <ul class="list-group list-group-flush small" id="hrpReqTimeline"></ul>

                    @can('edit Portal Requests')
                        <form id="hrpDecisionForm" class="mt-3 d-none" enctype="multipart/form-data" novalidate>
                            <div class="mb-2">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.decision_note') }}</label>
                                <textarea class="form-control form-control-sm" name="note" rows="2" maxlength="1000"></textarea>
                            </div>
                            <div class="mb-2 d-none" id="hrpDocumentWrap">
                                <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.upload_document') }}</label>
                                <input type="file" class="form-control form-control-sm" name="document" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                        </form>
                    @endcan
                </div>
                <div class="modal-footer py-2">
                    @can('edit Portal Requests')
                        <button type="button" class="btn btn-danger d-none hrp-decide" data-decision="reject">
                            <i class="las la-times me-1"></i> {{ __('hr::portal.reject') }}
                        </button>
                        <button type="button" class="btn btn-success d-none hrp-decide" data-decision="approve">
                            <i class="las la-check me-1"></i> {{ __('hr::portal.approve') }}
                        </button>
                    @endcan
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('hr::portal.close') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: تعيين كلمة مرور --}}
    @can('edit Employee Portal')
        <div class="modal fade" id="hrpPasswordModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" id="hrpPasswordForm" novalidate autocomplete="off">
                    <div class="modal-header py-2">
                        <h5 class="modal-title font-hold fw-bold">{{ __('hr::portal.reset_password') }} — <span id="hrpPasswordName"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="{{ $hrpLabel }}" style="{{ $hrpLabelStyle }}">{{ __('hr::portal.field_new_password') }}</label>
                        <input type="text" class="form-control" name="password" minlength="6" maxlength="100" required>
                        <div class="form-text">{{ __('hr::portal.reset_password_hint') }}</div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="submit" class="btn btn-main" id="hrpPasswordSave">{{ __('hr::portal.save') }}</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('hr::portal.cancel') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@push('scripts')
<script>
    window._hrPortalConfig = {
        activeTab: @json($activeTab),
        csrfToken: @json(csrf_token()),
        can: {
            editRequests: @json(auth()->user()->can('edit Portal Requests')),
            editDevices: @json(auth()->user()->can('edit Employee Devices')),
            editPortal: @json(auth()->user()->can('edit Employee Portal'))
        },
        routes: {
            requestsData: @json(route('hr.employee-portal.requests.data')),
            requestShow: @json(route('hr.employee-portal.requests.show', ['portalRequest' => '__ID__'])),
            requestApprove: @json(route('hr.employee-portal.requests.approve', ['portalRequest' => '__ID__'])),
            requestReject: @json(route('hr.employee-portal.requests.reject', ['portalRequest' => '__ID__'])),
            devicesData: @json(route('hr.employee-portal.devices.data')),
            deviceApprove: @json(route('hr.employee-portal.devices.approve', ['device' => '__ID__'])),
            deviceRevoke: @json(route('hr.employee-portal.devices.revoke', ['device' => '__ID__'])),
            employeesData: @json(route('hr.employee-portal.employees.data')),
            employeeToggle: @json(route('hr.employee-portal.employees.toggle', ['employee' => '__ID__'])),
            employeePassword: @json(route('hr.employee-portal.employees.password', ['employee' => '__ID__'])),
            employeesBulk: @json(route('hr.employee-portal.employees.bulk')),
            suspiciousData: @json(route('hr.employee-portal.suspicious.data')),
            selfie: @json(route('hr.employee-portal.attendance.selfie', ['attendance' => '__ID__'])),
            settingsUpdate: @json(route('hr.employee-portal.settings.update'))
        },
        trans: {
            empty: @json(__('hr::portal.no_data')),
            loading: @json(__('hr::portal.loading')),
            error: @json(__('hr::portal.error_generic')),
            view: @json(__('hr::portal.view')),
            approve: @json(__('hr::portal.approve')),
            revoke: @json(__('hr::portal.revoke')),
            resetPassword: @json(__('hr::portal.reset_password')),
            passwordNotSet: @json(__('hr::portal.password_not_set')),
            passwordTemporary: @json(__('hr::portal.password_temporary')),
            passwordSet: @json(__('hr::portal.password_set')),
            remote: @json(__('hr::portal.remote')),
            selectFirst: @json(__('hr::portal.select_employees_first')),
            confirmRevoke: @json(__('hr::portal.confirm_revoke_device')),
            confirmApproveDevice: @json(__('hr::portal.confirm_approve_device')),
            rejectReasonRequired: @json(__('hr::portal.reject_reason_required')),
            manager: @json(__('hr::portal.manager'))
        }
    };
</script>
<script src="{{ asset('js/hr-employee-portal.js') }}"></script>
@endpush
