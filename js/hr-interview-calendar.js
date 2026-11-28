/**
 * HR Interview Calendar — Ajax month view
 * Config: window._hrInterviewCalendarConfig
 */
(function ($) {
    'use strict';

    var cfg = window._hrInterviewCalendarConfig;
    if (!cfg) return;

    var state = { month: cfg.initialMonth || '', type: '', status: '' };

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function pad(n) { return n < 10 ? '0' + n : String(n); }

    function shiftMonth(delta) {
        var parts = state.month.split('-');
        var y = parseInt(parts[0], 10);
        var m = parseInt(parts[1], 10) - 1 + delta;
        var d = new Date(y, m, 1);
        state.month = d.getFullYear() + '-' + pad(d.getMonth() + 1);
        load();
    }

    function buildCalendar(month, interviews) {
        var parts = month.split('-');
        var year = parseInt(parts[0], 10);
        var mon = parseInt(parts[1], 10);
        var first = new Date(year, mon - 1, 1);
        var daysInMonth = new Date(year, mon, 0).getDate();
        var startDow = first.getDay();
        var byDate = {};
        (interviews || []).forEach(function (iv) {
            if (!iv.date) return;
            if (!byDate[iv.date]) byDate[iv.date] = [];
            byDate[iv.date].push(iv);
        });

        var titleDate = new Date(year, mon - 1, 1);
        $('#hrCalMonthLabel').text(titleDate.toLocaleDateString(undefined, { month: 'long', year: 'numeric' }));

        var cells = [];
        var i;
        for (i = 0; i < startDow; i++) cells.push(null);
        for (i = 1; i <= daysInMonth; i++) {
            cells.push(year + '-' + pad(mon) + '-' + pad(i));
        }
        while (cells.length % 7 !== 0) cells.push(null);

        var today = new Date();
        var todayStr = today.getFullYear() + '-' + pad(today.getMonth() + 1) + '-' + pad(today.getDate());
        var html = '';
        for (var w = 0; w < cells.length; w += 7) {
            html += '<tr>';
            for (var d = 0; d < 7; d++) {
                var date = cells[w + d];
                html += '<td class="align-top" style="height:140px;min-width:110px;">';
                if (date) {
                    var dayNum = parseInt(date.slice(-2), 10);
                    html += '<div class="d-flex justify-content-between mb-1"><strong>' + dayNum + '</strong>';
                    if (date === todayStr) html += '<span class="badge bg-primary">' + escapeHtml(cfg.trans.today) + '</span>';
                    html += '</div><div class="small">';
                    (byDate[date] || []).forEach(function (iv) {
                        var bg = iv.status === 'completed' ? '#d4edda' : (iv.status === 'cancelled' ? '#f8d7da' : '#fff3cd');
                        html += '<div class="mb-1 p-1 rounded" style="background:' + bg + ';font-size:0.75rem;">' +
                            '<div class="fw-bold" style="color:#22262E">' + escapeHtml(iv.cv_name || cfg.trans.unknown) + '</div>' +
                            '<div class="" style="color:#22262E">' + escapeHtml(iv.time || '') + '</div>' +
                            '<div class="" style="color:#22262E">' + escapeHtml(iv.interview_type || '') + '</div></div>';
                    });
                    html += '</div>';
                }
                html += '</td>';
            }
            html += '</tr>';
        }
        $('#hrCalBody').html(html);
    }

    function load() {
        $.ajax({
            url: cfg.routes.data,
            method: 'GET',
            data: { month: state.month, type: state.type, status: state.status },
            success: function (res) {
                state.month = res.month || state.month;
                buildCalendar(state.month, res.interviews || []);
            },
            error: function () {
                $('#hrCalBody').html('<tr><td colspan="7" class="text-center text-danger py-4">Error</td></tr>');
            }
        });
    }

    $(function () {
        if (!state.month) {
            var n = new Date();
            state.month = n.getFullYear() + '-' + pad(n.getMonth() + 1);
        }
        load();
        $('#hrCalPrev').on('click', function () { shiftMonth(-1); });
        $('#hrCalNext').on('click', function () { shiftMonth(1); });
        $('#hrCalToday').on('click', function () {
            var n = new Date();
            state.month = n.getFullYear() + '-' + pad(n.getMonth() + 1);
            load();
        });
        $('#hrCalTypeFilter').on('change', function () {
            state.type = $(this).val() || '';
            load();
        });
        $('#hrCalStatusFilter').on('change', function () {
            state.status = $(this).val() || '';
            load();
        });
        $('#hrCalClearBtn').on('click', function () {
            state.type   = '';
            state.status = '';
            $('#hrCalTypeFilter').val('');
            $('#hrCalStatusFilter').val('');
            load();
        });
    });
})(jQuery);
