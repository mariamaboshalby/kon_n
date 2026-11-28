/**
 * HR Shifts CRUD
 * Config: window._hrShiftsConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrShiftsConfig;
    if (!cfg) {
        return;
    }

    var state = {
        search: '',
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
        var $box = $('#hrShiftAlert');
        $box.removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () {
            $box.addClass('d-none').removeClass('show');
        }, 4000);
    }

    function setSubmitting(on) {
        state.isSubmitting = !!on;
        $('#hrShiftSubmitBtn').prop('disabled', !!on);
    }

    function clearErrors() {
        $('#hrShiftForm .is-invalid').removeClass('is-invalid');
        $('#hrShiftForm .invalid-feedback').text('');
        $('[data-error-for="days"]').text('');
    }

    function applyErrors(errors) {
        clearErrors();
        Object.keys(errors || {}).forEach(function (key) {
            var msg = (errors[key] || [])[0] || '';
            if (key === 'days' || key.indexOf('days.') === 0) {
                $('[data-error-for="days"]').text(msg);
                return;
            }
            var $input = $('#hrShiftForm [name="' + key + '"]');
            $input.addClass('is-invalid');
            $input.siblings('.invalid-feedback').text(msg);
        });
    }

    function resetForm() {
        clearErrors();
        state.editId = null;
        var form = $('#hrShiftForm')[0];
        if (form) {
            form.reset();
        }
        $('#hrShiftForm [name="allowed_late_minutes"]').val('0');
        $('#hrShiftForm [name="allowed_early_leave_minutes"]').val('0');
        $('#hrShiftModalTitle').text(cfg.trans.add);
        $('#hrShiftSubmitBtn').text(cfg.trans.save);
    }

    function fillForm(row) {
        $('#hrShiftForm [name="name"]').val(row.name || '');
        $('#hrShiftForm [name="start_time"]').val(row.start_time || '');
        $('#hrShiftForm [name="end_time"]').val(row.end_time || '');
        $('#hrShiftForm [name="beginning_check_in"]').val(row.beginning_check_in || '');
        $('#hrShiftForm [name="ending_check_in"]').val(row.ending_check_in || '');
        $('#hrShiftForm [name="beginning_check_out"]').val(row.beginning_check_out || '');
        $('#hrShiftForm [name="ending_check_out"]').val(row.ending_check_out || '');
        $('#hrShiftForm [name="allowed_late_minutes"]').val(row.allowed_late_minutes != null ? row.allowed_late_minutes : 0);
        $('#hrShiftForm [name="allowed_early_leave_minutes"]').val(row.allowed_early_leave_minutes != null ? row.allowed_early_leave_minutes : 0);
        $('#hrShiftForm [name="shift_type"]').val(row.shift_type || '');
        $('#hrShiftForm [name="notes"]').val(row.notes || '');
        $('#hrShiftForm [name="days[]"]').prop('checked', false);
        (row.days || []).forEach(function (day) {
            $('#hrShiftForm [name="days[]"][value="' + day + '"]').prop('checked', true);
        });
    }

    function formPayload() {
        var days = [];
        $('#hrShiftForm [name="days[]"]:checked').each(function () {
            days.push($(this).val());
        });
        return {
            name: $('#hrShiftForm [name="name"]').val(),
            start_time: $('#hrShiftForm [name="start_time"]').val(),
            end_time: $('#hrShiftForm [name="end_time"]').val(),
            beginning_check_in: $('#hrShiftForm [name="beginning_check_in"]').val() || null,
            ending_check_in: $('#hrShiftForm [name="ending_check_in"]').val() || null,
            beginning_check_out: $('#hrShiftForm [name="beginning_check_out"]').val() || null,
            ending_check_out: $('#hrShiftForm [name="ending_check_out"]').val() || null,
            allowed_late_minutes: $('#hrShiftForm [name="allowed_late_minutes"]').val() || 0,
            allowed_early_leave_minutes: $('#hrShiftForm [name="allowed_early_leave_minutes"]').val() || 0,
            shift_type: $('#hrShiftForm [name="shift_type"]').val(),
            notes: $('#hrShiftForm [name="notes"]').val() || null,
            days: days
        };
    }

    function daysBadges(days) {
        if (!days || !days.length) {
            return '-';
        }
        return days.map(function (d) {
            return '<span class="badge bg-info me-1">' + escapeHtml((cfg.weekDays && cfg.weekDays[d]) || d) + '</span>';
        }).join('');
    }

    function renderRows(rows) {
        state.rows = rows || [];
        var $tbody = $('#hrShiftTableBody');
        $tbody.empty();

        if (!state.rows.length) {
            var cols = cfg.canEdit || cfg.canDelete ? 13 : 12;
            $tbody.append(
                '<tr><td colspan="' + cols + '" class="text-center font-hold fw-bold py-4">' +
                '<div class="alert alert-info mb-0">' + escapeHtml(cfg.trans.empty) + '</div></td></tr>'
            );
            return;
        }

        state.rows.forEach(function (row) {
            var actions = '';
            if (cfg.canEdit || cfg.canDelete) {
                actions = '<td><div class="btn-group" role="group">';
                if (cfg.canEdit) {
                    actions += '<button type="button" class="btn btn-success btn-sm hr-shift-edit" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.edit) + '"><i class="las la-edit"></i></button>';
                }
                if (cfg.canDelete) {
                    actions += '<button type="button" class="btn btn-danger btn-sm hr-shift-delete" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.delete) + '"><i class="las la-trash"></i></button>';
                }
                actions += '</div></td>';
            }

            $tbody.append(
                '<tr>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.name) + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.start_time || '-') + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.beginning_check_in || '-') + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.ending_check_in || '-') + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.allowed_late_minutes != null ? row.allowed_late_minutes : '-') + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.end_time || '-') + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.beginning_check_out || '-') + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.ending_check_out || '-') + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.allowed_early_leave_minutes != null ? row.allowed_early_leave_minutes : '-') + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml((cfg.shiftTypes && cfg.shiftTypes[row.shift_type]) || row.shift_type || '-') + '</td>' +
                '<td class="font-hold fw-bold">' + daysBadges(row.days) + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.notes || '-') + '</td>' +
                actions +
                '</tr>'
            );
        });
    }

    function loadList() {
        $.ajax({
            url: cfg.routes.data,
            method: 'GET',
            data: { search: state.search },
            success: function (res) {
                renderRows(res.data || []);
            },
            error: function () {
                showAlert('danger', 'Error');
            }
        });
    }

    function openCreate() {
        resetForm();
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.getElementById('hrShiftModal'));
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
        fillForm(row);
        $('#hrShiftModalTitle').text(cfg.trans.editTitle);
        $('#hrShiftSubmitBtn').text(cfg.trans.update);
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.getElementById('hrShiftModal'));
        }
        state.modal.show();
    }

    function save() {
        if (state.isSubmitting) {
            return;
        }
        setSubmitting(true);
        clearErrors();

        var isEdit = !!state.editId;
        var url = isEdit
            ? cfg.routes.update.replace('__ID__', state.editId)
            : cfg.routes.store;

        $.ajax({
            url: url,
            method: isEdit ? 'PUT' : 'POST',
            data: formPayload(),
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            success: function (res) {
                setSubmitting(false);
                if (state.modal) {
                    state.modal.hide();
                }
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
        if (!window.confirm(cfg.trans.confirmDelete)) {
            return;
        }
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

        $('#hrShiftSearch').on('input', function () {
            var val = $(this).val();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () {
                state.search = val;
                loadList();
            }, 300);
        });

        $('#hrShiftCreateBtn').on('click', openCreate);

        $(document).on('click', '#hrShiftTableBody .hr-shift-edit', function () {
            openEdit($(this).data('id'));
        });

        $(document).on('click', '#hrShiftTableBody .hr-shift-delete', function () {
            destroy($(this).data('id'));
        });

        $('#hrShiftForm').on('submit', function (e) {
            e.preventDefault();
            save();
        });
    });
})(jQuery);
