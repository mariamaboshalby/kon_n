/**
 * HR Job Postings — Ajax CRUD
 * Config: window._hrJobPostingsConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrJobPostingsConfig;
    if (!cfg) {
        return;
    }

    var state = {
        search: '',
        status: '',
        jobId: '',
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
        var $box = $('#hrJobPostingAlert');
        $box.removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () { $box.addClass('d-none').removeClass('show'); }, 4000);
    }

    function setSubmitting(on) {
        state.isSubmitting = !!on;
        $('#hrJobPostingSubmitBtn').prop('disabled', !!on);
    }

    function clearErrors() {
        $('#hrJobPostingForm .is-invalid').removeClass('is-invalid');
        $('#hrJobPostingForm .invalid-feedback').text('');
    }

    function applyErrors(errors) {
        clearErrors();
        if (!errors) return;
        Object.keys(errors).forEach(function (key) {
            var $input = $('#hrJobPostingForm [name="' + key + '"]');
            $input.addClass('is-invalid');
            $input.siblings('.invalid-feedback').text((errors[key] || [])[0] || '');
        });
    }

    function statusBadge(status) {
        var cls = status === 'active' ? 'success' : (status === 'closed' ? 'secondary' : 'danger');
        var label = (cfg.statusLabels && cfg.statusLabels[status]) || status;
        return '<span class="badge bg-' + cls + '">' + escapeHtml(label) + '</span>';
    }

    function formatDateWithDay(value, fallback) {
        if (!value) {
            return fallback || '-';
        }
        var iso = String(value).substring(0, 10);
        var parts = iso.split('-');
        if (parts.length !== 3) {
            return iso;
        }
        var year = parseInt(parts[0], 10);
        var month = parseInt(parts[1], 10) - 1;
        var day = parseInt(parts[2], 10);
        var date = new Date(year, month, day);
        if (isNaN(date.getTime())) {
            return iso;
        }
        var names = (cfg.trans && cfg.trans.weekdays) || [];
        var name = names[date.getDay()] || '';
        return name ? (iso + ' (' + name + ')') : iso;
    }

    function renderRows(rows) {
        state.rows = rows || [];
        var $tbody = $('#hrJobPostingTableBody');
        $tbody.empty();
        $('#hrJobPostingCount').text(state.rows.length);

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
                    actions += '<button type="button" class="btn btn-sm btn-success hr-jp-edit" data-id="' + row.id + '"><i class="las la-edit"></i></button>';
                }
                if (cfg.canDelete) {
                    actions += '<button type="button" class="btn btn-sm btn-danger hr-jp-delete" data-id="' + row.id + '"><i class="las la-trash"></i></button>';
                }
                actions += '</div></td>';
            }

            $tbody.append(
                '<tr>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.title) +
                    (row.description ? '<br><small class="text-muted">' + escapeHtml(String(row.description).substring(0, 50)) + '</small>' : '') +
                '</td>' +
                '<td>' + escapeHtml(row.job_title || cfg.trans.noJob) + '</td>' +
                '<td>' + statusBadge(row.status) + '</td>' +
                '<td>' + escapeHtml(formatDateWithDay(row.start_date, '-')) + '</td>' +
                '<td>' + escapeHtml(formatDateWithDay(row.end_date, cfg.trans.noDeadline)) + '</td>' +
                '<td><a href="' + (cfg.routes.latestApplicants || '#') + '?job_posting_id=' + row.id + '" class="badge bg-info text-decoration-none" title="' + escapeHtml(cfg.trans.viewApplicants || 'عرض المتقدمين') + '">' + escapeHtml(row.cvs_count || 0) + '</a></td>' +
                actions +
                '</tr>'
            );
        });
    }

    function activeFiltersCount() {
        var count = 0;
        if (state.status) count++;
        if (state.jobId) count++;
        if (state.dateFrom) count++;
        if (state.dateTo) count++;
        return count;
    }

    function updateFiltersUI() {
        var count = activeFiltersCount();
        var $badge = $('#hrJobPostingFiltersCount');
        if (count > 0) {
            $badge.removeClass('d-none').text(count);
            $('#hrJobPostingClearFilters').removeClass('d-none');
        } else {
            $badge.addClass('d-none').empty();
            $('#hrJobPostingClearFilters').addClass('d-none');
        }
    }

    function loadList() {
        updateFiltersUI();
        $.ajax({
            url: cfg.routes.data,
            method: 'GET',
            data: {
                search: state.search,
                status: state.status,
                job_id: state.jobId,
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
        var form = $('#hrJobPostingForm')[0];
        if (form) form.reset();
        $('#hrJobPostingForm [name="status"]').val('active');
        $('#hrJobPostingModalTitle').text(cfg.trans.add);
        $('#hrJobPostingSubmitBtn').text(cfg.trans.save);
    }

    function openCreate() {
        resetForm();
        if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrJobPostingModal'));
        state.modal.show();
    }

    function openEdit(id) {
        var row = state.rows.find(function (r) { return String(r.id) === String(id); });
        if (!row) return;
        resetForm();
        state.editId = id;
        ['title', 'description', 'job_id', 'qualification_type', 'status', 'start_date', 'end_date', 'requirements', 'benefits'].forEach(function (f) {
            $('#hrJobPostingForm [name="' + f + '"]').val(row[f] == null ? '' : row[f]);
        });
        $('#hrJobPostingModalTitle').text(cfg.trans.editTitle);
        $('#hrJobPostingSubmitBtn').text(cfg.trans.update);
        if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrJobPostingModal'));
        state.modal.show();
    }

    function save() {
        if (state.isSubmitting) return;
        setSubmitting(true);
        clearErrors();

        var isEdit = !!state.editId;
        var url = isEdit ? cfg.routes.update.replace('__ID__', state.editId) : cfg.routes.store;
        var data = {
            title: $('#hrJobPostingForm [name="title"]').val(),
            description: $('#hrJobPostingForm [name="description"]').val(),
            job_id: $('#hrJobPostingForm [name="job_id"]').val() || null,
            qualification_type: $('#hrJobPostingForm [name="qualification_type"]').val() || null,
            status: $('#hrJobPostingForm [name="status"]').val(),
            start_date: $('#hrJobPostingForm [name="start_date"]').val(),
            end_date: $('#hrJobPostingForm [name="end_date"]').val() || null,
            requirements: $('#hrJobPostingForm [name="requirements"]').val(),
            benefits: $('#hrJobPostingForm [name="benefits"]').val()
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

        $('#hrJobPostingSearch').on('input', function () {
            var val = $(this).val();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () {
                state.search = val;
                loadList();
            }, 300);
        });

        $('#hrJobPostingStatusFilter').on('change', function () {
            state.status = $(this).val();
            loadList();
        });

        $('#hrJobPostingJobFilter').on('change', function () {
            state.jobId = $(this).val();
            loadList();
        });

        $('#hrJobPostingDateFrom, #hrJobPostingDateTo').on('change', function () {
            state.dateFrom = $('#hrJobPostingDateFrom').val() || '';
            state.dateTo = $('#hrJobPostingDateTo').val() || '';
            loadList();
        });

        $('#hrJobPostingClearFilters').on('click', function () {
            state.status = '';
            state.jobId = '';
            state.dateFrom = '';
            state.dateTo = '';
            state.search = '';
            $('#hrJobPostingStatusFilter').val('');
            $('#hrJobPostingJobFilter').val('');
            $('#hrJobPostingDateFrom').val('');
            $('#hrJobPostingDateTo').val('');
            $('#hrJobPostingSearch').val('');
            loadList();
        });

        $('#hrJobPostingCreateBtn').on('click', openCreate);
        $(document).on('click', '.hr-jp-edit', function () { openEdit($(this).data('id')); });
        $(document).on('click', '.hr-jp-delete', function () { destroy($(this).data('id')); });
        $('#hrJobPostingForm').on('submit', function (e) {
            e.preventDefault();
            save();
        });
    });
})(jQuery);
