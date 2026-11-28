<form id="hrLeaveRequestForm">
    <div id="hrLeaveRequestGeneralError" class="alert alert-danger d-none font-hold fw-bold"></div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="employee_id" class="form-label font-hold fw-bold">{{ __('hr::hr.employee') }} <span class="text-danger">*</span></label>
            <select name="employee_id" id="employee_id" class="form-select font-hold fw-bold js-searchable-filter" required>
                <option value="">{{ __('hr::hr.select_employee') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected((string) old('employee_id', $leaveRequest?->employee_id) === (string) $employee->id)>
                        {{ $employee->name }}
                    </option>
                @endforeach
            </select>
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6 mb-3">
            <label for="leave_type_id" class="form-label font-hold fw-bold">{{ __('hr::hr.leave_type') }} <span class="text-danger">*</span></label>
            <select name="leave_type_id" id="leave_type_id" class="form-select font-hold fw-bold" required>
                <option value="">{{ __('hr::hr.select_leave_type') }}</option>
                @foreach ($leaveTypes as $type)
                    <option value="{{ $type->id }}" @selected((string) old('leave_type_id', $leaveRequest?->leave_type_id) === (string) $type->id)>
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6 mb-3">
            <label for="start_date" class="form-label font-hold fw-bold">{{ __('hr::hr.start_date') }} <span class="text-danger">*</span></label>
            <input type="date" name="start_date" id="start_date" class="form-control font-hold fw-bold"
                   value="{{ old('start_date', $startDate) }}" required>
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6 mb-3">
            <label for="end_date" class="form-label font-hold fw-bold">{{ __('hr::hr.end_date') }} <span class="text-danger">*</span></label>
            <input type="date" name="end_date" id="end_date" class="form-control font-hold fw-bold"
                   value="{{ old('end_date', $endDate) }}" required>
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12 mb-3">
            <label for="reason" class="form-label font-hold fw-bold">{{ __('hr::hr.leave_reason') }}</label>
            <textarea name="reason" id="reason" rows="3" class="form-control font-hold fw-bold"
                      placeholder="{{ __('hr::hr.leave_reason_placeholder') }}">{{ old('reason', $leaveRequest?->reason) }}</textarea>
            <div class="invalid-feedback"></div>
        </div>
    </div>

    <div id="hrLeaveRequestPreview" class="row mt-2 d-none">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-header">
                    <h5 class="card-title mb-0 font-hold fw-bold">{{ __('hr::hr.balance_information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <h4 class="text-primary" id="hrLrAvailable">0.0</h4>
                            <p class="text-muted mb-0 font-hold">{{ __('hr::hr.available_balance') }}</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4 class="text-info" id="hrLrDays">0.0</h4>
                            <p class="text-muted mb-0 font-hold">{{ __('hr::hr.calculated_days') }}</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4 id="hrLrRemaining">0.0</h4>
                            <p class="text-muted mb-0 font-hold">{{ __('hr::hr.remaining_balance') }}</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <div id="hrLrOverlap" class="alert mb-0"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-end mt-4">
        <a href="{{ $cancelUrl }}" class="btn btn-secondary font-hold fw-bold">{{ __('hr::hr.cancel') }}</a>
        <button type="submit" class="btn btn-main font-hold fw-bold" id="hrLeaveRequestSubmitBtn">
            <i class="fas fa-save me-1"></i>{{ $submitLabel }}
        </button>
    </div>
</form>
