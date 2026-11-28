/**
 * HR Attendance CRUD + lazy loading + bulk actions
 * Config: window._hrAttendanceConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrAttendanceConfig;
    if (!cfg) {
        return;
    }

    var selectedIds = {};
    var state = {
        isSubmitting: false,
        isBulkSubmitting: false,
        editId: null,
        pendingEmployeeId: null,
        rows: [],
        modal: null,
        timer: null,
        page: 0,
        lastPage: 1,
        hasMore: false,
        loading: false,
        scrollObserver: null
    };

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }
    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }
    function showAlert(type, message) {
        var alertClass = type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'danger');
        $('#hrAttendanceAlert').removeClass('d-none alert-success alert-danger alert-warning')
            .addClass('alert alert-dismissible fade show alert-' + alertClass)
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
    }
    function setSubmitting(on) {
        state.isSubmitting = !!on;
        $('#hrAttSubmitBtn').prop('disabled', !!on);
    }
    var availableColumns = [
        { key: 'id', label: 'رقم السجل (#)', defaultVisible: true },
        { key: 'employee', label: 'اسم الموظف', defaultVisible: true },
        { key: 'employee_code', label: 'كود الموظف', defaultVisible: true },
        { key: 'finger_print_id', label: 'رقم الموظف', defaultVisible: true },
        { key: 'finger_print_name', label: 'اسم البصمة', defaultVisible: true },
        { key: 'type', label: 'النوع (حضور/انصراف)', defaultVisible: true },
        { key: 'date', label: 'التاريخ', defaultVisible: true },
        { key: 'time', label: 'الوقت', defaultVisible: true },
        { key: 'location', label: 'الموقع الجغرافي', defaultVisible: true },
        { key: 'project_code', label: 'كود المشروع', defaultVisible: true },
        { key: 'device_type', label: 'نوع الجهاز', defaultVisible: true },
        { key: 'status', label: 'الحالة', defaultVisible: true },
        { key: 'notes', label: 'ملاحظات', defaultVisible: true }
    ];

    function getColumnVisibilityMap() {
        try {
            var saved = localStorage.getItem('hr_attendance_columns_visibility');
            if (saved) return JSON.parse(saved);
        } catch (e) {}
        var map = {};
        availableColumns.forEach(function (col) {
            map[col.key] = col.defaultVisible;
        });
        return map;
    }
    function isColVisible(key) {
        var map = getColumnVisibilityMap();
        return map[key] !== false;
    }
    function setColVisible(key, visible) {
        var map = getColumnVisibilityMap();
        map[key] = !!visible;
        try {
            localStorage.setItem('hr_attendance_columns_visibility', JSON.stringify(map));
        } catch (e) {}
        applyColumnVisibility();
    }
    function resetColumnsVisibility() {
        try {
            localStorage.removeItem('hr_attendance_columns_visibility');
        } catch (e) {}
        applyColumnVisibility();
        buildColsModalList();
    }
    function applyColumnVisibility() {
        availableColumns.forEach(function (col) {
            var visible = isColVisible(col.key);
            $('[data-col="' + col.key + '"]').toggleClass('d-none', !visible);
        });
    }
    function colCount() {
        var cols = 0;
        availableColumns.forEach(function (col) {
            if (isColVisible(col.key)) cols++;
        });
        if (cfg.canBulk) cols += 1;
        if (cfg.canEdit || cfg.canDelete) cols += 1;
        return cols;
    }
    function statusBadge(status) {
        if (status === 'approved') return '<span class="badge bg-success" style="font-size: 11px;">' + escapeHtml(cfg.trans.approved) + '</span>';
        if (status === 'rejected') return '<span class="badge bg-danger" style="font-size: 11px;">' + escapeHtml(cfg.trans.rejected) + '</span>';
        if (status === 'pending') return '<span class="badge bg-warning text-dark" style="font-size: 11px;">' + escapeHtml(cfg.trans.pending) + '</span>';
        return '<span class="badge bg-secondary" style="font-size: 11px;">' + escapeHtml(status || '-') + '</span>';
    }
    function updateLazyFooter() {
        var $hint = $('#hrAttLoadHint');
        var $all = $('#hrAttAllLoaded');
        if (!$hint.length) {
            return;
        }
        if (state.loading) {
            $hint.addClass('d-none');
            $all.addClass('d-none');
            return;
        }
        if (state.hasMore) {
            $hint.removeClass('d-none');
            $all.addClass('d-none');
        } else if (state.rows.length > 0) {
            $hint.addClass('d-none');
            $all.removeClass('d-none');
        } else {
            $hint.addClass('d-none');
            $all.addClass('d-none');
        }
    }
    function setLoading(on, append) {
        state.loading = !!on;
        var $tb = $('#hrAttTableBody');
        if (on && !append) {
            $tb.html('<tr><td colspan="' + colCount() + '" class="text-center py-4">' +
                '<span class="spinner-border spinner-border-sm text-secondary" role="status"></span></td></tr>');
        }
        updateLazyFooter();
    }

    function getSelectedIds() {
        return Object.keys(selectedIds).map(function (id) {
            return parseInt(id, 10);
        }).filter(function (id) {
            return !isNaN(id);
        });
    }
    function setRowSelected(id, checked) {
        var key = String(id);
        if (checked) {
            selectedIds[key] = true;
        } else {
            delete selectedIds[key];
        }
        updateBulkBar();
    }
    function syncSelectAllPageState() {
        if (!cfg.canBulk) return;
        var $checkboxes = $('#hrAttTableBody .hr-att-row-checkbox');
        var $selectAll = $('#hrAttSelectAllPage');
        if (!$checkboxes.length) {
            $selectAll.prop('checked', false).prop('indeterminate', false);
            return;
        }
        var checkedCount = $checkboxes.filter(':checked').length;
        $selectAll.prop('checked', checkedCount === $checkboxes.length);
        $selectAll.prop('indeterminate', checkedCount > 0 && checkedCount < $checkboxes.length);
    }
    function updateBulkBar() {
        if (!cfg.canBulk) return;
        var count = getSelectedIds().length;
        var $bar = $('#hrAttBulkBar');
        if (count === 0) {
            $bar.addClass('d-none');
            $('#hrAttBulkCountLabel').text('');
            syncSelectAllPageState();
            return;
        }
        $bar.removeClass('d-none');
        $('#hrAttBulkCountLabel').text(
            (cfg.trans.bulkSelectedCount || ':count').replace(':count', count)
        );
        syncSelectAllPageState();
    }
    function restoreRowCheckboxes() {
        if (!cfg.canBulk) return;
        $('#hrAttTableBody .hr-att-row-checkbox').each(function () {
            var id = String($(this).val());
            $(this).prop('checked', !!selectedIds[id]);
        });
        syncSelectAllPageState();
    }
    function clearSelection() {
        selectedIds = {};
        $('.hr-att-row-checkbox, #hrAttSelectAllPage').prop('checked', false);
        $('#hrAttSelectAllPage').prop('indeterminate', false);
        $('#hrAttBulkAction').val('');
        updateBulkBar();
    }
    function bulkConfirmMessage(action, count) {
        var key = action === 'approve'
            ? 'confirmBulkApprove'
            : (action === 'reject' ? 'confirmBulkReject' : 'confirmBulkDelete');
        return (cfg.trans[key] || '').replace(':count', count);
    }

    // كاش لنتائج Reverse Geocoding عشان ما نعمل طلبات متكررة لنفس الموقع
    var _geocodeCache = {};

    function locationCell(row) {
        var loc = row.location;

        if (!loc || (!loc.address && !loc.latitude && !loc.longitude)) {
            return '<span class="text-muted" style="font-size:12px;">—</span>';
        }

        // بناء رابط Google Maps
        var mapsUrl = loc.maps_url || null;
        if (!mapsUrl && loc.latitude && loc.longitude) {
            mapsUrl = 'https://www.google.com/maps?q=' + loc.latitude + ',' + loc.longitude;
        }

        // لو في عنوان نصي → اعرضه مباشرة
        if (loc.address) {
            if (mapsUrl) {
                return '<a href="' + escapeHtml(mapsUrl) + '" target="_blank" ' +
                    'class="text-dark text-decoration-none" style="font-size:12px; line-height:1.4; display:inline-block;" ' +
                    'title="عرض على الخريطة">' +
                    '<i class="fas fa-map-marker-alt text-danger me-1" style="font-size:11px;"></i>' +
                    escapeHtml(loc.address) +
                    '</a>';
            }
            return '<span class="text-dark" style="font-size:12px; line-height:1.4;">' +
                '<i class="fas fa-map-marker-alt text-danger me-1" style="font-size:11px;"></i>' +
                escapeHtml(loc.address) + '</span>';
        }

        // لو في إحداثيات بس من غير عنوان → نجيب العنوان من Nominatim
        if (loc.latitude && loc.longitude) {
            var cacheKey = loc.latitude.toFixed(5) + ',' + loc.longitude.toFixed(5);
            var cellId   = 'geo-' + row.id;
            var linkOpen = mapsUrl
                ? '<a href="' + escapeHtml(mapsUrl) + '" target="_blank" class="text-dark text-decoration-none" style="font-size:12px; line-height:1.4;">'
                : '<span style="font-size:12px;">';
            var linkClose = mapsUrl ? '</a>' : '</span>';
            var icon = '<i class="fas fa-map-marker-alt text-danger me-1" style="font-size:11px;"></i>';

            // لو موجود في الكاش اعرضه مباشرة
            if (_geocodeCache[cacheKey]) {
                return linkOpen + icon + escapeHtml(_geocodeCache[cacheKey]) + linkClose;
            }

            // اعرض spinner مؤقت وجيب العنوان
            setTimeout(function () {
                var cell = document.getElementById(cellId);
                if (!cell) return;
                if (_geocodeCache[cacheKey]) {
                    cell.innerHTML = linkOpen + icon + escapeHtml(_geocodeCache[cacheKey]) + linkClose;
                    return;
                }
                var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' +
                    loc.latitude + '&lon=' + loc.longitude + '&accept-language=ar';
                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var addr = data.display_name || (loc.latitude.toFixed(5) + ', ' + loc.longitude.toFixed(5));
                        _geocodeCache[cacheKey] = addr;
                        if (document.getElementById(cellId)) {
                            document.getElementById(cellId).innerHTML = linkOpen + icon + escapeHtml(addr) + linkClose;
                        }
                    })
                    .catch(function () {
                        _geocodeCache[cacheKey] = loc.latitude.toFixed(5) + ', ' + loc.longitude.toFixed(5);
                        if (document.getElementById(cellId)) {
                            document.getElementById(cellId).innerHTML = linkOpen + icon + escapeHtml(_geocodeCache[cacheKey]) + linkClose;
                        }
                    });
            }, 0);

            return '<span id="' + cellId + '" style="font-size:12px; color:#aaa;">' +
                '<i class="fas fa-spinner fa-spin me-1" style="font-size:11px;"></i>جاري التحميل...</span>';
        }

        return '<span class="text-muted" style="font-size:12px;">—</span>';
    }

    function renderRow(row, idx) {
        var checkbox = '';
        if (cfg.canBulk) {
            checkbox = '<td><input type="checkbox" class="form-check-input hr-att-row-checkbox" value="' +
                row.id + '"' + (selectedIds[String(row.id)] ? ' checked' : '') + '></td>';
        }
        var actions = '';
        if (cfg.canEdit || cfg.canDelete) {
            actions = '<td><div class="btn-group">';
            if (cfg.canEdit) {
                actions += '<button type="button" class="btn btn-info btn-sm hr-att-edit" data-id="' + row.id + '"><i class="las la-edit"></i></button>';
            }
            if (cfg.canDelete) {
                actions += '<button type="button" class="btn btn-danger btn-sm hr-att-delete" data-id="' + row.id + '"><i class="las la-trash"></i></button>';
            }
            actions += '</div></td>';
        }
        return '<tr>' + checkbox +
            '<td data-col="id">' + row.id + '</td>' +
            '<td data-col="employee" class="fw-bold">' + escapeHtml((row.employee && row.employee.name) || '-') + '</td>' +
            '<td data-col="employee_code">' + escapeHtml((row.employee && row.employee.code) || '-') + '</td>' +
            '<td data-col="finger_print_id">' + escapeHtml(row.finger_print_id || '-') + '</td>' +
            '<td data-col="finger_print_name">' + escapeHtml(row.finger_print_name || '-') + '</td>' +
            '<td data-col="type">' + escapeHtml(row.type === 'check_out' ? cfg.trans.checkOut : cfg.trans.checkIn) + '</td>' +
            '<td data-col="date">' + escapeHtml((row.date || '').substring(0, 10)) + '</td>' +
            '<td data-col="time">' + escapeHtml(row.time || '-') + '</td>' +
            '<td data-col="location" style="min-width:200px; max-width:320px; text-align:center; vertical-align:middle;">' + locationCell(row) + '</td>' +
            '<td data-col="project_code">' + escapeHtml(row.project_code || '-') + '</td>' +
            '<td data-col="device_type">' + (row.device_type ? '<span class="badge bg-light text-dark border">' + escapeHtml(row.device_type) + '</span>' : '-') + '</td>' +
            '<td data-col="status">' + statusBadge(row.status) + '</td>' +
            '<td data-col="notes" style="max-width:200px;" class="small text-muted" title="' + escapeHtml(row.notes || '') + '">' + escapeHtml(row.notes || '-') + '</td>' +
            actions + '</tr>';
    }
    function appendRows(rows, startIndex) {
        var $tb = $('#hrAttTableBody');
        rows.forEach(function (row, i) {
            $tb.append(renderRow(row, startIndex + i));
        });
        restoreRowCheckboxes();
        applyColumnVisibility();
    }

    function loadList(options) {
        options = options || {};
        var append = !!options.append;

        if (state.loading) {
            return;
        }
        if (append && !state.hasMore) {
            return;
        }

        var nextPage = append ? (state.page + 1) : 1;
        setLoading(true, append);

        $.get(cfg.routes.data, {
            search_employee_id: $('#hrAttSearchEmployee').val() || '',
            search_fingerprint_name: $('#hrAttSearchFingerprintName').val() || '',
            date_from: $('#hrAttDateFrom').val(),
            date_to: $('#hrAttDateTo').val(),
            page: nextPage,
            per_page: cfg.perPage || 50
        })
            .done(function (res) {
                var rows = res.data || [];
                state.page = res.current_page || nextPage;
                state.lastPage = res.last_page || 1;
                state.hasMore = !!res.has_more;

                if (!append) {
                    state.rows = rows;
                    var $tb = $('#hrAttTableBody').empty();
                    if (!rows.length) {
                        $tb.append('<tr><td colspan="' + colCount() + '" class="text-center py-4">' + escapeHtml(cfg.trans.empty) + '</td></tr>');
                    } else {
                        appendRows(rows, 0);
                    }
                } else {
                    var startIndex = state.rows.length;
                    state.rows = state.rows.concat(rows);
                    appendRows(rows, startIndex);
                }
                updateBulkBar();
            })
            .fail(function () {
                if (!append) {
                    state.rows = [];
                    state.hasMore = false;
                    $('#hrAttTableBody').html(
                        '<tr><td colspan="' + colCount() + '" class="text-center py-4 text-danger">' +
                        escapeHtml(cfg.trans.loadError || cfg.trans.empty) + '</td></tr>'
                    );
                }
            })
            .always(function () {
                setLoading(false, append);
            });
    }

    function resetAndLoad() {
        state.page = 0;
        state.hasMore = false;
        state.rows = [];
        clearSelection();
        loadList({ append: false });
    }

    function loadMore() {
        if (state.loading || !state.hasMore) {
            return;
        }
        loadList({ append: true });
    }

    function setupScrollObserver() {
        var sentinel = document.getElementById('hrAttLoadSentinel');
        if (!sentinel) {
            return;
        }

        if (typeof IntersectionObserver === 'undefined') {
            $(window).on('scroll.hrAttLazy', function () {
                var scrollBottom = $(window).scrollTop() + $(window).height();
                var docHeight = $(document).height();
                if (docHeight - scrollBottom < 220) {
                    loadMore();
                }
            });
            return;
        }

        if (state.scrollObserver) {
            state.scrollObserver.disconnect();
        }

        state.scrollObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    loadMore();
                }
            });
        }, { root: null, rootMargin: '120px', threshold: 0 });

        state.scrollObserver.observe(sentinel);
    }

    function executeBulkAction() {
        if (!cfg.canBulk || state.isBulkSubmitting) {
            return;
        }
        var action = $('#hrAttBulkAction').val();
        var ids = getSelectedIds();
        if (ids.length === 0) {
            showAlert('warning', cfg.trans.bulkSelectFirst || '');
            return;
        }
        if (!action) {
            showAlert('warning', cfg.trans.bulkChooseActionFirst || '');
            return;
        }
        if (!window.confirm(bulkConfirmMessage(action, ids.length))) {
            return;
        }

        state.isBulkSubmitting = true;
        var $apply = $('#hrAttBulkApply');
        $apply.prop('disabled', true);

        $.ajax({
            url: cfg.routes.bulkActions,
            method: 'POST',
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': csrf(),
                Accept: 'application/json'
            },
            data: JSON.stringify({ ids: ids, action: action })
        })
            .done(function (res) {
                showAlert('success', (res && res.message) || '');
                clearSelection();
                state.page = 0;
                state.hasMore = false;
                state.rows = [];
                loadList({ append: false });
            })
            .fail(function (xhr) {
                var data = (xhr && xhr.responseJSON) ? xhr.responseJSON : {};
                var msg = data.message || cfg.trans.bulkChooseActionFirst || 'Error';
                if (data.errors && data.errors.length) {
                    msg += ' — ' + data.errors.slice(0, 3).join('; ');
                }
                showAlert('danger', msg);
            })
            .always(function () {
                state.isBulkSubmitting = false;
                $apply.prop('disabled', false);
                $('#hrAttBulkAction').val('');
            });
    }

    function refreshModalEmployeeSelect() {
        if (typeof window.refreshSearchableFilterSelects === 'function') {
            window.refreshSearchableFilterSelects('#hrAttModal');
            return;
        }
        var $modalSelect = $('#hrAttEmployeeSelect');
        if (!$modalSelect.length || typeof $.fn.select2 !== 'function') {
            return;
        }
        if ($modalSelect.hasClass('select2-hidden-accessible')) {
            $modalSelect.select2('destroy');
        }
        $modalSelect.select2({
            allowClear: true,
            width: '100%',
            dropdownParent: $('#hrAttModal'),
            placeholder: cfg.trans.searchEmployeePlaceholder || cfg.trans.selectEmployee || undefined
        });
    }

    function setEmployeeSelectValue(value) {
        if (typeof window.setSearchableFilterValue === 'function') {
            window.setSearchableFilterValue('#hrAttEmployeeSelect', value);
            return;
        }
        var $sel = $('#hrAttEmployeeSelect');
        if (!$sel.length) return;
        $sel.val(value == null || value === '' ? '' : String(value));
        if ($sel.hasClass('select2-hidden-accessible')) {
            $sel.trigger('change.select2');
        }
    }

    function openModal(title) {
        $('#hrAttModalTitle').text(title);
        if (!state.modal) {
            state.modal = new bootstrap.Modal(document.getElementById('hrAttModal'));
        }
        state.modal.show();
    }

    function save() {
        if (state.isSubmitting) return;
        setSubmitting(true);
        var isEdit = !!state.editId;
        $.ajax({
            url: isEdit ? cfg.routes.update.replace('__ID__', state.editId) : cfg.routes.store,
            method: isEdit ? 'PUT' : 'POST',
            data: {
                employee_id: $('#hrAttEmployeeSelect').val(),
                type: $('#hrAttForm [name="type"]').val(),
                date: $('#hrAttForm [name="date"]').val(),
                time: $('#hrAttForm [name="time"]').val(),
                status: $('#hrAttForm [name="status"]').val(),
                notes: $('#hrAttForm [name="notes"]').val(),
                device_type: 'desktop'
            },
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            success: function (res) {
                setSubmitting(false);
                if (state.modal) state.modal.hide();
                showAlert('success', res.message || '');
                resetAndLoad();
            },
            error: function (xhr) {
                setSubmitting(false);
                showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error');
            }
        });
    }

    function initBulkActions() {
        if (!cfg.canBulk) return;

        $(document).on('change', '.hr-att-row-checkbox', function () {
            setRowSelected($(this).val(), $(this).prop('checked'));
        });
        $('#hrAttSelectAllPage').on('change', function () {
            var checked = $(this).prop('checked');
            $('#hrAttTableBody .hr-att-row-checkbox').each(function () {
                $(this).prop('checked', checked);
                setRowSelected($(this).val(), checked);
            });
        });
        $('#hrAttBulkApply').on('click', executeBulkAction);
        $('#hrAttBulkClear').on('click', clearSelection);
    }

    function buildColsModalList() {
        var $container = $('#hrAttColsList');
        if (!$container.length) return;
        $container.empty();
        availableColumns.forEach(function (col) {
            var checked = isColVisible(col.key) ? ' checked' : '';
            var html = '<div class="col-6 col-md-4">' +
                '<div class="form-check form-switch p-2 border rounded bg-light me-1 mb-1">' +
                '<input class="form-check-input hr-att-col-toggle float-end me-1" type="checkbox" id="colToggle_' + col.key + '" data-key="' + col.key + '"' + checked + '>' +
                '<label class="form-check-label small font-hold fw-bold me-2 cursor-pointer" for="colToggle_' + col.key + '">' + escapeHtml(col.label) + '</label>' +
                '</div></div>';
            $container.append(html);
        });
    }

    function initColsModal() {
        $('#hrAttColsBtn').on('click', function () {
            buildColsModalList();
            var modal = new bootstrap.Modal(document.getElementById('hrAttColsModal'));
            modal.show();
        });
        $(document).on('change', '.hr-att-col-toggle', function () {
            var key = $(this).data('key');
            setColVisible(key, $(this).prop('checked'));
        });
        $('#hrAttResetColsBtn').on('click', resetColumnsVisibility);
    }

    $(function () {
        applyColumnVisibility();
        initColsModal();
        setupScrollObserver();
        initBulkActions();
        resetAndLoad();

        $('#hrAttModal').on('shown.bs.modal', function () {
            var keepValue = state.pendingEmployeeId;
            refreshModalEmployeeSelect();
            if (keepValue !== null && keepValue !== undefined) {
                setEmployeeSelectValue(keepValue);
                state.pendingEmployeeId = null;
            }
            window.setTimeout(function () {
                var $search = $('#hrAttModal .select2-search__field');
                if ($search.length) {
                    $search.trigger('focus');
                }
            }, 50);
        });

        $('#hrAttSearchEmployee, #hrAttSearchFingerprintName, #hrAttDateFrom, #hrAttDateTo').on('change input', function () {
            clearTimeout(state.timer);
            state.timer = setTimeout(resetAndLoad, 300);
        });
        $('#hrAttClear').on('click', function () {
            $('#hrAttDateFrom, #hrAttDateTo, #hrAttSearchFingerprintName').val('');
            if (typeof window.setSearchableFilterValue === 'function') {
                window.setSearchableFilterValue('#hrAttSearchEmployee', '');
            } else {
                $('#hrAttSearchEmployee').val('').trigger('change');
            }
            resetAndLoad();
        });
        $('#hrAttCreateBtn').on('click', function () {
            state.editId = null;
            state.pendingEmployeeId = '';
            $('#hrAttForm')[0].reset();
            openModal(cfg.trans.add);
        });
        $(document).on('click', '.hr-att-edit', function () {
            var id = $(this).data('id');
            var row = state.rows.find(function (r) { return String(r.id) === String(id); });
            if (!row) return;
            state.editId = row.id;
            state.pendingEmployeeId = row.employee_id;
            $('#hrAttForm [name="type"]').val(row.type);
            $('#hrAttForm [name="date"]').val(row.date || '');
            $('#hrAttForm [name="time"]').val(row.time || '');
            $('#hrAttForm [name="status"]').val(row.status);
            $('#hrAttForm [name="notes"]').val(row.notes || '');
            openModal(cfg.trans.editTitle);
        });
        $(document).on('click', '.hr-att-delete', function () {
            if (!window.confirm(cfg.trans.confirmDelete)) return;
            $.ajax({
                url: cfg.routes.destroy.replace('__ID__', $(this).data('id')),
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                success: function (res) { showAlert('success', res.message || ''); resetAndLoad(); },
                error: function (xhr) { showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Error'); }
            });
        });
        $('#hrAttForm').on('submit', function (e) { e.preventDefault(); save(); });
    });
})(jQuery);
