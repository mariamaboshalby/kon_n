/**
 * HR Applicants (أحدث المتقدمين) — Ajax CRUD
 * Config: window._hrApplicantsConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrApplicantsConfig;
    if (!cfg) { return; }

    var state = {
        search: '', status: '', jobPostingId: '',
        dateFrom: '', dateTo: '',
        searchTimer: null, isSubmitting: false, editId: null,
        modal: null, rows: []
    };

    /* ─── helpers ─── */
    function csrf() { return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || ''; }
    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

    function showAlert(type, msg) {
        var $b = $('#hrApplicantAlert');
        $b.removeClass('d-none alert-success alert-danger')
          .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
          .html(esc(msg) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () { $b.addClass('d-none').removeClass('show'); }, 4500);
    }

    function setSubmitting(on) { state.isSubmitting = !!on; $('#hrApplicantSubmitBtn').prop('disabled', !!on); }

    function clearErrors() {
        $('#hrApplicantForm .is-invalid').removeClass('is-invalid');
        $('#hrApplicantForm .invalid-feedback').text('');
    }

    function applyErrors(errors) {
        clearErrors();
        if (!errors) { return; }
        Object.keys(errors).forEach(function (k) {
            var $el = $('#hrApplicantForm [name="' + k + '"]');
            $el.addClass('is-invalid');
            $el.siblings('.invalid-feedback').text((errors[k] || [])[0] || '');
        });
    }

    function statusBadge(status) {
        var lbl   = (cfg.statusLabels && cfg.statusLabels[status]) || status;
        var color = (cfg.statusColors && cfg.statusColors[status]) || 'secondary';
        return '<span class="badge bg-' + color + '">' + esc(lbl) + '</span>';
    }

    function jpStatusBadge(status) {
        var lbl   = (cfg.jpStatusLabels && cfg.jpStatusLabels[status]) || status;
        var color = (cfg.jpStatusColors && cfg.jpStatusColors[status]) || 'secondary';
        return '<span class="badge bg-' + color + '">' + esc(lbl) + '</span>';
    }

    /* ─── render postings panel ─── */
    function renderPostings(rows) {
        var $body  = $('#hrApplicantPostingBody').empty();
        var $count = $('#hrApplicantPostingCount');
        $count.text(rows.length);

        if (!rows.length) {
            $body.append(
                '<tr><td colspan="4" class="text-center py-3 text-muted small">' +
                esc(cfg.trans.noPostings) + '</td></tr>'
            );
            return;
        }

        rows.forEach(function (jp) {
            var filterBtn =
                '<button type="button" class="btn btn-sm btn-outline-main hr-applicant-filter-by-post" ' +
                'data-id="' + jp.id + '" title="' + esc(cfg.trans.filterByPost) + '">' +
                '<i class="las la-filter me-1"></i>' + esc(cfg.trans.filterByPost) +
                '</button>';

            $body.append(
                '<tr>' +
                '<td class="font-hold fw-bold text-start ps-3">' + esc(jp.title) + '</td>' +
                '<td>' + jpStatusBadge(jp.status) + '</td>' +
                '<td><a href="' + (cfg.routes.latestApplicants || '#') + '?job_posting_id=' + jp.id + '" class="badge bg-primary fs-6 text-decoration-none">' + esc(jp.applicants_count) + '</a></td>' +
                '<td>' + filterBtn + '</td>' +
                '</tr>'
            );
        });
    }

    function loadPostings() {
        if (!cfg.routes.postingStats) { return; }
        $.ajax({
            url: cfg.routes.postingStats, method: 'GET',
            success: function (res) { renderPostings(res.data || []); },
            error:   function ()    { $('#hrApplicantPostingBody').html('<tr><td colspan="4" class="text-center text-danger py-2">' + esc(cfg.trans.errorOccurred) + '</td></tr>'); }
        });
    }

    /* ─── render applicants table ─── */
    function renderRows(rows) {
        state.rows = rows || [];
        var $tb = $('#hrApplicantTableBody').empty();
        $('#hrApplicantCount').text(state.rows.length);

        if (!state.rows.length) {
            $tb.append('<tr><td colspan="6" class="text-center py-4"><div class="alert alert-info mb-0">' + esc(cfg.trans.empty) + '</div></td></tr>');
            return;
        }

        state.rows.forEach(function (r) {
            var postingCell = r.job_posting_id
                ? '<a href="' + cfg.routes.jobPosting.replace('__ID__', r.job_posting_id) + '" target="_blank" class="text-decoration-none">' +
                  esc(r.job_posting_title) + ' <i class="las la-external-link-alt small"></i></a>'
                : '<span class="text-muted small">' + esc(cfg.trans.noPosting) + '</span>';

            var fileCell = r.has_file
                ? '<a href="' + cfg.routes.download.replace('__ID__', r.id) + '" class="btn btn-sm btn-outline-secondary" title="' + esc(cfg.trans.download) + '"><i class="las la-download me-1"></i>' + esc(cfg.trans.hasFile) + '</a>'
                : '<span class="text-muted small">' + esc(cfg.trans.noFile) + '</span>';

            var acts = '<td class="text-center"><div class="btn-group">';
            if (cfg.canEdit)   acts += '<button type="button" class="btn btn-sm btn-success hr-applicant-edit" data-id="' + r.id + '"><i class="las la-edit"></i></button>';
            if (cfg.canDelete) acts += '<button type="button" class="btn btn-sm btn-danger hr-applicant-delete" data-id="' + r.id + '"><i class="las la-trash"></i></button>';
            acts += '</div></td>';

            $tb.append(
                '<tr>' +
                '<td class="font-hold fw-bold text-start">' + esc(r.name || '-') + '</td>' +
                '<td>' + postingCell + '</td>' +
                '<td>' + statusBadge(r.status || 'pending') + '</td>' +
                '<td>' + fileCell + '</td>' +
                '<td>' + esc(r.created_at || '-') + '</td>' +
                acts + '</tr>'
            );
        });
    }

    /* ─── filters ─── */
    function activeFiltersCount() {
        return [state.status, state.jobPostingId, state.dateFrom, state.dateTo].filter(Boolean).length;
    }

    function updateFiltersUI() {
        var n = activeFiltersCount();
        $('#hrApplicantFiltersCount').toggleClass('d-none', !n).text(n || '');
        $('#hrApplicantClearFilters').toggleClass('d-none', !n);
    }

    function loadList() {
        updateFiltersUI();
        $.ajax({
            url: cfg.routes.data, method: 'GET',
            data: { search: state.search, status: state.status, job_posting_id: state.jobPostingId, date_from: state.dateFrom, date_to: state.dateTo },
            success: function (res) { renderRows(res.data || []); },
            error:   function ()    { showAlert('danger', cfg.trans.errorLoading); }
        });
    }

    /* ─── form ─── */
    function resetForm() {
        clearErrors(); state.editId = null;
        var f = $('#hrApplicantForm')[0]; if (f) f.reset();
        $('#hrApplicantForm [name="status"]').val('pending');
        $('#hrApplicantForm [name="job_posting_id"]').val('');
        $('#hrApplicantModalTitle').text(cfg.trans.add);
        $('#hrApplicantSubmitBtn').text(cfg.trans.save);
        $('#hrApplicantCurrentFileHint').addClass('d-none').text('');
    }

    function openCreate() {
        resetForm();
        if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrApplicantModal'));
        state.modal.show();
    }

    function openEdit(id) {
        var r = state.rows.find(function (x) { return String(x.id) === String(id); });
        if (!r) return;
        resetForm(); state.editId = id;
        $('#hrApplicantForm [name="name"]').val(r.name || '');
        $('#hrApplicantForm [name="job_posting_id"]').val(r.job_posting_id || '');
        $('#hrApplicantForm [name="status"]').val(r.status || 'pending');
        if (r.has_file) {
            $('#hrApplicantCurrentFileHint').removeClass('d-none')
                .text((cfg.trans.currentFile || '') + (r.file_name ? ': ' + r.file_name : ''));
        }
        $('#hrApplicantModalTitle').text(cfg.trans.editTitle);
        $('#hrApplicantSubmitBtn').text(cfg.trans.update);
        if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrApplicantModal'));
        state.modal.show();
    }

    /* ─── save ─── */
    function save() {
        if (state.isSubmitting) return;
        setSubmitting(true); clearErrors();
        var isEdit = !!state.editId;
        var fd = new FormData();
        fd.append('name',           $('#hrApplicantForm [name="name"]').val() || '');
        fd.append('job_posting_id', $('#hrApplicantForm [name="job_posting_id"]').val() || '');
        fd.append('status',         $('#hrApplicantForm [name="status"]').val() || 'pending');
        var fi = $('#hrApplicantForm [name="cv_file"]')[0];
        if (fi && fi.files && fi.files[0]) fd.append('cv_file', fi.files[0]);
        if (isEdit) fd.append('_method', 'PUT');

        $.ajax({
            url: isEdit ? cfg.routes.update.replace('__ID__', state.editId) : cfg.routes.store,
            method: 'POST', data: fd, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            success: function (res) {
                setSubmitting(false);
                if (state.modal) state.modal.hide();
                showAlert('success', res.message || (isEdit ? cfg.trans.updated : cfg.trans.saved));
                loadList();
                loadPostings(); /* تحديث عدد المتقدمين في كارد الإعلانات */
            },
            error: function (xhr) {
                setSubmitting(false);
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) { applyErrors(xhr.responseJSON.errors); return; }
                showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || cfg.trans.errorOccurred);
            }
        });
    }

    /* ─── destroy ─── */
    function destroy(id) {
        if (!window.confirm(cfg.trans.confirmDelete)) return;
        $.ajax({
            url: cfg.routes.destroy.replace('__ID__', id), method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            success: function (res) {
                showAlert('success', res.message || cfg.trans.deleted);
                loadList();
                loadPostings(); /* تحديث عدد المتقدمين في كارد الإعلانات */
            },
            error:   function (xhr) { showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || cfg.trans.errorOccurred); }
        });
    }

    /* ─── boot ─── */
    $(function () {
        loadPostings();
        loadList();
        $('#hrApplicantSearch').on('input', function () {
            var v = $(this).val(); clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () { state.search = v; loadList(); }, 300);
        });
        $('#hrApplicantStatusFilter').on('change',    function () { state.status       = $(this).val(); loadList(); });
        $('#hrApplicantJobPostingFilter').on('change', function () { state.jobPostingId = $(this).val(); loadList(); });
        $('#hrApplicantDateFrom, #hrApplicantDateTo').on('change', function () {
            state.dateFrom = $('#hrApplicantDateFrom').val() || '';
            state.dateTo   = $('#hrApplicantDateTo').val()   || '';
            loadList();
        });
        $('#hrApplicantClearFilters').on('click', function () {
            state.search = state.status = state.jobPostingId = state.dateFrom = state.dateTo = '';
            $('#hrApplicantSearch, #hrApplicantStatusFilter, #hrApplicantJobPostingFilter, #hrApplicantDateFrom, #hrApplicantDateTo').val('');
            loadList();
        });
        $('#hrApplicantCreateBtn').on('click', openCreate);
        $(document).on('click', '.hr-applicant-edit',   function () { openEdit($(this).data('id')); });
        $(document).on('click', '.hr-applicant-delete', function () { destroy($(this).data('id')); });
        $(document).on('click', '.hr-applicant-filter-by-post', function () {
            var postId = String($(this).data('id'));
            if (cfg.routes.latestApplicants) {
                window.location.href = cfg.routes.latestApplicants + '?job_posting_id=' + postId;
            }
        });
        $('#hrApplicantForm').on('submit', function (e) { e.preventDefault(); save(); });
    });
})(jQuery);
