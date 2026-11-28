/**
 * HR weekly operational calendar — employees stacked per dated day inside a configurable day window.
 * Drag moves a shift. Ctrl+drag copies it. Hours past 24 belong to the next calendar day.
 * Config: window._hrWeeklyShiftsConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrWeeklyShiftsConfig;
    if (!cfg) {
        return;
    }

    var PALETTE = [
        '#1976d2', '#ef6c00', '#6a1b9a', '#00897b', '#c62828',
        '#1565c0', '#6d4c41', '#2e7d32', '#ad1457', '#455a64',
        '#f9a825', '#00838f', '#5e35b1', '#d84315', '#33691e'
    ];

    var state = {
        isSubmitting: false,
        dirty: false,
        tempId: 0,
        search: '',
        selectedEmployeeId: null,
        employees: [],
        assignments: [],
        days: [],
        settings: { day_start_hour: 0, day_end_hour: 24, week_start_day: 'saturday' },
        weekStart: '',
        weekEnd: '',
        prevWeek: '',
        nextWeek: '',
        currentWeek: '',
        gesture: null
    };

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || '';
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function pad(hour) {
        return (hour < 10 ? '0' : '') + hour;
    }

    function dayStart() {
        return state.settings.day_start_hour;
    }

    function dayEnd() {
        return state.settings.day_end_hour;
    }

    function spanHours() {
        return Math.max(1, dayEnd() - dayStart());
    }

    function showAlert(type, message) {
        var $box = $('#hrWeekAlert');
        $box.removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
    }

    function setBusy(on) {
        state.isSubmitting = !!on;
        $('#hrWeekBoard').toggleClass('is-busy', !!on);
        $('#hrWeekModalSaveBtn, #hrWeekRemoveBtn, #hrWeekCopyPrevBtn, #hrWeekPrevBtn, #hrWeekNextBtn, #hrWeekTodayBtn').prop('disabled', !!on);
        syncCommitButton();
        syncSettingsButton();
    }

    function syncCommitButton() {
        $('#hrWeekCommitBtn').prop('disabled', state.isSubmitting || !state.dirty);
    }

    function markDirty() {
        state.dirty = true;
        syncCommitButton();
    }

    function confirmDiscard() {
        return !state.dirty || window.confirm(cfg.trans.unsavedConfirm);
    }

    function hideModal() {
        var modalEl = document.getElementById('hrWeekModal');
        if (modalEl && window.bootstrap) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        }
    }

    function firstError(xhr) {
        var errors = xhr && xhr.responseJSON && xhr.responseJSON.errors;
        if (errors) {
            var key = Object.keys(errors)[0];
            if (key && errors[key] && errors[key][0]) {
                return errors[key][0];
            }
        }
        if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
            return xhr.responseJSON.message;
        }
        return cfg.trans.saveFailed;
    }

    function toMinutes(value) {
        var parts = String(value || '').split(':');
        var hour = parseInt(parts[0], 10) || 0;
        var minute = parseInt(parts[1], 10) || 0;
        return (hour * 60) + minute;
    }

    function clockText(minutes) {
        if (minutes === 1440) {
            return '24:00';
        }
        var hour = Math.floor(minutes / 60) % 24;
        return pad(hour) + ':' + pad(minutes % 60);
    }

    function labelTime(value) {
        return clockText(toMinutes(value));
    }

    function hourTime(hour) {
        return pad(hour) + ':00';
    }

    function hourOptionLabel(hour) {
        if (hour <= 24) {
            return hourTime(hour);
        }
        return hourTime(hour - 24) + ' ' + cfg.trans.nextDay;
    }

    function formatDate(iso) {
        var parts = String(iso || '').split('-');
        if (parts.length !== 3) {
            return iso || '';
        }
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    function employeeColor(id) {
        var index = Math.abs(parseInt(id, 10) || 0) % PALETTE.length;
        return PALETTE[index];
    }

    function visibleEmployees() {
        var term = state.search.trim().toLowerCase();
        return state.employees.filter(function (employee) {
            if (!term) {
                return true;
            }
            return (employee.name + ' ' + (employee.department || '')).toLowerCase().indexOf(term) !== -1;
        });
    }

    function employeeById(id) {
        for (var i = 0; i < state.employees.length; i++) {
            if (String(state.employees[i].id) === String(id)) {
                return state.employees[i];
            }
        }
        return null;
    }

    function assignmentById(id) {
        for (var i = 0; i < state.assignments.length; i++) {
            if (String(state.assignments[i].id) === String(id)) {
                return state.assignments[i];
            }
        }
        return null;
    }

    function dayByDate(date) {
        for (var i = 0; i < state.days.length; i++) {
            if (state.days[i].date === date) {
                return state.days[i];
            }
        }
        return null;
    }

    function dayRows(employeeId, date, exceptId) {
        return state.assignments.filter(function (row) {
            return String(row.employee_id) === String(employeeId)
                && row.shift_date === date
                && String(row.id) !== String(exceptId || '');
        });
    }

    function applyBoard(data) {
        state.settings = data.settings || state.settings;
        state.days = data.days || [];
        state.weekStart = data.week_start || '';
        state.weekEnd = data.week_end || '';
        state.prevWeek = data.prev_week || '';
        state.nextWeek = data.next_week || '';
        state.currentWeek = data.current_week || '';
        state.employees = data.employees || [];
        state.assignments = data.assignments || [];
        if (state.selectedEmployeeId && !employeeById(state.selectedEmployeeId)) {
            state.selectedEmployeeId = null;
        }
        state.dirty = false;
        state.tempId = 0;
        syncCommitButton();
        syncSettingsControls();
        renderWeekHeader();
        renderModalOptions();
        renderAxis();
        renderEmployees();
        renderDays();
    }

    function renderWeekHeader() {
        $('#hrWeekRangeLabel').text(formatDate(state.weekStart) + ' - ' + formatDate(state.weekEnd));
        $('#hrWeekTodayBtn').toggleClass('active', state.weekStart === state.currentWeek);
    }

    function fillEndOptions(startHour, selected) {
        var html = '';
        for (var hour = startHour + 1; hour <= startHour + 24; hour++) {
            html += '<option value="' + hour + '">' + escapeHtml(hourOptionLabel(hour)) + '</option>';
        }
        var $end = $('#hrWeekDayEndHour');
        $end.html(html).data('start', startHour);
        var target = Math.min(startHour + 24, Math.max(startHour + 1, selected));
        $end.val(String(target));
    }

    function syncSettingsControls() {
        $('#hrWeekDayStartHour').val(String(dayStart()));
        fillEndOptions(dayStart(), dayEnd());
        $('#hrWeekStartDay').val(state.settings.week_start_day);
        syncSettingsButton();
    }

    function pendingSettings() {
        return {
            day_start_hour: parseInt($('#hrWeekDayStartHour').val(), 10) || 0,
            day_end_hour: parseInt($('#hrWeekDayEndHour').val(), 10) || 24,
            week_start_day: String($('#hrWeekStartDay').val() || 'saturday')
        };
    }

    function syncSettingsButton() {
        var next = pendingSettings();
        var changed = next.day_start_hour !== dayStart()
            || next.day_end_hour !== dayEnd()
            || next.week_start_day !== state.settings.week_start_day;
        $('#hrWeekSettingsSaveBtn').prop('disabled', state.isSubmitting || !changed);
    }

    function renderModalOptions() {
        var dayHtml = '';
        state.days.forEach(function (day) {
            dayHtml += '<option value="' + escapeHtml(day.date) + '">' + escapeHtml(day.label + ' ' + formatDate(day.date)) + '</option>';
        });
        $('#hrWeekDay').html(dayHtml);

        var startHtml = '';
        for (var hour = dayStart(); hour < dayEnd(); hour++) {
            startHtml += '<option value="' + hourTime(hour) + '">' + escapeHtml(hourOptionLabel(hour)) + '</option>';
        }
        $('#hrWeekStart').html(startHtml);

        var endHtml = '';
        for (var end = dayStart() + 1; end <= dayEnd(); end++) {
            endHtml += '<option value="' + hourTime(end) + '">' + escapeHtml(hourOptionLabel(end)) + '</option>';
        }
        $('#hrWeekEnd').html(endHtml);
    }

    function ensureOption($select, hour) {
        var value = hourTime(hour);
        if (!$select.find('option[value="' + value + '"]').length) {
            $select.append('<option value="' + value + '">' + escapeHtml(hourOptionLabel(hour)) + '</option>');
        }
        $select.val(value);
    }

    function renderAxis() {
        $('#hrWeekBoard').css('--hr-hours', spanHours());
        var html = '<div></div><div></div><div class="hr-week-axis-hours" dir="rtl">';
        for (var hour = dayStart(); hour < dayEnd(); hour++) {
            html += '<span>' + pad(hour % 24) + '</span>';
        }
        html += '</div>';
        $('#hrWeekAxis').html(html);
    }

    function renderEmployees() {
        var people = visibleEmployees();
        var html = '';
        people.forEach(function (employee) {
            var selected = String(state.selectedEmployeeId) === String(employee.id) ? ' is-selected' : '';
            var color = employeeColor(employee.id);
            html += '<div class="hr-week-person' + selected + '" draggable="' + (cfg.canEdit ? 'true' : 'false') + '" data-employee-id="' + employee.id + '" style="--hr-emp:' + color + '">'
                + '<strong>' + escapeHtml(employee.name) + '</strong>'
                + (employee.department ? '<small>' + escapeHtml(employee.department) + '</small>' : '')
                + '</div>';
        });
        if (!html) {
            html = '<div class="hr-week-drop-hint">' + escapeHtml(cfg.trans.noEmployees) + '</div>';
        }
        $('#hrWeekEmployeeList').html(html);
    }

    function blockMarkup(row) {
        var windowStart = dayStart() * 60;
        var windowLength = spanHours() * 60;
        var startMin = toMinutes(row.start_time);
        var endMin = toMinutes(row.end_time);
        var visibleStart = Math.max(startMin, windowStart);
        var visibleEnd = Math.min(endMin, windowStart + windowLength);
        if (visibleEnd <= visibleStart) {
            return '';
        }
        var right = ((visibleStart - windowStart) / windowLength) * 100;
        var width = ((visibleEnd - visibleStart) / windowLength) * 100;
        var color = employeeColor(row.employee_id);
        var html = '<div class="hr-week-block" data-id="' + row.id + '" data-employee-id="' + row.employee_id + '" data-start="' + escapeHtml(row.start_time) + '" data-end="' + escapeHtml(row.end_time) + '" style="right:' + right + '%;width:' + width + '%;left:auto;--hr-emp:' + color + '">';
        if (cfg.canEdit) {
            html += '<span class="hr-week-handle" data-edge="start"></span>';
        }
        html += '<span class="hr-week-block-label">' + escapeHtml(labelTime(row.start_time) + ' - ' + labelTime(row.end_time)) + '</span>';
        if (cfg.canEdit) {
            html += '<span class="hr-week-handle" data-edge="end"></span>';
        }
        html += '</div>';
        return html;
    }

    function renderDays() {
        var people = visibleEmployees();
        var span = spanHours();
        var html = '';
        state.days.forEach(function (day) {
            var isToday = day.date === todayIso() ? ' is-today' : '';
            html += '<div class="hr-week-day' + isToday + '">';
            html += '<div class="hr-week-day-name font-hold fw-bold"><span>' + escapeHtml(day.label) + '</span><small>' + escapeHtml(formatDate(day.date).slice(0, 5)) + '</small></div>';
            html += '<div class="hr-week-lanes">';
            if (!people.length) {
                html += '<div class="hr-week-drop-hint">' + escapeHtml(cfg.trans.noEmployees) + '</div>';
            }
            people.forEach(function (employee) {
                var color = employeeColor(employee.id);
                var selected = String(state.selectedEmployeeId) === String(employee.id) ? ' is-selected' : '';
                html += '<div class="hr-week-lane' + selected + '" data-employee-id="' + employee.id + '" style="--hr-emp:' + color + '">';
                html += '<div class="hr-week-lane-name"><span class="hr-week-swatch"></span><span>' + escapeHtml(employee.name) + '</span></div>';
                html += '<div class="hr-week-track" dir="rtl" data-date="' + escapeHtml(day.date) + '" data-employee-id="' + employee.id + '">';
                html += '<div class="hr-week-cells">';
                for (var i = 0; i < span; i++) {
                    html += '<div class="hr-week-cell" data-hour="' + (dayStart() + i) + '"></div>';
                }
                html += '</div>';
                state.assignments.forEach(function (row) {
                    if (String(row.employee_id) !== String(employee.id) || row.shift_date !== day.date) {
                        return;
                    }
                    html += blockMarkup(row);
                });
                html += '</div></div>';
            });
            html += '</div></div>';
        });
        $('#hrWeekDays').html(html);
    }

    function todayIso() {
        var now = new Date();
        return now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());
    }

    function hourFromClientX(track, clientX) {
        var rect = track.getBoundingClientRect();
        var fromRight = rect.right - clientX;
        var ratio = rect.width <= 0 ? 0 : fromRight / rect.width;
        ratio = Math.min(0.9999, Math.max(0, ratio));
        return dayStart() + Math.floor(ratio * spanHours());
    }

    function boundaryFromClientX(track, clientX) {
        var rect = track.getBoundingClientRect();
        var fromRight = rect.right - clientX;
        var ratio = rect.width <= 0 ? 0 : fromRight / rect.width;
        ratio = Math.min(1, Math.max(0, ratio));
        return dayStart() + Math.round(ratio * spanHours());
    }

    function applyBlockGeometry($block, startHour, endHour) {
        var span = spanHours();
        $block.css({
            right: ((startHour - dayStart()) / span * 100) + '%',
            width: ((endHour - startHour) / span * 100) + '%',
            left: 'auto'
        });
        var $label = $block.find('.hr-week-block-label');
        if (!$label.length) {
            $block.append('<span class="hr-week-block-label"></span>');
            $label = $block.find('.hr-week-block-label');
        }
        $label.text(clockText(startHour * 60) + ' - ' + clockText(endHour * 60));
    }

    function paintDraft($track, startHour, endHour, copying) {
        $('.hr-week-draft').each(function () {
            if (this.parentElement !== $track[0]) {
                $(this).remove();
            }
        });
        var $draft = $track.find('.hr-week-draft');
        if (!$draft.length) {
            $track.append('<div class="hr-week-block is-draft hr-week-draft"><span class="hr-week-block-label"></span></div>');
            $draft = $track.find('.hr-week-draft');
        }
        $draft.css('--hr-emp', employeeColor($track.data('employee-id')));
        $draft.toggleClass('is-copy', !!copying);
        applyBlockGeometry($draft, startHour, endHour);
    }

    function trackFor(date, employeeId) {
        return document.querySelector('#hrWeekDays .hr-week-track[data-date="' + date + '"][data-employee-id="' + employeeId + '"]');
    }

    function trackFromPoint(clientX, clientY) {
        var stack = document.elementsFromPoint ? document.elementsFromPoint(clientX, clientY) : [document.elementFromPoint(clientX, clientY)];
        for (var i = 0; i < stack.length; i++) {
            var node = stack[i];
            if (!node || !node.closest) {
                continue;
            }
            var track = node.closest('.hr-week-track');
            if (track) {
                return track;
            }
        }
        return null;
    }

    function placeDuration(dropHour, duration) {
        var length = Math.max(1, Math.min(spanHours(), duration));
        var start = dropHour;
        var end = start + length;
        if (end > dayEnd()) {
            end = dayEnd();
            start = Math.max(dayStart(), end - length);
        }
        return { start: start, end: end };
    }

    function snappedHours(startValue, endValue) {
        var startHour = Math.floor(toMinutes(startValue) / 60);
        var endHour = Math.ceil(toMinutes(endValue) / 60);
        if (endHour <= startHour) {
            endHour = startHour + 1;
        }
        return { start: startHour, end: endHour };
    }

    function localConflict(employeeId, date, startHour, endHour, exceptId) {
        if (startHour >= endHour || (endHour - startHour) > 24) {
            return cfg.trans.invalidRange;
        }
        var rows = dayRows(employeeId, date, exceptId);
        if (rows.length >= 2) {
            return cfg.trans.maxTwo;
        }
        return '';
    }

    function fetchBoard(weekStart) {
        return $.ajax({
            url: cfg.routes.data,
            method: 'GET',
            dataType: 'json',
            data: weekStart ? { week_start: weekStart } : {}
        });
    }

    function loadBoard(weekStart) {
        if (state.isSubmitting) {
            return;
        }
        setBusy(true);
        fetchBoard(weekStart).done(function (res) {
            applyBoard((res && res.data) || {});
        }).fail(function (xhr) {
            showAlert('danger', firstError(xhr));
        }).always(function () {
            setBusy(false);
        });
    }

    function goToWeek(weekStart) {
        if (!weekStart || state.isSubmitting || !confirmDiscard()) {
            return;
        }
        loadBoard(weekStart);
    }

    function commitLocal(employeeId, date, startHour, endHour, assignmentId) {
        if (!cfg.canEdit) {
            return;
        }
        if (!employeeId) {
            showAlert('danger', cfg.trans.selectEmployee);
            renderDays();
            return;
        }
        var day = dayByDate(date);
        if (!day) {
            renderDays();
            return;
        }
        var problem = localConflict(employeeId, date, startHour, endHour, assignmentId);
        if (problem) {
            showAlert('danger', problem);
            renderDays();
            return;
        }
        var person = employeeById(employeeId);
        if (assignmentId) {
            var row = assignmentById(assignmentId);
            if (!row) {
                renderDays();
                return;
            }
            row.employee_id = Number(employeeId);
            row.employee_name = person ? person.name : row.employee_name;
            row.weekday = day.weekday;
            row.shift_date = date;
            row.start_time = hourTime(startHour);
            row.end_time = hourTime(endHour);
        } else {
            state.tempId -= 1;
            state.assignments.push({
                id: state.tempId,
                employee_id: Number(employeeId),
                employee_name: person ? person.name : '',
                weekday: day.weekday,
                shift_date: date,
                start_time: hourTime(startHour),
                end_time: hourTime(endHour)
            });
        }
        markDirty();
        renderDays();
        hideModal();
    }

    function removeLocal(assignmentId) {
        if (!cfg.canEdit || !assignmentId) {
            return;
        }
        state.assignments = state.assignments.filter(function (row) {
            return String(row.id) !== String(assignmentId);
        });
        markDirty();
        renderDays();
        hideModal();
    }

    function saveBoard() {
        if (state.isSubmitting || !cfg.canEdit || !state.dirty) {
            return;
        }
        setBusy(true);
        var payload = state.assignments.map(function (row) {
            var item = {
                employee_id: row.employee_id,
                shift_date: row.shift_date,
                start_time: String(row.start_time).slice(0, 5),
                end_time: String(row.end_time).slice(0, 5)
            };
            if (Number(row.id) > 0) {
                item.id = Number(row.id);
            }
            return item;
        });
        $.ajax({
            url: cfg.routes.save,
            method: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json'
            },
            data: JSON.stringify({ week_start: state.weekStart, assignments: payload })
        }).done(function (res) {
            applyBoard((res && res.data) || {});
            showAlert('success', (res && res.message) || cfg.trans.saved);
        }).fail(function (xhr) {
            showAlert('danger', firstError(xhr));
        }).always(function () {
            setBusy(false);
        });
    }

    function saveSettings() {
        if (state.isSubmitting || !cfg.canEdit) {
            return;
        }
        var next = pendingSettings();
        if (next.day_end_hour <= next.day_start_hour || next.day_end_hour > next.day_start_hour + 24) {
            showAlert('danger', cfg.trans.invalidWindow);
            return;
        }
        if (!confirmDiscard()) {
            return;
        }
        setBusy(true);
        $.ajax({
            url: cfg.routes.settings,
            method: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json'
            },
            data: JSON.stringify($.extend({ week_start: state.weekStart }, next))
        }).done(function (res) {
            applyBoard((res && res.data) || {});
            showAlert('success', (res && res.message) || cfg.trans.settingsSaved);
        }).fail(function (xhr) {
            showAlert('danger', firstError(xhr));
        }).always(function () {
            setBusy(false);
        });
    }

    function copyPreviousWeek() {
        if (state.isSubmitting || !cfg.canEdit || !state.prevWeek) {
            return;
        }
        if (state.assignments.length && !window.confirm(cfg.trans.copyConfirm)) {
            return;
        }
        setBusy(true);
        fetchBoard(state.prevWeek).done(function (res) {
            var prev = (res && res.data) || {};
            var prevDays = prev.days || [];
            var dateMap = {};
            prevDays.forEach(function (day, index) {
                if (state.days[index]) {
                    dateMap[day.date] = state.days[index];
                }
            });
            var copied = [];
            (prev.assignments || []).forEach(function (row) {
                var target = dateMap[row.shift_date];
                if (!target || !employeeById(row.employee_id)) {
                    return;
                }
                state.tempId -= 1;
                copied.push({
                    id: state.tempId,
                    employee_id: Number(row.employee_id),
                    employee_name: row.employee_name,
                    weekday: target.weekday,
                    shift_date: target.date,
                    start_time: row.start_time,
                    end_time: row.end_time
                });
            });
            if (!copied.length) {
                showAlert('danger', cfg.trans.noPrevious);
                return;
            }
            state.assignments = copied;
            markDirty();
            renderDays();
            showAlert('success', cfg.trans.copied);
        }).fail(function (xhr) {
            showAlert('danger', firstError(xhr));
        }).always(function () {
            setBusy(false);
        });
    }

    function openModal(assignmentId) {
        var row = assignmentById(assignmentId);
        if (!row || !window.bootstrap) {
            return;
        }
        var hours = snappedHours(row.start_time, row.end_time);
        $('#hrWeekAssignmentId').val(row.id);
        $('#hrWeekDay').val(row.shift_date);
        ensureOption($('#hrWeekStart'), hours.start);
        ensureOption($('#hrWeekEnd'), hours.end);
        window.bootstrap.Modal.getOrCreateInstance(document.getElementById('hrWeekModal')).show();
    }

    function trackByGesture(gesture) {
        return trackFor(gesture.date, gesture.employeeId);
    }

    $('#hrWeekEmployeeSearch').on('input', function () {
        state.search = $(this).val() || '';
        renderEmployees();
        renderDays();
    });

    $('#hrWeekEmployeeList').on('click', '.hr-week-person', function () {
        state.selectedEmployeeId = $(this).data('employee-id');
        renderEmployees();
        renderDays();
        var lane = document.querySelector('#hrWeekDays .hr-week-lane[data-employee-id="' + state.selectedEmployeeId + '"]');
        if (lane) {
            lane.scrollIntoView({ block: 'nearest' });
        }
    });

    $('#hrWeekEmployeeList').on('dragstart', '.hr-week-person', function (event) {
        if (!cfg.canEdit || state.isSubmitting) {
            event.preventDefault();
            return;
        }
        var employeeId = $(this).data('employee-id');
        state.selectedEmployeeId = employeeId;
        event.originalEvent.dataTransfer.setData('text/plain', JSON.stringify({ employeeId: employeeId }));
        event.originalEvent.dataTransfer.effectAllowed = 'copy';
    });

    $('#hrWeekDays').on('dragover', '.hr-week-track', function (event) {
        if (!cfg.canEdit) {
            return;
        }
        event.preventDefault();
        $(this).addClass('is-over');
    });

    $('#hrWeekDays').on('dragleave', '.hr-week-track', function () {
        $(this).removeClass('is-over');
    });

    $('#hrWeekDays').on('drop', '.hr-week-track', function (event) {
        event.preventDefault();
        $(this).removeClass('is-over');
        if (!cfg.canEdit || state.isSubmitting) {
            return;
        }
        var raw = event.originalEvent.dataTransfer.getData('text/plain');
        if (!raw) {
            return;
        }
        var payload;
        try {
            payload = JSON.parse(raw);
        } catch (e) {
            return;
        }
        if (!payload.employeeId) {
            return;
        }
        var hour = hourFromClientX(this, event.originalEvent.clientX);
        commitLocal(payload.employeeId, String($(this).data('date')), hour, Math.min(dayEnd(), hour + 1), null);
    });

    $('#hrWeekDays').on('pointerdown', '.hr-week-track', function (event) {
        if (!cfg.canEdit || state.isSubmitting || event.button !== 0) {
            return;
        }
        var $handle = $(event.target).closest('.hr-week-handle');
        var $block = $(event.target).closest('.hr-week-block');
        var track = this;

        if ($handle.length) {
            event.preventDefault();
            event.stopPropagation();
            var $owned = $handle.closest('.hr-week-block');
            var hours = snappedHours($owned.data('start'), $owned.data('end'));
            state.gesture = {
                mode: 'resize',
                edge: String($handle.data('edge')),
                id: String($owned.data('id')),
                employeeId: String($(track).data('employee-id')),
                date: String($(track).data('date')),
                startHour: hours.start,
                endHour: hours.end,
                originStart: hours.start,
                originEnd: hours.end,
                originX: event.clientX,
                originY: event.clientY,
                pointerId: event.pointerId
            };
            track.setPointerCapture(event.pointerId);
            return;
        }

        if ($block.length && !$block.hasClass('is-draft')) {
            var blockHours = snappedHours($block.data('start'), $block.data('end'));
            state.gesture = {
                mode: 'move',
                copy: event.ctrlKey || event.metaKey,
                id: String($block.data('id')),
                employeeId: String($block.data('employee-id') || $(track).data('employee-id')),
                date: String($(track).data('date')),
                startHour: blockHours.start,
                endHour: blockHours.end,
                x: event.clientX,
                y: event.clientY,
                moved: false,
                pointerId: event.pointerId
            };
            track.setPointerCapture(event.pointerId);
            return;
        }

        var laneEmployeeId = $(track).data('employee-id');
        var laneDate = String($(track).data('date'));
        var hour = hourFromClientX(track, event.clientX);
        var problem = localConflict(laneEmployeeId, laneDate, hour, hour + 1, null);
        if (problem === cfg.trans.maxTwo) {
            showAlert('danger', problem);
            return;
        }

        state.gesture = {
            mode: 'draw',
            employeeId: String(laneEmployeeId),
            date: laneDate,
            anchor: hour,
            startHour: hour,
            endHour: hour + 1,
            pointerId: event.pointerId
        };
        paintDraft($(track), hour, hour + 1, false);
        track.setPointerCapture(event.pointerId);
    });

    $('#hrWeekBoard').on('pointermove', function (event) {
        var gesture = state.gesture;
        if (!gesture || event.pointerId !== gesture.pointerId) {
            return;
        }
        if (gesture.mode === 'move') {
            if (Math.abs(event.clientX - gesture.x) > 12 || Math.abs(event.clientY - gesture.y) > 12) {
                gesture.moved = true;
            }
            gesture.copy = event.ctrlKey || event.metaKey;
            if (!gesture.moved) {
                return;
            }
            var target = trackFromPoint(event.clientX, event.clientY);
            if (!target) {
                return;
            }
            var dropHour = hourFromClientX(target, event.clientX);
            var placed = placeDuration(dropHour, gesture.endHour - gesture.startHour);
            gesture.dropStart = placed.start;
            gesture.dropEnd = placed.end;
            gesture.targetEmployeeId = String($(target).data('employee-id'));
            gesture.targetDate = String($(target).data('date'));
            $('.hr-week-block[data-id="' + gesture.id + '"]').toggleClass('is-dragging', !gesture.copy);
            paintDraft($(target), placed.start, placed.end, gesture.copy);
            return;
        }
        var track = trackByGesture(gesture);
        if (!track) {
            return;
        }
        if (gesture.mode === 'draw') {
            var hour = hourFromClientX(track, event.clientX);
            gesture.startHour = Math.min(gesture.anchor, hour);
            gesture.endHour = Math.min(dayEnd(), Math.max(gesture.anchor, hour) + 1);
            paintDraft($(track), gesture.startHour, gesture.endHour, false);
            return;
        }
        if (gesture.mode === 'resize') {
            if (Math.abs(event.clientX - gesture.originX) < 12 && Math.abs(event.clientY - gesture.originY) < 12) {
                return;
            }
            var boundary = boundaryFromClientX(track, event.clientX);
            if (gesture.edge === 'start') {
                gesture.startHour = Math.max(dayStart(), Math.min(boundary, gesture.endHour - 1));
            } else {
                gesture.endHour = Math.min(dayEnd(), Math.max(boundary, gesture.startHour + 1));
            }
            applyBlockGeometry($('.hr-week-block[data-id="' + gesture.id + '"]'), gesture.startHour, gesture.endHour);
        }
    });

    $('#hrWeekBoard').on('pointerup pointercancel', function (event) {
        var gesture = state.gesture;
        if (!gesture || event.pointerId !== gesture.pointerId) {
            return;
        }
        state.gesture = null;
        if (gesture.mode === 'move') {
            var tapped = Math.abs(event.clientX - gesture.x) < 12 && Math.abs(event.clientY - gesture.y) < 12;
            if (tapped && event.type !== 'pointercancel') {
                openModal(gesture.id);
                return;
            }
            if (!gesture.moved || event.type === 'pointercancel') {
                renderDays();
                return;
            }
            gesture.copy = event.ctrlKey || event.metaKey;
            if (!gesture.targetEmployeeId || gesture.dropStart == null) {
                renderDays();
                return;
            }
            var unchanged = !gesture.copy
                && String(gesture.targetEmployeeId) === String(gesture.employeeId)
                && gesture.targetDate === gesture.date
                && gesture.dropStart === gesture.startHour
                && gesture.dropEnd === gesture.endHour;
            if (unchanged) {
                renderDays();
                return;
            }
            commitLocal(
                gesture.targetEmployeeId,
                gesture.targetDate,
                gesture.dropStart,
                gesture.dropEnd,
                gesture.copy ? null : gesture.id
            );
            return;
        }
        if (gesture.mode === 'draw') {
            commitLocal(gesture.employeeId, gesture.date, gesture.startHour, gesture.endHour, null);
            return;
        }
        if (gesture.mode === 'resize') {
            var resizeTap = Math.abs(event.clientX - gesture.originX) < 12 && Math.abs(event.clientY - gesture.originY) < 12;
            if (resizeTap && event.type !== 'pointercancel') {
                openModal(gesture.id);
                return;
            }
            if (gesture.startHour === gesture.originStart && gesture.endHour === gesture.originEnd) {
                renderDays();
                return;
            }
            var row = assignmentById(gesture.id);
            if (!row) {
                return;
            }
            commitLocal(row.employee_id, gesture.date, gesture.startHour, gesture.endHour, row.id);
        }
    });

    $('#hrWeekForm').on('submit', function (event) {
        event.preventDefault();
        if (state.isSubmitting || !cfg.canEdit) {
            return;
        }
        var startHour = parseInt(String($('#hrWeekStart').val() || '0'), 10);
        var endHour = parseInt(String($('#hrWeekEnd').val() || '0'), 10);
        var row = assignmentById($('#hrWeekAssignmentId').val());
        var employeeId = row ? row.employee_id : state.selectedEmployeeId;
        commitLocal(
            employeeId,
            String($('#hrWeekDay').val() || ''),
            startHour,
            endHour,
            $('#hrWeekAssignmentId').val()
        );
    });

    $('#hrWeekRemoveBtn').on('click', function (event) {
        event.preventDefault();
        if (state.isSubmitting || !cfg.canEdit) {
            return;
        }
        var assignmentId = $('#hrWeekAssignmentId').val();
        if (!assignmentId) {
            return;
        }
        removeLocal(assignmentId);
    });

    $('#hrWeekCommitBtn').on('click', function (event) {
        event.preventDefault();
        saveBoard();
    });

    $('#hrWeekPrevBtn').on('click', function (event) {
        event.preventDefault();
        goToWeek(state.prevWeek);
    });

    $('#hrWeekNextBtn').on('click', function (event) {
        event.preventDefault();
        goToWeek(state.nextWeek);
    });

    $('#hrWeekTodayBtn').on('click', function (event) {
        event.preventDefault();
        if (state.weekStart === state.currentWeek) {
            return;
        }
        goToWeek(state.currentWeek);
    });

    $('#hrWeekCopyPrevBtn').on('click', function (event) {
        event.preventDefault();
        copyPreviousWeek();
    });

    $('#hrWeekDayStartHour').on('change', function () {
        var startHour = parseInt($(this).val(), 10) || 0;
        var $end = $('#hrWeekDayEndHour');
        var previousStart = parseInt($end.data('start'), 10) || 0;
        var currentSpan = (parseInt($end.val(), 10) || 24) - previousStart;
        fillEndOptions(startHour, startHour + Math.max(1, currentSpan));
        syncSettingsButton();
    });

    $('#hrWeekDayEndHour, #hrWeekStartDay').on('change', function () {
        syncSettingsButton();
    });

    $('#hrWeekSettingsSaveBtn').on('click', function (event) {
        event.preventDefault();
        saveSettings();
    });

    $(window).on('beforeunload', function () {
        if (state.dirty) {
            return cfg.trans.unsavedConfirm;
        }
        return undefined;
    });

    loadBoard(null);
}(jQuery));
