/**
 * HR Job-Applicants page — Ajax CRUD
 * Config: window._hrJaConfig
 */
(function ($) {
    'use strict';
    var cfg = window._hrJaConfig;
    if (!cfg) { return; }

    var state = {
        search: '', status: '', jobPostingId: '', dateFrom: '', dateTo: '',
        searchTimer: null, isSubmitting: false, editId: null, modal: null, rows: []
    };

    function csrf() { return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || ''; }
    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

    function showAlert(type, msg) {
        var $b = $('#hrJaAlert');
        $b.removeClass('d-none alert-success alert-danger')
          .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
          .html(esc(msg) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () { $b.addClass('d-none').removeClass('show'); }, 4500);
    }

    function setSubmitting(on) { state.isSubmitting = !!on; $('#hrJaSubmitBtn').prop('disabled', !!on); }

    function clearErrors() {
        $('#hrJaForm .is-invalid').removeClass('is-invalid');
        $('#hrJaForm .invalid-feedback').text('');
    }

    function applyErrors(errors) {
        clearErrors();
        if (!errors) { return; }
        Object.keys(errors).forEach(function (k) {
            var $el = $('#hrJaForm [name="' + k + '"]');
            $el.addClass('is-invalid');
            $el.siblings('.invalid-feedback').text((errors[k] || [])[0] || '');
        });
    }

    function badge(lMap, cMap, key) {
        return '<span class="badge bg-' + ((cMap && cMap[key]) || 'secondary') + '">' + esc((lMap && lMap[key]) || key) + '</span>';
    }

    /* ── posting stats ── */
    function loadPostingStats() {
        $.ajax({
            url: cfg.routes.postingStats, method: 'GET', headers: { Accept: 'application/json' },
            success: function (res) {
                var rows = res.data || [];
                $('#hrJaPostingCount').text(rows.length);
                var $tb = $('#hrJaPostingBody').empty();
                if (!rows.length) {
                    $tb.append('<tr><td colspan="4" class="text-muted text-center py-3">—</td></tr>'); return;
                }
                rows.forEach(function (jp) {
                    $tb.append(
                        '<tr>' +
                        '<td class="text-start ps-3"><a href="' + cfg.routes.jobPosting.replace('__ID__', jp.id) + '" target="_blank" class="fw-bold text-decoration-none">' + esc(jp.title) + ' <i class="las la-external-link-alt small"></i></a></td>' +
                        '<td>' + badge(cfg.jpStatusLabels, cfg.jpStatusColors, jp.status) + '</td>' +
                        '<td><span class="badge bg-info fs-6">' + esc(jp.applicants_count) + '</span></td>' +
                        '<td><button type="button" class="btn btn-sm btn-outline-primary hr-ja-filter-posting" data-id="' + jp.id + '" title="فلترة"><i class="las la-filter"></i></button></td>' +
                        '</tr>'
                    );
                });
            }
        });
    }

    /* ── applicants list ── */
    function renderRows(rows) {
        state.rows = rows || [];
        var $tb = $('#hrJaTableBody').empty();
        $('#hrJaCount').text(state.rows.length);
        if (!state.rows.length) {
            $tb.append('<tr><td colspan="6" class="text-center py-4"><div class="alert alert-info mb-0">' + esc(cfg.trans.empty) + '</div></td></tr>');
            return;
        }
        state.rows.forEach(function (r) {
            var postingCell = r.job_posting_id
                ? '<a href="' + cfg.routes.jobPosting.replace('__ID__', r.job_posting_id) + '" target="_blank" class="text-decoration-none">' + esc(r.job_posting_title) + ' <i class="las la-external-link-alt small"></i></a>'
                : '<span class="text-muted small">' + esc(cfg.trans.noPosting) + '</span>';

            var fileCell = r.has_file
                ? '<a href="' + cfg.routes.download.replace('__ID__', r.id) + '" class="btn btn-sm btn-outline-secondary" title="' + esc(cfg.trans.download) + '"><i class="las la-download me-1"></i>' + esc(cfg.trans.hasFile) + '</a>'
                : '<span class="text-muted small">' + esc(cfg.trans.noFile) + '</span>';

            var acts = '<td class="text-center"><div class="btn-group">';
            if (cfg.canEdit)   acts += '<button type="button" class="btn btn-sm btn-success hr-ja-edit" data-id="' + r.id + '"><i class="las la-edit"></i></button>';
            if (cfg.canDelete) acts += '<button type="button" class="btn btn-sm btn-danger hr-ja-delete" data-id="' + r.id + '"><i class="las la-trash"></i></button>';
            acts += '</div></td>';

            $tb.append(
                '<tr>' +
                '<td class="font-hold fw-bold text-start">' + esc(r.name || '-') + '</td>' +
                '<td>' + postingCell + '</td>' +
                '<td>' + badge(cfg.statusLabels, cfg.statusColors, r.status || 'pending') + '</td>' +
                '<td>' + fileCell + '</td>' +
                '<td>' + esc(r.created_at || '-') + '</td>' +
                acts + '</tr>'
            );
        });
    }

    function activeFiltersCount() {
        return [state.status, state.jobPostingId, state.dateFrom, state.dateTo].filter(Boolean).length;
    }

    function updateFiltersUI() {
        var n = activeFiltersCount();
        $('#hrJaFiltersCount').toggleClass('d-none', !n).text(n || '');
        $('#hrJaClearFilters').toggleClass('d-none', !n);
    }

    function loadList() {
        updateFiltersUI();
        $.ajax({
            url: cfg.routes.data, method: 'GET',
            data: { search: state.search, status: state.status, job_posting_id: state.jobPostingId, date_from: state.dateFrom, date_to: state.dateTo },
            success: function (res) { renderRows(res.data || []); },
            error:   function ()    { showAlert('danger', 'حدث خطأ أثناء تحميل البيانات'); }
        });
    }

    /* ── form ── */
    function resetForm() {
        clearErrors(); state.editId = null;
        var f = $('#hrJaForm')[0]; if (f) f.reset();
        $('#hrJaForm [name="status"]').val('pending');
        $('#hrJaForm [name="job_posting_id"]').val('');
        $('#hrJaModalTitle').text(cfg.trans.add);
        $('#hrJaSubmitBtn').text(cfg.trans.save);
        $('#hrJaCurrentFileHint').addClass('d-none').text('');
    }

    function openCreate(jpId) {
        resetForm();
        if (jpId) { $('#hrJaForm [name="job_posting_id"]').val(jpId); }
        if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrJaModal'));
        state.modal.show();
    }

    function openEdit(id) {
        var r = state.rows.find(function (x) { return String(x.id) === String(id); });
        if (!r) return;
        resetForm(); state.editId = id;
        $('#hrJaForm [name="name"]').val(r.name || '');
        $('#hrJaForm [name="job_posting_id"]').val(r.job_posting_id || '');
        $('#hrJaForm [name="status"]').val(r.status || 'pending');
        if (r.has_file) $('#hrJaCurrentFileHint').removeClass('d-none').text((cfg.trans.currentFile || '') + (r.file_name ? ': ' + r.file_name : ''));
        $('#hrJaModalTitle').text(cfg.trans.editTitle);
        $('#hrJaSubmitBtn').text(cfg.trans.update);
        if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrJaModal'));
        state.modal.show();
    }

    function save() {
        if (state.isSubmitting) return;
        setSubmitting(true); clearErrors();
        var isEdit = !!state.editId;
        var fd = new FormData();
        fd.append('name',           $('#hrJaForm [name="name"]').val() || '');
        fd.append('job_posting_id', $('#hrJaForm [name="job_posting_id"]').val() || '');
        fd.append('status',         $('#hrJaForm [name="status"]').val() || 'pending');
        var fi = $('#hrJaForm [name="cv_file"]')[0];
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
                loadList(); loadPostingStats();
            },
            error: function (xhr) {
                setSubmitting(false);
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) { applyErrors(xhr.responseJSON.errors); return; }
                showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'حدث خطأ');
            }
        });
    }

    function destroy(id) {
        if (!window.confirm(cfg.trans.confirmDelete)) return;
        $.ajax({
            url: cfg.routes.destroy.replace('__ID__', id), method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            success: function (res) { showAlert('success', res.message || cfg.trans.deleted); loadList(); loadPostingStats(); },
            error:   function (xhr) { showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'حدث خطأ'); }
        });
    }

    /* ── boot ── */
    $(function () {
        loadPostingStats(); loadList();

        $('#hrJaSearch').on('input', function () {
            var v = $(this).val(); clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () { state.search = v; loadList(); }, 300);
        });
        $('#hrJaStatusFilter').on('change',      function () { state.status       = $(this).val(); loadList(); });
        $('#hrJaJobPostingFilter').on('change',   function () { state.jobPostingId = $(this).val(); loadList(); });
        $('#hrJaDateFrom, #hrJaDateTo').on('change', function () {
            state.dateFrom = $('#hrJaDateFrom').val() || '';
            state.dateTo   = $('#hrJaDateTo').val()   || '';
            loadList();
        });
        $('#hrJaClearFilters').on('click', function () {
            state.search = state.status = state.jobPostingId = state.dateFrom = state.dateTo = '';
            $('#hrJaSearch, #hrJaJobPostingFilter, #hrJaStatusFilter, #hrJaDateFrom, #hrJaDateTo').val('');
            loadList();
        });
        $(document).on('click', '.hr-ja-filter-posting', function () {
            var id = String($(this).data('id'));
            state.jobPostingId = id;
            $('#hrJaJobPostingFilter').val(id);
            loadList();
            $('html,body').animate({ scrollTop: $('#hrJaTableBody').offset().top - 80 }, 300);
        });
        $('#hrJaCreateBtn').on('click', function () { openCreate(); });
        $(document).on('click', '.hr-ja-edit',   function () { openEdit($(this).data('id')); });
        $(document).on('click', '.hr-ja-delete', function () { destroy($(this).data('id')); });
        $('#hrJaForm').on('submit', function (e) { e.preventDefault(); save(); });
    });
})(jQuery);
