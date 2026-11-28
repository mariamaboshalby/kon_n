/**
 * HR Errands CRUD + approve + view
 * Config: window._hrErrandsConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrErrandsConfig;
    if (!cfg) {
        return;
    }

    var state = {
        search: '',
        employeeId: '',
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
        var $box = $('#hrErrandAlert');
        $box.removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () { $box.addClass('d-none').removeClass('show'); }, 4000);
    }

    function setSubmitting(on) {
        state.isSubmitting = !!on;
        $('#hrErrandSubmitBtn').prop('disabled', !!on);
    }

    function clearErrors() {
        $('#hrErrandForm .is-invalid').removeClass('is-invalid');
        $('#hrErrandForm .invalid-feedback').text('');
    }

    function applyErrors(errors) {
        clearErrors();
        Object.keys(errors || {}).forEach(function (key) {
            var $input = $('#hrErrandForm [name="' + key + '"]');
            $input.addClass('is-invalid');
            $input.siblings('.invalid-feedback').text((errors[key] || [])[0] || '');
        });
    }

    function formatDate(iso) {
        return iso ? String(iso).substring(0, 10) : '-';
    }

    function durationDays(start, end) {
        if (!start || !end) {
            return 0;
        }
        var s = new Date(formatDate(start));
        var e = new Date(formatDate(end));
        return Math.round((e - s) / 86400000) + 1;
    }

    function statusBadge(row) {
        if (row.approved_at) {
            return '<span class="badge bg-success">' + escapeHtml(cfg.trans.approved) + '</span>';
        }
        return '<span class="badge bg-warning text-dark">' + escapeHtml(cfg.trans.pending) + '</span>';
    }

    function renderRows() {
        var $tbody = $('#hrErrandTableBody');
        $tbody.empty();
        var cols = (cfg.canView || cfg.canEdit || cfg.canDelete || cfg.canApprove) ? 9 : 8;

        if (!state.rows.length) {
            $tbody.append('<tr><td colspan="' + cols + '" class="text-center py-4"><div class="alert alert-info mb-0">' + escapeHtml(cfg.trans.empty) + '</div></td></tr>');
            return;
        }

        state.rows.forEach(function (row, idx) {
            var actions = '';
            if (cfg.canView || cfg.canEdit || cfg.canDelete || cfg.canApprove) {
                actions = '<td class="text-center"><div class="btn-group">';
                if (cfg.canView) {
                    actions += '<button type="button" class="btn btn-sm btn-outline-info hr-errand-view" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.view) + '"><i class="las la-eye"></i></button>';
                }
                if (cfg.canEdit && !row.approved_at) {
                    actions += '<button type="button" class="btn btn-sm btn-outline-warning hr-errand-edit" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.edit) + '"><i class="las la-edit"></i></button>';
                }
                if (cfg.canApprove && !row.approved_at) {
                    actions += '<button type="button" class="btn btn-sm btn-outline-success hr-errand-approve" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.approve) + '"><i class="las la-check"></i></button>';
                }
                if (cfg.canDelete) {
                    actions += '<button type="button" class="btn btn-sm btn-outline-danger hr-errand-delete" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.delete) + '"><i class="las la-trash"></i></button>';
                }
                actions += '</div></td>';
            }

            var details = row.details ? '<br><small class="text-muted">' + escapeHtml(String(row.details).substring(0, 50)) + '</small>' : '';

            $tbody.append(
                '<tr>' +
                '<td class="font-hold fw-bold">' + (idx + 1) + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.title || cfg.trans.noTitle) + details + '</td>' +
                '<td><span class="badge bg-info text-white">' + escapeHtml(row.employee ? row.employee.name : '-') + '</span></td>' +
                '<td class="font-hold">' + escapeHtml(formatDate(row.start_date)) + '</td>' +
                '<td class="font-hold">' + escapeHtml(formatDate(row.end_date)) + '</td>' +
                '<td><span class="badge bg-secondary">' + durationDays(row.start_date, row.end_date) + ' ' + escapeHtml(cfg.trans.days) + '</span></td>' +
                '<td>' + statusBadge(row) + '</td>' +
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
            start_date: state.startDate || '',
            end_date: state.endDate || ''
        }, function (res) {
            state.rows = res.data || [];
            renderRows();
        });
    }

    function openCreate() {
        clearErrors();
        state.editId = null;
        $('#hrErrandForm')[0].reset();
        $('#hrErrandModalTitle').text(cfg.trans.add);
        $('#hrErrandSubmitBtn').text(cfg.trans.save);
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.getElementById('hrErrandModal'));
        }
        state.modal.show();
    }

    function openEdit(id) {
        var row = state.rows.find(function (r) { return String(r.id) === String(id); });
        if (!row || row.approved_at) {
            return;
        }
        clearErrors();
        state.editId = id;
        $('#hrErrandForm [name="employee_id"]').val(row.employee_id);
        $('#hrErrandForm [name="title"]').val(row.title || '');
        $('#hrErrandForm [name="details"]').val(row.details || '');
        $('#hrErrandForm [name="start_date"]').val(formatDate(row.start_date));
        $('#hrErrandForm [name="end_date"]').val(formatDate(row.end_date));
        $('#hrErrandModalTitle').text(cfg.trans.editTitle);
        $('#hrErrandSubmitBtn').text(cfg.trans.update);
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.getElementById('hrErrandModal'));
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
            employee_id: $('#hrErrandForm [name="employee_id"]').val(),
            title: $('#hrErrandForm [name="title"]').val(),
            details: $('#hrErrandForm [name="details"]').val(),
            start_date: $('#hrErrandForm [name="start_date"]').val(),
            end_date: $('#hrErrandForm [name="end_date"]').val()
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

    function openView(id) {
        $('#hrErrandViewBody').html('<div class="text-center py-4">...</div>');
        if (!state.viewModal) {
            state.viewModal = new bootstrap.Modal(document.getElementById('hrErrandViewModal'));
        }
        state.viewModal.show();
        $.getJSON(cfg.routes.show.replace('__ID__', id), function (res) {
            var row = res.data || {};
            $('#hrErrandViewBody').html(
                '<div class="row g-3">' +
                '<div class="col-md-6"><strong>' + escapeHtml(cfg.trans.employee) + ':</strong><p>' + escapeHtml(row.employee ? row.employee.name : '-') + '</p></div>' +
                '<div class="col-md-6"><strong>' + escapeHtml(cfg.trans.title) + ':</strong><p>' + escapeHtml(row.title || cfg.trans.noTitle) + '</p></div>' +
                '<div class="col-md-6"><strong>' + escapeHtml(cfg.trans.startDate) + ':</strong><p>' + escapeHtml(formatDate(row.start_date)) + '</p></div>' +
                '<div class="col-md-6"><strong>' + escapeHtml(cfg.trans.endDate) + ':</strong><p>' + escapeHtml(formatDate(row.end_date)) + '</p></div>' +
                '<div class="col-md-6"><strong>' + escapeHtml(cfg.trans.duration) + ':</strong><p>' + durationDays(row.start_date, row.end_date) + ' ' + escapeHtml(cfg.trans.days) + '</p></div>' +
                '<div class="col-md-6"><strong>' + escapeHtml(cfg.trans.status) + ':</strong><p>' + statusBadge(row) + '</p></div>' +
                '<div class="col-12"><strong>' + escapeHtml(cfg.trans.details) + ':</strong><p>' + escapeHtml(row.details || cfg.trans.noDetails) + '</p></div>' +
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

        $('#hrErrandSearch').on('input', function () {
            var v = $(this).val();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () {
                state.search = v;
                loadList();
            }, 300);
        });

        $('#hrErrandFilterEmployee, #hrErrandFilterStart, #hrErrandFilterEnd').on('change', function () {
            state.employeeId = $('#hrErrandFilterEmployee').val();
            state.startDate = $('#hrErrandFilterStart').val();
            state.endDate = $('#hrErrandFilterEnd').val();
            loadList();
        });

        $('#hrErrandClearFilters').on('click', function () {
            $('#hrErrandSearch').val('');
            $('#hrErrandFilterEmployee').val('');
            $('#hrErrandFilterStart').val('');
            $('#hrErrandFilterEnd').val('');
            state.search = '';
            state.employeeId = '';
            state.startDate = '';
            state.endDate = '';
            loadList();
        });

        $('#hrErrandCreateBtn').on('click', openCreate);
        $(document).on('click', '.hr-errand-edit', function () { openEdit($(this).data('id')); });
        $(document).on('click', '.hr-errand-delete', function () { destroy($(this).data('id')); });
        $(document).on('click', '.hr-errand-approve', function () { approve($(this).data('id')); });
        $(document).on('click', '.hr-errand-view', function () { openView($(this).data('id')); });
        $('#hrErrandForm').on('submit', function (e) {
            e.preventDefault();
            save();
        });
    });
})(jQuery);
