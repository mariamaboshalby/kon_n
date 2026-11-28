/**
 * HR Latest Applicants (أحدث المتقدمين) — عرض + تغيير حالة
 * يقرأ من جدول cvs مباشرةً (نفس نمط Interviews)
 * Config: window._hrLaConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrLaConfig;
    if (!cfg) { return; }

    var state = {
        search: '', status: '', jobPostingId: '', dateFrom: '', dateTo: '',
        searchTimer: null, isSubmitting: false,
        statusModal: null, statusCvId: null,
        createModal: null, rows: []
    };

    function csrf() { return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || ''; }
    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

    function showAlert(type, msg) {
        var $b = $('#hrLaAlert');
        $b.removeClass('d-none alert-success alert-danger')
          .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
          .html(esc(msg) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () { $b.addClass('d-none').removeClass('show'); }, 4500);
    }

    function statusBadge(status) {
        var lbl   = (cfg.statusLabels && cfg.statusLabels[status]) || status;
        var color = (cfg.statusColors && cfg.statusColors[status]) || 'secondary';
        return '<span class="badge bg-' + color + ' cursor-pointer hr-la-status-badge" style="cursor:pointer" title="' + esc(cfg.trans.changeStatus) + '">' + esc(lbl) + ' <i class="las la-pen small"></i></span>';
    }

    /* ─── render ─── */
    function renderRows(rows) {
        state.rows = rows || [];
        var $tb = $('#hrLaTableBody').empty();
        $('#hrLaCount').text(state.rows.length);

        if (!state.rows.length) {
            var cols = cfg.canEdit ? 7 : 6;
            $tb.append('<tr><td colspan="' + cols + '" class="text-center py-4"><div class="alert alert-info mb-0">' + esc(cfg.trans.empty) + '</div></td></tr>');
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

            var nameCell = '<a href="' + cfg.routes.cvShow.replace('__ID__', r.id) + '" class="font-hold fw-bold text-decoration-none">' +
                esc(r.name) + '</a>';

            var statusCell = cfg.canEdit
                ? '<span class="hr-la-status-badge" data-id="' + r.id + '" data-name="' + esc(r.name) + '" data-status="' + esc(r.status) + '" style="cursor:pointer">' + statusBadge(r.status) + '</span>'
                : statusBadge(r.status).replace('cursor:pointer', '').replace('hr-la-status-badge', '');

            var actionsTd = cfg.canEdit
                ? '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-warning hr-la-change-status" data-id="' + r.id + '" data-name="' + esc(r.name) + '" data-status="' + esc(r.status) + '" title="' + esc(cfg.trans.changeStatus) + '"><i class="las la-exchange-alt"></i></button></td>'
                : '';

            $tb.append(
                '<tr>' +
                '<td class="text-start">' + nameCell + '</td>' +
                '<td>' + esc(r.phone || '-') + '</td>' +
                '<td>' + postingCell + '</td>' +
                '<td>' + statusBadge(r.status) + '</td>' +
                '<td>' + fileCell + '</td>' +
                '<td>' + esc(r.created_at || '-') + '</td>' +
                actionsTd +
                '</tr>'
            );
        });
    }

    /* ─── filters ─── */
    function activeFiltersCount() {
        return [state.status, state.jobPostingId, state.dateFrom, state.dateTo].filter(Boolean).length;
    }

    function updateFiltersUI() {
        var n = activeFiltersCount();
        $('#hrLaFiltersCount').toggleClass('d-none', !n).text(n || '');
        $('#hrLaClearFilters').toggleClass('d-none', !n);
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

    /* ─── create modal ─── */
    function clearCreateErrors() {
        $('#hrLaCreateForm .is-invalid').removeClass('is-invalid');
        $('#hrLaCreateForm .invalid-feedback').text('');
    }

    function openCreateModal() {
        clearCreateErrors();
        var f = $('#hrLaCreateForm')[0]; if (f) f.reset();
        $('#hrLaCreateForm [name="status"]').val('pending');
        $('#hrLaCreateForm [name="job_posting_id"]').val('');

        /* تحميل السير الذاتية في الـ select */
        var $sel = $('#hrLaCvSelect').empty()
            .append('<option value="">' + esc(cfg.trans.selectCv || 'اختر سيرة ذاتية') + '</option>');
        if (cfg.routes.cvSelectList) {
            $.ajax({
                url: cfg.routes.cvSelectList, method: 'GET',
                success: function (res) {
                    (res.data || []).forEach(function (cv) {
                        var label = esc(cv.name) + (cv.phone ? ' — ' + esc(cv.phone) : '');
                        $sel.append('<option value="' + cv.id + '">' + label + '</option>');
                    });
                }
            });
        }

        if (!state.createModal) {
            state.createModal = new bootstrap.Modal(document.getElementById('hrLaCreateModal'));
        }
        state.createModal.show();
    }

    function saveCreate() {
        if (state.isSubmitting) { return; }
        state.isSubmitting = true;
        $('#hrLaCreateSubmit').prop('disabled', true);
        clearCreateErrors();

        var fd = new FormData();
        fd.append('name',           $('#hrLaCreateForm [name="name"]').val() || '');
        fd.append('job_posting_id', $('#hrLaCreateForm [name="job_posting_id"]').val() || '');
        fd.append('status',         $('#hrLaCreateForm [name="status"]').val() || 'pending');
        var cvId = $('#hrLaCvSelect').val();
        if (cvId) { fd.append('cv_id', cvId); }

        $.ajax({
            url: cfg.routes.store, method: 'POST',
            data: fd, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            success: function (res) {
                state.isSubmitting = false;
                $('#hrLaCreateSubmit').prop('disabled', false);
                if (state.createModal) { state.createModal.hide(); }
                showAlert('success', res.message || cfg.trans.created);
                loadList();
            },
            error: function (xhr) {
                state.isSubmitting = false;
                $('#hrLaCreateSubmit').prop('disabled', false);
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    clearCreateErrors();
                    Object.keys(xhr.responseJSON.errors).forEach(function (k) {
                        var $el = $('#hrLaCreateForm [name="' + k + '"]');
                        $el.addClass('is-invalid');
                        $el.siblings('.invalid-feedback').text((xhr.responseJSON.errors[k] || [])[0] || '');
                    });
                    return;
                }
                showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'حدث خطأ');
            }
        });
    }

    /* ─── status modal ─── */
    function openStatusModal(id, name, currentStatus) {
        state.statusCvId = id;
        $('#hrLaStatusApplicantName').text(name || '');
        $('#hrLaStatusSelect').val(currentStatus || 'pending');
        if (!state.statusModal) {
            state.statusModal = new bootstrap.Modal(document.getElementById('hrLaStatusModal'));
        }
        state.statusModal.show();
    }

    function saveStatus() {
        if (state.isSubmitting || !state.statusCvId) { return; }
        state.isSubmitting = true;
        $('#hrLaStatusSubmit').prop('disabled', true);

        $.ajax({
            url: cfg.routes.status.replace('__ID__', state.statusCvId),
            method: 'POST',
            data: { _method: 'PATCH', status: $('#hrLaStatusSelect').val() },
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            success: function (res) {
                state.isSubmitting = false;
                $('#hrLaStatusSubmit').prop('disabled', false);
                if (state.statusModal) { state.statusModal.hide(); }
                showAlert('success', res.message || cfg.trans.saved);
                loadList();
            },
            error: function (xhr) {
                state.isSubmitting = false;
                $('#hrLaStatusSubmit').prop('disabled', false);
                showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'حدث خطأ');
            }
        });
    }

    /* ─── boot ─── */
    $(function () {
        /* قراءة job_posting_id من الـ URL لو جه من صفحة الإعلانات */
        var urlParams = new URLSearchParams(window.location.search);
        var presetPostId = urlParams.get('job_posting_id');
        if (presetPostId) {
            state.jobPostingId = presetPostId;
            $('#hrLaJobPostingFilter').val(presetPostId);
        }

        loadList();

        $('#hrLaSearch').on('input', function () {
            var v = $(this).val(); clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () { state.search = v; loadList(); }, 300);
        });
        $('#hrLaStatusFilter').on('change',    function () { state.status       = $(this).val(); loadList(); });
        $('#hrLaJobPostingFilter').on('change', function () { state.jobPostingId = $(this).val(); loadList(); });
        $('#hrLaDateFrom, #hrLaDateTo').on('change', function () {
            state.dateFrom = $('#hrLaDateFrom').val() || '';
            state.dateTo   = $('#hrLaDateTo').val()   || '';
            loadList();
        });
        $('#hrLaClearFilters').on('click', function () {
            state.search = state.status = state.jobPostingId = state.dateFrom = state.dateTo = '';
            $('#hrLaSearch, #hrLaStatusFilter, #hrLaJobPostingFilter, #hrLaDateFrom, #hrLaDateTo').val('');
            loadList();
        });

        $(document).on('click', '.hr-la-change-status', function () {
            openStatusModal($(this).data('id'), $(this).data('name'), $(this).data('status'));
        });

        $('#hrLaCreateBtn').on('click', openCreateModal);
        $('#hrLaCreateForm').on('submit', function (e) { e.preventDefault(); saveCreate(); });
        $('#hrLaStatusForm').on('submit', function (e) { e.preventDefault(); saveStatus(); });
    });
})(jQuery);
