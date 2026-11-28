/**
 * HR Employee create/edit form — tabs helpers, cascade, KPI/leave rows, Ajax submit.
 * Config: window._hrEmployeeFormConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrEmployeeFormConfig;
    if (!cfg || !$) {
        return;
    }

    var state = {
        isSubmitting: false,
        kpiIds: [],
        kpiWeights: {},
        leaveBalances: {},
        lineManagerId: null
    };

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function setSubmitting(on) {
        state.isSubmitting = !!on;
        $('.js-employee-submit').prop('disabled', !!on);
        if (on) {
            $('.js-employee-submit').html('<i class="fas fa-spinner fa-spin me-2"></i>' + escapeHtml(cfg.lang.saving));
        } else {
            $('.js-employee-submit').html('<i class="fas fa-save me-2"></i>' + escapeHtml(cfg.lang.save));
        }
    }

    function clearErrors() {
        $('#hrEmployeeForm .is-invalid').removeClass('is-invalid');
        $('#hrEmployeeForm .invalid-feedback').text('');
        $('#hrEmployeeValidationErrors').addClass('d-none');
        $('#hrEmployeeValidationList').empty();
        $('#employeeFormTabs .hr-tab-error-badge').addClass('d-none').text('0');
        $('#employeeFormTabs .nav-link').removeClass('text-danger');
    }

    function applyErrors(errors) {
        clearErrors();
        if (!errors) {
            return;
        }
        var list = [];
        Object.keys(errors).forEach(function (key) {
            var messages = errors[key] || [];
            var msg = messages[0] || '';
            list.push(msg);
            var $input = $('#hrEmployeeForm [name="' + key + '"]');
            if (!$input.length && key.indexOf('.') !== -1) {
                $input = $('#hrEmployeeForm [name="' + key.replace(/\./g, '][') + '"]');
            }
            if ($input.length) {
                $input.addClass('is-invalid');
                $input.siblings('.invalid-feedback').first().text(msg);
            }
            $('[data-error-for="' + key + '"]').text(msg);
        });
        if (list.length) {
            var $ul = $('#hrEmployeeValidationList');
            list.forEach(function (m) {
                $ul.append('<li>' + escapeHtml(m) + '</li>');
            });
            $('#hrEmployeeValidationErrors').removeClass('d-none');
        }
    }

    function serverErrorMessage(xhr) {
        var json = xhr.responseJSON || null;
        if (!json && xhr.responseText) {
            try {
                json = JSON.parse(xhr.responseText);
            } catch (e) {
                json = null;
            }
        }
        if (json) {
            if (json.message) {
                return String(json.message);
            }
            if (json.error) {
                return String(json.error);
            }
        }
        if (xhr.status) {
            return 'HTTP ' + xhr.status;
        }
        return cfg.lang.error;
    }

    function showAlert(type, message) {
        var $box = $('#hrEmployeeFormAlert');
        $box.removeClass('d-none alert-success alert-danger')
            .addClass(type === 'success' ? 'alert-success' : 'alert-danger')
            .addClass('alert')
            .text(message);
    }

    /* ---------- Cascade address ---------- */
    function filterSelect($select, attr, parentId, keepValue) {
        var $options = $select.find('option').not(':first');
        $options.each(function () {
            var match = !parentId || String($(this).data(attr)) === String(parentId);
            $(this).toggle(match);
            if (!match && String($(this).val()) === String($select.val())) {
                $select.val('');
            }
        });
        if (keepValue) {
            $select.val(keepValue);
        }
    }

    function bindCascade() {
        $('#hrEmpCountry').on('change', function () {
            var countryId = $(this).val();
            filterSelect($('#hrEmpState'), 'country', countryId);
            $('#hrEmpCity').val('');
            $('#hrEmpTown').val('');
            filterSelect($('#hrEmpCity'), 'state', null);
            filterSelect($('#hrEmpTown'), 'city', null);
        });

        $('#hrEmpState').on('change', function () {
            var stateId = $(this).val();
            filterSelect($('#hrEmpCity'), 'state', stateId);
            $('#hrEmpTown').val('');
            filterSelect($('#hrEmpTown'), 'city', null);
        });

        $('#hrEmpCity').on('change', function () {
            filterSelect($('#hrEmpTown'), 'city', $(this).val());
        });

        // Initial filter from payload
        var payload = cfg.formPayload || {};
        if (payload.country_id) {
            filterSelect($('#hrEmpState'), 'country', payload.country_id, payload.state_id);
        }
        if (payload.state_id) {
            filterSelect($('#hrEmpCity'), 'state', payload.state_id, payload.city_id);
        }
        if (payload.city_id) {
            filterSelect($('#hrEmpTown'), 'city', payload.city_id, payload.town_id);
        }
    }

    /* ---------- Line managers ---------- */
    function refreshLineManagers() {
        var deptId = $('#hrEmpDepartment').val();
        var $director = $('#hrEmpDeptDirector');
        var $lm = $('#hrEmpLineManager');
        var selected = state.lineManagerId || (cfg.formPayload && cfg.formPayload.line_manager_id) || '';
        var directorId = $('#hrEmpDepartment option:selected').data('director-id') || '';
        $director.val(String(directorId));

        $lm.empty().append('<option value="">' + escapeHtml(cfg.lang.selectLineManager) + '</option>');
        (cfg.lookups.employees || []).forEach(function (emp) {
            if (!deptId || String(emp.department_id) !== String(deptId)) {
                return;
            }
            if (cfg.formPayload && String(emp.id) === String(cfg.formPayload.id)) {
                return;
            }
            $lm.append(
                $('<option>', { value: emp.id, text: emp.name, selected: String(emp.id) === String(selected) })
            );
        });
    }

    /* ---------- KPIs ---------- */
    function renderKpis() {
        var $list = $('#hrEmpKpiList').empty();
        var $hidden = $('#hrEmpKpiHiddenInputs').empty();
        $('#hrEmpKpiEmpty').toggle(state.kpiIds.length === 0);

        var total = 0;
        state.kpiIds.forEach(function (id) {
            var weight = parseInt(state.kpiWeights[id], 10) || 0;
            total += weight;
            var kpi = (cfg.lookups.kpis || []).find(function (k) { return String(k.id) === String(id); });
            var name = kpi ? kpi.name : id;
            var desc = kpi && kpi.description ? String(kpi.description).substring(0, 50) : '';

            $list.append(
                '<div class="col-md-4 col-sm-6" data-kpi-id="' + id + '">' +
                '<div class="card border-success h-100"><div class="card-body">' +
                '<div class="d-flex justify-content-between"><div><h6 class="text-success fw-bold mb-1">' + escapeHtml(name) + '</h6>' +
                '<small class="text-muted">' + escapeHtml(desc) + '</small></div>' +
                '<button type="button" class="btn btn-outline-danger btn-sm js-remove-kpi" data-id="' + id + '"><i class="fas fa-times"></i></button></div>' +
                '<label class="form-label small mt-2">%</label>' +
                '<input type="text" class="form-control form-control-sm js-kpi-weight" data-id="' + id + '" value="' + weight + '">' +
                '</div></div></div>'
            );

            $hidden.append('<input type="hidden" name="kpi_ids[]" value="' + id + '">');
            $hidden.append('<input type="hidden" name="kpi_weights[' + id + ']" class="js-kpi-weight-hidden" data-id="' + id + '" value="' + weight + '">');
        });

        var $alert = $('#hrEmpKpiWeightAlert');
        var $bar = $('#hrEmpKpiProgress');
        $bar.css('width', Math.min(total, 100) + '%').text(total + '%');
        $bar.removeClass('bg-success bg-danger bg-warning');
        $alert.removeClass('d-none alert-success alert-danger alert-warning');
        if (state.kpiIds.length === 0) {
            $alert.addClass('d-none');
            $bar.addClass('bg-warning');
        } else if (total === 100) {
            $alert.addClass('alert-success').text(cfg.lang.weightOk);
            $bar.addClass('bg-success');
        } else if (total > 100) {
            $alert.addClass('alert-danger').text(String(cfg.lang.weightHigh).replace(':total', total));
            $bar.addClass('bg-danger');
        } else {
            $alert.addClass('alert-warning').text(String(cfg.lang.weightLow).replace(':total', total));
            $bar.addClass('bg-warning');
        }
    }

    function addKpi() {
        var id = $('#hrEmpKpiSelect').val();
        if (!id) {
            return;
        }
        id = parseInt(id, 10);
        if (state.kpiIds.indexOf(id) !== -1) {
            return;
        }
        state.kpiIds.push(id);
        state.kpiWeights[id] = state.kpiWeights[id] || 0;
        $('#hrEmpKpiSelect').val('');
        renderKpis();
    }

    /* ---------- Leave balances ---------- */
    function leaveRemaining(b) {
        var rem = (parseFloat(b.opening_balance_days) || 0) - (parseFloat(b.used_days) || 0) - (parseFloat(b.pending_days) || 0);
        return rem.toFixed(1);
    }

    function leaveTypeName(id) {
        var lt = (cfg.lookups.leaveTypes || []).find(function (x) { return String(x.id) === String(id); });
        return lt ? lt.name : id;
    }

    function renderLeaves() {
        var $body = $('#hrEmpLeaveBody').empty();
        var keys = Object.keys(state.leaveBalances);
        $('#hrEmpLeaveEmpty').toggle(keys.length === 0);
        $('#hrEmpLeaveTable').toggle(keys.length > 0);

        keys.forEach(function (key, index) {
            var b = state.leaveBalances[key];
            var rem = leaveRemaining(b);
            $body.append(
                '<tr data-leave-key="' + escapeHtml(key) + '">' +
                '<td class="align-middle fw-bold text-primary">' + escapeHtml(leaveTypeName(b.leave_type_id)) +
                '<input type="hidden" name="leave_balances[' + index + '][leave_type_id]" value="' + b.leave_type_id + '"></td>' +
                '<td><input type="text" class="form-control form-control-sm text-center" name="leave_balances[' + index + '][year]" value="' + (b.year || '') + '"></td>' +
                '<td><input type="text" class="form-control form-control-sm text-center js-leave-field" data-field="opening_balance_days" name="leave_balances[' + index + '][opening_balance_days]" value="' + (b.opening_balance_days || 0) + '"></td>' +
                '<td><input type="text" class="form-control form-control-sm text-center js-leave-field" data-field="used_days" name="leave_balances[' + index + '][used_days]" value="' + (b.used_days || 0) + '"></td>' +
                '<td><input type="text" class="form-control form-control-sm text-center js-leave-field" data-field="pending_days" name="leave_balances[' + index + '][pending_days]" value="' + (b.pending_days || 0) + '"></td>' +
                '<td><input type="text" class="form-control form-control-sm text-center js-leave-field" data-field="max_monthly_days" name="leave_balances[' + index + '][max_monthly_days]" value="' + (b.max_monthly_days || 0) + '"></td>' +
                '<td class="text-center align-middle"><span class="badge ' + (parseFloat(rem) >= 0 ? 'bg-success' : 'bg-danger') + ' js-leave-rem">' + rem + '</span></td>' +
                '<td><textarea class="form-control form-control-sm js-leave-field" data-field="notes" name="leave_balances[' + index + '][notes]" rows="1">' + escapeHtml(b.notes || '') + '</textarea></td>' +
                '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger js-remove-leave" data-key="' + escapeHtml(key) + '"><i class="fas fa-times"></i></button></td>' +
                '</tr>'
            );
        });
    }

    function addLeave() {
        var typeId = $('#hrEmpLeaveTypeSelect').val();
        if (!typeId) {
            return;
        }
        typeId = parseInt(typeId, 10);
        var year = cfg.currentYear || (new Date()).getFullYear();
        var key = typeId + '_' + year;
        if (state.leaveBalances[key]) {
            return;
        }
        state.leaveBalances[key] = {
            leave_type_id: typeId,
            year: year,
            opening_balance_days: 0,
            used_days: 0,
            pending_days: 0,
            max_monthly_days: 0,
            notes: ''
        };
        $('#hrEmpLeaveTypeSelect').val('');
        renderLeaves();
    }

    /* ---------- Submit ---------- */
    function submitForm(e) {
        e.preventDefault();
        if (state.isSubmitting) {
            return;
        }

        // Sync leave state from inputs before submit
        $('#hrEmpLeaveBody tr').each(function () {
            var key = $(this).data('leave-key');
            if (!state.leaveBalances[key]) {
                return;
            }
            $(this).find('.js-leave-field').each(function () {
                var field = $(this).data('field');
                state.leaveBalances[key][field] = $(this).val();
            });
            var yearVal = $(this).find('[name*="[year]"]').val();
            if (yearVal) {
                state.leaveBalances[key].year = yearVal;
            }
        });
        renderLeaves();
        renderKpis();

        setSubmitting(true);
        clearErrors();

        var form = $('#hrEmployeeForm')[0];
        var formData = new FormData(form);

        $.ajax({
            url: cfg.storeUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function (res) {
                showAlert('success', (res && res.message) || 'OK');
                if (res && res.redirect) {
                    window.location.href = res.redirect;
                    return;
                }
                setSubmitting(false);
            },
            error: function (xhr) {
                setSubmitting(false);
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    applyErrors(xhr.responseJSON.errors);
                    showAlert('error', cfg.lang.error);
                    return;
                }
                showAlert('error', serverErrorMessage(xhr));
            }
        });
    }

    function hydrateFromPayload() {
        var p = cfg.formPayload || {};
        if (p.kpi_ids) {
            state.kpiIds = p.kpi_ids.map(function (id) { return parseInt(id, 10); });
        }
        if (p.kpi_weights) {
            state.kpiWeights = p.kpi_weights;
        }
        if (p.leave_balances) {
            state.leaveBalances = p.leave_balances;
        }
        state.lineManagerId = p.line_manager_id || null;
    }

    $(function () {
        hydrateFromPayload();
        bindCascade();
        refreshLineManagers();
        renderKpis();
        renderLeaves();

        $('#hrEmpDepartment').on('change', function () {
            state.lineManagerId = null;
            refreshLineManagers();
        });

        $('#hrEmpAddKpiBtn').on('click', addKpi);
        $(document).on('click', '.js-remove-kpi', function () {
            if (!window.confirm(cfg.lang.confirmRemoveKpi)) {
                return;
            }
            var id = parseInt($(this).data('id'), 10);
            state.kpiIds = state.kpiIds.filter(function (x) { return x !== id; });
            delete state.kpiWeights[id];
            renderKpis();
        });
        $(document).on('input', '.js-kpi-weight', function () {
            var id = $(this).data('id');
            state.kpiWeights[id] = parseInt($(this).val(), 10) || 0;
            renderKpis();
        });

        $('#hrEmpAddLeaveBtn').on('click', addLeave);
        $(document).on('click', '.js-remove-leave', function () {
            if (!window.confirm(cfg.lang.confirmRemoveLeave)) {
                return;
            }
            delete state.leaveBalances[$(this).data('key')];
            renderLeaves();
        });
        $(document).on('input', '.js-leave-field', function () {
            var $tr = $(this).closest('tr');
            var key = $tr.data('leave-key');
            if (!state.leaveBalances[key]) {
                return;
            }
            var field = $(this).data('field');
            state.leaveBalances[key][field] = $(this).val();
            $tr.find('.js-leave-rem').text(leaveRemaining(state.leaveBalances[key]));
        });

        $('#hrEmpTogglePassword').on('click', function () {
            var $input = $('#hrEmpPassword');
            var isPass = $input.attr('type') === 'password';
            $input.attr('type', isPass ? 'text' : 'password');
            $(this).find('i').toggleClass('fa-eye fa-eye-slash');
        });

        $('#employee-image-input').on('change', function () {
            var file = this.files && this.files[0];
            if (!file) {
                return;
            }
            if (!file.type || file.type.indexOf('image/') !== 0) {
                return; // Non-image files: keep current preview
            }
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#employee-image-preview').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        });

        $('#hrEmployeeForm').on('submit', submitForm);
        $('.js-employee-submit').on('click', function (e) {
            e.preventDefault();
            $('#hrEmployeeForm').trigger('submit');
        });
    });
})(window.jQuery);
