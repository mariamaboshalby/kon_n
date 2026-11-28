/**
 * Shared simple HR CRUD (title + optional parent select + extra fields).
 * Config: window._hrSimpleCrudConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrSimpleCrudConfig;
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
        var $box = $(cfg.alertSelector || '#hrCrudAlert');
        if (!$box.length) {
            return;
        }
        $box
            .removeClass('d-none alert-success alert-danger alert-info')
            .addClass('alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>')
            .addClass('alert alert-dismissible fade show');
        setTimeout(function () {
            $box.addClass('d-none').removeClass('show');
        }, 4000);
    }

    function setSubmitting(on) {
        state.isSubmitting = !!on;
        $(cfg.formSelector + ' button[type="submit"]').prop('disabled', !!on);
    }

    function clearErrors() {
        $(cfg.formSelector + ' .is-invalid').removeClass('is-invalid');
        $(cfg.formSelector + ' .invalid-feedback').text('');
    }

    function applyErrors(errors) {
        clearErrors();
        if (!errors) {
            return;
        }
        Object.keys(errors).forEach(function (key) {
            var $input = $(cfg.formSelector + ' [name="' + key + '"]');
            $input.addClass('is-invalid');
            $input.siblings('.invalid-feedback').text((errors[key] || [])[0] || '');
        });
    }

    function resetForm() {
        clearErrors();
        state.editId = null;
        var form = $(cfg.formSelector)[0];
        if (form) {
            form.reset();
        }
        $(cfg.modalTitleSelector).text(cfg.trans.add);
        $(cfg.submitBtnSelector).text(cfg.trans.save);
    }

    function fillForm(row) {
        (cfg.fields || []).forEach(function (field) {
            var $el = $(cfg.formSelector + ' [name="' + field.name + '"]');
            var val = row[field.name];
            if (field.type === 'checkbox') {
                $el.prop('checked', !!val);
            } else {
                $el.val(val == null ? '' : val);
            }
        });
    }

    function formPayload() {
        var data = {};
        (cfg.fields || []).forEach(function (field) {
            var $el = $(cfg.formSelector + ' [name="' + field.name + '"]');
            if (field.type === 'checkbox') {
                data[field.name] = $el.is(':checked') ? 1 : 0;
            } else {
                data[field.name] = $el.val();
            }
        });
        return data;
    }

    function parentLabel(row) {
        if (!cfg.parent) {
            return '';
        }
        var rel = row[cfg.parent.relation];
        return rel && rel.title ? rel.title : '-';
    }

    function renderRows(rows) {
        state.rows = rows || [];
        var $tbody = $(cfg.tbodySelector);
        $tbody.empty();

        if (!state.rows.length) {
            var cols = cfg.columnCount || 3;
            $tbody.append(
                '<tr><td colspan="' + cols + '" class="text-center font-hold fw-bold py-4">' +
                '<div class="alert alert-info mb-0">' + escapeHtml(cfg.trans.empty) + '</div></td></tr>'
            );
            return;
        }

        state.rows.forEach(function (row, idx) {
            var actions = '';
            if (cfg.canEdit || cfg.canDelete) {
                actions = '<td class="text-center"><div class="btn-group" role="group">';
                if (cfg.canEdit) {
                    actions += '<button type="button" class="btn btn-success btn-sm hr-crud-edit" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.edit) + '"><i class="las la-edit"></i></button>';
                }
                if (cfg.canDelete) {
                    actions += '<button type="button" class="btn btn-danger btn-sm hr-crud-delete" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.delete) + '"><i class="las la-trash"></i></button>';
                }
                actions += '</div></td>';
            }

            var extraCols = '';
            if (cfg.parent) {
                extraCols += '<td class="font-hold fw-bold text-center">' + escapeHtml(parentLabel(row)) + '</td>';
            }
            (cfg.extraColumns || []).forEach(function (col) {
                var v = row[col.key];
                if (col.format === 'number') {
                    v = v == null || v === '' ? '-' : v;
                }
                extraCols += '<td class="font-hold fw-bold text-center">' + escapeHtml(v == null || v === '' ? '-' : v) + '</td>';
            });

            $tbody.append(
                '<tr>' +
                '<td class="font-hold fw-bold text-center">' + (idx + 1) + '</td>' +
                '<td class="font-hold fw-bold text-center">' + escapeHtml(row[cfg.titleField || 'title']) + '</td>' +
                extraCols +
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
                showAlert('danger', cfg.trans.loadError || 'Error');
            }
        });
    }

    function openCreate() {
        resetForm();
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.querySelector(cfg.modalSelector));
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
        $(cfg.modalTitleSelector).text(cfg.trans.editTitle);
        $(cfg.submitBtnSelector).text(cfg.trans.update);
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.querySelector(cfg.modalSelector));
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
        var method = isEdit ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            data: formPayload(),
            headers: {
                'X-CSRF-TOKEN': csrf(),
                Accept: 'application/json'
            },
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
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || cfg.trans.saveError || 'Error';
                showAlert('danger', msg);
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
            headers: {
                'X-CSRF-TOKEN': csrf(),
                Accept: 'application/json'
            },
            success: function (res) {
                showAlert('success', res.message || cfg.trans.deleted);
                loadList();
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || cfg.trans.deleteError || 'Error';
                showAlert('danger', msg);
            }
        });
    }

    $(function () {
        loadList();

        $(cfg.searchSelector).on('input', function () {
            var val = $(this).val();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () {
                state.search = val;
                loadList();
            }, 300);
        });

        $(cfg.createBtnSelector).on('click', openCreate);

        $(document).on('click', cfg.tbodySelector + ' .hr-crud-edit', function () {
            openEdit($(this).data('id'));
        });

        $(document).on('click', cfg.tbodySelector + ' .hr-crud-delete', function () {
            destroy($(this).data('id'));
        });

        $(cfg.formSelector).on('submit', function (e) {
            e.preventDefault();
            save();
        });
    });
})(jQuery);
