/**
 * HR Covenants CRUD + assign + view
 * Config: window._hrCovenantsConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrCovenantsConfig;
    if (!cfg) {
        return;
    }

    var state = {
        search: '',
        employeeId: '',
        assignmentStatus: '',
        searchTimer: null,
        isSubmitting: false,
        isAssigning: false,
        editId: null,
        assignId: null,
        rows: [],
        modal: null,
        viewModal: null,
        assignModal: null
    };

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function showAlert(type, message) {
        var $box = $('#hrCovenantAlert');
        $box.removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () { $box.addClass('d-none').removeClass('show'); }, 4000);
    }

    function setSubmitting(on) {
        state.isSubmitting = !!on;
        $('#hrCovenantSubmitBtn').prop('disabled', !!on);
    }

    function setAssigning(on) {
        state.isAssigning = !!on;
        $('#hrCovenantAssignSubmitBtn').prop('disabled', !!on);
    }

    function clearErrors($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');
    }

    function applyErrors($form, errors) {
        clearErrors($form);
        Object.keys(errors || {}).forEach(function (key) {
            var $input = $form.find('[name="' + key + '"]');
            $input.addClass('is-invalid');
            $input.siblings('.invalid-feedback').text((errors[key] || [])[0] || '');
        });
    }

    function limitText(text, len) {
        var t = text || cfg.trans.noDescription;
        return t.length > len ? t.substring(0, len) + '...' : t;
    }

    function formatDate(iso) {
        if (!iso) {
            return '-';
        }
        return String(iso).substring(0, 10);
    }

    function renderRows() {
        var $tbody = $('#hrCovenantTableBody');
        $tbody.empty();
        var cols = (cfg.canView || cfg.canEdit || cfg.canDelete) ? 8 : 7;

        if (!state.rows.length) {
            $tbody.append('<tr><td colspan="' + cols + '" class="text-center py-4"><div class="alert alert-info mb-0">' + escapeHtml(cfg.trans.empty) + '</div></td></tr>');
            return;
        }

        state.rows.forEach(function (row, idx) {
            var statusText = row.status ? ((cfg.trans.statusLabels && cfg.trans.statusLabels[row.status]) || row.status) : '';
            var statusHtml = statusText
                ? '<span class="badge ' + (row.status === 'returned' ? 'bg-success' : 'bg-warning text-dark') + '">' + escapeHtml(statusText) + '</span>'
                : '<span class="text-muted">-</span>';
            var imageHtml = row.image_url
                ? '<div class="d-flex justify-content-center align-items-center gap-1">' +
                  '<img src="' + escapeHtml(row.image_url) + '" alt="" class="img-thumbnail" style="max-width:50px;max-height:50px;object-fit:cover;">' +
                  '<a class="btn btn-sm btn-outline-primary" href="' + cfg.routes.download.replace('__ID__', row.id) + '" title="' + escapeHtml(cfg.trans.downloadImage) + '"><i class="las la-download"></i></a>' +
                  '</div>'
                : '<span class="text-muted"><i class="las la-file-image"></i> ' + escapeHtml(cfg.trans.noFile) + '</span>';

            var actions = '';
            if (cfg.canView || cfg.canEdit || cfg.canDelete) {
                actions = '<td class="text-center"><div class="btn-group">';
                if (cfg.canView) {
                    actions += '<button type="button" class="btn btn-sm btn-outline-info hr-covenant-view" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.view) + '"><i class="las la-eye"></i></button>';
                }
                if (cfg.canEdit) {
                    actions += '<button type="button" class="btn btn-sm btn-outline-warning hr-covenant-edit" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.edit) + '"><i class="las la-edit"></i></button>';
                    actions += '<button type="button" class="btn btn-sm btn-outline-success hr-covenant-assign" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.assign) + '"><i class="las la-user-check"></i></button>';
                }
                if (cfg.canDelete) {
                    actions += '<button type="button" class="btn btn-sm btn-outline-danger hr-covenant-delete" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.delete) + '"><i class="las la-trash"></i></button>';
                }
                actions += '</div></td>';
            }

            $tbody.append(
                '<tr>' +
                '<td class="font-hold fw-bold">' + (idx + 1) + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.name) + '</td>' +
                '<td class="font-hold">' + escapeHtml(limitText(row.description, 50)) + '</td>' +
                '<td class="font-hold">' + (row.employee ? '<span class="badge bg-info text-white">' + escapeHtml(row.employee.name) + '</span>' : '<span class="text-muted">' + escapeHtml(cfg.trans.noEmployee) + '</span>') + '</td>' +
                '<td class="font-hold">' + statusHtml + '</td>' +
                '<td>' + imageHtml + '</td>' +
                '<td class="font-hold">' + escapeHtml(formatDate(row.created_at)) + '</td>' +
                actions +
                '</tr>'
            );
        });
    }

    function loadList() {
        $.getJSON(cfg.routes.data, {
            search: state.search,
            employee_id: state.employeeId || '',
            assignment_status: state.assignmentStatus || ''
        }, function (res) {
            state.rows = res.data || [];
            renderRows();
        }).fail(function () {
            showAlert('danger', 'Error');
        });
    }

    function renderCurrentImage($box, url, caption) {
        if (url) {
            $box.removeClass('d-none').html(
                '<img src="' + escapeHtml(url) + '" class="img-thumbnail" style="max-height:120px;">' +
                '<p class="small text-muted mb-0">' + escapeHtml(caption) + '</p>'
            );
        } else {
            $box.addClass('d-none').empty();
        }
    }

    function openCreate() {
        clearErrors($('#hrCovenantForm'));
        state.editId = null;
        $('#hrCovenantForm')[0].reset();
        $('#hrCovenantCurrentImage').addClass('d-none').empty();
        $('#hrCovenantImagePreview').addClass('d-none').empty();
        $('#hrCovenantCurrentAcknowledgment').addClass('d-none').empty();
        $('#hrCovenantAcknowledgmentPreview').addClass('d-none').empty();
        $('#hrCovenantCurrentDischarge').addClass('d-none').empty();
        $('#hrCovenantDischargePreview').addClass('d-none').empty();
        $('#hrCovenantModalTitle').text(cfg.trans.add);
        $('#hrCovenantSubmitBtn').text(cfg.trans.save);
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.getElementById('hrCovenantModal'));
        }
        state.modal.show();
    }

    function openEdit(id) {
        var row = state.rows.find(function (r) { return String(r.id) === String(id); });
        if (!row) {
            return;
        }
        clearErrors($('#hrCovenantForm'));
        state.editId = id;
        $('#hrCovenantForm')[0].reset();
        $('#hrCovenantForm [name="name"]').val(row.name);
        $('#hrCovenantForm [name="description"]').val(row.description || '');
        $('#hrCovenantForm [name="status"]').val(row.status || '');
        $('#hrCovenantForm [name="handover_status"]').val(row.handover_status || '');
        $('#hrCovenantForm [name="cost"]').val(row.cost !== null && row.cost !== undefined ? row.cost : '');
        renderCurrentImage($('#hrCovenantCurrentImage'), row.image_url, cfg.trans.currentImage);
        renderCurrentImage($('#hrCovenantCurrentAcknowledgment'), row.acknowledgment_url, cfg.trans.currentImage);
        renderCurrentImage($('#hrCovenantCurrentDischarge'), row.discharge_url, cfg.trans.currentImage);
        $('#hrCovenantImagePreview').addClass('d-none').empty();
        $('#hrCovenantAcknowledgmentPreview').addClass('d-none').empty();
        $('#hrCovenantDischargePreview').addClass('d-none').empty();
        $('#hrCovenantModalTitle').text(cfg.trans.editTitle);
        $('#hrCovenantSubmitBtn').text(cfg.trans.update);
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.getElementById('hrCovenantModal'));
        }
        state.modal.show();
    }

    function save() {
        if (state.isSubmitting) {
            return;
        }
        setSubmitting(true);
        clearErrors($('#hrCovenantForm'));

        var formData = new FormData();
        formData.append('name', $('#hrCovenantForm [name="name"]').val());
        formData.append('description', $('#hrCovenantForm [name="description"]').val() || '');
        formData.append('status', $('#hrCovenantForm [name="status"]').val() || '');
        formData.append('handover_status', $('#hrCovenantForm [name="handover_status"]').val() || '');
        formData.append('cost', $('#hrCovenantForm [name="cost"]').val() || '');
        var file = $('#hrCovenantImageInput')[0].files[0];
        if (file) {
            formData.append('image', file);
        }
        var ackFile = $('#hrCovenantAcknowledgmentInput')[0].files[0];
        if (ackFile) {
            formData.append('acknowledgment_image', ackFile);
        }
        var dischargeFile = $('#hrCovenantDischargeInput')[0].files[0];
        if (dischargeFile) {
            formData.append('discharge_image', dischargeFile);
        }

        var isEdit = !!state.editId;
        var url = isEdit ? cfg.routes.update.replace('__ID__', state.editId) : cfg.routes.store;
        if (isEdit) {
            formData.append('_method', 'PUT');
        }

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
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
                    applyErrors($('#hrCovenantForm'), xhr.responseJSON.errors);
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

    function openView(id) {
        $('#hrCovenantViewBody').html('<div class="text-center py-4">...</div>');
        if (!state.viewModal) {
            state.viewModal = new bootstrap.Modal(document.getElementById('hrCovenantViewModal'));
        }
        state.viewModal.show();
        $.getJSON(cfg.routes.show.replace('__ID__', id), function (res) {
            var row = res.data || {};
            var statusText = row.status ? ((cfg.trans.statusLabels && cfg.trans.statusLabels[row.status]) || row.status) : cfg.trans.noDescription;
            var imageBlock = row.image_url
                ? '<img src="' + escapeHtml(row.image_url) + '" class="img-fluid rounded mb-2" style="max-height:300px;">' +
                  '<div><a class="btn btn-sm btn-primary" href="' + cfg.routes.download.replace('__ID__', row.id) + '"><i class="las la-download"></i> ' + escapeHtml(cfg.trans.downloadImage) + '</a></div>'
                : '<div class="text-muted">' + escapeHtml(cfg.trans.noImage) + '</div>';
            var ackBlock = row.acknowledgment_url
                ? '<img src="' + escapeHtml(row.acknowledgment_url) + '" class="img-fluid rounded" style="max-height:300px;">'
                : '<div class="text-muted">' + escapeHtml(cfg.trans.noImage) + '</div>';
            var dischargeBlock = row.discharge_url
                ? '<img src="' + escapeHtml(row.discharge_url) + '" class="img-fluid rounded" style="max-height:300px;">'
                : '<div class="text-muted">' + escapeHtml(cfg.trans.noImage) + '</div>';

            $('#hrCovenantViewBody').html(
                '<div class="row g-3">' +
                '<div class="col-md-6"><label class="text-muted">' + escapeHtml(cfg.trans.name) + '</label><div class="p-2 bg-light rounded">' + escapeHtml(row.name) + '</div></div>' +
                '<div class="col-md-6"><label class="text-muted">' + escapeHtml(cfg.trans.employee) + '</label><div class="p-2 bg-light rounded">' + escapeHtml(row.employee ? row.employee.name : cfg.trans.noEmployee) + '</div></div>' +
                '<div class="col-md-6"><label class="text-muted">' + escapeHtml(cfg.trans.covenantStatus) + '</label><div class="p-2 bg-light rounded">' + escapeHtml(statusText) + '</div></div>' +
                '<div class="col-md-6"><label class="text-muted">' + escapeHtml(cfg.trans.handoverStatus) + '</label><div class="p-2 bg-light rounded">' + escapeHtml(row.handover_status || cfg.trans.noDescription) + '</div></div>' +
                '<div class="col-md-6"><label class="text-muted">' + escapeHtml(cfg.trans.covenantCost) + '</label><div class="p-2 bg-light rounded">' + (row.cost !== null && row.cost !== undefined ? escapeHtml(String(row.cost)) : '<span class="text-muted">-</span>') + '</div></div>' +
                '<div class="col-12"><label class="text-muted">' + escapeHtml(cfg.trans.description) + '</label><div class="p-2 bg-light rounded">' + escapeHtml(row.description || cfg.trans.noDescription) + '</div></div>' +
                '<div class="col-12"><label class="text-muted">' + escapeHtml(cfg.trans.image) + '</label><div class="p-2 bg-light rounded text-center">' + imageBlock + '</div></div>' +
                '<div class="col-12"><label class="text-muted">' + escapeHtml(cfg.trans.acknowledgmentImage) + '</label><div class="p-2 bg-light rounded text-center">' + ackBlock + '</div></div>' +
                '<div class="col-12"><label class="text-muted">' + escapeHtml(cfg.trans.dischargeImage) + '</label><div class="p-2 bg-light rounded text-center">' + dischargeBlock + '</div></div>' +
                '<div class="col-md-6"><label class="text-muted">' + escapeHtml(cfg.trans.createdAt) + '</label><div class="p-2 bg-light rounded">' + escapeHtml(formatDate(row.created_at)) + '</div></div>' +
                '<div class="col-md-6"><label class="text-muted">' + escapeHtml(cfg.trans.updatedAt) + '</label><div class="p-2 bg-light rounded">' + escapeHtml(formatDate(row.updated_at)) + '</div></div>' +
                '</div>'
            );
        });
    }

    function openAssign(id) {
        var row = state.rows.find(function (r) { return String(r.id) === String(id); });
        if (!row) {
            return;
        }
        clearErrors($('#hrCovenantAssignForm'));
        state.assignId = id;
        $('#hrCovenantAssignEmployee').val(row.employee_id || 0);
        if (!state.assignModal) {
            state.assignModal = new bootstrap.Modal(document.getElementById('hrCovenantAssignModal'));
        }
        state.assignModal.show();
    }

    function saveAssign() {
        if (state.isAssigning || !state.assignId) {
            return;
        }
        setAssigning(true);
        clearErrors($('#hrCovenantAssignForm'));
        $.ajax({
            url: cfg.routes.assign.replace('__ID__', state.assignId),
            method: 'POST',
            data: { employee_id: $('#hrCovenantAssignEmployee').val() },
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            success: function (res) {
                setAssigning(false);
                if (state.assignModal) {
                    state.assignModal.hide();
                }
                showAlert('success', res.message);
                loadList();
            },
            error: function (xhr) {
                setAssigning(false);
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    applyErrors($('#hrCovenantAssignForm'), xhr.responseJSON.errors);
                    return;
                }
                showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error');
            }
        });
    }

    $(function () {
        loadList();

        $('#hrCovenantSearch').on('input', function () {
            var v = $(this).val();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () {
                state.search = v;
                loadList();
            }, 300);
        });

        $('#hrCovenantFilterEmployee, #hrCovenantFilterAssignment').on('change', function () {
            state.employeeId = $('#hrCovenantFilterEmployee').val();
            state.assignmentStatus = $('#hrCovenantFilterAssignment').val();
            loadList();
        });

        $('#hrCovenantClearFilters').on('click', function () {
            $('#hrCovenantSearch').val('');
            $('#hrCovenantFilterEmployee').val('');
            $('#hrCovenantFilterAssignment').val('');
            state.search = '';
            state.employeeId = '';
            state.assignmentStatus = '';
            loadList();
        });

        $('#hrCovenantCreateBtn').on('click', openCreate);
        $(document).on('click', '.hr-covenant-edit', function () { openEdit($(this).data('id')); });
        $(document).on('click', '.hr-covenant-delete', function () { destroy($(this).data('id')); });
        $(document).on('click', '.hr-covenant-view', function () { openView($(this).data('id')); });
        $(document).on('click', '.hr-covenant-assign', function () { openAssign($(this).data('id')); });

        function bindImagePreview(inputSelector, previewSelector) {
            $(document).on('change', inputSelector, function (e) {
                var file = e.target.files[0];
                if (!file) {
                    $(previewSelector).addClass('d-none').empty();
                    return;
                }
                var reader = new FileReader();
                reader.onload = function (ev) {
                    $(previewSelector).removeClass('d-none').html(
                        '<img src="' + ev.target.result + '" class="img-thumbnail" style="max-height:120px;">'
                    );
                };
                reader.readAsDataURL(file);
            });
        }

        bindImagePreview('#hrCovenantImageInput', '#hrCovenantImagePreview');
        bindImagePreview('#hrCovenantAcknowledgmentInput', '#hrCovenantAcknowledgmentPreview');
        bindImagePreview('#hrCovenantDischargeInput', '#hrCovenantDischargePreview');

        $('#hrCovenantForm').on('submit', function (e) {
            e.preventDefault();
            save();
        });

        $('#hrCovenantAssignForm').on('submit', function (e) {
            e.preventDefault();
            saveAssign();
        });
    });
})(jQuery);
