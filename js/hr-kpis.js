/**
 * HR KPIs CRUD
 * Config: window._hrKpisConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrKpisConfig;
    if (!cfg) {
        return;
    }

    var state = {
        search: '',
        dateFrom: '',
        dateTo: '',
        searchTimer: null,
        isSubmitting: false,
        editId: null,
        rows: [],
        modal: null
    };

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function showAlert(type, message) {
        var $box = $('#hrKpiAlert');
        $box.removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () { $box.addClass('d-none').removeClass('show'); }, 4000);
    }

    function setSubmitting(on) {
        state.isSubmitting = !!on;
        $('#hrKpiSubmitBtn').prop('disabled', !!on);
    }

    function clearErrors() {
        $('#hrKpiForm .is-invalid').removeClass('is-invalid');
        $('#hrKpiForm .invalid-feedback').text('');
    }

    function applyErrors(errors) {
        clearErrors();
        Object.keys(errors || {}).forEach(function (key) {
            var $input = $('#hrKpiForm [name="' + key + '"]');
            $input.addClass('is-invalid');
            $input.siblings('.invalid-feedback').text((errors[key] || [])[0] || '');
        });
    }

    function formatDate(iso) {
        return iso ? String(iso).substring(0, 10) : '-';
    }

    function renderRows() {
        var $tbody = $('#hrKpiTableBody');
        $tbody.empty();
        var cols = (cfg.canEdit || cfg.canDelete) ? 5 : 4;

        if (!state.rows.length) {
            $tbody.append('<tr><td colspan="' + cols + '" class="text-center py-4"><div class="alert alert-info mb-0">' + escapeHtml(cfg.trans.empty) + '</div></td></tr>');
            return;
        }

        state.rows.forEach(function (row, idx) {
            var actions = '';
            if (cfg.canEdit || cfg.canDelete) {
                actions = '<td class="text-center"><div class="btn-group">';
                if (cfg.canEdit) {
                    actions += '<button type="button" class="btn btn-sm btn-success hr-kpi-edit" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.edit) + '"><i class="fas fa-edit"></i></button>';
                }
                if (cfg.canDelete) {
                    actions += '<button type="button" class="btn btn-sm btn-danger hr-kpi-delete" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.delete) + '"><i class="fas fa-trash"></i></button>';
                }
                actions += '</div></td>';
            }

            $tbody.append(
                '<tr>' +
                '<td class="font-hold fw-bold">' + (idx + 1) + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.name) + '</td>' +
                '<td class="font-hold">' + escapeHtml(row.description || '-') + '</td>' +
                '<td class="font-hold">' + escapeHtml(formatDate(row.created_at)) + '</td>' +
                actions +
                '</tr>'
            );
        });
    }

    function loadList() {
        $.getJSON(cfg.routes.data, {
            search:    state.search,
            date_from: state.dateFrom,
            date_to:   state.dateTo
        }, function (res) {
            state.rows = res.data || [];
            renderRows();
        });
    }

    function openCreate() {
        clearErrors();
        state.editId = null;
        $('#hrKpiForm')[0].reset();
        $('#hrKpiModalTitle').text(cfg.trans.add);
        $('#hrKpiSubmitBtn').text(cfg.trans.save);
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.getElementById('hrKpiModal'));
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
        $('#hrKpiForm [name="name"]').val(row.name);
        $('#hrKpiForm [name="description"]').val(row.description || '');
        $('#hrKpiModalTitle').text(cfg.trans.editTitle);
        $('#hrKpiSubmitBtn').text(cfg.trans.update);
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.getElementById('hrKpiModal'));
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
            name: $('#hrKpiForm [name="name"]').val(),
            description: $('#hrKpiForm [name="description"]').val()
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
        $('#hrKpiSearch').on('input', function () {
            var v = $(this).val();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () {
                state.search = v;
                loadList();
            }, 300);
        });
        $('#hrKpiDateFrom').on('change', function () { state.dateFrom = $(this).val(); loadList(); });
        $('#hrKpiDateTo').on('change',   function () { state.dateTo   = $(this).val(); loadList(); });
        $('#hrKpiClearBtn').on('click', function () {
            state.search = '';
            state.dateFrom = '';
            state.dateTo = '';
            $('#hrKpiSearch').val('');
            $('#hrKpiDateFrom').val('');
            $('#hrKpiDateTo').val('');
            loadList();
        });
        $('#hrKpiCreateBtn').on('click', openCreate);
        $(document).on('click', '.hr-kpi-edit',   function () { openEdit($(this).data('id')); });
        $(document).on('click', '.hr-kpi-delete', function () { destroy($(this).data('id')); });
        $('#hrKpiForm').on('submit', function (e) {
            e.preventDefault();
            save();
        });
    });
})(jQuery);
