@php $p = $p ?? []; @endphp
<div>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white py-3">
            <h6 class="card-title mb-0 font-hold fw-bold">
                <i class="fas fa-calendar-check me-2"></i>{{ __('hr::hr.leave_balances') }}
            </h6>
        </div>
        <div class="card-body p-4">
            <div class="card mb-4 border border-primary">
                <div class="card-header bg-light border-0 py-2">
                    <h6 class="card-title mb-0 font-hold fw-bold text-primary">{{ __('إضافة رصيد إجازة جديد') }}</h6>
                </div>
                <div class="card-body py-3">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">{{ __('اختر نوع الإجازة') }}</label>
                            <select id="hrEmpLeaveTypeSelect" class="form-select">
                                <option value="">{{ __('ابحث عن نوع الإجازة...') }}</option>
                                @foreach ($leaveTypes as $leaveType)
                                    <option value="{{ $leaveType->id }}" data-code="{{ e($leaveType->code) }}">
                                        {{ $leaveType->name }} @if($leaveType->code) ({{ $leaveType->code }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-main w-100" id="hrEmpAddLeaveBtn">
                                <i class="fas fa-plus me-2"></i>{{ __('إضافة') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="hrEmpLeaveTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">{{ __('نوع الإجازة') }}</th>
                            <th class="text-center">{{ __('السنة') }}</th>
                            <th class="text-center">{{ __('الرصيد الافتتاحي') }}</th>
                            <th class="text-center">{{ __('المستخدمة') }}</th>
                            <th class="text-center">{{ __('المعلقة') }}</th>
                            <th class="text-center">{{ __('الحد الشهري الأقصى') }}</th>
                            <th class="text-center">{{ __('المتبقي') }}</th>
                            <th class="text-center">{{ __('ملاحظات') }}</th>
                            <th class="text-center">{{ __('hr::hr.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="hrEmpLeaveBody"></tbody>
                </table>
            </div>
            <div id="hrEmpLeaveEmpty" class="alert alert-info text-center">
                {{ __('لم يتم إضافة أي رصيد إجازة بعد.') }}
            </div>
        </div>
    </div>
</div>
