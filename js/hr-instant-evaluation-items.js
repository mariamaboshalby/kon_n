/**
 * Instant Evaluation Items CRUD
 * Config: window._hrInstantEvaluationItemsConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrInstantEvaluationItemsConfig;
    if (!cfg) return;

    var state = { search: '', polarity: '', searchTimer: null, isSubmitting: false, editId: null, modal: null };

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }

    function esc(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function showAlert(type, message) {
        var $box = $('#hrIeItemAlert');
        $box.removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(esc(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () { $box.addClass('d-none').removeClass('show'); }, 4000);
    }

    function loadData() {
        var $body = $('#hrIeItemTableBody');
        $body.html('<tr><td colspan="6" class="text-muted">...</td></tr>');
        $.get(cfg.urls.data, { search: state.search, polarity: state.polarity }).done(function (res) {
            var rows = res.data || [];
            if (!rows.length) {
                $body.html('<tr><td colspan="6" class="text-muted">' + esc(cfg.lang.empty) + '</td></tr>');
                return;
            }
            $body.html(rows.map(function (row, idx) {
                var actions = '';
                if (cfg.canEdit || cfg.canDelete) {
                    actions = '<td>';
                    if (cfg.canEdit) {
                        actions += '<button type="button" class="btn btn-sm btn-outline-primary font-hold me-1 hr-ie-item-edit" data-id="' + row.id + '">' + esc(cfg.lang.editBtn) + '</button>';
                    }
                    if (cfg.canDelete) {
                        actions += '<button type="button" class="btn btn-sm btn-outline-danger font-hold hr-ie-item-del" data-id="' + row.id + '">' + esc(cfg.lang.delete) + '</button>';
                    }
                    actions += '</td>';
                }
                return '<tr data-row=\'' + esc(JSON.stringify(row)).replace(/'/g, '&#39;') + '\'>' +
                    '<td>' + (idx + 1) + '</td>' +
                    '<td class="font-hold">' + esc(row.name) + '</td>' +
                    '<td class="font-hold">' + esc(row.polarity === 'negative' ? cfg.lang.negative : cfg.lang.positive) + '</td>' +
                    '<td class="d-none d-md-table-cell font-hold text-start">' + esc(row.description || '-') + '</td>' +
                    '<td>' + esc(row.is_active ? cfg.lang.yes : cfg.lang.no) + '</td>' +
                    actions + '</tr>';
            }).join(''));
        });
    }

    function openModal(row) {
        state.editId = row ? row.id : null;
        clearErrors();
        $('#hrIeItemModalTitle').text(row ? cfg.lang.edit : cfg.lang.add);
        $('#hrIeItemId').val(row ? row.id : '');
        $('#hrIeItemName').val(row ? row.name : '');
        $('#hrIeItemPolarity').val(row ? row.polarity : 'positive');
        $('#hrIeItemDescription').val(row ? (row.description || '') : '');
        $('#hrIeItemActive').prop('checked', row ? !!row.is_active : true);
        if (!state.modal) state.modal = new bootstrap.Modal(document.getElementById('hrIeItemModal'));
        state.modal.show();
    }

    function clearErrors() {
        $('#hrIeItemForm .is-invalid').removeClass('is-invalid');
        $('#hrIeItemForm .invalid-feedback').text('');
    }

    $(function () {
        loadData();

        $('#hrIeItemSearch').on('input', function () {
            clearTimeout(state.searchTimer);
            var v = $(this).val();
            state.searchTimer = setTimeout(function () { state.search = v; loadData(); }, 280);
        });
        $('#hrIeItemPolarityFilter').on('change', function () {
            state.polarity = $(this).val() || '';
            loadData();
        });
        $('#hrIeItemCreateBtn').on('click', function () { openModal(null); });

        $(document).on('click', '.hr-ie-item-edit', function () {
            var raw = $(this).closest('tr').attr('data-row');
            try { openModal(JSON.parse($('<textarea/>').html(raw).text())); } catch (e) {}
        });

        $(document).on('click', '.hr-ie-item-del', function () {
            var id = $(this).data('id');
            if (!id || !window.confirm(cfg.lang.confirmDelete)) return;
            $.ajax({
                url: cfg.urls.destroy + '/' + id,
                method: 'DELETE',
                data: { _token: csrf() },
                success: function (res) { showAlert('success', res.message || ''); loadData(); },
                error: function (xhr) { showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error'); }
            });
        });

        $('#hrIeItemForm').on('submit', function (e) {
            e.preventDefault();
            if (state.isSubmitting) return;
            state.isSubmitting = true;
            $('#hrIeItemSubmitBtn').prop('disabled', true);
            clearErrors();

            var payload = {
                _token: csrf(),
                name: $('#hrIeItemName').val(),
                polarity: $('#hrIeItemPolarity').val(),
                description: $('#hrIeItemDescription').val(),
                is_active: $('#hrIeItemActive').is(':checked') ? 1 : 0
            };
            var isEdit = !!state.editId;
            $.ajax({
                url: isEdit ? (cfg.urls.update + '/' + state.editId) : cfg.urls.store,
                method: isEdit ? 'POST' : 'POST',
                data: isEdit ? Object.assign(payload, { _method: 'PUT' }) : payload,
                success: function (res) {
                    if (state.modal) state.modal.hide();
                    showAlert('success', res.message || '');
                    loadData();
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        Object.keys(xhr.responseJSON.errors).forEach(function (key) {
                            var $el = $('#hrIeItemForm [name="' + key + '"]');
                            $el.addClass('is-invalid');
                            $el.siblings('.invalid-feedback').text((xhr.responseJSON.errors[key] || [])[0] || '');
                        });
                    } else {
                        showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error');
                    }
                },
                complete: function () {
                    state.isSubmitting = false;
                    $('#hrIeItemSubmitBtn').prop('disabled', false);
                }
            });
        });
    });
})(jQuery);
