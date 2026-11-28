/**
 * HR Terminations — Ajax CRUD
 * Config: window._hrTerminationsConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrTerminationsConfig;
    if (!cfg) {
        return;
    }

    var state = {
        search: '',
        type: '',
        employeeId: '',
        contractId: '',
        dateFrom: '',
        dateTo: '',
        searchTimer: null,
        isSubmitting: false,
        editId: null,
        modal: null,
        rows: []
    };

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function showAlert(type, message) {
        var $box = $('#hrTerminationAlert');
        $box.removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () { $box.addClass('d-none').removeClass('show'); }, 4000);
    }

    function setSubmitting(on) {
        state.isSubmitting = !!on;
        $('#hrTerminationSubmitBtn').prop('disabled', !!on);
    }

    function clearErrors() {
        $('#hrTerminationForm .is-invalid').removeClass('is-invalid');
        $('#hrTerminationForm .invalid-feedback').text('');
    }

    function applyErrors(errors) {
        clearErrors();
        if (!errors) return;
        Object.keys(errors).forEach(function (key) {
            var $input = $('#hrTerminationForm [name="' + key + '"]');
            $input.addClass('is-invalid');
            $input.siblings('.invalid-feedback').text((errors[key] || [])[0] || '');
        });
    }

    function typeLabel(type) {
        return (cfg.typeLabels && cfg.typeLabels[type]) || type;
    }

    function renderRows(rows) {
        state.rows = rows || [];
        var $tbody = $('#hrTerminationTableBody');
        $tbody.empty();
        $('#hrTerminationCount').text(state.rows.length);

        if (!state.rows.length) {
            $tbody.append(
                '<tr><td colspan="' + cfg.columnCount + '" class="text-center py-4">' +
                '<div class="alert alert-info mb-0">' + escapeHtml(cfg.trans.empty) + '</div></td></tr>'
            );
            return;
        }

        state.rows.forEach(function (row) {
            var actions = '';
            if (cfg.canEdit || cfg.canDelete) {
                actions = '<td class="text-center"><div class="btn-group">';
                if (cfg.canEdit) {
                    actions += '<button type="button" class="btn btn-sm btn-success hr-tm-edit" data-id="' + row.id + '"><i class="las la-edit"></i></button>';
                }
                if (cfg.canDelete) {
                    actions += '<button type="button" class="btn btn-sm btn-danger hr-tm-delete" data-id="' + row.id + '"><i class="las la-trash"></i></button>';
                }
                actions += '</div></td>';
            }

            $tbody.append(
                '<tr>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.employee_name || '-') + '</td>' +
                '<td>' + escapeHtml(typeLabel(row.termination_type)) + '</td>' +
                '<td>' + escapeHtml(row.termination_date || '-') + '</td>' +
                '<td>' + escapeHtml(row.reason || '-') + '</td>' +
                '<td>' + escapeHtml(row.final_settlement == null ? '-' : row.final_settlement) + '</td>' +
                actions +
                '</tr>'
            );
        });
    }

    function activeFiltersCount() {
        var count = 0;
        if (state.type) count++;
        if (state.employeeId) count++;
        if (state.contractId) count++;
        if (state.dateFrom) count++;
        if (state.dateTo) count++;
        return count;
    }

    function updateFiltersUI() {
        var count = activeFiltersCount();
        var $badge = $('#hrTerminationFiltersCount');
        if (count > 0) {
            $badge.removeClass('d-none').text(count);
            $('#hrTerminationClearFilters').removeClass('d-none');
        } else {
            $badge.addClass('d-none').empty();
            $('#hrTerminationClearFilters').addClass('d-none');
        }
    }

    function loadList() {
        updateFiltersUI();
        $.ajax({
            url: cfg.routes.data,
            method: 'GET',
            data: {
                search: state.search,
                type: state.type,
                employee_id: state.employeeId,
                contract_id: state.contractId,
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
        var form = $('#hrTerminationForm')[0];
        if (form) form.reset();
        $('#hrTerminationForm [name="termination_type"]').val('resignation');
        $('#hrTerminationModalTitle').text(cfg.trans.add);
        $('#hrTerminationSubmitBtn').text(cfg.trans.save);
    }

    function openCreate() {
        resetForm();
        if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrTerminationModal'));
        state.modal.show();
    }

    function openEdit(id) {
        var row = state.rows.find(function (r) { return String(r.id) === String(id); });
        if (!row) return;
        resetForm();
        state.editId = id;
        ['employee_id', 'contract_id', 'termination_type', 'termination_date', 'reason', 'final_settlement'].forEach(function (f) {
            $('#hrTerminationForm [name="' + f + '"]').val(row[f] == null ? '' : row[f]);
        });
        $('#hrTerminationModalTitle').text(cfg.trans.editTitle);
        $('#hrTerminationSubmitBtn').text(cfg.trans.update);
        if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrTerminationModal'));
        state.modal.show();
    }

    function save() {
        if (state.isSubmitting) return;
        setSubmitting(true);
        clearErrors();

        var isEdit = !!state.editId;
        var url = isEdit ? cfg.routes.update.replace('__ID__', state.editId) : cfg.routes.store;
        var data = {
            employee_id: $('#hrTerminationForm [name="employee_id"]').val(),
            contract_id: $('#hrTerminationForm [name="contract_id"]').val() || null,
            termination_type: $('#hrTerminationForm [name="termination_type"]').val(),
            termination_date: $('#hrTerminationForm [name="termination_date"]').val(),
            reason: $('#hrTerminationForm [name="reason"]').val(),
            final_settlement: $('#hrTerminationForm [name="final_settlement"]').val() || null
        };

        $.ajax({
            url: url,
            method: isEdit ? 'PUT' : 'POST',
            data: data,
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

        $('#hrTerminationSearch').on('input', function () {
            var val = $(this).val();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () {
                state.search = val;
                loadList();
            }, 300);
        });

        $('#hrTerminationTypeFilter').on('change', function () {
            state.type = $(this).val();
            loadList();
        });

        $('#hrTerminationEmployeeFilter').on('change', function () { state.employeeId = $(this).val(); loadList(); });
        $('#hrTerminationContractFilter').on('change', function () { state.contractId = $(this).val(); loadList(); });
        $('#hrTerminationDateFrom').on('change', function () { state.dateFrom = $(this).val(); loadList(); });
        $('#hrTerminationDateTo').on('change', function () { state.dateTo = $(this).val(); loadList(); });
        $('#hrTerminationClearFilters').on('click', function () {
            state.type = '';
            state.employeeId = '';
            state.contractId = '';
            state.dateFrom = '';
            state.dateTo = '';
            $('#hrTerminationTypeFilter, #hrTerminationEmployeeFilter, #hrTerminationContractFilter').val('');
            $('#hrTerminationDateFrom, #hrTerminationDateTo').val('');
            loadList();
        });

        $('#hrTerminationCreateBtn').on('click', openCreate);
        $(document).on('click', '.hr-tm-edit', function () { openEdit($(this).data('id')); });
        $(document).on('click', '.hr-tm-delete', function () { destroy($(this).data('id')); });
        $('#hrTerminationForm').on('submit', function (e) {
            e.preventDefault();
            save();
        });
    });
})(jQuery);
