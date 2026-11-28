/**
 * HR Employee Evaluations CRUD
 * Config: window._hrEmployeeEvaluationsConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrEmployeeEvaluationsConfig;
    if (!cfg) {
        return;
    }

    var state = {
        search: '',
        employeeId: '',
        finalRating: '',
        dateFrom: '',
        dateTo: '',
        searchTimer: null,
        isSubmitting: false,
        editId: null,
        rows: [],
        employeeKpis: [],
        scores: {},
        notes: {},
        modal: null,
        viewModal: null
    };

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function showAlert(type, message) {
        var $box = $('#hrEvalAlert');
        $box.removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () { $box.addClass('d-none').removeClass('show'); }, 4000);
    }

    function setSubmitting(on) {
        state.isSubmitting = !!on;
        $('#hrEvalSubmitBtn').prop('disabled', !!on);
    }

    function clearErrors() {
        $('#hrEvalForm .is-invalid').removeClass('is-invalid');
        $('#hrEvalForm .invalid-feedback').text('');
    }

    function applyErrors(errors) {
        clearErrors();
        Object.keys(errors || {}).forEach(function (key) {
            if (key.indexOf('scores.') === 0 || key.indexOf('notes.') === 0) {
                var parts = key.split('.');
                var $input = $('#hrEvalForm [name="' + parts[0] + '[' + parts[1] + ']"]');
                $input.addClass('is-invalid');
                return;
            }
            var $el = $('#hrEvalForm [name="' + key + '"]');
            $el.addClass('is-invalid');
            $el.siblings('.invalid-feedback').text((errors[key] || [])[0] || '');
        });
    }

    function formatDate(iso) {
        return iso ? String(iso).substring(0, 10) : '-';
    }

    function today() {
        return new Date().toISOString().substring(0, 10);
    }

    function monthStart() {
        var d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-01';
    }

    function monthEnd() {
        var d = new Date();
        var last = new Date(d.getFullYear(), d.getMonth() + 1, 0);
        return last.toISOString().substring(0, 10);
    }

    function ratingLabel(score) {
        if (score >= 85) return cfg.ratings.excellent;
        if (score >= 75) return cfg.ratings.veryGood;
        if (score >= 60) return cfg.ratings.good;
        if (score >= 50) return cfg.ratings.acceptable;
        if (score >= 35) return cfg.ratings.weak;
        if (score >= 20) return cfg.ratings.veryWeak;
        return cfg.ratings.unacceptable;
    }

    function calcTotals() {
        var total = 0;
        state.employeeKpis.forEach(function (kpi) {
            var score = parseFloat(state.scores[kpi.id]);
            if (isNaN(score)) {
                return;
            }
            total += (score / 100) * (parseFloat(kpi.weight_percentage) || 0);
        });
        total = Math.min(Math.round(total * 100) / 100, 100);
        $('#hrEvalTotalScore').text(total);
        $('#hrEvalFinalRating').text(ratingLabel(total));
        return total;
    }

    function renderKpiRows() {
        var $tbody = $('#hrEvalKpiRows');
        $tbody.empty();

        if (!state.employeeKpis.length) {
            $tbody.append('<tr><td colspan="7" class="text-center text-muted py-3">' + escapeHtml(cfg.trans.noKpis) + '</td></tr>');
            calcTotals();
            return;
        }

        state.employeeKpis.forEach(function (kpi, idx) {
            var score = state.scores[kpi.id] != null ? state.scores[kpi.id] : '';
            var note = state.notes[kpi.id] != null ? state.notes[kpi.id] : '';
            var contribution = '-';
            if (score !== '' && !isNaN(parseFloat(score))) {
                contribution = ((parseFloat(score) / 100) * (parseFloat(kpi.weight_percentage) || 0)).toFixed(2);
            }

            $tbody.append(
                '<tr data-kpi-id="' + kpi.id + '">' +
                '<td>' + (idx + 1) + '</td>' +
                '<td class="font-hold">' + escapeHtml(kpi.name) + '</td>' +
                '<td class="font-hold">' + escapeHtml(kpi.description || '-') + '</td>' +
                '<td><span class="badge bg-info">' + escapeHtml(String(kpi.weight_percentage)) + '%</span></td>' +
                '<td><input type="text" inputmode="decimal" class="form-control hr-eval-score" name="scores[' + kpi.id + ']" data-kpi-id="' + kpi.id + '" value="' + escapeHtml(String(score)) + '"></td>' +
                '<td class="hr-eval-contribution">' + escapeHtml(contribution) + '</td>' +
                '<td><input type="text" class="form-control hr-eval-note" name="notes[' + kpi.id + ']" data-kpi-id="' + kpi.id + '" value="' + escapeHtml(String(note)) + '"></td>' +
                '</tr>'
            );
        });

        calcTotals();
    }

    function loadEmployeeKpis(employeeId, keepScores) {
        if (!employeeId) {
            state.employeeKpis = [];
            if (!keepScores) {
                state.scores = {};
                state.notes = {};
            }
            renderKpiRows();
            return;
        }

        $.getJSON(cfg.routes.employeeKpis.replace('__ID__', employeeId), function (res) {
            state.employeeKpis = res.data || [];
            if (!keepScores) {
                state.scores = {};
                state.notes = {};
            }
            if (res.employee) {
                $('#hrEvalJobTitle').val(res.employee.job_title || '');
                $('#hrEvalDepartment').val(res.employee.department || '');
            }
            renderKpiRows();
        });
    }

    function renderRows() {
        var $tbody = $('#hrEvalTableBody');
        $tbody.empty();
        var cols = (cfg.canView || cfg.canEdit || cfg.canDelete) ? 9 : 8;

        if (!state.rows.length) {
            $tbody.append('<tr><td colspan="' + cols + '" class="text-center py-4"><div class="alert alert-info mb-0">' + escapeHtml(cfg.trans.empty) + '</div></td></tr>');
            return;
        }

        state.rows.forEach(function (row, idx) {
            var actions = '';
            if (cfg.canView || cfg.canEdit || cfg.canDelete) {
                actions = '<td class="text-center"><div class="d-flex justify-content-center gap-1">';
                if (cfg.canView) {
                    actions += '<button type="button" class="btn btn-info btn-sm hr-eval-view" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.view) + '"><i class="las la-eye"></i></button>';
                }
                if (cfg.canEdit) {
                    actions += '<button type="button" class="btn btn-success btn-sm hr-eval-edit" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.edit) + '"><i class="las la-edit"></i></button>';
                }
                if (cfg.canDelete) {
                    actions += '<button type="button" class="btn btn-danger btn-sm hr-eval-delete" data-id="' + row.id + '" title="' + escapeHtml(cfg.trans.delete) + '"><i class="las la-trash"></i></button>';
                }
                actions += '</div></td>';
            }

            $tbody.append(
                '<tr>' +
                '<td class="font-hold fw-bold">' + (idx + 1) + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.employee ? row.employee.name : '-') + '</td>' +
                '<td class="font-hold d-none d-md-table-cell">' + escapeHtml(row.employee && row.employee.job ? row.employee.job.title : '-') + '</td>' +
                '<td class="font-hold d-none d-md-table-cell">' + escapeHtml(row.employee && row.employee.department ? row.employee.department.title : '-') + '</td>' +
                '<td class="font-hold">' + escapeHtml(formatDate(row.evaluation_date)) + '</td>' +
                '<td class="font-hold d-none d-lg-table-cell">' + escapeHtml(row.direct_manager || '-') + '</td>' +
                '<td class="font-hold">' + escapeHtml(String(row.total_score != null ? row.total_score : '-')) + '</td>' +
                '<td class="font-hold">' + escapeHtml(row.final_rating || '-') + '</td>' +
                actions +
                '</tr>'
            );
        });
    }

    function loadList() {
        $.getJSON(cfg.routes.data, {
            search:       state.search,
            employee_id:  state.employeeId,
            final_rating: state.finalRating,
            date_from:    state.dateFrom,
            date_to:      state.dateTo
        }, function (res) {
            state.rows = res.data || [];
            renderRows();
        });
    }

    function resetFormDefaults() {
        clearErrors();
        state.scores = {};
        state.notes = {};
        state.employeeKpis = [];
        $('#hrEvalForm')[0].reset();
        $('#hrEvalDate').val(today());
        $('#hrEvalPeriodFrom').val(monthStart());
        $('#hrEvalPeriodTo').val(monthEnd());
        $('#hrEvalJobTitle').val('');
        $('#hrEvalDepartment').val('');
        renderKpiRows();
    }

    function openCreate() {
        state.editId = null;
        resetFormDefaults();
        $('#hrEvalModalTitle').text(cfg.trans.add);
        $('#hrEvalSubmitBtn').text(cfg.trans.save);
        $('#hrEvalEmployeeSelect').prop('disabled', false);
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.getElementById('hrEvalModal'));
        }
        state.modal.show();
    }

    function openEdit(id) {
        $.getJSON(cfg.routes.show.replace('__ID__', id), function (res) {
            var row = res.data || {};
            state.editId = id;
            clearErrors();
            state.scores = {};
            state.notes = {};

            $('#hrEvalEmployeeSelect').val(row.employee_id).prop('disabled', false);
            $('#hrEvalDate').val(formatDate(row.evaluation_date));
            $('#hrEvalForm [name="direct_manager"]').val(row.direct_manager || '');
            $('#hrEvalPeriodFrom').val(formatDate(row.evaluation_period_from));
            $('#hrEvalPeriodTo').val(formatDate(row.evaluation_period_to));
            $('#hrEvalJobTitle').val(row.employee && row.employee.job ? row.employee.job.title : '');
            $('#hrEvalDepartment').val(row.employee && row.employee.department ? row.employee.department.title : '');

            (row.kpis || []).forEach(function (kpi) {
                state.scores[kpi.id] = kpi.pivot ? kpi.pivot.score : 0;
                state.notes[kpi.id] = kpi.pivot && kpi.pivot.notes ? kpi.pivot.notes : '';
            });

            $('#hrEvalModalTitle').text(cfg.trans.editTitle);
            $('#hrEvalSubmitBtn').text(cfg.trans.update);
            if (!state.modal) {
                state.modal = new bootstrap.Modal(document.getElementById('hrEvalModal'));
            }
            state.modal.show();
            loadEmployeeKpis(row.employee_id, true);
        });
    }

    function save() {
        if (state.isSubmitting) {
            return;
        }
        setSubmitting(true);
        clearErrors();

        var scores = {};
        var notes = {};
        state.employeeKpis.forEach(function (kpi) {
            scores[kpi.id] = state.scores[kpi.id] != null ? state.scores[kpi.id] : '';
            notes[kpi.id] = state.notes[kpi.id] != null ? state.notes[kpi.id] : '';
        });

        var payload = {
            employee_id: $('#hrEvalEmployeeSelect').val(),
            evaluation_date: $('#hrEvalDate').val(),
            direct_manager: $('#hrEvalForm [name="direct_manager"]').val(),
            evaluation_period_from: $('#hrEvalPeriodFrom').val(),
            evaluation_period_to: $('#hrEvalPeriodTo').val(),
            scores: scores,
            notes: notes
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

    function openView(id) {
        $('#hrEvalViewBody').html('<div class="text-center py-4">...</div>');
        if (!state.viewModal) {
            state.viewModal = new bootstrap.Modal(document.getElementById('hrEvalViewModal'));
        }
        state.viewModal.show();

        $.getJSON(cfg.routes.show.replace('__ID__', id), function (res) {
            var row = res.data || {};
            var emp = row.employee || {};
            var weightMap = {};
            (emp.kpis || []).forEach(function (k) {
                weightMap[k.id] = k.pivot ? parseFloat(k.pivot.weight_percentage) : 0;
            });

            var kpiRows = '';
            (row.kpis || []).forEach(function (kpi, idx) {
                var weight = weightMap[kpi.id] != null ? weightMap[kpi.id] : 0;
                var score = kpi.pivot ? parseFloat(kpi.pivot.score || 0) : 0;
                var contribution = ((score / 100) * weight).toFixed(2);
                kpiRows +=
                    '<tr>' +
                    '<td>' + (idx + 1) + '</td>' +
                    '<td>' + escapeHtml(kpi.name) + '</td>' +
                    '<td>' + escapeHtml(kpi.description || '-') + '</td>' +
                    '<td><span class="badge bg-info">' + weight + '%</span></td>' +
                    '<td><span class="badge bg-primary">' + score + '</span></td>' +
                    '<td><span class="badge bg-success">' + contribution + '</span></td>' +
                    '<td>' + escapeHtml((kpi.pivot && kpi.pivot.notes) ? kpi.pivot.notes : cfg.trans.noNotes) + '</td>' +
                    '</tr>';
            });

            $('#hrEvalViewBody').html(
                '<h6 class="text-primary mb-3">' + escapeHtml(cfg.trans.employeeInfo) + '</h6>' +
                '<div class="row g-2 mb-4">' +
                '<div class="col-md-4"><div class="p-3 bg-light rounded"><div class="text-muted small">' + escapeHtml(cfg.trans.name) + '</div><strong>' + escapeHtml(emp.name || '-') + '</strong></div></div>' +
                '<div class="col-md-4"><div class="p-3 bg-light rounded"><div class="text-muted small">' + escapeHtml(cfg.trans.jobTitle) + '</div><strong>' + escapeHtml(emp.job ? emp.job.title : cfg.trans.notSpecified) + '</strong></div></div>' +
                '<div class="col-md-4"><div class="p-3 bg-light rounded"><div class="text-muted small">' + escapeHtml(cfg.trans.department) + '</div><strong>' + escapeHtml(emp.department ? emp.department.title : cfg.trans.notSpecified) + '</strong></div></div>' +
                '</div>' +
                '<h6 class="text-primary mb-3">' + escapeHtml(cfg.trans.evaluationInfo) + '</h6>' +
                '<div class="row g-2 mb-4">' +
                '<div class="col-md-3"><div class="p-3 bg-light rounded"><div class="text-muted small">' + escapeHtml(cfg.trans.evaluationDate) + '</div><strong>' + escapeHtml(formatDate(row.evaluation_date)) + '</strong></div></div>' +
                '<div class="col-md-3"><div class="p-3 bg-light rounded"><div class="text-muted small">' + escapeHtml(cfg.trans.directManager) + '</div><strong>' + escapeHtml(row.direct_manager || cfg.trans.notSpecified) + '</strong></div></div>' +
                '<div class="col-md-6"><div class="p-3 bg-light rounded"><div class="text-muted small">' + escapeHtml(cfg.trans.evaluationPeriod) + '</div><strong>' + escapeHtml(formatDate(row.evaluation_period_from)) + ' - ' + escapeHtml(formatDate(row.evaluation_period_to)) + '</strong></div></div>' +
                '</div>' +
                '<h6 class="text-primary mb-3">' + escapeHtml(cfg.trans.evaluationResults) + '</h6>' +
                '<div class="table-responsive"><table class="table table-bordered text-center"><thead><tr>' +
                '<th>#</th><th>' + escapeHtml(cfg.trans.criterion) + '</th><th>' + escapeHtml(cfg.trans.description) + '</th><th>' + escapeHtml(cfg.trans.weight) + '</th><th>' + escapeHtml(cfg.trans.score) + '</th><th>' + escapeHtml(cfg.trans.contribution) + '</th><th>' + escapeHtml(cfg.trans.notes) + '</th>' +
                '</tr></thead><tbody>' + kpiRows + '</tbody><tfoot><tr class="table-success">' +
                '<td colspan="4" class="text-end"><strong>' + escapeHtml(cfg.trans.totalScore) + '</strong></td>' +
                '<td><strong>' + escapeHtml(String(row.total_score != null ? row.total_score : '-')) + '</strong></td>' +
                '<td><span class="badge bg-success">' + escapeHtml(row.final_rating || '-') + '</span></td><td></td>' +
                '</tr></tfoot></table></div>'
            );
        });
    }

    $(function () {
        loadList();

        $('#hrEvalSearch').on('input', function () {
            var v = $(this).val();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () {
                state.search = v;
                loadList();
            }, 300);
        });

        $('#hrEvalEmployeeFilter').on('change', function () {
            state.employeeId = $(this).val() || '';
            loadList();
        });
        $('#hrEvalRatingFilter').on('change', function () {
            state.finalRating = $(this).val() || '';
            loadList();
        });
        $('#hrEvalDateFrom').on('change', function () { state.dateFrom = $(this).val(); loadList(); });
        $('#hrEvalDateTo').on('change',   function () { state.dateTo   = $(this).val(); loadList(); });

        $('#hrEvalClearBtn').on('click', function () {
            state.search = '';
            state.employeeId = '';
            state.finalRating = '';
            state.dateFrom = '';
            state.dateTo = '';
            $('#hrEvalSearch').val('');
            $('#hrEvalEmployeeFilter').val('');
            $('#hrEvalRatingFilter').val('');
            $('#hrEvalDateFrom').val('');
            $('#hrEvalDateTo').val('');
            loadList();
        });

        $('#hrEvalCreateBtn').on('click', openCreate);
        $(document).on('click', '.hr-eval-edit', function () { openEdit($(this).data('id')); });
        $(document).on('click', '.hr-eval-delete', function () { destroy($(this).data('id')); });
        $(document).on('click', '.hr-eval-view', function () { openView($(this).data('id')); });

        $('#hrEvalEmployeeSelect').on('change', function () {
            var $opt = $(this).find(':selected');
            $('#hrEvalJobTitle').val($opt.data('job') || '');
            $('#hrEvalDepartment').val($opt.data('department') || '');
            loadEmployeeKpis($(this).val(), false);
        });

        $(document).on('input', '.hr-eval-score', function () {
            var kpiId = $(this).data('kpi-id');
            state.scores[kpiId] = $(this).val();
            var kpi = state.employeeKpis.find(function (k) { return String(k.id) === String(kpiId); });
            var score = parseFloat($(this).val());
            var contribution = '-';
            if (kpi && !isNaN(score)) {
                contribution = ((score / 100) * (parseFloat(kpi.weight_percentage) || 0)).toFixed(2);
            }
            $(this).closest('tr').find('.hr-eval-contribution').text(contribution);
            calcTotals();
        });

        $(document).on('input', '.hr-eval-note', function () {
            state.notes[$(this).data('kpi-id')] = $(this).val();
        });

        $('#hrEvalForm').on('submit', function (e) {
            e.preventDefault();
            save();
        });
    });
})(jQuery);
