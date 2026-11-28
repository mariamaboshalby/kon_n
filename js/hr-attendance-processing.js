/**
 * Attendance processing form + history
 * Config: window._hrAttendanceProcessingConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrAttendanceProcessingConfig;
    if (!cfg) return;

    var isSubmitting = false;

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }
    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }
    function showAlert(type, message) {
        $('#hrProcAlert').removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message).replace(/\n/g, '<br>') + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
    }

    function toggleType() {
        var t = $('#hrProcType').val();
        $('#hrProcEmployeeWrap').toggleClass('d-none', t !== 'single');
        $('#hrProcEmployeesWrap').toggleClass('d-none', t !== 'multiple');
        $('#hrProcDeptWrap').toggleClass('d-none', t !== 'department');
        if (typeof window.refreshSearchableFilterSelects === 'function') {
            if (t === 'single') {
                window.refreshSearchableFilterSelects('#hrProcEmployeeWrap');
            } else if (t === 'multiple') {
                window.refreshSearchableFilterSelects('#hrProcEmployeesWrap');
            }
        }
    }

    function loadList() {
        $.get(cfg.routes.data, function (res) {
            var rows = res.data || [];
            var $tb = $('#hrProcTableBody').empty();
            if (!rows.length) {
                $tb.append('<tr><td colspan="6" class="text-center py-4">' + escapeHtml(cfg.trans.empty) + '</td></tr>');
                return;
            }
            rows.forEach(function (row, idx) {
                var actions = '<td><div class="btn-group">';
                if (cfg.canEdit && row.status === 'pending') {
                    actions += '<button type="button" class="btn btn-primary btn-sm hr-proc-approve" data-id="' + row.id + '"><i class="las la-check"></i></button>';
                    actions += '<button type="button" class="btn btn-warning btn-sm hr-proc-reject" data-id="' + row.id + '"><i class="las la-times"></i></button>';
                }
                if (cfg.canDelete && row.status !== 'approved') {
                    actions += '<button type="button" class="btn btn-danger btn-sm hr-proc-delete" data-id="' + row.id + '"><i class="las la-trash"></i></button>';
                }
                actions += '</div></td>';
                var periodStart = (row.period_start || '').substring(0, 10);
                var periodEnd = (row.period_end || '').substring(0, 10);
                var period = escapeHtml(periodStart) + ' - ' + escapeHtml(periodEnd);
                var empName = escapeHtml((row.employee && row.employee.name) || '-');
                var empId = row.employee && row.employee.id;
                var empCell = empName;
                if (empId && cfg.routes.employeeComprehensive) {
                    var reportUrl = cfg.routes.employeeComprehensive.replace('__ID__', empId) +
                        '?date_from=' + encodeURIComponent(periodStart) +
                        '&date_to=' + encodeURIComponent(periodEnd);
                    empCell = '<a href="' + reportUrl + '" class="hr-proc-employee-report-link">' + empName + '</a>';
                }
                $tb.append('<tr><td>' + (idx + 1) + '</td><td>' + empCell +
                    '</td><td>' + escapeHtml((row.department && row.department.title) || '-') +
                    '</td><td>' + period + '</td><td>' + escapeHtml(row.status) + '</td>' + actions + '</tr>');
            });
        });
    }

    function post(url, confirmMsg) {
        if (confirmMsg && !window.confirm(confirmMsg)) return;
        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            success: function (res) { showAlert('success', res.message || ''); loadList(); },
            error: function (xhr) { showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error'); }
        });
    }

    $(function () {
        toggleType();
        loadList();
        $('#hrProcType').on('change', toggleType);
        $('#hrProcForm').on('submit', function (e) {
            e.preventDefault();
            if (isSubmitting) return;
            isSubmitting = true;
            $('#hrProcSubmitBtn').prop('disabled', true);
            $.ajax({
                url: cfg.routes.process,
                method: 'POST',
                data: $(this).serialize(),
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                success: function (res) {
                    isSubmitting = false;
                    $('#hrProcSubmitBtn').prop('disabled', false);
                    showAlert('success', res.message || '');
                    loadList();
                },
                error: function (xhr) {
                    isSubmitting = false;
                    $('#hrProcSubmitBtn').prop('disabled', false);
                    showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error');
                }
            });
        });
        $(document).on('click', '.hr-proc-approve', function () {
            post(cfg.routes.approve.replace('__ID__', $(this).data('id')), cfg.trans.confirmApprove);
        });
        $(document).on('click', '.hr-proc-reject', function () {
            post(cfg.routes.reject.replace('__ID__', $(this).data('id')), cfg.trans.confirmReject);
        });
        $(document).on('click', '.hr-proc-delete', function () {
            if (!window.confirm(cfg.trans.confirmDelete)) return;
            $.ajax({
                url: cfg.routes.destroy.replace('__ID__', $(this).data('id')),
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                success: function (res) { showAlert('success', res.message || ''); loadList(); },
                error: function (xhr) { showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error'); }
            });
        });
    });
})(jQuery);
