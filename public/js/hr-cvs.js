/**
 * HR CVs — Ajax CRUD (multipart)
 * Config: window._hrCvsConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrCvsConfig;
    if (!cfg) return;

    var FIELD_NAMES = [
        'name', 'email', 'phone', 'country', 'state', 'city', 'address',
        'birth_date', 'gender', 'marital_status', 'nationality', 'religion',
        'summary', 'skills', 'experience', 'education', 'projects',
        'certifications', 'languages', 'interests', 'references',
        'cover_letter', 'portfolio', 'status', 'job_title', 'job_posting_id'
    ];

    var state = {
        search: '', gender: '', marital_status: '', nationality: '',
        status: '', job_posting_id: '',
        dateFrom: '', dateTo: '',
        searchTimer: null, natTimer: null, isSubmitting: false, editId: null,
        modal: null, viewModal: null, rows: []
    };

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }
    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }
    function showAlert(type, message) {
        var $box = $('#hrCvAlert');
        $box.removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () { $box.addClass('d-none').removeClass('show'); }, 4000);
    }
    function setSubmitting(on) {
        state.isSubmitting = !!on;
        $('#hrCvSubmitBtn').prop('disabled', !!on);
    }
    function clearErrors() {
        $('#hrCvForm .is-invalid').removeClass('is-invalid');
        $('#hrCvForm .invalid-feedback').text('');
    }
    function applyErrors(errors) {
        clearErrors();
        if (!errors) return;
        Object.keys(errors).forEach(function (key) {
            var $input = $('#hrCvForm [name="' + key + '"]');
            $input.addClass('is-invalid');
            $input.siblings('.invalid-feedback').text((errors[key] || [])[0] || '');
        });
    }
    function label(map, key) {
        return (map && map[key]) || key || '-';
    }
    function dateOnly(val) {
        if (!val) return '';
        return String(val).substring(0, 10);
    }

    function statusBadge(status) {
        var lbl = label(cfg.statusLabels, status);
        var color = (cfg.statusColors && cfg.statusColors[status]) || 'secondary';
        return '<span class="badge bg-' + color + '">' + escapeHtml(lbl) + '</span>';
    }

    function renderRows(rows) {
        state.rows = rows || [];
        var $tbody = $('#hrCvTableBody');
        $tbody.empty();
        $('#hrCvCount').text(state.rows.length);
        if (!state.rows.length) {
            $tbody.append(
                '<tr><td colspan="' + cfg.columnCount + '" class="text-center py-4">' +
                '<div class="alert alert-info mb-0">' + escapeHtml(cfg.trans.empty) + '</div></td></tr>'
            );
            return;
        }
        state.rows.forEach(function (row) {
            var fileCell = row.has_file
                ? '<span class="badge bg-success">' + escapeHtml(cfg.trans.hasFile || '✓') + '</span>'
                : '<span class="badge bg-secondary">' + escapeHtml(cfg.trans.noFile || '-') + '</span>';

            var jobTitleCell = escapeHtml(row.job_posting_title || row.job_title || '-');

            var actions = '<td class="text-center"><div class="btn-group">';
            actions += '<button type="button" class="btn btn-sm btn-info hr-cv-view" data-id="' + row.id + '"><i class="las la-eye"></i></button>';
            if (row.has_file) {
                actions += '<a href="' + cfg.routes.download.replace('__ID__', row.id) + '" class="btn btn-sm btn-secondary hr-cv-download" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.download || '') + '"><i class="las la-download"></i></a>';
            }
            if (cfg.canEdit) actions += '<button type="button" class="btn btn-sm btn-success hr-cv-edit" data-id="' + row.id + '"><i class="las la-edit"></i></button>';
            if (cfg.canDelete) actions += '<button type="button" class="btn btn-sm btn-danger hr-cv-delete" data-id="' + row.id + '"><i class="las la-trash"></i></button>';
            actions += '</div></td>';

            $tbody.append(
                '<tr>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.name || '-') + '</td>' +
                '<td>' + jobTitleCell + '</td>' +
                '<td>' + statusBadge(row.status || 'pending') + '</td>' +
                '<td>' + escapeHtml(row.phone || '-') + '</td>' +
                '<td>' + escapeHtml(row.email || '-') + '</td>' +
                '<td>' + escapeHtml(label(cfg.genderLabels, row.gender)) + '</td>' +
                '<td>' + escapeHtml(row.nationality || '-') + '</td>' +
                '<td>' + fileCell + '</td>' +
                actions +
                '</tr>'
            );
        });
    }

    function activeFiltersCount() {
        var count = 0;
        if (state.gender) count++;
        if (state.marital_status) count++;
        if (state.nationality) count++;
        if (state.status) count++;
        if (state.job_posting_id) count++;
        if (state.dateFrom) count++;
        if (state.dateTo) count++;
        return count;
    }

    function updateFiltersUI() {
        var count = activeFiltersCount();
        var $badge = $('#hrCvFiltersCount');
        if (count > 0) {
            $badge.removeClass('d-none').text(count);
            $('#hrCvClearFilters').removeClass('d-none');
        } else {
            $badge.addClass('d-none').empty();
            $('#hrCvClearFilters').addClass('d-none');
        }
    }

    function loadList() {
        updateFiltersUI();
        $.ajax({
            url: cfg.routes.data,
            method: 'GET',
            data: {
                search: state.search,
                gender: state.gender,
                marital_status: state.marital_status,
                nationality: state.nationality,
                status: state.status,
                job_posting_id: state.job_posting_id,
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
        var form = $('#hrCvForm')[0];
        if (form) form.reset();
        $('#hrCvForm [name="gender"]').val('male');
        $('#hrCvForm [name="marital_status"]').val('single');
        $('#hrCvForm [name="status"]').val('pending');
        $('#hrCvForm [name="job_posting_id"]').val('');
        $('#hrCvModalTitle').text(cfg.trans.add);
        $('#hrCvSubmitBtn').text(cfg.trans.save);
        $('#hrCvCurrentFileHint').addClass('d-none').text('');
    }

    function fillForm(row) {
        FIELD_NAMES.forEach(function (f) {
            var val = row[f];
            if (f === 'birth_date') val = dateOnly(val);
            $('#hrCvForm [name="' + f + '"]').val(val == null ? '' : val);
        });
        if (row.has_file) {
            $('#hrCvCurrentFileHint').removeClass('d-none')
                .text((cfg.trans.currentFile || '') + (row.file_name ? ': ' + row.file_name : ''));
        } else {
            $('#hrCvCurrentFileHint').addClass('d-none').text('');
        }
    }

    function openCreate() {
        resetForm();
        if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrCvModal'));
        state.modal.show();
    }

    function openEdit(id) {
        var row = state.rows.find(function (r) { return String(r.id) === String(id); });
        if (!row) return;
        resetForm();
        state.editId = id;
        fillForm(row);
        $('#hrCvModalTitle').text(cfg.trans.editTitle);
        $('#hrCvSubmitBtn').text(cfg.trans.update);
        if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrCvModal'));
        state.modal.show();
    }

    function openView(id) {
        var row = state.rows.find(function (r) { return String(r.id) === String(id); });
        if (!row) return;
        var t = cfg.trans;
        var html =
            '<dl class="row mb-0">' +
            '<dt class="col-sm-4">' + escapeHtml(t.name) + '</dt><dd class="col-sm-8">' + escapeHtml(row.name || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.jobTitle || 'المسمى الوظيفي') + '</dt><dd class="col-sm-8">' + escapeHtml(row.job_posting_title || row.job_title || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.status || 'الحالة') + '</dt><dd class="col-sm-8">' + statusBadge(row.status || 'pending') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.email) + '</dt><dd class="col-sm-8">' + escapeHtml(row.email || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.phone) + '</dt><dd class="col-sm-8">' + escapeHtml(row.phone || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.birthDate) + '</dt><dd class="col-sm-8">' + escapeHtml(dateOnly(row.birth_date) || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.gender) + '</dt><dd class="col-sm-8">' + escapeHtml(label(cfg.genderLabels, row.gender)) + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.maritalStatus) + '</dt><dd class="col-sm-8">' + escapeHtml(label(cfg.maritalLabels, row.marital_status)) + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.nationality) + '</dt><dd class="col-sm-8">' + escapeHtml(row.nationality || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.religion) + '</dt><dd class="col-sm-8">' + escapeHtml(row.religion || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.address) + '</dt><dd class="col-sm-8">' + escapeHtml([row.country, row.state, row.city, row.address].filter(Boolean).join(' / ') || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.summary) + '</dt><dd class="col-sm-8">' + escapeHtml(row.summary || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.skills) + '</dt><dd class="col-sm-8">' + escapeHtml(row.skills || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.experience) + '</dt><dd class="col-sm-8">' + escapeHtml(row.experience || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.education) + '</dt><dd class="col-sm-8">' + escapeHtml(row.education || '-') + '</dd>' +
            '<dt class="col-sm-4">' + escapeHtml(t.portfolio) + '</dt><dd class="col-sm-8">' + escapeHtml(row.portfolio || '-') + '</dd>' +
            '</dl>';
        $('#hrCvViewBody').html(html);
        if (!state.viewModal) state.viewModal = new bootstrap.Modal(document.getElementById('hrCvViewModal'));
        state.viewModal.show();
    }

    function buildFormData() {
        var fd = new FormData();
        FIELD_NAMES.forEach(function (f) {
            var $el = $('#hrCvForm [name="' + f + '"]');
            fd.append(f, $el.val() == null ? '' : $el.val());
        });
        var fileInput = $('#hrCvForm [name="cv_file"]')[0];
        if (fileInput && fileInput.files && fileInput.files[0]) {
            fd.append('cv_file', fileInput.files[0]);
        }
        return fd;
    }

    function save() {
        if (state.isSubmitting) return;
        setSubmitting(true);
        clearErrors();
        var isEdit = !!state.editId;
        var fd = buildFormData();
        if (isEdit) {
            fd.append('_method', 'PUT');
        }
        $.ajax({
            url: isEdit ? cfg.routes.update.replace('__ID__', state.editId) : cfg.routes.store,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
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
        $('#hrCvSearch').on('input', function () {
            var val = $(this).val();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () { state.search = val; loadList(); }, 300);
        });
        $('#hrCvGenderFilter').on('change', function () { state.gender = $(this).val(); loadList(); });
        $('#hrCvMaritalFilter').on('change', function () { state.marital_status = $(this).val(); loadList(); });
        $('#hrCvNationalityFilter').on('input', function () {
            var val = $(this).val();
            clearTimeout(state.natTimer);
            state.natTimer = setTimeout(function () { state.nationality = val; loadList(); }, 300);
        });
        $('#hrCvStatusFilter').on('change', function () { state.status = $(this).val(); loadList(); });
        $('#hrCvJobPostingFilter').on('change', function () { state.job_posting_id = $(this).val(); loadList(); });
        $('#hrCvDateFrom, #hrCvDateTo').on('change', function () {
            state.dateFrom = $('#hrCvDateFrom').val() || '';
            state.dateTo = $('#hrCvDateTo').val() || '';
            loadList();
        });
        $('#hrCvClearFilters').on('click', function () {
            state.search = '';
            state.gender = '';
            state.marital_status = '';
            state.nationality = '';
            state.status = '';
            state.job_posting_id = '';
            state.dateFrom = '';
            state.dateTo = '';
            $('#hrCvSearch').val('');
            $('#hrCvGenderFilter').val('');
            $('#hrCvMaritalFilter').val('');
            $('#hrCvNationalityFilter').val('');
            $('#hrCvStatusFilter').val('');
            $('#hrCvJobPostingFilter').val('');
            $('#hrCvDateFrom').val('');
            $('#hrCvDateTo').val('');
            loadList();
        });
        $('#hrCvCreateBtn').on('click', openCreate);
        $(document).on('click', '.hr-cv-edit', function () { openEdit($(this).data('id')); });
        $(document).on('click', '.hr-cv-view', function () { openView($(this).data('id')); });
        $(document).on('click', '.hr-cv-delete', function () { destroy($(this).data('id')); });
        $('#hrCvForm').on('submit', function (e) { e.preventDefault(); save(); });
    });
})(jQuery);
