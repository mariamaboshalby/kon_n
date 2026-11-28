/**
 * HR Instant Evaluations (+ polarity / items)
 * Config: window._hrInstantEvaluationsConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrInstantEvaluationsConfig;
    if (!cfg) return;

    var state = {
        search: '',
        employeeId: '',
        polarity: '',
        dateFrom: '',
        dateTo: '',
        searchTimer: null,
        isSubmitting: false,
        page: 1,
        modal: null
    };

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }

    function esc(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function showAlert(type, message) {
        var $box = $('#hrInstantEvalAlert');
        $box.removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(esc(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () { $box.addClass('d-none').removeClass('show'); }, 4000);
    }

    function currentPolarity() {
        return $('input[name="polarity"]:checked').val() || 'positive';
    }

    function fillItems() {
        var polarity = currentPolarity();
        var items = (cfg.items && cfg.items[polarity]) || [];
        var $sel = $('#hrInstantEvalItemSelect');
        var html = '<option value="">' + esc(cfg.lang.selectItem) + '</option>';
        items.forEach(function (it) {
            html += '<option value="' + it.id + '">' + esc(it.name) + '</option>';
        });
        $sel.html(html);
        $('#hrInstantEvalScoreHint').text(
            polarity === 'negative' ? cfg.lang.scoreHintNegative : cfg.lang.scoreHintPositive
        );
    }

    function loadData() {
        var $body = $('#hrInstantEvalTableBody');
        $body.html('<tr><td colspan="9" class="text-muted">...</td></tr>');

        $.ajax({
            url: cfg.urls.data,
            method: 'GET',
            data: {
                search:      state.search || '',
                employee_id: state.employeeId || '',
                polarity:    state.polarity || '',
                date_from:   state.dateFrom || '',
                date_to:     state.dateTo   || '',
                page:        state.page
            },
            success: function (res) {
                var rows = res.data || [];
                if (!rows.length) {
                    $body.html('<tr><td colspan="9" class="text-muted">' + esc(cfg.lang.noData) + '</td></tr>');
                    return;
                }

                $body.html(rows.map(function (row, idx) {
                    var pol = row.polarity === 'negative' ? cfg.lang.negative : cfg.lang.positive;
                    var scoreCls = row.score < 0 ? 'text-danger' : 'text-success';
                    var scoreLabel = (row.score > 0 ? '+' : '') + row.score;
                    var actions = '';
                    if (cfg.canDelete) {
                        actions = '<td><button type="button" class="btn btn-sm btn-outline-danger font-hold hr-instant-eval-delete" data-id="' + row.id + '">' +
                            esc(cfg.lang.delete) + '</button></td>';
                    }
                    return '<tr>' +
                        '<td>' + (idx + 1) + '</td>' +
                        '<td class="font-hold">' + esc(row.employee_name || '-') + '</td>' +
                        '<td class="font-hold">' + esc(pol) + '</td>' +
                        '<td class="fw-bold ' + scoreCls + '">' + esc(scoreLabel) + '</td>' +
                        '<td class="font-hold">' + esc(row.item_name || '-') + '</td>' +
                        '<td class="d-none d-lg-table-cell font-hold text-start">' + esc(row.note || '-') + '</td>' +
                        '<td class="font-hold">' + esc(row.evaluator_name || '-') + '</td>' +
                        '<td class="font-hold">' + esc(row.evaluated_at_label || '-') + '</td>' +
                        actions +
                        '</tr>';
                }).join(''));
            },
            error: function () {
                $body.html('<tr><td colspan="9" class="text-danger">Error</td></tr>');
            }
        });
    }

    function openCreateModal() {
        $('#hrInstantEvalForm')[0].reset();
        $('#hrIePolPositive').prop('checked', true);
        fillItems();
        if (typeof $().select2 === 'function') {
            $('#hrInstantEvalEmployeeSelect').val(null).trigger('change');
        }
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.getElementById('hrInstantEvalModal'));
        }
        state.modal.show();
    }

    $(function () {
        fillItems();
        loadData();

        $('#hrInstantEvalSearch').on('input', function () {
            clearTimeout(state.searchTimer);
            var val = $(this).val();
            state.searchTimer = setTimeout(function () {
                state.search = val;
                state.page = 1;
                loadData();
            }, 300);
        });

        $('#hrInstantEvalEmployeeFilter').on('change', function () {
            state.employeeId = $(this).val() || '';
            state.page = 1;
            loadData();
        });

        $('#hrInstantEvalPolarityFilter').on('change', function () {
            state.polarity = $(this).val() || '';
            state.page = 1;
            loadData();
        });

        $('#hrInstantEvalDateFrom').on('change', function () {
            state.dateFrom = $(this).val();
            state.page = 1;
            loadData();
        });

        $('#hrInstantEvalDateTo').on('change', function () {
            state.dateTo = $(this).val();
            state.page = 1;
            loadData();
        });

        $('#hrInstantEvalClearBtn').on('click', function () {
            state.search = '';
            state.employeeId = '';
            state.polarity = '';
            state.dateFrom = '';
            state.dateTo = '';
            state.page = 1;
            $('#hrInstantEvalSearch').val('');
            $('#hrInstantEvalEmployeeFilter').val('');
            $('#hrInstantEvalPolarityFilter').val('');
            $('#hrInstantEvalDateFrom').val('');
            $('#hrInstantEvalDateTo').val('');
            loadData();
        });

        $('#hrInstantEvalCreateBtn').on('click', openCreateModal);
        $('input[name="polarity"]').on('change', fillItems);

        $('#hrInstantEvalForm').on('submit', function (e) {
            e.preventDefault();
            if (state.isSubmitting) return;
            state.isSubmitting = true;
            $('#hrInstantEvalSubmitBtn').prop('disabled', true);

            $.ajax({
                url: cfg.urls.store,
                method: 'POST',
                data: {
                    _token: csrf(),
                    employee_id: $('#hrInstantEvalEmployeeSelect').val(),
                    polarity: currentPolarity(),
                    score: $('#hrInstantEvalScore').val(),
                    item_id: $('#hrInstantEvalItemSelect').val(),
                    note: $('#hrInstantEvalNote').val()
                },
                success: function (res) {
                    if (state.modal) state.modal.hide();
                    showAlert('success', res.message || '');
                    loadData();
                    setTimeout(function () { window.location.reload(); }, 600);
                },
                error: function (xhr) {
                    showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error');
                },
                complete: function () {
                    state.isSubmitting = false;
                    $('#hrInstantEvalSubmitBtn').prop('disabled', false);
                }
            });
        });

        $(document).on('click', '.hr-instant-eval-delete', function () {
            var id = $(this).data('id');
            if (!id || !window.confirm(cfg.lang.confirmDelete)) return;
            $.ajax({
                url: cfg.urls.destroy + '/' + id,
                method: 'DELETE',
                data: { _token: csrf() },
                success: function (res) {
                    showAlert('success', res.message || '');
                    loadData();
                },
                error: function (xhr) {
                    showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error');
                }
            });
        });
    });
})(jQuery);
