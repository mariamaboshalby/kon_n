/**
 * Flexible salary processing — index / create / edit
 * Config: window._hrFlexibleSalaryConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrFlexibleSalaryConfig;
    if (!cfg) return;

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }
    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }
    function showAlert(type, message) {
        $('#hrFlexAlert').removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
    }

    var isSubmitting = false;
    function setSubmitting(on) {
        isSubmitting = !!on;
        $('#hrFlexSubmitBtn').prop('disabled', !!on);
    }

    function renderHours(employees) {
        var $tb = $('#hrFlexHoursBody').empty();
        (employees || []).forEach(function (emp) {
            $tb.append('<tr><td>' + escapeHtml(emp.name) + '</td><td><input type="text" class="form-control hr-flex-hours" data-id="' + emp.id + '" inputmode="decimal"></td></tr>');
        });
    }

    if (cfg.mode === 'index') {
        var availableColumns = [
            { key: 'id', label: 'رقم السجل (#)', defaultVisible: true },
            { key: 'employee', label: 'الموظف', defaultVisible: true },
            { key: 'department', label: 'القسم', defaultVisible: true },
            { key: 'period', label: 'الفترة', defaultVisible: true },
            { key: 'fixed_salary', label: 'الراتب الثابت', defaultVisible: true },
            { key: 'hours_worked', label: 'عدد الساعات', defaultVisible: true },
            { key: 'hourly_wage', label: 'أجر الساعة', defaultVisible: true },
            { key: 'total_salary', label: 'إجمالي الراتب', defaultVisible: true },
            { key: 'status', label: 'الحالة', defaultVisible: true }
        ];

        function getColumnVisibilityMap() {
            try {
                var saved = localStorage.getItem('hr_flexible_salary_columns_visibility');
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
                localStorage.setItem('hr_flexible_salary_columns_visibility', JSON.stringify(map));
            } catch (e) {}
            applyColumnVisibility();
        }
        function resetColumnsVisibility() {
            try {
                localStorage.removeItem('hr_flexible_salary_columns_visibility');
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
            var $container = $('#hrFlexColsList').empty();
            availableColumns.forEach(function (col) {
                var checked = isColVisible(col.key) ? 'checked' : '';
                var html = '<div class="col-6"><div class="form-check form-switch">' +
                    '<input class="form-check-input hr-flex-col-toggle" type="checkbox" id="col_flex_' + col.key + '" data-key="' + col.key + '" ' + checked + '>' +
                    '<label class="form-check-label small font-hold ms-2" for="col_flex_' + col.key + '">' + escapeHtml(col.label) + '</label>' +
                    '</div></div>';
                $container.append(html);
            });
        }

        function renderStatusBadge(status) {
            if (status === 'approved') return '<span class="badge bg-success">' + escapeHtml(cfg.trans.approved || 'معتمد') + '</span>';
            if (status === 'rejected') return '<span class="badge bg-danger">' + escapeHtml(cfg.trans.rejected || 'مرفوض') + '</span>';
            return '<span class="badge bg-warning text-dark">' + escapeHtml(cfg.trans.pending || 'قيد المراجعة') + '</span>';
        }

        function loadList() {
            var params = {
                start_date: $('#hrFilterStartDate').val() || '',
                end_date: $('#hrFilterEndDate').val() || '',
                department_id: $('#hrFilterDepartment').val() || '',
                employee_id: $('#hrFilterEmployee').val() || '',
                status: $('#hrFilterStatus').val() || ''
            };

            $.get(cfg.routes.data, params, function (res) {
                var rows = res.data || [];
                var summary = res.summary || {};
                var $tb = $('#hrFlexTableBody').empty();

                // Update summary footer bar
                $('#hrSummaryCount').text(summary.total_count || 0);
                $('#hrSummaryHours').text(summary.total_hours || 0);
                $('#hrSummaryFixed').text(Number(summary.total_fixed_salary || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#hrSummaryTotal').text(Number(summary.total_salary || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                if (!rows.length) {
                    $tb.append('<tr><td colspan="10" class="text-center py-4 text-muted">' + escapeHtml(cfg.trans.empty) + '</td></tr>');
                    applyColumnVisibility();
                    return;
                }

                rows.forEach(function (row, idx) {
                    var actions = '<td><div class="btn-group">';
                    if (cfg.canEdit && row.status === 'pending') {
                        actions += '<a class="btn btn-success btn-sm" href="' + cfg.routes.edit.replace('__ID__', row.id) + '" title="' + escapeHtml(cfg.trans.edit) + '"><i class="las la-edit"></i></a>';
                    }
                    if (cfg.canApprove && row.status === 'pending') {
                        actions += '<button type="button" class="btn btn-primary btn-sm hr-flex-approve" data-id="' + row.id + '"><i class="las la-check"></i></button>';
                        actions += '<button type="button" class="btn btn-warning btn-sm hr-flex-reject" data-id="' + row.id + '"><i class="las la-times"></i></button>';
                    }
                    actions += '</div></td>';

                    $tb.append(
                        '<tr>' +
                        '<td data-col="id">' + (idx + 1) + '</td>' +
                        '<td data-col="employee"><strong>' + escapeHtml((row.employee && row.employee.name) || '-') + '</strong></td>' +
                        '<td data-col="department">' + escapeHtml((row.department && row.department.title) || '-') + '</td>' +
                        '<td data-col="period">' + escapeHtml((row.period_start || '').substring(0, 10)) + ' ~ ' + escapeHtml((row.period_end || '').substring(0, 10)) + '</td>' +
                        '<td data-col="fixed_salary">' + escapeHtml(row.fixed_salary) + '</td>' +
                        '<td data-col="hours_worked"><span class="badge bg-info text-dark">' + escapeHtml(row.hours_worked) + '</span></td>' +
                        '<td data-col="hourly_wage">' + escapeHtml(row.hourly_wage) + '</td>' +
                        '<td data-col="total_salary"><strong class="text-success">' + escapeHtml(row.total_salary) + '</strong></td>' +
                        '<td data-col="status">' + renderStatusBadge(row.status) + '</td>' +
                        actions +
                        '</tr>'
                    );
                });

                applyColumnVisibility();
            });
        }

        $(function () {
            applyColumnVisibility();
            loadList();

            // Clear button
            $('#hrFlexClear').on('click', function () {
                $('#hrFilterStartDate, #hrFilterEndDate, #hrFilterDepartment, #hrFilterEmployee, #hrFilterStatus').val('').trigger('change.select2');
                loadList();
            });

            // Filter inputs auto-trigger on change
            $('#hrFilterStartDate, #hrFilterEndDate, #hrFilterDepartment, #hrFilterEmployee, #hrFilterStatus').on('change', function () {
                loadList();
            });

            // Column customization modal
            $('#hrFlexColsBtn').on('click', function () {
                buildColsModalList();
                var modal = new bootstrap.Modal(document.getElementById('hrFlexColsModal'));
                modal.show();
            });

            $(document).on('change', '.hr-flex-col-toggle', function () {
                var key = $(this).data('key');
                setColVisible(key, $(this).is(':checked'));
            });

            $('#hrFlexResetColsBtn').on('click', function () {
                resetColumnsVisibility();
            });

            $(document).on('click', '.hr-flex-approve', function () {
                if (!window.confirm(cfg.trans.confirmApprove)) return;
                $.ajax({
                    url: cfg.routes.approve.replace('__ID__', $(this).data('id')),
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                    success: function (res) { showAlert('success', res.message || ''); loadList(); },
                    error: function (xhr) { showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error'); }
                });
            });

            $(document).on('click', '.hr-flex-reject', function () {
                if (!window.confirm(cfg.trans.confirmReject)) return;
                $.ajax({
                    url: cfg.routes.reject.replace('__ID__', $(this).data('id')),
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                    success: function (res) { showAlert('success', res.message || ''); loadList(); },
                    error: function (xhr) { showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error'); }
                });
            });
        });
        return;
    }

    if (cfg.mode === 'create') {
        function toggleType() {
            var t = $('#hrFlexType').val();
            $('#hrFlexEmpWrap').toggleClass('d-none', t !== 'single');
            $('#hrFlexDeptWrap').toggleClass('d-none', t !== 'department');
            if (t === 'single') {
                var id = $('#hrFlexEmployee').val();
                var emp = (cfg.employees || []).find(function (e) { return String(e.id) === String(id); });
                renderHours(emp ? [emp] : []);
            }
        }
        $(function () {
            toggleType();
            $('#hrFlexType, #hrFlexEmployee').on('change', toggleType);
            $('#hrFlexDepartment').on('change', function () {
                $.get(cfg.routes.employees, { department_id: $(this).val() }, function (res) {
                    renderHours(res.data || []);
                });
            });
            $('#hrFlexCreateForm').on('submit', function (e) {
                e.preventDefault();
                if (isSubmitting) return;
                setSubmitting(true);
                var hours = {};
                $('.hr-flex-hours').each(function () {
                    hours[$(this).data('id')] = $(this).val();
                });
                $.ajax({
                    url: cfg.routes.store,
                    method: 'POST',
                    data: {
                        processing_type: $('#hrFlexType').val(),
                        selected_employee: $('#hrFlexEmployee').val(),
                        selected_department: $('#hrFlexDepartment').val(),
                        start_date: $('#hrFlexCreateForm [name="start_date"]').val(),
                        end_date: $('#hrFlexCreateForm [name="end_date"]').val(),
                        notes: $('#hrFlexCreateForm [name="notes"]').val(),
                        employee_hours: hours
                    },
                    headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                    success: function (res) {
                        setSubmitting(false);
                        if (res.redirect) {
                            window.location.href = res.redirect;
                            return;
                        }
                        showAlert('success', res.message || '');
                    },
                    error: function (xhr) {
                        setSubmitting(false);
                        showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error');
                    }
                });
            });
        });
        return;
    }

    if (cfg.mode === 'edit') {
        $(function () {
            $('#hrFlexEditForm').on('submit', function (e) {
                e.preventDefault();
                if (isSubmitting) return;
                setSubmitting(true);
                $.ajax({
                    url: cfg.routes.update,
                    method: 'PUT',
                    data: {
                        hours_worked: $('#hrFlexEditForm [name="hours_worked"]').val(),
                        notes: $('#hrFlexEditForm [name="notes"]').val()
                    },
                    headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                    success: function (res) {
                        setSubmitting(false);
                        if (res.redirect) {
                            window.location.href = res.redirect;
                            return;
                        }
                        showAlert('success', res.message || '');
                    },
                    error: function (xhr) {
                        setSubmitting(false);
                        showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error');
                    }
                });
            });
        });
    }
})(jQuery);
