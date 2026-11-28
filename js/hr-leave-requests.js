/**
 * HR Leave Requests — index, trashed, create/edit form, show actions
 * Config: window._hrLeaveRequestsConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrLeaveRequestsConfig;
    if (!cfg) {
        return;
    }

    var state = {
        search: '',
        employeeId: '',
        leaveTypeId: '',
        status: '',
        dateFrom: '',
        dateTo: '',
        searchTimer: null,
        previewTimer: null,
        isSubmitting: false,
        actionLock: false
    };

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function showAlert(type, message) {
        var $box = $('#hrLeaveRequestAlert');
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

    function ajaxAction(url, method, confirmMsg) {
        if (state.actionLock) {
            return;
        }
        if (confirmMsg && !window.confirm(confirmMsg)) {
            return;
        }
        state.actionLock = true;
        $('.hr-lr-row-action, .hr-lr-action').prop('disabled', true);

        $.ajax({
            url: url,
            method: method || 'POST',
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            success: function (res) {
                state.actionLock = false;
                $('.hr-lr-row-action, .hr-lr-action').prop('disabled', false);
                if (res.redirect) {
                    window.location.href = res.redirect;
                    return;
                }
                showAlert('success', res.message);
                if (typeof window._hrLeaveRequestReload === 'function') {
                    window._hrLeaveRequestReload();
                }
            },
            error: function (xhr) {
                state.actionLock = false;
                $('.hr-lr-row-action, .hr-lr-action').prop('disabled', false);
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || cfg.trans.loadError || cfg.trans.saveError;
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.general) {
                    msg = xhr.responseJSON.errors.general[0];
                }
                showAlert('danger', msg);
            }
        });
    }

    function initList(trashed) {
        function filters() {
            return {
                search: state.search,
                employee_id: state.employeeId,
                leave_type_id: state.leaveTypeId,
                status: state.status,
                date_from: state.dateFrom,
                date_to: state.dateTo
            };
        }

        function loadList() {
            $.ajax({
                url: cfg.routes.data,
                method: 'GET',
                data: filters(),
                success: function (res) {
                    if (trashed) {
                        renderTrashed(res.data || []);
                    } else {
                        renderIndex(res.data || []);
                    }
                },
                error: function () {
                    showAlert('danger', cfg.trans.loadError || 'Error');
                }
            });
        }

        window._hrLeaveRequestReload = loadList;

        function renderIndex(rows) {
            var $tbody = $('#hrLeaveRequestTableBody');
            $tbody.empty();
            if (!rows.length) {
                $tbody.append('<tr><td colspan="9" class="text-center font-hold fw-bold py-4">' + escapeHtml(cfg.trans.empty) + '</td></tr>');
                return;
            }
            rows.forEach(function (row) {
                var approver = '-';
                if (row.approver_name) {
                    approver = escapeHtml(row.approver_name);
                    if (row.approved_at || row.updated_at) {
                        approver += '<br><small class="text-muted font-hold">' + escapeHtml(row.approved_at || row.updated_at) + '</small>';
                    }
                }
                var actions = '<td><div class="btn-group d-flex justify-content-center align-items-center gap-2" role="group">';
                actions += '<a href="' + row.show_url + '" class="btn btn-sm btn-info font-hold fw-bold" title="' + escapeHtml(cfg.trans.view) + '"><i class="fas fa-eye"></i></a>';
                if (row.can_edit) {
                    actions += '<a href="' + row.edit_url + '" class="btn btn-sm btn-primary font-hold fw-bold" title="' + escapeHtml(cfg.trans.edit) + '"><i class="fas fa-edit"></i></a>';
                }
                if (row.can_approve) {
                    actions += '<button type="button" class="btn btn-sm btn-success font-hold fw-bold hr-lr-row-action" data-action="approve" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.approve) + '"><i class="fas fa-check"></i></button>';
                }
                if (row.can_reject) {
                    actions += '<button type="button" class="btn btn-sm btn-danger font-hold fw-bold hr-lr-row-action" data-action="reject" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.reject) + '"><i class="fas fa-times"></i></button>';
                }
                if (row.can_cancel) {
                    actions += '<button type="button" class="btn btn-sm btn-warning font-hold fw-bold hr-lr-row-action" data-action="cancel" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.cancel) + '"><i class="fas fa-ban"></i></button>';
                }
                if (row.can_delete) {
                    actions += '<button type="button" class="btn btn-sm btn-danger font-hold fw-bold hr-lr-row-action" data-action="destroy" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.delete) + '"><i class="fas fa-trash"></i></button>';
                }
                actions += '</div></td>';

                $tbody.append(
                    '<tr>' +
                    '<td class="font-hold fw-bold">' + escapeHtml(row.employee_name) + '</td>' +
                    '<td class="font-hold fw-bold">' + escapeHtml(row.leave_type_name) + '</td>' +
                    '<td class="font-hold fw-bold">' + escapeHtml(row.start_date) + '</td>' +
                    '<td class="font-hold fw-bold">' + escapeHtml(row.end_date) + '</td>' +
                    '<td class="font-hold fw-bold">' + escapeHtml(formatNum(row.duration_days)) + '</td>' +
                    '<td><span class="badge font-hold fw-bold ' + escapeHtml(row.status_badge) + '">' + escapeHtml(row.status_text) + '</span></td>' +
                    '<td class="font-hold fw-bold">' + approver + '</td>' +
                    '<td class="font-hold fw-bold">' + escapeHtml(row.created_at || '') + '</td>' +
                    actions +
                    '</tr>'
                );
            });
        }

        function renderTrashed(rows) {
            var $tbody = $('#hrLeaveRequestTableBody');
            $tbody.empty();
            if (!rows.length) {
                $tbody.append('<tr><td colspan="8" class="text-center font-hold fw-bold py-4">' + escapeHtml(cfg.trans.empty) + '</td></tr>');
                return;
            }
            rows.forEach(function (row) {
                var actions = '<td><div class="btn-group d-flex justify-content-center align-items-center gap-2" role="group">';
                if (row.can_restore) {
                    actions += '<button type="button" class="btn btn-sm btn-success font-hold fw-bold hr-lr-row-action" data-action="restore" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.restore) + '"><i class="fas fa-undo"></i></button>';
                }
                if (row.can_force_delete) {
                    actions += '<button type="button" class="btn btn-sm btn-danger font-hold fw-bold hr-lr-row-action" data-action="force" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.forceDelete) + '"><i class="fas fa-trash-alt"></i></button>';
                }
                actions += '</div></td>';

                $tbody.append(
                    '<tr>' +
                    '<td class="font-hold fw-bold">' + escapeHtml(row.employee_name) + '</td>' +
                    '<td class="font-hold fw-bold">' + escapeHtml(row.leave_type_name) + '</td>' +
                    '<td class="font-hold fw-bold">' + escapeHtml(row.start_date) + '</td>' +
                    '<td class="font-hold fw-bold">' + escapeHtml(row.end_date) + '</td>' +
                    '<td class="font-hold fw-bold">' + escapeHtml(formatNum(row.duration_days)) + '</td>' +
                    '<td><span class="badge font-hold fw-bold ' + escapeHtml(row.status_badge) + '">' + escapeHtml(row.status_text) + '</span></td>' +
                    '<td class="font-hold fw-bold">' + escapeHtml(row.deleted_at || '') + '</td>' +
                    actions +
                    '</tr>'
                );
            });
        }

        loadList();

        $('#hrLeaveRequestSearch').on('input', function () {
            var val = $(this).val();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () {
                state.search = val;
                loadList();
            }, 300);
        });
        $('#hrLeaveRequestEmployeeFilter').on('change', function () {
            state.employeeId = $(this).val();
            loadList();
        });
        $('#hrLeaveRequestTypeFilter').on('change', function () {
            state.leaveTypeId = $(this).val();
            loadList();
        });
        $('#hrLeaveRequestStatusFilter').on('change', function () {
            state.status = $(this).val();
            loadList();
        });
        $('#hrLeaveRequestDateFrom').on('change', function () {
            state.dateFrom = $(this).val();
            loadList();
        });
        $('#hrLeaveRequestDateTo').on('change', function () {
            state.dateTo = $(this).val();
            loadList();
        });

        $(document).on('click', '.hr-lr-row-action', function () {
            var action = $(this).data('action');
            var id = $(this).data('id');
            if (action === 'approve') {
                ajaxAction(cfg.routes.approve.replace('__ID__', id), 'POST', cfg.trans.confirmApprove);
            } else if (action === 'reject') {
                ajaxAction(cfg.routes.reject.replace('__ID__', id), 'POST', cfg.trans.confirmReject);
            } else if (action === 'cancel') {
                ajaxAction(cfg.routes.cancel.replace('__ID__', id), 'POST', cfg.trans.confirmCancel);
            } else if (action === 'destroy') {
                ajaxAction(cfg.routes.destroy.replace('__ID__', id), 'DELETE', cfg.trans.confirmDelete);
            } else if (action === 'restore') {
                ajaxAction(cfg.routes.restore.replace('__ID__', id), 'POST', cfg.trans.confirmRestore);
            } else if (action === 'force') {
                ajaxAction(cfg.routes.forceDestroy.replace('__ID__', id), 'DELETE', cfg.trans.confirmForceDelete);
            }
        });
    }

    function initForm() {
        function clearErrors() {
            $('#hrLeaveRequestForm .is-invalid').removeClass('is-invalid');
            $('#hrLeaveRequestForm .invalid-feedback').text('');
            $('#hrLeaveRequestGeneralError').addClass('d-none').text('');
        }

        function applyErrors(errors) {
            clearErrors();
            Object.keys(errors || {}).forEach(function (key) {
                var msg = (errors[key] || [])[0] || '';
                if (key === 'general') {
                    $('#hrLeaveRequestGeneralError').removeClass('d-none').text(msg);
                    return;
                }
                var $input = $('#hrLeaveRequestForm [name="' + key + '"]');
                $input.addClass('is-invalid');
                $input.siblings('.invalid-feedback').text(msg);
            });
        }

        function setSubmitting(on) {
            state.isSubmitting = !!on;
            $('#hrLeaveRequestSubmitBtn').prop('disabled', !!on);
        }

        function loadPreview() {
            var employeeId = $('#employee_id').val();
            var leaveTypeId = $('#leave_type_id').val();
            if (!employeeId || !leaveTypeId) {
                $('#hrLeaveRequestPreview').addClass('d-none');
                return;
            }
            $.ajax({
                url: cfg.routes.preview,
                method: 'GET',
                data: {
                    employee_id: employeeId,
                    leave_type_id: leaveTypeId,
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val()
                },
                success: function (res) {
                    $('#hrLeaveRequestPreview').removeClass('d-none');
                    $('#hrLrAvailable').text(formatNum(res.available_balance));
                    $('#hrLrDays').text(formatNum(res.calculated_days));
                    var remaining = parseFloat(res.remaining_after);
                    $('#hrLrRemaining')
                        .text(formatNum(remaining))
                        .toggleClass('text-success', remaining >= 0)
                        .toggleClass('text-danger', remaining < 0);
                    if (res.overlaps_attendance) {
                        $('#hrLrOverlap').removeClass('alert-success').addClass('alert alert-warning').html(
                            '<i class="fas fa-exclamation-triangle"></i> ' + escapeHtml(cfg.trans.overlap)
                        );
                    } else {
                        $('#hrLrOverlap').removeClass('alert-warning').addClass('alert alert-success').html(
                            '<i class="fas fa-check"></i> ' + escapeHtml(cfg.trans.noOverlap)
                        );
                    }
                }
            });
        }

        function schedulePreview() {
            clearTimeout(state.previewTimer);
            state.previewTimer = setTimeout(loadPreview, 200);
        }

        $('#employee_id').on('change', function () {
            $('#leave_type_id').val('');
            $('#hrLeaveRequestPreview').addClass('d-none');
        });
        $('#leave_type_id, #start_date, #end_date').on('change', schedulePreview);

        if ($('#employee_id').val() && $('#leave_type_id').val()) {
            loadPreview();
        }

        $('#hrLeaveRequestForm').on('submit', function (e) {
            e.preventDefault();
            if (state.isSubmitting) {
                return;
            }
            setSubmitting(true);
            clearErrors();

            $.ajax({
                url: cfg.isEdit ? cfg.routes.update : cfg.routes.store,
                method: cfg.isEdit ? 'PUT' : 'POST',
                data: {
                    employee_id: $('#employee_id').val(),
                    leave_type_id: $('#leave_type_id').val(),
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    reason: $('#reason').val()
                },
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

    function initShow() {
        $(document).on('click', '.hr-lr-action', function () {
            var action = $(this).data('action');
            var confirmMsg = $(this).data('confirm');
            var url = cfg.routes[action];
            if (!url) {
                return;
            }
            ajaxAction(url, 'POST', confirmMsg);
        });
    }

    $(function () {
        if (cfg.page === 'index') {
            initList(false);
        } else if (cfg.page === 'trashed') {
            initList(true);
        } else if (cfg.page === 'form') {
            initForm();
        } else if (cfg.page === 'show') {
            initShow();
        }
    });
})(jQuery);
