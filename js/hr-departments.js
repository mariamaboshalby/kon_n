/**
 * HR Departments CRUD + hierarchy
 */
(function ($) {
    'use strict';

    var cfg = window._hrDepartmentsConfig;
    if (!cfg) {
        return;
    }

    var state = {
        search: '',
        searchTimer: null,
        isSubmitting: false,
        editId: null,
        rows: [],
        modal: null,
        hierarchyModal: null
    };

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function showAlert(type, message) {
        var $box = $('#hrDeptAlert');
        $box.removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () { $box.addClass('d-none').removeClass('show'); }, 4000);
    }

    function setSubmitting(on) {
        state.isSubmitting = !!on;
        $('#hrDeptSubmitBtn').prop('disabled', !!on);
    }

    function clearErrors() {
        $('#hrDeptForm .is-invalid').removeClass('is-invalid');
        $('#hrDeptForm .invalid-feedback').text('');
    }

    function applyErrors(errors) {
        clearErrors();
        Object.keys(errors || {}).forEach(function (key) {
            var $input = $('#hrDeptForm [name="' + key + '"]');
            $input.addClass('is-invalid');
            $input.siblings('.invalid-feedback').text((errors[key] || [])[0] || '');
        });
    }

    function refreshParentOptions(excludeId) {
        var $sel = $('#hrDeptParentSelect');
        var current = $sel.val();
        $sel.find('option:not(:first)').remove();
        state.rows.forEach(function (row) {
            if (excludeId && String(row.id) === String(excludeId)) {
                return;
            }
            $sel.append('<option value="' + row.id + '">' + escapeHtml(row.title) + '</option>');
        });
        if (current) {
            $sel.val(current);
        }
    }

    function renderRows() {
        var $tbody = $('#hrDeptTableBody');
        $tbody.empty();
        var cols = (cfg.canEdit || cfg.canDelete) ? 8 : 7;

        if (!state.rows.length) {
            $tbody.append('<tr><td colspan="' + cols + '" class="text-center py-4"><div class="alert alert-info mb-0">' + escapeHtml(cfg.trans.empty) + '</div></td></tr>');
            return;
        }

        state.rows.forEach(function (row, idx) {
            var pct = row.max_leave_percentage
                ? '<span class="badge bg-primary text-white">' + escapeHtml(Number(row.max_leave_percentage).toFixed(2)) + '%</span>'
                : '<span class="text-muted">-</span>';

            var actions = '';
            if (cfg.canEdit || cfg.canDelete) {
                actions = '<td class="text-center"><div class="btn-group">' +
                    '<button type="button" class="btn btn-warning btn-sm hr-dept-hierarchy" data-id="' + row.id + '" data-title="' + escapeHtml(row.title) + '" title="' + escapeHtml(cfg.trans.viewHierarchy) + '"><i class="las la-sitemap"></i></button>';
                if (cfg.canEdit) {
                    actions += '<button type="button" class="btn btn-success btn-sm hr-dept-edit" data-id="' + row.id + '"><i class="las la-edit"></i></button>';
                }
                if (cfg.canDelete) {
                    actions += '<button type="button" class="btn btn-danger btn-sm hr-dept-delete" data-id="' + row.id + '"><i class="las la-trash"></i></button>';
                }
                actions += '</div></td>';
            }

            $tbody.append(
                '<tr>' +
                '<td class="font-hold fw-bold text-center">' + (idx + 1) + '</td>' +
                '<td class="font-hold fw-bold text-center">' + escapeHtml(row.title) + '</td>' +
                '<td class="font-hold fw-bold text-center">' + escapeHtml(row.parent ? row.parent.title : '-') + '</td>' +
                '<td class="font-hold fw-bold text-center">' + escapeHtml(row.director ? row.director.name : '-') + '</td>' +
                '<td class="font-hold fw-bold text-center">' + escapeHtml(row.deputy_director ? row.deputy_director.name : '-') + '</td>' +
                '<td class="font-hold fw-bold text-center">' + escapeHtml(row.description || '-') + '</td>' +
                '<td class="font-hold fw-bold text-center">' + pct + '</td>' +
                actions +
                '</tr>'
            );
        });
    }

    function loadList() {
        $.getJSON(cfg.routes.data, { search: state.search }, function (res) {
            state.rows = res.data || [];
            renderRows();
            refreshParentOptions(state.editId);
        });
    }

    function openCreate() {
        clearErrors();
        state.editId = null;
        $('#hrDeptForm')[0].reset();
        $('#hrDeptModalTitle').text(cfg.trans.add);
        $('#hrDeptSubmitBtn').text(cfg.trans.save);
        refreshParentOptions(null);
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.getElementById('hrDeptModal'));
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
        refreshParentOptions(id);
        $('#hrDeptForm [name="title"]').val(row.title);
        $('#hrDeptForm [name="parent_id"]').val(row.parent_id || '');
        $('#hrDeptForm [name="director_id"]').val(row.director_id || '');
        $('#hrDeptForm [name="deputy_director_id"]').val(row.deputy_director_id || '');
        $('#hrDeptForm [name="description"]').val(row.description || '');
        $('#hrDeptForm [name="max_leave_percentage"]').val(row.max_leave_percentage != null ? row.max_leave_percentage : '');
        $('#hrDeptModalTitle').text(cfg.trans.editTitle);
        $('#hrDeptSubmitBtn').text(cfg.trans.update);
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.getElementById('hrDeptModal'));
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
            title: $('#hrDeptForm [name="title"]').val(),
            parent_id: $('#hrDeptForm [name="parent_id"]').val() || null,
            director_id: $('#hrDeptForm [name="director_id"]').val() || null,
            deputy_director_id: $('#hrDeptForm [name="deputy_director_id"]').val() || null,
            description: $('#hrDeptForm [name="description"]').val() || null,
            max_leave_percentage: $('#hrDeptForm [name="max_leave_percentage"]').val() || null
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

    function openHierarchy(id, title) {
        $('#hrDeptHierarchyTitle').text(cfg.trans.hierarchy + ' — ' + title);
        $('#hrDeptHierarchyBody').html('<div class="text-center py-3">...</div>');
        if (!state.hierarchyModal) {
            state.hierarchyModal = new bootstrap.Modal(document.getElementById('hrDeptHierarchyModal'));
        }
        state.hierarchyModal.show();
        $.get(cfg.routes.hierarchy.replace('__ID__', id), function (html) {
            $('#hrDeptHierarchyBody').html(html);
        });
    }

    $(function () {
        loadList();
        $('#hrDeptSearch').on('input', function () {
            var v = $(this).val();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () {
                state.search = v;
                loadList();
            }, 300);
        });
        $('#hrDeptCreateBtn').on('click', openCreate);
        $(document).on('click', '.hr-dept-edit', function () { openEdit($(this).data('id')); });
        $(document).on('click', '.hr-dept-delete', function () { destroy($(this).data('id')); });
        $(document).on('click', '.hr-dept-hierarchy', function () {
            openHierarchy($(this).data('id'), $(this).data('title'));
        });
        $('#hrDeptForm').on('submit', function (e) {
            e.preventDefault();
            save();
        });
    });
})(jQuery);
