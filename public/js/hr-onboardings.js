/**
 * HR Onboardings — Ajax CRUD (multipart + checklist + documents)
 * Config: window._hrOnboardingsConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrOnboardingsConfig;
    if (!cfg) return;

    var HEADER_FIELDS = [
        'contract_id', 'interview_id', 'cv_id', 'status',
        'start_date', 'completion_date', 'notes'
    ];

    var state = {
        search: '', status: '', contractId: '', cvId: '', dateFrom: '', dateTo: '',
        searchTimer: null,
        isSubmitting: false, editId: null,
        modal: null, rows: []
    };

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }
    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }
    function showAlert(type, message) {
        var $box = $('#hrOnboardingAlert');
        $box.removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () { $box.addClass('d-none').removeClass('show'); }, 4000);
    }
    function setSubmitting(on) {
        state.isSubmitting = !!on;
        $('#hrOnboardingSubmitBtn').prop('disabled', !!on);
    }
    function clearErrors() {
        $('#hrOnboardingForm .is-invalid').removeClass('is-invalid');
        $('#hrOnboardingForm .invalid-feedback').text('');
    }
    function errorFieldName(key) {
        return key.replace(/\.(\d+)/g, '[$1]').replace(/\.([^.]+)/g, '[$1]');
    }
    function applyErrors(errors) {
        clearErrors();
        if (!errors) return;
        Object.keys(errors).forEach(function (key) {
            var name = errorFieldName(key);
            var $input = $('#hrOnboardingForm [name="' + name + '"]');
            $input.addClass('is-invalid');
            var $fb = $input.siblings('.invalid-feedback');
            if (!$fb.length) {
                $fb = $input.closest('.hr-ob-checklist-row').find('.invalid-feedback').first();
            }
            $fb.text((errors[key] || [])[0] || '');
        });
    }
    function dateOnly(val) {
        if (!val) return '';
        return String(val).substring(0, 10);
    }
    function statusBadge(status) {
        var map = { completed: 'success', cancelled: 'danger', in_progress: 'warning', pending: 'secondary' };
        var cls = map[status] || 'secondary';
        var label = (cfg.statusLabels && cfg.statusLabels[status]) || status || '-';
        return '<span class="badge bg-' + cls + '">' + escapeHtml(label) + '</span>';
    }

    function checklistRowHtml(index, item) {
        item = item || {};
        var text = item.item || '';
        var done = !!item.completed;
        return (
            '<div class="d-flex gap-2 mb-2 align-items-center hr-ob-checklist-row" data-index="' + index + '">' +
            '<div class="flex-grow-1">' +
            '<input type="text" name="checklist[' + index + '][item]" class="form-control font-hold fw-bold" value="' + escapeHtml(text) + '" placeholder="' + escapeHtml(cfg.trans.checklistItem || '') + '">' +
            '<div class="invalid-feedback"></div>' +
            '</div>' +
            '<div class="form-check d-flex align-items-center mb-0">' +
            '<input type="checkbox" class="form-check-input hr-ob-checklist-done" name="checklist[' + index + '][completed]" value="1"' + (done ? ' checked' : '') + '>' +
            '<label class="form-check-label ms-2 font-hold fw-bold">' + escapeHtml(cfg.trans.completed || '') + '</label>' +
            '</div>' +
            '<button type="button" class="btn btn-danger btn-sm hr-ob-remove-checklist" title="' + escapeHtml(cfg.trans.removeChecklist || '') + '"><i class="las la-trash"></i></button>' +
            '</div>'
        );
    }

    function reindexChecklist() {
        $('#hrOnboardingChecklistWrap .hr-ob-checklist-row').each(function (i) {
            $(this).attr('data-index', i);
            $(this).find('input[type="text"]').attr('name', 'checklist[' + i + '][item]');
            $(this).find('input[type="checkbox"]').attr('name', 'checklist[' + i + '][completed]');
        });
    }

    function renderChecklist(items) {
        var $wrap = $('#hrOnboardingChecklistWrap');
        $wrap.empty();
        var list = items && items.length ? items : [{ item: '', completed: false }];
        list.forEach(function (it, i) { $wrap.append(checklistRowHtml(i, it)); });
    }

    function renderExistingDocs(docs, onboardingId) {
        var $box = $('#hrOnboardingExistingDocs');
        $box.empty();
        if (!onboardingId || !docs || !docs.length) {
            $box.addClass('d-none');
            return;
        }
        $box.removeClass('d-none');
        var html = '<div class="small font-hold fw-bold mb-2">' + escapeHtml(cfg.trans.documents || '') + '</div><ul class="list-unstyled mb-0">';
        docs.forEach(function (doc) {
            var dl = cfg.routes.documentDownload
                .replace('__OID__', onboardingId)
                .replace('__MID__', doc.id);
            html += '<li class="d-flex align-items-center justify-content-between gap-2 mb-1">' +
                '<span class="text-truncate">' + escapeHtml(doc.name || ('#' + doc.id)) + '</span>' +
                '<span class="btn-group btn-group-sm">' +
                '<a href="' + dl + '" class="btn btn-outline-secondary" title="' + escapeHtml(cfg.trans.download || '') + '"><i class="las la-download"></i></a>';
            if (cfg.canEdit) {
                html += '<button type="button" class="btn btn-outline-danger hr-ob-doc-delete" data-oid="' + onboardingId + '" data-mid="' + doc.id + '" title="' + escapeHtml(cfg.trans.deleteDocument || '') + '"><i class="las la-trash"></i></button>';
            }
            html += '</span></li>';
        });
        html += '</ul>';
        $box.html(html);
    }

    function renderRows(rows) {
        state.rows = rows || [];
        var $tbody = $('#hrOnboardingTableBody');
        $tbody.empty();
        $('#hrOnboardingCount').text(state.rows.length);
        if (!state.rows.length) {
            $tbody.append(
                '<tr><td colspan="' + cfg.columnCount + '" class="text-center py-4">' +
                '<div class="alert alert-info mb-0">' + escapeHtml(cfg.trans.empty) + '</div></td></tr>'
            );
            return;
        }
        state.rows.forEach(function (row) {
            var candidate = row.employee_name
                ? '<span class="badge bg-success me-1">' + escapeHtml(cfg.trans.employeeCreated || '') + '</span>' + escapeHtml(row.cv_name || row.employee_name)
                : escapeHtml(row.cv_name || '-');
            var docsCount = (row.documents && row.documents.length) ? row.documents.length : 0;
            var docsCell = docsCount
                ? '<span class="badge bg-info">' + docsCount + '</span>'
                : '<span class="text-muted">-</span>';

            var actions = '<td class="text-center"><div class="btn-group">';
            if (cfg.canEdit) {
                actions += '<button type="button" class="btn btn-sm btn-success hr-ob-edit" data-id="' + row.id + '"><i class="las la-edit"></i></button>';
            }
            if (cfg.canDelete) {
                actions += '<button type="button" class="btn btn-sm btn-danger hr-ob-delete" data-id="' + row.id + '"><i class="las la-trash"></i></button>';
            }
            actions += '</div></td>';

            $tbody.append(
                '<tr>' +
                '<td class="font-hold fw-bold">' + candidate + '</td>' +
                '<td>' + escapeHtml(row.contract_name || '-') + '</td>' +
                '<td>' + statusBadge(row.status) + '</td>' +
                '<td>' + escapeHtml(dateOnly(row.start_date) || '-') + '</td>' +
                '<td>' + escapeHtml(dateOnly(row.completion_date) || '-') + '</td>' +
                '<td>' + docsCell + '</td>' +
                actions +
                '</tr>'
            );
        });
    }

    function activeFiltersCount() {
        var count = 0;
        if (state.status) count++;
        if (state.contractId) count++;
        if (state.cvId) count++;
        if (state.dateFrom) count++;
        if (state.dateTo) count++;
        return count;
    }

    function updateFiltersUI() {
        var count = activeFiltersCount();
        var $badge = $('#hrOnboardingFiltersCount');
        if (count > 0) {
            $badge.removeClass('d-none').text(count);
            $('#hrOnboardingClearFilters').removeClass('d-none');
        } else {
            $badge.addClass('d-none').empty();
            $('#hrOnboardingClearFilters').addClass('d-none');
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
                contract_id: state.contractId,
                cv_id: state.cvId,
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
        var form = $('#hrOnboardingForm')[0];
        if (form) form.reset();
        $('#hrOnboardingForm [name="status"]').val('pending');
        renderChecklist([{ item: '', completed: false }]);
        renderExistingDocs([], null);
        $('#hrOnboardingModalTitle').text(cfg.trans.add);
        $('#hrOnboardingSubmitBtn').text(cfg.trans.save);
    }

    function fillForm(row) {
        HEADER_FIELDS.forEach(function (f) {
            var val = row[f];
            if (f === 'start_date' || f === 'completion_date') val = dateOnly(val);
            $('#hrOnboardingForm [name="' + f + '"]').val(val == null ? '' : val);
        });
        renderChecklist(row.checklist || []);
        renderExistingDocs(row.documents || [], row.id);
    }

    function openCreate() {
        resetForm();
        if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrOnboardingModal'));
        state.modal.show();
    }

    function openEdit(id) {
        var row = state.rows.find(function (r) { return String(r.id) === String(id); });
        if (!row) return;
        resetForm();
        state.editId = id;
        fillForm(row);
        $('#hrOnboardingModalTitle').text(cfg.trans.editTitle);
        $('#hrOnboardingSubmitBtn').text(cfg.trans.update);
        if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrOnboardingModal'));
        state.modal.show();
    }

    function buildFormData() {
        var fd = new FormData();
        HEADER_FIELDS.forEach(function (f) {
            var $el = $('#hrOnboardingForm [name="' + f + '"]');
            fd.append(f, $el.val() == null ? '' : $el.val());
        });

        var checklistIndex = 0;
        $('#hrOnboardingChecklistWrap .hr-ob-checklist-row').each(function () {
            var item = $(this).find('input[type="text"]').val();
            if (!item) return;
            fd.append('checklist[' + checklistIndex + '][item]', item);
            fd.append('checklist[' + checklistIndex + '][completed]', $(this).find('input[type="checkbox"]').is(':checked') ? '1' : '0');
            checklistIndex += 1;
        });

        var fileInput = $('#hrOnboardingForm [name="documents[]"]')[0];
        if (fileInput && fileInput.files && fileInput.files.length) {
            for (var i = 0; i < fileInput.files.length; i++) {
                fd.append('documents[]', fileInput.files[i]);
            }
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

    function destroyDocument(oid, mid) {
        if (!window.confirm(cfg.trans.confirmDeleteDocument || cfg.trans.confirmDelete)) return;
        $.ajax({
            url: cfg.routes.documentDestroy.replace('__OID__', oid).replace('__MID__', mid),
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            success: function (res) {
                showAlert('success', res.message || cfg.trans.documentDeleted || '');
                var row = state.rows.find(function (r) { return String(r.id) === String(oid); });
                if (row && row.documents) {
                    row.documents = row.documents.filter(function (d) { return String(d.id) !== String(mid); });
                    if (String(state.editId) === String(oid)) {
                        renderExistingDocs(row.documents, oid);
                    }
                }
                loadList();
            },
            error: function (xhr) {
                showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error');
            }
        });
    }

    $(function () {
        loadList();
        $('#hrOnboardingSearch').on('input', function () {
            var val = $(this).val();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () { state.search = val; loadList(); }, 300);
        });
        $('#hrOnboardingStatusFilter').on('change', function () {
            state.status = $(this).val();
            loadList();
        });
        $('#hrOnboardingContractFilter').on('change', function () { state.contractId = $(this).val(); loadList(); });
        $('#hrOnboardingCandidateFilter').on('change', function () { state.cvId = $(this).val(); loadList(); });
        $('#hrOnboardingDateFrom').on('change', function () { state.dateFrom = $(this).val(); loadList(); });
        $('#hrOnboardingDateTo').on('change', function () { state.dateTo = $(this).val(); loadList(); });
        $('#hrOnboardingClearFilters').on('click', function () {
            state.status = '';
            state.contractId = '';
            state.cvId = '';
            state.dateFrom = '';
            state.dateTo = '';
            $('#hrOnboardingStatusFilter, #hrOnboardingContractFilter, #hrOnboardingCandidateFilter').val('');
            $('#hrOnboardingDateFrom, #hrOnboardingDateTo').val('');
            loadList();
        });
        $('#hrOnboardingCreateBtn').on('click', openCreate);
        $('#hrOnboardingAddChecklist').on('click', function () {
            var $wrap = $('#hrOnboardingChecklistWrap');
            var i = $wrap.children().length;
            $wrap.append(checklistRowHtml(i, {}));
        });
        $(document).on('click', '.hr-ob-remove-checklist', function () {
            var $wrap = $('#hrOnboardingChecklistWrap');
            $(this).closest('.hr-ob-checklist-row').remove();
            reindexChecklist();
            if (!$wrap.children().length) {
                $wrap.append(checklistRowHtml(0, {}));
            }
        });
        $(document).on('click', '.hr-ob-edit', function () { openEdit($(this).data('id')); });
        $(document).on('click', '.hr-ob-delete', function () { destroy($(this).data('id')); });
        $(document).on('click', '.hr-ob-doc-delete', function () {
            destroyDocument($(this).data('oid'), $(this).data('mid'));
        });
        $('#hrOnboardingForm').on('submit', function (e) { e.preventDefault(); save(); });
    });
})(jQuery);
