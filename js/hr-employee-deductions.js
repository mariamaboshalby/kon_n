/**
 * Employee deductions & rewards CRUD + filters + column customizer
 * Config: window._hrDeductionsConfig
 */
(function ($) {
    'use strict';
    var cfg = window._hrDeductionsConfig;
    if (!cfg) return;

    var state = { isSubmitting: false, editId: null, rows: [], modal: null };

    var availableColumns = [
        { key: 'id', label: 'رقم السجل (#)', defaultVisible: true },
        { key: 'employee', label: 'الموظف', defaultVisible: true },
        { key: 'type', label: 'النوع (خصم/مكافأة)', defaultVisible: true },
        { key: 'amount', label: 'المبلغ', defaultVisible: true },
        { key: 'date', label: 'التاريخ', defaultVisible: true },
        { key: 'reason', label: 'السبب', defaultVisible: true },
        { key: 'status', label: 'الحالة', defaultVisible: true },
        { key: 'notes', label: 'ملاحظات', defaultVisible: true }
    ];

    function getColumnVisibilityMap() {
        try {
            var saved = localStorage.getItem('hr_deductions_columns_visibility');
            if (saved) return JSON.parse(saved);
        } catch (e) {}
        var map = {};
        availableColumns.forEach(function (col) {
            map[col.key] = col.defaultVisible;
        });
        return map;
    }
    function isColVisible(key) {
        var map = getColumnVisibilityMap();
        return map[key] !== false;
    }
    function setColVisible(key, visible) {
        var map = getColumnVisibilityMap();
        map[key] = !!visible;
        try {
            localStorage.setItem('hr_deductions_columns_visibility', JSON.stringify(map));
        } catch (e) {}
        applyColumnVisibility();
    }
    function resetColumnsVisibility() {
        try {
            localStorage.removeItem('hr_deductions_columns_visibility');
        } catch (e) {}
        applyColumnVisibility();
        buildColsModalList();
    }
    function applyColumnVisibility() {
        availableColumns.forEach(function (col) {
            var visible = isColVisible(col.key);
            $('[data-col="' + col.key + '"]').toggleClass('d-none', !visible);
        });
    }
    function buildColsModalList() {
        var $container = $('#hrDrColsList').empty();
        availableColumns.forEach(function (col) {
            var checked = isColVisible(col.key) ? 'checked' : '';
            var html = '<div class="col-6"><div class="form-check form-switch">' +
                '<input class="form-check-input hr-dr-col-toggle" type="checkbox" id="col_dr_' + col.key + '" data-key="' + col.key + '" ' + checked + '>' +
                '<label class="form-check-label small font-hold ms-2" for="col_dr_' + col.key + '">' + escapeHtml(col.label) + '</label>' +
                '</div></div>';
            $container.append(html);
        });
    }

    function csrf() { return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || ''; }
    function escapeHtml(str) { return $('<div>').text(str == null ? '' : String(str)).html(); }
    function showAlert(type, message) {
        $('#hrDrAlert').removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
    }
    function setSubmitting(on) { state.isSubmitting = !!on; $('#hrDrSubmitBtn').prop('disabled', !!on); }
    function hasJournal(row) { return !!row.journal_id; }

    function renderTypeBadge(type) {
        if (type === 'reward') return '<span class="badge bg-info text-dark">' + escapeHtml(cfg.trans.reward || 'مكافأة') + '</span>';
        return '<span class="badge bg-danger">' + escapeHtml(cfg.trans.deduction || 'خصم') + '</span>';
    }

    function renderStatusBadge(row) {
        if (hasJournal(row)) return '<span class="badge bg-success">' + escapeHtml(cfg.trans.approved || 'معتمد') + '</span>';
        return '<span class="badge bg-warning text-dark">' + escapeHtml(cfg.trans.pending || 'قيد المراجعة') + '</span>';
    }

    function loadList() {
        var params = {
            employee_id: $('#hrDrSearchEmployee').val() || '',
            type: $('#hrDrSearchType').val() || '',
            department_id: $('#hrDrSearchDepartment').val() || '',
            start_date: $('#hrDrDateFrom').val() || '',
            end_date: $('#hrDrDateTo').val() || ''
        };

        $.get(cfg.routes.data, params, function (res) {
            state.rows = res.data || [];
            var summary = res.summary || {};
            var $tb = $('#hrDrTableBody').empty();

            $('#hrDrSummaryCount').text(summary.total_count || 0);
            $('#hrDrSummaryDeductions').text(Number(summary.total_deductions || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#hrDrSummaryRewards').text(Number(summary.total_rewards || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#hrDrSummaryNet').text(Number(summary.net_total || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            if (!state.rows.length) {
                $tb.append('<tr><td colspan="9" class="text-center py-4 text-muted">' + escapeHtml(cfg.trans.empty) + '</td></tr>');
                applyColumnVisibility();
                return;
            }
            state.rows.forEach(function (row, idx) {
                var locked = hasJournal(row);
                var actions = '<td><div class="btn-group">';
                if (cfg.canEdit && !locked) {
                    actions += '<button type="button" class="btn btn-success btn-sm hr-dr-edit" data-id="' + row.id + '"><i class="las la-edit"></i></button>';
                    actions += '<button type="button" class="btn btn-primary btn-sm hr-dr-approve" data-id="' + row.id + '"><i class="las la-check"></i></button>';
                }
                if (cfg.canDelete && !locked) {
                    actions += '<button type="button" class="btn btn-danger btn-sm hr-dr-delete" data-id="' + row.id + '"><i class="las la-trash"></i></button>';
                }
                actions += '</div></td>';

                $tb.append(
                    '<tr>' +
                    '<td data-col="id">' + (idx + 1) + '</td>' +
                    '<td data-col="employee"><strong>' + escapeHtml((row.employee && row.employee.name) || '-') + '</strong></td>' +
                    '<td data-col="type">' + renderTypeBadge(row.type) + '</td>' +
                    '<td data-col="amount"><strong class="' + (row.type === 'reward' ? 'text-info' : 'text-danger') + '">' + escapeHtml(row.amount) + '</strong></td>' +
                    '<td data-col="date">' + escapeHtml((row.date || '').substring(0, 10)) + '</td>' +
                    '<td data-col="reason">' + escapeHtml(row.reason || '') + '</td>' +
                    '<td data-col="status">' + renderStatusBadge(row) + '</td>' +
                    '<td data-col="notes">' + escapeHtml(row.notes || '-') + '</td>' +
                    actions +
                    '</tr>'
                );
            });

            applyColumnVisibility();
        });
    }

    function save() {
        if (state.isSubmitting) return;
        setSubmitting(true);
        var isEdit = !!state.editId;
        $.ajax({
            url: isEdit ? cfg.routes.update.replace('__ID__', state.editId) : cfg.routes.store,
            method: isEdit ? 'PUT' : 'POST',
            data: {
                employee_id: $('#hrDrForm [name="employee_id"]').val(),
                type: $('#hrDrForm [name="type"]').val(),
                amount: $('#hrDrForm [name="amount"]').val(),
                date: $('#hrDrForm [name="date"]').val(),
                reason: $('#hrDrForm [name="reason"]').val(),
                notes: $('#hrDrForm [name="notes"]').val()
            },
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            success: function (res) {
                setSubmitting(false);
                if (state.modal) state.modal.hide();
                showAlert('success', res.message || '');
                loadList();
            },
            error: function (xhr) {
                setSubmitting(false);
                showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error');
            }
        });
    }

    $(function () {
        applyColumnVisibility();
        loadList();

        $('#hrDrSearchEmployee, #hrDrSearchType, #hrDrSearchDepartment, #hrDrDateFrom, #hrDrDateTo').on('change', function () {
            loadList();
        });

        $('#hrDrClear').on('click', function () {
            $('#hrDrSearchEmployee, #hrDrSearchType, #hrDrSearchDepartment, #hrDrDateFrom, #hrDrDateTo').val('').trigger('change.select2');
            loadList();
        });

        $('#hrDrColsBtn').on('click', function () {
            buildColsModalList();
            var modal = new bootstrap.Modal(document.getElementById('hrDrColsModal'));
            modal.show();
        });

        $(document).on('change', '.hr-dr-col-toggle', function () {
            var key = $(this).data('key');
            setColVisible(key, $(this).is(':checked'));
        });

        $('#hrDrResetColsBtn').on('click', function () {
            resetColumnsVisibility();
        });

        $('#hrDrCreateBtn').on('click', function () {
            state.editId = null;
            $('#hrDrForm')[0].reset();
            $('#hrDrForm [name="date"]').val(new Date().toISOString().substring(0, 10));
            if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrDrModal'));
            state.modal.show();
        });

        $(document).on('click', '.hr-dr-edit', function () {
            var id = $(this).data('id');
            var row = state.rows.find(function (r) { return String(r.id) === String(id); });
            if (!row) return;
            state.editId = row.id;
            $('#hrDrForm [name="employee_id"]').val(row.employee_id);
            $('#hrDrForm [name="type"]').val(row.type);
            $('#hrDrForm [name="amount"]').val(row.amount);
            $('#hrDrForm [name="date"]').val((row.date || '').substring(0, 10));
            $('#hrDrForm [name="reason"]').val(row.reason);
            $('#hrDrForm [name="notes"]').val(row.notes || '');
            if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrDrModal'));
            state.modal.show();
        });

        $(document).on('click', '.hr-dr-approve', function () {
            if (!window.confirm(cfg.trans.confirmApprove)) return;
            $.ajax({
                url: cfg.routes.approve.replace('__ID__', $(this).data('id')), method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                success: function (res) { showAlert('success', res.message || ''); loadList(); },
                error: function (xhr) { showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error'); }
            });
        });

        $(document).on('click', '.hr-dr-delete', function () {
            if (!window.confirm(cfg.trans.confirmDelete)) return;
            $.ajax({
                url: cfg.routes.destroy.replace('__ID__', $(this).data('id')), method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                success: function (res) { showAlert('success', res.message || ''); loadList(); },
                error: function (xhr) { showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error'); }
            });
        });

        $('#hrDrForm').on('submit', function (e) { e.preventDefault(); save(); });
    });
})(jQuery);
