/**
 * HR Leave Types CRUD
 */
(function ($) {
    'use strict';

    var cfg = window._hrLeaveTypesConfig;
    if (!cfg) {
        return;
    }

    var state = { search: '', searchTimer: null, isSubmitting: false, editId: null, rows: [], modal: null };

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function showAlert(type, message) {
        $('#hrLeaveTypeAlert')
            .removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
    }

    function setSubmitting(on) {
        state.isSubmitting = !!on;
        $('#hrLeaveTypeSubmitBtn').prop('disabled', !!on);
    }

    function clearErrors() {
        $('#hrLeaveTypeForm .is-invalid').removeClass('is-invalid');
        $('#hrLeaveTypeForm .invalid-feedback').text('');
    }

    function applyErrors(errors) {
        clearErrors();
        Object.keys(errors || {}).forEach(function (key) {
            var $input = $('#hrLeaveTypeForm [name="' + key + '"]');
            $input.addClass('is-invalid');
            $input.siblings('.invalid-feedback').text((errors[key] || [])[0] || '');
        });
    }

    function badge(yes) {
        return yes
            ? '<span class="badge bg-success">' + escapeHtml(cfg.trans.yes) + '</span>'
            : '<span class="badge bg-warning">' + escapeHtml(cfg.trans.no) + '</span>';
    }

    function renderRows() {
        var $tbody = $('#hrLeaveTypeTableBody');
        $tbody.empty();
        var cols = (cfg.canEdit || cfg.canDelete) ? 6 : 5;
        if (!state.rows.length) {
            $tbody.append('<tr><td colspan="' + cols + '" class="text-center py-4 text-muted">' + escapeHtml(cfg.trans.empty) + '</td></tr>');
            return;
        }
        state.rows.forEach(function (row) {
            var actions = '';
            if (cfg.canEdit || cfg.canDelete) {
                actions = '<td><div class="btn-group">';
                if (cfg.canEdit) {
                    actions += '<button type="button" class="btn btn-sm btn-outline-primary hr-lt-edit" data-id="' + row.id + '"><i class="fas fa-edit"></i></button>';
                }
                if (cfg.canDelete) {
                    actions += '<button type="button" class="btn btn-sm btn-outline-danger hr-lt-delete" data-id="' + row.id + '"><i class="fas fa-trash"></i></button>';
                }
                actions += '</div></td>';
            }
            $tbody.append(
                '<tr>' +
                '<td><strong>' + escapeHtml(row.name) + '</strong></td>' +
                '<td><span class="badge bg-secondary">' + escapeHtml(row.code) + '</span></td>' +
                '<td>' + badge(!!row.is_paid) + '</td>' +
                '<td>' + badge(!!row.requires_approval) + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.max_per_request_days) + ' ' + escapeHtml(cfg.trans.days) + '</td>' +
                actions +
                '</tr>'
            );
        });
    }

    function loadList() {
        $.getJSON(cfg.routes.data, { search: state.search }, function (res) {
            state.rows = res.data || [];
            renderRows();
        });
    }

    function resetForm() {
        clearErrors();
        state.editId = null;
        $('#hrLeaveTypeForm')[0].reset();
        $('#hrLeaveTypeIsPaid, #hrLeaveTypeRequiresApproval').prop('checked', false);
        $('#hrLeaveTypeModalTitle').text(cfg.trans.add);
        $('#hrLeaveTypeSubmitBtn').text(cfg.trans.save);
    }

    function openCreate() {
        resetForm();
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.getElementById('hrLeaveTypeModal'));
        }
        state.modal.show();
    }

    function openEdit(id) {
        var row = state.rows.find(function (r) { return String(r.id) === String(id); });
        if (!row) {
            return;
        }
        resetForm();
        state.editId = id;
        $('#hrLeaveTypeForm [name="name"]').val(row.name);
        $('#hrLeaveTypeForm [name="code"]').val(row.code);
        $('#hrLeaveTypeForm [name="max_per_request_days"]').val(row.max_per_request_days);
        $('#hrLeaveTypeIsPaid').prop('checked', !!row.is_paid);
        $('#hrLeaveTypeRequiresApproval').prop('checked', !!row.requires_approval);
        $('#hrLeaveTypeModalTitle').text(cfg.trans.editTitle);
        $('#hrLeaveTypeSubmitBtn').text(cfg.trans.update);
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.getElementById('hrLeaveTypeModal'));
        }
        state.modal.show();
    }

    function save() {
        if (state.isSubmitting) {
            return;
        }
        setSubmitting(true);
        clearErrors();
        var payload = {
            name: $('#hrLeaveTypeForm [name="name"]').val(),
            code: $('#hrLeaveTypeForm [name="code"]').val(),
            max_per_request_days: $('#hrLeaveTypeForm [name="max_per_request_days"]').val(),
            is_paid: $('#hrLeaveTypeIsPaid').is(':checked') ? 1 : 0,
            requires_approval: $('#hrLeaveTypeRequiresApproval').is(':checked') ? 1 : 0
        };
        var isEdit = !!state.editId;
        $.ajax({
            url: isEdit ? cfg.routes.update.replace('__ID__', state.editId) : cfg.routes.store,
            method: isEdit ? 'PUT' : 'POST',
            data: payload,
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            success: function (res) {
                setSubmitting(false);
                if (state.modal) {
                    state.modal.hide();
                }
                showAlert('success', res.message);
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
                showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error');
            }
        });
    }

    $(function () {
        loadList();
        $('#hrLeaveTypeSearch').on('input', function () {
            var v = $(this).val();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () {
                state.search = v;
                loadList();
            }, 300);
        });
        $('#hrLeaveTypeCreateBtn').on('click', openCreate);
        $(document).on('click', '.hr-lt-edit', function () { openEdit($(this).data('id')); });
        $(document).on('click', '.hr-lt-delete', function () { destroy($(this).data('id')); });
        $('#hrLeaveTypeForm').on('submit', function (e) {
            e.preventDefault();
            save();
        });
    });
})(jQuery);
