/**
 * HR Interviews — Ajax CRUD
 * Config: window._hrInterviewsConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrInterviewsConfig;
    if (!cfg) return;

    var state = {
        search: '', status: '', type: '', result: '',
        jobPosting: '', interviewer: '', dateFrom: '', dateTo: '',
        searchTimer: null, isSubmitting: false, editId: null,
        modal: null, viewModal: null, rows: []
    };

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }
    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }
    function showAlert(type, message) {
        var $box = $('#hrInterviewAlert');
        $box.removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () { $box.addClass('d-none').removeClass('show'); }, 4000);
    }
    function setSubmitting(on) {
        state.isSubmitting = !!on;
        $('#hrInterviewSubmitBtn').prop('disabled', !!on);
    }
    function clearErrors() {
        $('#hrInterviewForm .is-invalid').removeClass('is-invalid');
        $('#hrInterviewForm .invalid-feedback').text('');
    }
    function applyErrors(errors) {
        clearErrors();
        if (!errors) return;
        Object.keys(errors).forEach(function (key) {
            var $input = $('#hrInterviewForm [name="' + key + '"]');
            $input.addClass('is-invalid');
            $input.siblings('.invalid-feedback').text((errors[key] || [])[0] || '');
        });
    }
    function label(map, key) {
        return (map && map[key]) || key || '-';
    }

    function formatDateWithDay(value, fallback) {
        if (!value) {
            return fallback || '-';
        }
        var str = String(value).trim();
        var iso = str.substring(0, 10);
        var rest = str.length > 10 ? str.substring(10) : '';
        var parts = iso.split('-');
        if (parts.length !== 3) {
            return str;
        }
        var year = parseInt(parts[0], 10);
        var month = parseInt(parts[1], 10) - 1;
        var day = parseInt(parts[2], 10);
        var date = new Date(year, month, day);
        if (isNaN(date.getTime())) {
            return str;
        }
        var names = (cfg.trans && cfg.trans.weekdays) || [];
        var name = names[date.getDay()] || '';
        return name ? (iso + rest + ' (' + name + ')') : str;
    }

    function renderRows(rows) {
        state.rows = rows || [];
        var $tbody = $('#hrInterviewTableBody');
        $tbody.empty();
        $('#hrInterviewCount').text(state.rows.length);
        if (!state.rows.length) {
            $tbody.append('<tr><td colspan="' + cfg.columnCount + '" class="text-center py-4"><div class="alert alert-info mb-0">' + escapeHtml(cfg.trans.empty) + '</div></td></tr>');
            return;
        }
        state.rows.forEach(function (row) {
            var actions = '<td class="text-center"><div class="btn-group">';
            actions += '<button type="button" class="btn btn-sm btn-info hr-iv-view" data-id="' + row.id + '"><i class="las la-eye"></i></button>';
            if (cfg.canEdit) actions += '<button type="button" class="btn btn-sm btn-success hr-iv-edit" data-id="' + row.id + '"><i class="las la-edit"></i></button>';
            if (cfg.canDelete) actions += '<button type="button" class="btn btn-sm btn-danger hr-iv-delete" data-id="' + row.id + '"><i class="las la-trash"></i></button>';
            actions += '</div></td>';

            $tbody.append(
                '<tr>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.cv_name || '-') + '</td>' +
                '<td>' + escapeHtml(row.job_posting_title || '-') + '</td>' +
                '<td>' + escapeHtml(label(cfg.typeLabels, row.interview_type)) + '</td>' +
                '<td>' + escapeHtml(label(cfg.statusLabels, row.status)) + '</td>' +
                '<td>' + escapeHtml(formatDateWithDay(row.scheduled_at_display, '-')) + '</td>' +
                '<td>' + escapeHtml(row.interviewer_name || '-') + '</td>' +
                '<td>' + escapeHtml(label(cfg.resultLabels, row.result)) + '</td>' +
                actions +
                '</tr>'
            );
        });
    }

    function loadList() {
        $.ajax({
            url: cfg.routes.data,
            method: 'GET',
            data: {
                search: state.search,
                status: state.status,
                type: state.type,
                result: state.result,
                job_posting_id: state.jobPosting,
                interviewer_id: state.interviewer,
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
        var form = $('#hrInterviewForm')[0];
        if (form) form.reset();
        $('#hrInterviewForm [name="interview_type"]').val('in_person');
        $('#hrInterviewForm [name="status"]').val('scheduled');
        $('#hrInterviewForm [name="duration"]').val(60);
        $('#hrInterviewModalTitle').text(cfg.trans.add);
        $('#hrInterviewSubmitBtn').text(cfg.trans.save);
    }

    function fillForm(row) {
        ['cv_id', 'job_posting_id', 'interview_type', 'status', 'scheduled_at', 'duration', 'location', 'notes', 'interviewer_id', 'result', 'feedback'].forEach(function (f) {
            $('#hrInterviewForm [name="' + f + '"]').val(row[f] == null ? '' : row[f]);
        });
    }

    function openCreate() {
        resetForm();
        if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrInterviewModal'));
        state.modal.show();
    }

    function openEdit(id) {
        var row = state.rows.find(function (r) { return String(r.id) === String(id); });
        if (!row) return;
        resetForm();
        state.editId = id;
        fillForm(row);
        $('#hrInterviewModalTitle').text(cfg.trans.editTitle);
        $('#hrInterviewSubmitBtn').text(cfg.trans.update);
        if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrInterviewModal'));
        state.modal.show();
    }

    function openView(id) {
        var row = state.rows.find(function (r) { return String(r.id) === String(id); });
        if (!row) return;
        var html =
            '<dl class="row mb-0">' +
            '<dt class="col-sm-4">' + escapeHtml(cfg.trans.cv) + '</dt><dd class="col-sm-8">' + escapeHtml(row.cv_name || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(cfg.trans.jobPosting) + '</dt><dd class="col-sm-8">' + escapeHtml(row.job_posting_title || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(cfg.trans.type) + '</dt><dd class="col-sm-8">' + escapeHtml(label(cfg.typeLabels, row.interview_type)) + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(cfg.trans.status) + '</dt><dd class="col-sm-8">' + escapeHtml(label(cfg.statusLabels, row.status)) + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(cfg.trans.scheduled) + '</dt><dd class="col-sm-8">' + escapeHtml(row.scheduled_at_display || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(cfg.trans.duration) + '</dt><dd class="col-sm-8">' + escapeHtml(row.duration || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(cfg.trans.location) + '</dt><dd class="col-sm-8">' + escapeHtml(row.location || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(cfg.trans.interviewer) + '</dt><dd class="col-sm-8">' + escapeHtml(row.interviewer_name || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(cfg.trans.result) + '</dt><dd class="col-sm-8">' + escapeHtml(label(cfg.resultLabels, row.result)) + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(cfg.trans.notes) + '</dt><dd class="col-sm-8">' + escapeHtml(row.notes || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(cfg.trans.feedback) + '</dt><dd class="col-sm-8">' + escapeHtml(row.feedback || '-') + '</dd>' +
            '</dl>';
        $('#hrInterviewViewBody').html(html);
        if (!state.viewModal) state.viewModal = new bootstrap.Modal(document.getElementById('hrInterviewViewModal'));
        state.viewModal.show();
    }

    function formData() {
        return {
            cv_id: $('#hrInterviewForm [name="cv_id"]').val(),
            job_posting_id: $('#hrInterviewForm [name="job_posting_id"]').val() || null,
            interview_type: $('#hrInterviewForm [name="interview_type"]').val(),
            status: $('#hrInterviewForm [name="status"]').val(),
            scheduled_at: $('#hrInterviewForm [name="scheduled_at"]').val(),
            duration: $('#hrInterviewForm [name="duration"]').val() || null,
            location: $('#hrInterviewForm [name="location"]').val(),
            notes: $('#hrInterviewForm [name="notes"]').val(),
            interviewer_id: $('#hrInterviewForm [name="interviewer_id"]').val() || null,
            result: $('#hrInterviewForm [name="result"]').val() || null,
            feedback: $('#hrInterviewForm [name="feedback"]').val()
        };
    }

    function save() {
        if (state.isSubmitting) return;
        setSubmitting(true);
        clearErrors();
        var isEdit = !!state.editId;
        $.ajax({
            url: isEdit ? cfg.routes.update.replace('__ID__', state.editId) : cfg.routes.store,
            method: isEdit ? 'PUT' : 'POST',
            data: formData(),
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

    function resetFilters() {
        state.search = '';
        state.status = '';
        state.type = '';
        state.result = '';
        state.jobPosting = '';
        state.interviewer = '';
        state.dateFrom = '';
        state.dateTo = '';
        $('#hrInterviewSearch').val('');
        $('#hrInterviewStatusFilter').val('');
        $('#hrInterviewTypeFilter').val('');
        $('#hrInterviewResultFilter').val('');
        $('#hrInterviewJobFilter').val('');
        $('#hrInterviewInterviewerFilter').val('');
        $('#hrInterviewDateFrom').val('');
        $('#hrInterviewDateTo').val('');
        loadList();
    }

    $(function () {
        loadList();
        $('#hrInterviewSearch').on('input', function () {
            var val = $(this).val();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () { state.search = val; loadList(); }, 300);
        });
        $('#hrInterviewStatusFilter').on('change', function () { state.status = $(this).val(); loadList(); });
        $('#hrInterviewTypeFilter').on('change', function () { state.type = $(this).val(); loadList(); });
        $('#hrInterviewResultFilter').on('change', function () { state.result = $(this).val(); loadList(); });
        $('#hrInterviewJobFilter').on('change', function () { state.jobPosting = $(this).val(); loadList(); });
        $('#hrInterviewInterviewerFilter').on('change', function () { state.interviewer = $(this).val(); loadList(); });
        $('#hrInterviewDateFrom').on('change', function () { state.dateFrom = $(this).val(); loadList(); });
        $('#hrInterviewDateTo').on('change', function () { state.dateTo = $(this).val(); loadList(); });
        $('#hrInterviewResetFilters').on('click', resetFilters);
        $('#hrInterviewFiltersToggle').on('click', function () {
            var $panel = $($(this).data('target'));
            $panel.slideToggle(200);
            $(this).attr('aria-expanded', $panel.is(':visible') ? 'false' : 'true');
        });
        $('#hrInterviewCreateBtn').on('click', openCreate);
        $(document).on('click', '.hr-iv-edit', function () { openEdit($(this).data('id')); });
        $(document).on('click', '.hr-iv-view', function () { openView($(this).data('id')); });
        $(document).on('click', '.hr-iv-delete', function () { destroy($(this).data('id')); });
        $('#hrInterviewForm').on('submit', function (e) { e.preventDefault(); save(); });
    });
})(jQuery);
