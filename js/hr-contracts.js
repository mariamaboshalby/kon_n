/**
 * HR Contracts — Ajax CRUD with nested points
 * Config: window._hrContractsConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrContractsConfig;
    if (!cfg) return;

    var HEADER_FIELDS = [
        'name', 'contract_type_id', 'employee_id', 'job_id',
        'contract_start_date', 'contract_end_date',
        'trial_period', 'status',
        'fixed_work_hours', 'additional_work_hours',
        'monthly_holidays', 'monthly_sick_days',
        'information', 'job_description'
    ];

    var state = {
        search: '', status: '', contractTypeId: '', jobId: '', employeeId: '', dateFrom: '', dateTo: '',
        searchTimer: null, isSubmitting: false, editId: null,
        modal: null, viewModal: null, rows: []
    };

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }
    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }
    function showAlert(type, message) {
        var $box = $('#hrContractAlert');
        $box.removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () { $box.addClass('d-none').removeClass('show'); }, 4000);
    }
    function setSubmitting(on) {
        state.isSubmitting = !!on;
        $('#hrContractSubmitBtn').prop('disabled', !!on);
    }
    function clearErrors() {
        $('#hrContractForm .is-invalid').removeClass('is-invalid');
        $('#hrContractForm .invalid-feedback').text('');
    }
    function errorFieldName(key) {
        return key.replace(/\.(\d+)/g, '[$1]').replace(/\.([^.]+)/g, '[$1]');
    }
    function applyErrors(errors) {
        clearErrors();
        if (!errors) return;
        Object.keys(errors).forEach(function (key) {
            var name = errorFieldName(key);
            var $input = $('#hrContractForm [name="' + name + '"]');
            $input.addClass('is-invalid');
            var $fb = $input.siblings('.invalid-feedback');
            if (!$fb.length) $fb = $input.closest('.hr-contract-point-row, .hr-salary-point-row').find('.invalid-feedback').first();
            $fb.text((errors[key] || [])[0] || '');
        });
    }
    function dateOnly(val) {
        if (!val) return '';
        return String(val).substring(0, 10);
    }

    function statusBadge(status) {
        var map = {
            pending: ['warning', 'text-dark'],
            active: ['success', ''],
            expired: ['secondary', ''],
            terminated: ['danger', '']
        };
        var labels = (cfg.trans && cfg.trans.statusLabels) || {};
        var m = map[status] || ['secondary', ''];
        var label = labels[status] || status || '-';
        return '<span class="badge bg-' + m[0] + ' ' + m[1] + '">' + escapeHtml(label) + '</span>';
    }

    function pointRowHtml(prefix, index, point) {
        point = point || {};
        var name = point.name || '';
        var info = point.information || '';
        var rowClass = prefix === 'contract_points' ? 'hr-contract-point-row' : 'hr-salary-point-row';
        return (
            '<div class="d-flex gap-2 mb-2 align-items-start ' + rowClass + '" data-index="' + index + '">' +
            '<div class="flex-grow-1">' +
            '<input type="text" name="' + prefix + '[' + index + '][name]" class="form-control font-hold fw-bold" value="' + escapeHtml(name) + '" placeholder="' + escapeHtml(cfg.trans.pointName || '') + '">' +
            '<div class="invalid-feedback"></div>' +
            '</div>' +
            '<div class="flex-grow-1">' +
            '<input type="text" name="' + prefix + '[' + index + '][information]" class="form-control font-hold fw-bold" value="' + escapeHtml(info) + '" placeholder="' + escapeHtml(cfg.trans.information || '') + '">' +
            '<div class="invalid-feedback"></div>' +
            '</div>' +
            '<button type="button" class="btn btn-danger btn-sm hr-contract-remove-point" title="remove"><i class="las la-trash"></i></button>' +
            '</div>'
        );
    }

    function reindexPoints($wrap, prefix) {
        $wrap.children().each(function (i) {
            $(this).attr('data-index', i);
            $(this).find('input').each(function () {
                var n = $(this).attr('name') || '';
                if (n.indexOf('[name]') !== -1) {
                    $(this).attr('name', prefix + '[' + i + '][name]');
                } else if (n.indexOf('[information]') !== -1) {
                    $(this).attr('name', prefix + '[' + i + '][information]');
                }
            });
        });
    }

    function renderPoints(contractPoints, salaryPoints) {
        var $c = $('#hrContractPointsWrap');
        var $s = $('#hrSalaryPointsWrap');
        $c.empty();
        $s.empty();
        var cps = contractPoints && contractPoints.length ? contractPoints : [{ name: '', information: '' }];
        var sps = salaryPoints && salaryPoints.length ? salaryPoints : [{ name: '', information: '' }];
        cps.forEach(function (p, i) { $c.append(pointRowHtml('contract_points', i, p)); });
        sps.forEach(function (p, i) { $s.append(pointRowHtml('salary_points', i, p)); });
    }

    function renderRows(rows) {
        state.rows = rows || [];
        var $tbody = $('#hrContractTableBody');
        $tbody.empty();
        $('#hrContractCount').text(state.rows.length);
        if (!state.rows.length) {
            $tbody.append(
                '<tr><td colspan="' + cfg.columnCount + '" class="text-center py-4">' +
                '<div class="alert alert-info mb-0">' + escapeHtml(cfg.trans.empty) + '</div></td></tr>'
            );
            return;
        }
        state.rows.forEach(function (row) {
            var actions = '<td class="text-center"><div class="btn-group">';
            actions += '<button type="button" class="btn btn-sm btn-info hr-ct-view" data-id="' + row.id + '"><i class="las la-eye"></i></button>';
            if (cfg.canEdit) actions += '<button type="button" class="btn btn-sm btn-success hr-ct-edit" data-id="' + row.id + '"><i class="las la-edit"></i></button>';
            if (cfg.canDelete) actions += '<button type="button" class="btn btn-sm btn-danger hr-ct-delete" data-id="' + row.id + '"><i class="las la-trash"></i></button>';
            actions += '</div></td>';

            $tbody.append(
                '<tr>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.name || '-') + '</td>' +
                '<td>' + escapeHtml(row.employee_name || '-') + '</td>' +
                '<td>' + escapeHtml(row.contract_type_name || '-') + '</td>' +
                '<td>' + escapeHtml(dateOnly(row.contract_start_date) || '-') + '</td>' +
                '<td>' + escapeHtml(dateOnly(row.contract_end_date) || '-') + '</td>' +
                '<td>' + (row.trial_period ? escapeHtml(row.trial_period) + ' ' + escapeHtml(cfg.trans.days || '') : '-') + '</td>' +
                '<td>' + escapeHtml(row.contract_duration || '-') + '</td>' +
                '<td>' + statusBadge(row.status) + '</td>' +
                actions +
                '</tr>'
            );
        });
    }

    function activeFiltersCount() {
        var count = 0;
        if (state.status) count++;
        if (state.contractTypeId) count++;
        if (state.jobId) count++;
        if (state.employeeId) count++;
        if (state.dateFrom) count++;
        if (state.dateTo) count++;
        return count;
    }

    function updateFiltersUI() {
        var count = activeFiltersCount();
        var $badge = $('#hrContractFiltersCount');
        if (count > 0) {
            $badge.removeClass('d-none').text(count);
            $('#hrContractClearFilters').removeClass('d-none');
        } else {
            $badge.addClass('d-none').empty();
            $('#hrContractClearFilters').addClass('d-none');
        }
    }

    function loadList() {
        updateFiltersUI();
        $.ajax({
            url: cfg.routes.data,
            method: 'GET',
            data: {
                search: state.search,
                status: state.status,
                contract_type_id: state.contractTypeId,
                job_id: state.jobId,
                employee_id: state.employeeId,
                date_from: state.dateFrom,
                date_to: state.dateTo
            },
            success: function (res) { renderRows(res.data || []); },
            error: function () { showAlert('danger', cfg.trans.loadError || 'Error'); }
        });
    }

    function resetForm() {
        clearErrors();
        state.editId = null;
        var form = $('#hrContractForm')[0];
        if (form) form.reset();
        renderPoints([], []);
        $('#hrContractModalTitle').text(cfg.trans.add);
        $('#hrContractSubmitBtn').text(cfg.trans.save);
    }

    function fillForm(row) {
        HEADER_FIELDS.forEach(function (f) {
            var val = row[f];
            if (f === 'contract_start_date' || f === 'contract_end_date') val = dateOnly(val);
            if (f === 'trial_period') {
                var tp = parseInt(val, 10);
                val = (val === null || val === '' || isNaN(tp)) ? '' : tp;
            }
            $('#hrContractForm [name="' + f + '"]').val(val == null ? '' : val);
        });
        renderPoints(row.contract_points || [], row.salary_points || []);
    }

    function openCreate() {
        resetForm();
        if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrContractModal'));
        state.modal.show();
    }

    function openEdit(id) {
        var row = state.rows.find(function (r) { return String(r.id) === String(id); });
        if (!row) return;
        resetForm();
        state.editId = id;
        fillForm(row);
        $('#hrContractModalTitle').text(cfg.trans.editTitle);
        $('#hrContractSubmitBtn').text(cfg.trans.update);
        if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrContractModal'));
        state.modal.show();
    }

    function openView(id) {
        var row = state.rows.find(function (r) { return String(r.id) === String(id); });
        if (!row) return;
        var t = cfg.trans;
        var pointsHtml = '';
        (row.contract_points || []).forEach(function (p) {
            pointsHtml += '<li>' + escapeHtml(p.name || '') + (p.information ? ': ' + escapeHtml(p.information) : '') + '</li>';
        });
        var salaryHtml = '';
        (row.salary_points || []).forEach(function (p) {
            salaryHtml += '<li>' + escapeHtml(p.name || '') + (p.information ? ': ' + escapeHtml(p.information) : '') + '</li>';
        });
        var html =
            '<dl class="row mb-0">' +
            '<dt class="col-sm-4">' + escapeHtml(t.name) + '</dt><dd class="col-sm-8">' + escapeHtml(row.name || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.employee) + '</dt><dd class="col-sm-8">' + escapeHtml(row.employee_name || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.contractType) + '</dt><dd class="col-sm-8">' + escapeHtml(row.contract_type_name || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.job) + '</dt><dd class="col-sm-8">' + escapeHtml(row.job_title || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.startDate) + '</dt><dd class="col-sm-8">' + escapeHtml(dateOnly(row.contract_start_date) || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.endDate) + '</dt><dd class="col-sm-8">' + escapeHtml(dateOnly(row.contract_end_date) || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.trialPeriod) + '</dt><dd class="col-sm-8">' + (row.trial_period ? escapeHtml(row.trial_period) + ' ' + escapeHtml(t.days || '') : '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.duration) + '</dt><dd class="col-sm-8">' + escapeHtml(row.contract_duration || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.status) + '</dt><dd class="col-sm-8">' + statusBadge(row.status) + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.jobDescription) + '</dt><dd class="col-sm-8">' + escapeHtml(row.job_description || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.information) + '</dt><dd class="col-sm-8">' + escapeHtml(row.information || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.contractPoints) + '</dt><dd class="col-sm-8"><ul class="mb-0">' + (pointsHtml || '-') + '</ul></dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.salaryPoints) + '</dt><dd class="col-sm-8"><ul class="mb-0">' + (salaryHtml || '-') + '</ul></dd>' +
            '</dl>';
        $('#hrContractViewBody').html(html);
        if (!state.viewModal) state.viewModal = new bootstrap.Modal(document.getElementById('hrContractViewModal'));
        state.viewModal.show();
    }

    function formPayload() {
        var data = {};
        HEADER_FIELDS.forEach(function (f) {
            var v = $('#hrContractForm [name="' + f + '"]').val();
            data[f] = v === '' ? null : v;
        });
        data.name = $('#hrContractForm [name="name"]').val();
        var tp = parseInt(data.trial_period, 10);
        data.trial_period = (data.trial_period === '' || data.trial_period === null || isNaN(tp)) ? null : tp;
        data.contract_points = [];
        data.salary_points = [];
        $('#hrContractPointsWrap .hr-contract-point-row').each(function () {
            var name = $(this).find('input[name$="[name]"]').val();
            var information = $(this).find('input[name$="[information]"]').val();
            if (name) data.contract_points.push({ name: name, information: information || '' });
        });
        $('#hrSalaryPointsWrap .hr-salary-point-row').each(function () {
            var name = $(this).find('input[name$="[name]"]').val();
            var information = $(this).find('input[name$="[information]"]').val();
            if (name) data.salary_points.push({ name: name, information: information || '' });
        });
        return data;
    }

    function save() {
        if (state.isSubmitting) return;
        setSubmitting(true);
        clearErrors();
        var isEdit = !!state.editId;
        $.ajax({
            url: isEdit ? cfg.routes.update.replace('__ID__', state.editId) : cfg.routes.store,
            method: isEdit ? 'PUT' : 'POST',
            data: formPayload(),
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            success: function (res) {
                setSubmitting(false);
                if (state.modal) state.modal.hide();
                showAlert('success', res.message || cfg.trans.saved);
                loadList();
            },
            error: function (xhr) {
                setSubmitting(false);
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    applyErrors(xhr.responseJSON.errors);
                    return;
                }
                showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error');
            }
        });
    }

    function destroy(id) {
        if (!window.confirm(cfg.trans.confirmDelete)) return;
        $.ajax({
            url: cfg.routes.destroy.replace('__ID__', id),
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            success: function (res) {
                showAlert('success', res.message || cfg.trans.deleted);
                loadList();
            },
            error: function (xhr) {
                showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error');
            }
        });
    }

    $(function () {
        loadList();
        $('#hrContractSearch').on('input', function () {
            var val = $(this).val();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () { state.search = val; loadList(); }, 300);
        });
        $('#hrContractStatusFilter').on('change', function () { state.status = $(this).val(); loadList(); });
        $('#hrContractTypeFilter').on('change', function () { state.contractTypeId = $(this).val(); loadList(); });
        $('#hrContractJobFilter').on('change', function () { state.jobId = $(this).val(); loadList(); });
        $('#hrContractEmployeeFilter').on('change', function () { state.employeeId = $(this).val(); loadList(); });
        $('#hrContractDateFrom').on('change', function () { state.dateFrom = $(this).val(); loadList(); });
        $('#hrContractDateTo').on('change', function () { state.dateTo = $(this).val(); loadList(); });
        $('#hrContractClearFilters').on('click', function () {
            state.status = '';
            state.contractTypeId = '';
            state.jobId = '';
            state.employeeId = '';
            state.dateFrom = '';
            state.dateTo = '';
            $('#hrContractStatusFilter, #hrContractTypeFilter, #hrContractJobFilter, #hrContractEmployeeFilter').val('');
            $('#hrContractDateFrom, #hrContractDateTo').val('');
            loadList();
        });
        $('#hrContractCreateBtn').on('click', openCreate);
        $('#hrContractAddPoint').on('click', function () {
            var $wrap = $('#hrContractPointsWrap');
            var i = $wrap.children().length;
            $wrap.append(pointRowHtml('contract_points', i, {}));
        });
        $('#hrContractAddSalary').on('click', function () {
            var $wrap = $('#hrSalaryPointsWrap');
            var i = $wrap.children().length;
            $wrap.append(pointRowHtml('salary_points', i, {}));
        });
        $(document).on('click', '.hr-contract-remove-point', function () {
            var $row = $(this).closest('.hr-contract-point-row, .hr-salary-point-row');
            var $wrap = $row.parent();
            var prefix = $row.hasClass('hr-contract-point-row') ? 'contract_points' : 'salary_points';
            $row.remove();
            reindexPoints($wrap, prefix);
            if (!$wrap.children().length) {
                $wrap.append(pointRowHtml(prefix, 0, {}));
            }
        });
        $(document).on('click', '.hr-ct-edit', function () { openEdit($(this).data('id')); });
        $(document).on('click', '.hr-ct-view', function () { openView($(this).data('id')); });
        $(document).on('click', '.hr-ct-delete', function () { destroy($(this).data('id')); });
        $('#hrContractForm').on('input', '[name="trial_period"]', function () {
            var digits = String($(this).val()).replace(/[^0-9]/g, '');
            if (digits !== $(this).val()) $(this).val(digits);
        });
        $('#hrContractForm').on('submit', function (e) { e.preventDefault(); save(); });
    });
})(jQuery);
