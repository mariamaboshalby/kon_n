/**
 * HR Employees index — lazy list + delete (jQuery/Ajax).
 * Config: window._hrEmployeesIndexConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrEmployeesIndexConfig;
    if (!cfg || !$) {
        return;
    }

    var state = {
        search: '',
        jobId: (cfg.initialFilters && cfg.initialFilters.job_id) || '',
        dateFrom: (cfg.initialFilters && cfg.initialFilters.date_from) || '',
        dateTo: (cfg.initialFilters && cfg.initialFilters.date_to) || '',
        page: 1,
        searchTimer: null,
        dateTimer: null,
        deleting: false
    };

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function urlFor(template, id) {
        return String(template).replace('__ID__', id);
    }

    function activeFiltersCount() {
        var count = 0;
        if (state.jobId) { count++; }
        if (state.dateFrom) { count++; }
        if (state.dateTo) { count++; }
        return count;
    }

    function syncFiltersToUrl() {
        if (!window.history || !window.history.replaceState) {
            return;
        }
        var params = new URLSearchParams(window.location.search);
        [['job_id', state.jobId], ['date_from', state.dateFrom], ['date_to', state.dateTo], ['page', state.page > 1 ? state.page : '']].forEach(function (pair) {
            if (pair[1]) {
                params.set(pair[0], pair[1]);
            } else {
                params.delete(pair[0]);
            }
        });
        var query = params.toString();
        window.history.replaceState(null, '', window.location.pathname + (query ? '?' + query : ''));
    }

    function updateFiltersUI() {
        var count = activeFiltersCount();
        var $badge = $('#hrEmployeesFiltersCount');
        if (count > 0) {
            $badge.removeClass('d-none').text(count);
            $('#hrEmployeesClearFilters').removeClass('d-none');
        } else {
            $badge.addClass('d-none').empty();
            $('#hrEmployeesClearFilters').addClass('d-none');
        }
        syncFiltersToUrl();
    }

    function showAlert(type, message) {
        var $box = $('#hrEmployeesAlert');
        if (!$box.length) {
            return;
        }
        $box
            .removeClass('d-none alert-success alert-danger')
            .addClass(type === 'success' ? 'alert-success' : 'alert-danger')
            .addClass('alert alert-dismissible fade show')
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        setTimeout(function () {
            $box.addClass('d-none').removeClass('show');
        }, 4000);
    }

    function actionsHtml(row) {
        var html = '<div class="d-flex justify-content-center gap-1">';
        if (cfg.canView) {
            html += '<a href="' + escapeHtml(urlFor(cfg.showUrlTemplate, row.id)) + '" class="btn btn-info btn-sm" title="' + escapeHtml(cfg.lang.view) + '"><i class="las la-eye fa-lg"></i></a>';
        }
        if (cfg.canEdit) {
            html += '<a href="' + escapeHtml(urlFor(cfg.editUrlTemplate, row.id)) + '" class="btn btn-success btn-sm" title="' + escapeHtml(cfg.lang.edit) + '"><i class="las la-edit fa-lg"></i></a>';
        }
        if (cfg.canDelete) {
            html += '<button type="button" class="btn btn-danger btn-sm js-hr-emp-delete" data-id="' + row.id + '" title="' + escapeHtml(cfg.lang.delete) + '"><i class="las la-trash fa-lg"></i></button>';
        }
        html += '</div>';
        return html;
    }

    function renderProjects(projects) {
        if (!projects || !projects.length) {
            return '<span class="text-muted">—</span>';
        }
        return projects.map(function (p) {
            var label = p.code ? escapeHtml(p.code) + ' — ' + escapeHtml(p.name) : escapeHtml(p.name);
            return '<span class="badge bg-secondary me-1 mb-1" style="font-size:11px;">' + label + '</span>';
        }).join('');
    }

    function renderRows(rows, meta) {
        var $tbody = $('#hrEmployeesTableBody');
        var $mobile = $('#hrEmployeesMobileList');
        var offset = ((meta.current_page || 1) - 1) * (meta.per_page || cfg.perPage || 20);
        var colSpan = (cfg.canView || cfg.canEdit || cfg.canDelete) ? 11 : 10;

        if (!rows.length) {
            $tbody.html('<tr><td colspan="' + colSpan + '" class="text-center py-4"><div class="alert alert-info mb-0">' + escapeHtml(cfg.lang.noEmployees) + '</div></td></tr>');
            $mobile.html('<div class="alert alert-info">' + escapeHtml(cfg.lang.noEmployees) + '</div>');
            $('#hrEmployeesPager').empty();
            return;
        }

        var tableHtml = '';
        var mobileHtml = '';
        rows.forEach(function (row, idx) {
            tableHtml += '<tr>' +
                '<td class="font-hold fw-bold">' + (offset + idx + 1) + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.name) + '</td>' +
                '<td class="font-hold d-none d-lg-table-cell">' + escapeHtml(row.nickname || '') + '</td>' +
                '<td class="font-hold fw-bold d-none d-lg-table-cell">' + escapeHtml(row.email) + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.phone) + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.department || '') + '</td>' +
                '<td class="font-hold fw-bold d-none d-lg-table-cell">' + escapeHtml(row.job || '') + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.code || '—') + '</td>' +
                '<td class="font-hold d-none d-xl-table-cell">' + renderProjects(row.projects) + '</td>' +
                '<td class="font-hold fw-bold">' + escapeHtml(row.status || '') + '</td>';
            if (cfg.canView || cfg.canEdit || cfg.canDelete) {
                tableHtml += '<td>' + actionsHtml(row) + '</td>';
            }
            tableHtml += '</tr>';

            mobileHtml += '<div class="card mb-3 border shadow-sm"><div class="card-body p-3">' +
                '<div class="d-flex justify-content-between mb-2"><div><h6 class="mb-1 font-hold fw-bold">' + escapeHtml(row.name) + '</h6>' +
                (row.nickname ? '<small class="text-muted d-block">' + escapeHtml(row.nickname) + '</small>' : '') +
                '<small class="text-muted">' + escapeHtml(row.department || '') + '</small></div>' +
                '<span class="badge bg-secondary">' + escapeHtml(row.status || '') + '</span></div>' +
                '<div class="mb-2"><small class="text-muted d-block">' + escapeHtml(row.phone || '') + '</small>' +
                '<small class="text-muted d-block">' + escapeHtml(row.email || '') + '</small></div>' +
                actionsHtml(row) +
                '</div></div>';
        });

        $tbody.html(tableHtml);
        $mobile.html(mobileHtml);
        renderPager(meta);
    }

    function renderPager(meta) {
        var $pager = $('#hrEmployeesPager');
        if (!meta || meta.last_page <= 1) {
            $pager.empty();
            return;
        }
        var html = '<nav><ul class="pagination mb-0">';
        for (var i = 1; i <= meta.last_page; i++) {
            html += '<li class="page-item ' + (i === meta.current_page ? 'active' : '') + '">' +
                '<a href="#" class="page-link js-hr-emp-page" data-page="' + i + '">' + i + '</a></li>';
        }
        html += '</ul></nav>';
        $pager.html(html);
    }

    function loadList() {
        $('#hrEmployeesTableBody').html(
            '<tr><td colspan="11" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>' +
            escapeHtml(cfg.lang.loading) + '</td></tr>'
        );

        $.ajax({
            url: cfg.dataUrl,
            method: 'GET',
            data: {
                search: state.search,
                job_id: state.jobId,
                date_from: state.dateFrom,
                date_to: state.dateTo,
                page: state.page,
                per_page: cfg.perPage || 20
            },
            success: function (res) {
                renderRows(res.data || [], res.meta || {});
            },
            error: function () {
                showAlert('error', cfg.lang.error);
            }
        });
    }

    function destroyEmployee(id) {
        if (state.deleting) {
            return;
        }
        if (!window.confirm(cfg.lang.confirmDelete)) {
            return;
        }
        state.deleting = true;
        $.ajax({
            url: urlFor(cfg.destroyUrlTemplate, id),
            method: 'POST',
            data: {
                _token: cfg.csrfToken,
                _method: 'DELETE'
            },
            success: function (res) {
                showAlert('success', (res && res.message) || 'OK');
                loadList();
            },
            error: function (xhr) {
                showAlert('error', (xhr.responseJSON && xhr.responseJSON.message) || cfg.lang.error);
            },
            complete: function () {
                state.deleting = false;
            }
        });
    }

    $(function () {
        // Restore filter controls from initial state (deep links / page reloads)
        $('#hrEmployeesJobFilter').val(state.jobId);
        $('#hrEmployeesDateFrom').val(state.dateFrom);
        $('#hrEmployeesDateTo').val(state.dateTo);
        updateFiltersUI();

        loadList();

        $('#hrEmployeesSearch').on('input', function () {
            var val = $(this).val();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () {
                state.search = val;
                state.page = 1;
                loadList();
            }, 400);
        });

        $('#hrEmployeesJobFilter').on('change', function () {
            state.jobId = $(this).val() || '';
            state.page = 1;
            updateFiltersUI();
            loadList();
        });

        $('#hrEmployeesDateFrom, #hrEmployeesDateTo').on('change', function () {
            var from = $('#hrEmployeesDateFrom').val() || '';
            var to = $('#hrEmployeesDateTo').val() || '';
            // Swap if the range is inverted so results stay sensible
            if (from && to && from > to) {
                $('#hrEmployeesDateFrom').val(to);
                $('#hrEmployeesDateTo').val(from);
                from = $('#hrEmployeesDateFrom').val();
                to = $('#hrEmployeesDateTo').val();
            }
            clearTimeout(state.dateTimer);
            state.dateTimer = setTimeout(function () {
                state.dateFrom = from;
                state.dateTo = to;
                state.page = 1;
                updateFiltersUI();
                loadList();
            }, 300);
        });

        $('#hrEmployeesClearFilters').on('click', function () {
            state.jobId = '';
            state.dateFrom = '';
            state.dateTo = '';
            state.search = '';
            state.page = 1;
            $('#hrEmployeesJobFilter').val('');
            $('#hrEmployeesDateFrom, #hrEmployeesDateTo').val('');
            $('#hrEmployeesSearch').val('');
            updateFiltersUI();
            loadList();
        });

        $(document).on('click', '.js-hr-emp-page', function (e) {
            e.preventDefault();
            state.page = parseInt($(this).data('page'), 10) || 1;
            loadList();
        });

        $(document).on('click', '.js-hr-emp-delete', function () {
            destroyEmployee($(this).data('id'));
        });
    });
})(window.jQuery);
