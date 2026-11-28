/**
 * HR Work Permissions CRUD + approve/reject + view
 * Config: window._hrWorkPermissionsConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrWorkPermissionsConfig;
    if (!cfg) {
        return;
    }

    var state = {
        search: '',
        employeeId: '',
        status: '',
        startDate: '',
        endDate: '',
        searchTimer: null,
        isSubmitting: false,
        editId: null,
        rows: [],
        modal: null,
        viewModal: null
    };

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function showAlert(type, message) {
        var $box = $('#hrWorkPermAlert');
        $box.removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () { $box.addClass('d-none').removeClass('show'); }, 4000);
    }

    function setSubmitting(on) {
        state.isSubmitting = !!on;
        $('#hrWorkPermSubmitBtn').prop('disabled', !!on);
    }

    function clearErrors() {
        $('#hrWorkPermForm .is-invalid').removeClass('is-invalid');
        $('#hrWorkPermForm .invalid-feedback').text('');
    }

    function applyErrors(errors) {
        clearErrors();
        Object.keys(errors || {}).forEach(function (key) {
            var $input = $('#hrWorkPermForm [name="' + key + '"]');
            $input.addClass('is-invalid');
            $input.siblings('.invalid-feedback').text((errors[key] || [])[0] || '');
        });
    }

    function formatDate(iso) {
        return iso ? String(iso).substring(0, 10) : '-';
    }

    function statusBadge(status) {
        if (status === 'approved') {
            return '<span class="badge bg-success">' + escapeHtml(cfg.trans.approved) + '</span>';
        }
        if (status === 'rejected') {
            return '<span class="badge bg-danger">' + escapeHtml(cfg.trans.rejected) + '</span>';
        }
        return '<span class="badge bg-warning text-dark">' + escapeHtml(cfg.trans.pending) + '</span>';
    }

    function renderRows() {
        var $tbody = $('#hrWorkPermTableBody');
        $tbody.empty();
        var cols = (cfg.canView || cfg.canEdit || cfg.canDelete || cfg.canApprove || cfg.canReject) ? 7 : 6;

        if (!state.rows.length) {
            $tbody.append('<tr><td colspan="' + cols + '" class="text-center py-4"><div class="alert alert-info mb-0">' + escapeHtml(cfg.trans.empty) + '</div></td></tr>');
            return;
        }

        state.rows.forEach(function (row, idx) {
            var actions = '';
            if (cfg.canView || cfg.canEdit || cfg.canDelete || cfg.canApprove || cfg.canReject) {
                actions = '<td class="text-center"><div class="btn-group">';
                if (cfg.canView) {
                    actions += '<button type="button" class="btn btn-sm btn-outline-info hr-workperm-view" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.view) + '"><i class="las la-eye"></i></button>';
                }
                if (cfg.canEdit && row.status === 'pending') {
                    actions += '<button type="button" class="btn btn-sm btn-outline-warning hr-workperm-edit" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.edit) + '"><i class="las la-edit"></i></button>';
                }
                if (row.status === 'pending') {
                    if (cfg.canApprove) {
                        actions += '<button type="button" class="btn btn-sm btn-outline-success hr-workperm-approve" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.approve) + '"><i class="las la-check"></i></button>';
                    }
                    if (cfg.canReject) {
                        actions += '<button type="button" class="btn btn-sm btn-outline-danger hr-workperm-reject" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.reject) + '"><i class="las la-times"></i></button>';
                    }
                }
                if (cfg.canDelete) {
                    actions += '<button type="button" class="btn btn-sm btn-outline-danger hr-workperm-delete" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.delete) + '"><i class="las la-trash"></i></button>';
                }
                actions += '</div></td>';
            }

            $tbody.append(
                '<tr>' +
                '<td class="font-hold fw-bold">' + (idx + 1) + '</td>' +
                '<td><span class="badge bg-info text-white">' + escapeHtml(row.employee ? row.employee.name : '-') + '</span></td>' +
                '<td class="font-hold">' + escapeHtml(formatDate(row.date)) + '</td>' +
                '<td>' + statusBadge(row.status) + '</td>' +
                '<td class="font-hold">' + escapeHtml(row.approved_by ? (row.approved_by.name || cfg.trans.unknown) : '-') + '</td>' +
                '<td class="font-hold">' + escapeHtml(formatDate(row.created_at)) + '</td>' +
                actions +
                '</tr>'
            );
        });
    }

    function loadList() {
        $.getJSON(cfg.routes.data, {
            search: state.search,
            employee_id: state.employeeId || '',
            status: state.status || '',
            start_date: state.startDate || '',
            end_date: state.endDate || ''
        }, function (res) {
            state.rows = res.data || [];
            renderRows();
        });
    }

    function setEmployeeSelectValue(value) {
        var $emp = $('#hrWorkPermForm [name="employee_id"]');
        if (typeof window.setSearchableFilterValue === 'function') {
            window.setSearchableFilterValue($emp, value);
        } else {
            $emp.val(value == null ? '' : String(value));
            if ($emp.hasClass('select2-hidden-accessible')) {
                $emp.trigger('change.select2');
            }
        }
    }

    function openCreate() {
        clearErrors();
        state.editId = null;
        $('#hrWorkPermForm')[0].reset();
        setEmployeeSelectValue('');
        $('#hrWorkPermStatusWrap').addClass('d-none');
        $('#hrWorkPermModalTitle').text(cfg.trans.add);
        $('#hrWorkPermSubmitBtn').text(cfg.trans.save);
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.getElementById('hrWorkPermModal'));
        }
        state.modal.show();
    }

    function openEdit(id) {
        var row = state.rows.find(function (r) { return String(r.id) === String(id); });
        if (!row) {
            return;
        }
        clearErrors();
        state.editId = id;
        setEmployeeSelectValue(row.employee_id);
        $('#hrWorkPermForm [name="date"]').val(formatDate(row.date));
        $('#hrWorkPermForm [name="status"]').val(row.status || 'pending');
        $('#hrWorkPermStatusWrap').removeClass('d-none');
        $('#hrWorkPermModalTitle').text(cfg.trans.editTitle);
        $('#hrWorkPermSubmitBtn').text(cfg.trans.update);
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.getElementById('hrWorkPermModal'));
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
            employee_id: $('#hrWorkPermForm [name="employee_id"]').val(),
            date: $('#hrWorkPermForm [name="date"]').val()
        };
        if (state.editId) {
            payload.status = $('#hrWorkPermForm [name="status"]').val();
        }

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

    function approve(id) {
        if (!window.confirm(cfg.trans.confirmApprove)) {
            return;
        }
        $.ajax({
            url: cfg.routes.approve.replace('__ID__', id),
            method: 'POST',
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

    function reject(id) {
        if (!window.confirm(cfg.trans.confirmReject)) {
            return;
        }
        $.ajax({
            url: cfg.routes.reject.replace('__ID__', id),
            method: 'POST',
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

    function openView(id) {
        $('#hrWorkPermViewBody').html('<div class="text-center py-4">...</div>');
        if (!state.viewModal) {
            state.viewModal = new bootstrap.Modal(document.getElementById('hrWorkPermViewModal'));
        }
        state.viewModal.show();
        $.getJSON(cfg.routes.show.replace('__ID__', id), function (res) {
            var row = res.data || {};
            $('#hrWorkPermViewBody').html(
                '<div class="row g-3">' +
                '<div class="col-md-6"><strong>' + escapeHtml(cfg.trans.employee) + ':</strong><p>' + escapeHtml(row.employee ? row.employee.name : '-') + '</p></div>' +
                '<div class="col-md-6"><strong>' + escapeHtml(cfg.trans.date) + ':</strong><p>' + escapeHtml(formatDate(row.date)) + '</p></div>' +
                '<div class="col-md-6"><strong>' + escapeHtml(cfg.trans.status) + ':</strong><p>' + statusBadge(row.status) + '</p></div>' +
                (row.created_by ? '<div class="col-md-6"><strong>' + escapeHtml(cfg.trans.createdBy) + ':</strong><p>' + escapeHtml(row.created_by.name || cfg.trans.unknown) + '</p></div>' : '') +
                (row.approved_by ? '<div class="col-md-6"><strong>' + escapeHtml(cfg.trans.approvedBy) + ':</strong><p>' + escapeHtml(row.approved_by.name || cfg.trans.unknown) + '</p></div>' : '') +
                (row.approved_at ? '<div class="col-md-6"><strong>' + escapeHtml(cfg.trans.approvedAt) + ':</strong><p>' + escapeHtml(String(row.approved_at).substring(0, 16).replace('T', ' ')) + '</p></div>' : '') +
                '<div class="col-md-6"><strong>' + escapeHtml(cfg.trans.createdAt) + ':</strong><p>' + escapeHtml(formatDate(row.created_at)) + '</p></div>' +
                '</div>'
            );
        });
    }

    $(function () {
        loadList();

        $('#hrWorkPermSearch').on('input', function () {
            var v = $(this).val();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () {
                state.search = v;
                loadList();
            }, 300);
        });

        $('#hrWorkPermFilterEmployee, #hrWorkPermFilterStatus, #hrWorkPermFilterStart, #hrWorkPermFilterEnd').on('change', function () {
            state.employeeId = $('#hrWorkPermFilterEmployee').val();
            state.status = $('#hrWorkPermFilterStatus').val();
            state.startDate = $('#hrWorkPermFilterStart').val();
            state.endDate = $('#hrWorkPermFilterEnd').val();
            loadList();
        });

        $('#hrWorkPermClearFilters').on('click', function () {
            $('#hrWorkPermSearch').val('');
            $('#hrWorkPermFilterEmployee').val('');
            $('#hrWorkPermFilterStatus').val('');
            $('#hrWorkPermFilterStart').val('');
            $('#hrWorkPermFilterEnd').val('');
            state.search = '';
            state.employeeId = '';
            state.status = '';
            state.startDate = '';
            state.endDate = '';
            loadList();
        });

        $('#hrWorkPermCreateBtn').on('click', openCreate);
        $(document).on('click', '.hr-workperm-edit', function () { openEdit($(this).data('id')); });
        $(document).on('click', '.hr-workperm-delete', function () { destroy($(this).data('id')); });
        $(document).on('click', '.hr-workperm-approve', function () { approve($(this).data('id')); });
        $(document).on('click', '.hr-workperm-reject', function () { reject($(this).data('id')); });
        $(document).on('click', '.hr-workperm-view', function () { openView($(this).data('id')); });
        $('#hrWorkPermForm').on('submit', function (e) {
            e.preventDefault();
            save();
        });
    });
})(jQuery);
