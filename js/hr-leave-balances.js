/**
 * HR Leave Balances — index list + create/edit form
 * Config: window._hrLeaveBalancesConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrLeaveBalancesConfig;
    if (!cfg) {
        return;
    }

    var state = {
        search: '',
        employeeId: '',
        leaveTypeId: '',
        year: '',
        searchTimer: null,
        isSubmitting: false
    };

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function showAlert(type, message) {
        var $box = $('#hrLeaveBalanceAlert');
        if (!$box.length) {
            return;
        }
        $box
            .removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
    }

    function formatNum(val) {
        var n = parseFloat(val);
        if (isNaN(n)) {
            return '0.0';
        }
        return n.toFixed(1);
    }

    function initIndex() {
        state.year = $('#hrLeaveBalanceYearFilter').val() || '';

        function loadList() {
            $.ajax({
                url: cfg.routes.data,
                method: 'GET',
                data: {
                    search: state.search,
                    employee_id: state.employeeId,
                    leave_type_id: state.leaveTypeId,
                    year: state.year
                },
                success: function (res) {
                    renderRows(res.data || []);
                },
                error: function () {
                    showAlert('danger', cfg.trans.loadError || 'Error');
                }
            });
        }

        function renderRows(rows) {
            var $tbody = $('#hrLeaveBalanceTableBody');
            $tbody.empty();
            var cols = (cfg.canEdit || cfg.canDelete) ? 9 : 8;
            if (!rows.length) {
                $tbody.append(
                    '<tr><td colspan="' + cols + '" class="text-center font-hold fw-bold py-4">' +
                    escapeHtml(cfg.trans.empty) + '</td></tr>'
                );
                return;
            }

            rows.forEach(function (row) {
                var remainingClass = parseFloat(row.remaining_days) > 0 ? 'bg-success' : 'bg-danger';
                var monthly = row.max_monthly_days
                    ? '<span class="badge bg-info">' + escapeHtml(formatNum(row.max_monthly_days)) + '</span>'
                    : '<span class="text-muted">-</span>';
                var actions = '';
                if (cfg.canEdit || cfg.canDelete) {
                    actions = '<td><div class="btn-group" role="group">';
                    if (cfg.canEdit) {
                        actions += '<a href="' + cfg.routes.edit.replace('__ID__', row.id) + '" class="btn btn-sm btn-warning font-hold fw-bold"><i class="fas fa-edit"></i></a>';
                    }
                    if (cfg.canDelete) {
                        actions += '<button type="button" class="btn btn-sm btn-danger font-hold fw-bold hr-lb-delete" data-id="' + row.id + '"><i class="fas fa-trash"></i></button>';
                    }
                    actions += '</div></td>';
                }

                $tbody.append(
                    '<tr>' +
                    '<td class="font-hold fw-bold">' + escapeHtml(row.employee_name) + '</td>' +
                    '<td class="font-hold fw-bold">' + escapeHtml(row.leave_type_name) + '</td>' +
                    '<td class="font-hold fw-bold">' + escapeHtml(row.year) + '</td>' +
                    '<td class="font-hold fw-bold">' + escapeHtml(formatNum(row.opening_balance_days)) + '</td>' +
                    '<td class="font-hold fw-bold">' + escapeHtml(formatNum(row.used_days)) + '</td>' +
                    '<td class="font-hold fw-bold">' + escapeHtml(formatNum(row.pending_days)) + '</td>' +
                    '<td class="font-hold fw-bold">' + monthly + '</td>' +
                    '<td><span class="badge font-hold fw-bold ' + remainingClass + '">' + escapeHtml(formatNum(row.remaining_days)) + '</span></td>' +
                    actions +
                    '</tr>'
                );
            });
        }

        function destroy(id) {
            if (!window.confirm(cfg.trans.confirmDelete)) {
                return;
            }
            $.ajax({
                url: cfg.routes.destroy.replace('__ID__', id),
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                success: function (res) {
                    showAlert('success', res.message);
                    loadList();
                },
                error: function (xhr) {
                    showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || cfg.trans.loadError);
                }
            });
        }

        loadList();

        $('#hrLeaveBalanceSearch').on('input', function () {
            var val = $(this).val();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () {
                state.search = val;
                loadList();
            }, 300);
        });

        $('#hrLeaveBalanceEmployeeFilter').on('change', function () {
            state.employeeId = $(this).val();
            loadList();
        });
        $('#hrLeaveBalanceTypeFilter').on('change', function () {
            state.leaveTypeId = $(this).val();
            loadList();
        });
        $('#hrLeaveBalanceYearFilter').on('change', function () {
            state.year = $(this).val();
            loadList();
        });

        $(document).on('click', '.hr-lb-delete', function () {
            destroy($(this).data('id'));
        });
    }

    function initForm() {
        function numVal(sel) {
            var n = parseFloat($(sel).val());
            return isNaN(n) ? 0 : n;
        }

        function updateSummary() {
            var opening = numVal('#opening_balance_days');
            var used = numVal('#used_days');
            var pending = numVal('#pending_days');
            $('#hrLbRemaining').text(formatNum(opening - used - pending));
            $('#hrLbOpening').text(formatNum(opening));
            $('#hrLbUsedTotal').text(formatNum(used + pending));
        }

        function clearErrors() {
            $('#hrLeaveBalanceForm .is-invalid').removeClass('is-invalid');
            $('#hrLeaveBalanceForm .invalid-feedback').text('');
            $('#hrLeaveBalanceGeneralError').addClass('d-none').text('');
        }

        function applyErrors(errors) {
            clearErrors();
            Object.keys(errors || {}).forEach(function (key) {
                var msg = (errors[key] || [])[0] || '';
                if (key === 'general') {
                    $('#hrLeaveBalanceGeneralError').removeClass('d-none').text(msg);
                    return;
                }
                var $input = $('#hrLeaveBalanceForm [name="' + key + '"]');
                $input.addClass('is-invalid');
                $input.siblings('.invalid-feedback').text(msg);
            });
        }

        function setSubmitting(on) {
            state.isSubmitting = !!on;
            $('#hrLeaveBalanceSubmitBtn').prop('disabled', !!on);
        }

        $('.hr-lb-calc').on('input', updateSummary);

        $('#hrLeaveBalanceForm').on('submit', function (e) {
            e.preventDefault();
            if (state.isSubmitting) {
                return;
            }
            setSubmitting(true);
            clearErrors();

            var payload = {
                employee_id: $('#employee_id').val(),
                leave_type_id: $('#leave_type_id').val(),
                year: $('#year').val(),
                opening_balance_days: $('#opening_balance_days').val(),
                used_days: $('#used_days').val(),
                pending_days: $('#pending_days').val(),
                max_monthly_days: $('#max_monthly_days').val(),
                notes: $('#notes').val()
            };

            $.ajax({
                url: cfg.isEdit ? cfg.routes.update : cfg.routes.store,
                method: cfg.isEdit ? 'PUT' : 'POST',
                data: payload,
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                success: function (res) {
                    if (res.redirect) {
                        window.location.href = res.redirect;
                        return;
                    }
                    setSubmitting(false);
                    showAlert('success', res.message);
                },
                error: function (xhr) {
                    setSubmitting(false);
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        applyErrors(xhr.responseJSON.errors);
                        return;
                    }
                    showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || cfg.trans.saveError);
                }
            });
        });
    }

    $(function () {
        if (cfg.page === 'index') {
            initIndex();
        } else if (cfg.page === 'form') {
            initForm();
        }
    });
})(jQuery);
