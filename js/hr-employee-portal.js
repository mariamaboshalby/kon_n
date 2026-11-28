/**
 * HR — Employee Portal admin (requests inbox, devices, employee access, suspicious punches, settings)
 * Config: window._hrPortalConfig
 */
(function ($) {
    'use strict';
    var cfg = window._hrPortalConfig;
    if (!cfg || !$) return;

    var panes = {
        requests: { url: cfg.routes.requestsData, render: renderRequestRow, cols: 8 },
        devices: { url: cfg.routes.devicesData, render: renderDeviceRow, cols: 9 },
        employees: { url: cfg.routes.employeesData, render: renderEmployeeRow, cols: 10 },
        suspicious: { url: cfg.routes.suspiciousData, render: renderSuspiciousRow, cols: 11 }
    };
    var state = {
        active: cfg.activeTab,
        submitting: false,
        currentRequest: null,
        passwordEmployeeId: null,
        filterTimer: null,
        lists: {}
    };
    Object.keys(panes).forEach(function (k) {
        state.lists[k] = { page: 1, hasMore: false, loading: false, loaded: false, count: 0, xhr: null };
    });

    function url(name, id) { return String(cfg.routes[name]).replace('__ID__', encodeURIComponent(id)); }
    function esc(v) { return $('<div>').text(v == null ? '' : String(v)).html(); }
    function csrf() { return $('meta[name="csrf-token"]').attr('content') || cfg.csrfToken || ''; }

    function showAlert(type, message) {
        $('#hrpAlert').removeClass('d-none alert-success alert-danger')
            .addClass('alert alert-dismissible fade show alert-' + (type === 'success' ? 'success' : 'danger'))
            .html(esc(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function errorMessage(xhr) {
        var res = xhr && xhr.responseJSON;
        if (res) {
            if (res.errors) {
                var first = Object.keys(res.errors)[0];
                if (first && res.errors[first][0]) return res.errors[first][0];
            }
            if (res.message) return res.message;
        }
        return cfg.trans.error;
    }

    function ajax(method, target, data, opts) {
        var settings = $.extend({
            url: target,
            method: method,
            data: data,
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }, opts || {});
        return $.ajax(settings);
    }

    /** Guard: طلب واحد فقط في نفس اللحظة لكل إجراءات الحفظ/القرار */
    function setSubmitting(on, $buttons) {
        state.submitting = !!on;
        ($buttons || $('.hrp-decide, #hrpSettingsSave, #hrpPasswordSave, .hrp-bulk, .hrp-dev-action, .hrp-emp-toggle')).prop('disabled', !!on);
    }

    function badge(cls, text) { return '<span class="badge ' + cls + '">' + esc(text) + '</span>'; }
    function statusBadge(status, label) {
        var map = { pending: 'bg-warning text-dark', approved: 'bg-success', rejected: 'bg-danger', cancelled: 'bg-secondary', revoked: 'bg-danger' };
        return badge(map[status] || 'bg-secondary', label || status);
    }

    // ---------------- Lists (lazy loading) ----------------
    function filtersFor(pane) {
        var params = {};
        $('.hrp-filter[data-pane="' + pane + '"]').each(function () {
            var v = $(this).val();
            if (v !== '' && v != null) params[this.name] = v;
        });
        return params;
    }

    function loadPane(pane, reset) {
        var cfgPane = panes[pane];
        var list = state.lists[pane];
        if (!cfgPane) return;
        if (reset) {
            if (list.xhr) list.xhr.abort();
            list.page = 1; list.hasMore = false; list.count = 0; list.loading = false;
            $('[data-hrp-body="' + pane + '"]').html('<tr><td colspan="' + cfgPane.cols + '" class="text-center py-4 text-muted">' + esc(cfg.trans.loading) + '</td></tr>');
        }
        if (list.loading) return;
        list.loading = true;

        var params = $.extend(filtersFor(pane), { page: list.page });
        list.xhr = $.get(cfgPane.url, params).done(function (res) {
            var $body = $('[data-hrp-body="' + pane + '"]');
            if (list.page === 1) $body.empty();
            var rows = res.data || [];
            rows.forEach(function (row) {
                list.count++;
                $body.append(cfgPane.render(row, list.count));
            });
            if (list.page === 1 && !rows.length) {
                $body.html('<tr><td colspan="' + cfgPane.cols + '" class="text-center py-4 text-muted">' + esc(cfg.trans.empty) + '</td></tr>');
            }
            list.hasMore = !!res.has_more;
            list.page = res.next_page || list.page;
            list.loaded = true;
            $('[data-hrp-more="' + pane + '"]').toggleClass('d-none', !list.hasMore);
            updateFooter();
        }).fail(function (xhr) {
            if (xhr.statusText !== 'abort') showAlert('error', errorMessage(xhr));
        }).always(function () {
            list.loading = false;
            list.xhr = null;
        });
    }

    function updateFooter() {
        var list = state.lists[state.active];
        $('#hrpFooterLoaded').text(list ? list.count : 0);
    }

    function switchTab(tab) {
        state.active = tab;
        $('#hrpTabs [data-hrp-tab]').removeClass('active');
        $('#hrpTabs [data-hrp-tab="' + tab + '"]').addClass('active');
        $('.hrp-pane').addClass('d-none');
        $('.hrp-pane[data-hrp-pane="' + tab + '"]').removeClass('d-none');
        if (window.history && window.history.replaceState) {
            var u = new URL(window.location.href);
            u.searchParams.set('tab', tab);
            window.history.replaceState(null, '', u.toString());
        }
        if (panes[tab] && !state.lists[tab].loaded) loadPane(tab, true);
        updateFooter();
    }

    // ---------------- Row renderers ----------------
    function renderRequestRow(r, n) {
        return '<tr>' +
            '<td>' + n + '</td>' +
            '<td>' + esc(r.employee ? r.employee.name : '') + (r.manager ? '<div class="small text-muted">' + esc(cfg.trans.manager) + ': ' + esc(r.manager) + '</div>' : '') + '</td>' +
            '<td>' + esc(r.type_label) + '</td>' +
            '<td class="small">' + esc(r.summary) + '</td>' +
            '<td>' + esc(r.stage_label) + '</td>' +
            '<td>' + statusBadge(r.status, r.status_label) + '</td>' +
            '<td class="small">' + esc(r.created_at) + '</td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-primary hrp-req-view" data-id="' + r.id + '"><i class="las la-eye"></i> ' + esc(cfg.trans.view) + '</button></td>' +
            '</tr>';
    }

    function renderDeviceRow(d, n) {
        var actions = '';
        if (cfg.can.editDevices) {
            if (d.status !== 'approved') {
                actions += '<button type="button" class="btn btn-sm btn-success hrp-dev-action me-1" data-action="approve" data-id="' + d.id + '"><i class="las la-check"></i> ' + esc(cfg.trans.approve) + '</button>';
            }
            if (d.status !== 'revoked') {
                actions += '<button type="button" class="btn btn-sm btn-outline-danger hrp-dev-action" data-action="revoke" data-id="' + d.id + '"><i class="las la-ban"></i> ' + esc(cfg.trans.revoke) + '</button>';
            }
        }
        return '<tr>' +
            '<td>' + n + '</td>' +
            '<td>' + esc(d.employee) + '<div class="small text-muted">' + esc(d.finger_print_id) + '</div></td>' +
            '<td>' + esc(d.model) + '</td>' +
            '<td>' + esc((d.platform || '') + ' ' + (d.os_version || '')) + '</td>' +
            '<td>' + esc(d.app_version) + '</td>' +
            '<td>' + statusBadge(d.status, d.status_label) + '</td>' +
            '<td class="small">' + esc(d.last_seen_at) + '</td>' +
            '<td class="small">' + esc(d.created_at) + '</td>' +
            '<td class="text-nowrap">' + actions + '</td>' +
            '</tr>';
    }

    function renderEmployeeRow(e, n) {
        var pw = !e.has_password ? badge('bg-secondary', cfg.trans.passwordNotSet)
            : (e.password_changed ? badge('bg-success', cfg.trans.passwordSet) : badge('bg-warning text-dark', cfg.trans.passwordTemporary));
        var toggle = '<div class="form-check form-switch m-0"><input class="form-check-input hrp-emp-toggle" type="checkbox" data-id="' + e.id + '"' +
            (e.portal_enabled ? ' checked' : '') + (cfg.can.editPortal ? '' : ' disabled') + '></div>';
        var actions = cfg.can.editPortal
            ? '<button type="button" class="btn btn-sm btn-outline-primary hrp-emp-password" data-id="' + e.id + '" data-name="' + esc(e.name) + '"><i class="las la-key"></i> ' + esc(cfg.trans.resetPassword) + '</button>'
            : '';
        return '<tr>' +
            '<td><input type="checkbox" class="form-check-input hrp-emp-check" value="' + e.id + '"></td>' +
            '<td>' + n + '</td>' +
            '<td>' + esc(e.name) + '</td>' +
            '<td>' + esc(e.finger_print_id) + '</td>' +
            '<td dir="ltr">' + esc(e.phone) + '</td>' +
            '<td>' + esc(e.department) + '</td>' +
            '<td>' + toggle + '</td>' +
            '<td>' + pw + '</td>' +
            '<td>' + esc(e.devices) + '</td>' +
            '<td>' + actions + '</td>' +
            '</tr>';
    }

    function renderSuspiciousRow(a, n) {
        var reasons = (a.reasons || []).map(function (r) { return badge('bg-danger me-1', r); }).join('');
        if (a.is_remote) reasons += badge('bg-info text-dark', cfg.trans.remote);
        return '<tr>' +
            '<td>' + n + '</td>' +
            '<td>' + esc(a.employee) + '</td>' +
            '<td>' + esc(a.type_label) + '</td>' +
            '<td>' + esc(a.date) + '</td>' +
            '<td>' + esc(a.time) + '</td>' +
            '<td>' + esc(a.distance == null ? '—' : a.distance) + '</td>' +
            '<td>' + esc(a.accuracy == null ? '—' : a.accuracy) + '</td>' +
            '<td>' + reasons + '</td>' +
            '<td class="small">' + esc(a.device) + '</td>' +
            '<td>' + (a.has_selfie ? '<a href="' + esc(url('selfie', a.id)) + '" target="_blank" rel="noopener"><i class="las la-image fs-5"></i></a>' : '—') + '</td>' +
            '<td>' + (a.map_url ? '<a href="' + esc(a.map_url) + '" target="_blank" rel="noopener"><i class="las la-map-marker fs-5"></i></a>' : '—') + '</td>' +
            '</tr>';
    }

    // ---------------- Request detail + decision ----------------
    function openRequest(id) {
        $.get(url('requestShow', id)).done(function (res) {
            var r = res.data;
            state.currentRequest = r;
            var html = '<div class="d-flex flex-wrap gap-3 mb-2">' +
                '<div><strong>' + esc(r.employee ? r.employee.name : '') + '</strong></div>' +
                '<div>' + esc(r.type_label) + '</div>' +
                '<div>' + statusBadge(r.status, r.status_label) + '</div>' +
                '<div class="text-muted small">' + esc(r.stage_label) + ' — ' + esc(r.created_at) + '</div>' +
                '</div><table class="table table-sm table-bordered mb-0"><tbody>';
            (r.details || []).forEach(function (d) {
                html += '<tr><th class="w-25 bg-light">' + esc(d.label) + '</th><td>' + esc(d.value) + '</td></tr>';
            });
            html += '</tbody></table>';
            $('#hrpReqDetails').html(html);

            var $tl = $('#hrpReqTimeline').empty();
            (r.timeline || []).forEach(function (t) {
                $tl.append('<li class="list-group-item px-0">' +
                    '<strong>' + esc(t.action_label) + '</strong> — ' + esc(t.stage_label) +
                    (t.actor ? ' — ' + esc(t.actor) : '') +
                    ' <span class="text-muted">' + esc(t.at) + '</span>' +
                    (t.note ? '<div class="text-muted">' + esc(t.note) + '</div>' : '') +
                    '</li>');
            });

            var pending = r.status === 'pending';
            var $form = $('#hrpDecisionForm');
            if ($form.length) {
                $form[0].reset();
                $form.toggleClass('d-none', !pending);
                $('#hrpDocumentWrap').toggleClass('d-none', r.type !== 'document');
            }
            $('.hrp-decide').toggleClass('d-none', !pending || !cfg.can.editRequests);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('hrpRequestModal')).show();
        }).fail(function (xhr) { showAlert('error', errorMessage(xhr)); });
    }

    function decide(decision) {
        if (state.submitting || !state.currentRequest) return;
        var $form = $('#hrpDecisionForm');
        var note = $.trim($form.find('[name="note"]').val() || '');
        if (decision === 'reject' && !note) {
            showModalError(cfg.trans.rejectReasonRequired);
            return;
        }
        var fd = new FormData();
        fd.append('note', note);
        var fileInput = $form.find('[name="document"]')[0];
        if (decision === 'approve' && fileInput && fileInput.files && fileInput.files[0]) {
            fd.append('document', fileInput.files[0]);
        }

        setSubmitting(true);
        ajax('POST', url(decision === 'approve' ? 'requestApprove' : 'requestReject', state.currentRequest.id), fd, {
            processData: false,
            contentType: false
        }).done(function (res) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('hrpRequestModal')).hide();
            showAlert('success', res.message);
            loadPane('requests', true);
        }).fail(function (xhr) {
            showModalError(errorMessage(xhr));
        }).always(function () { setSubmitting(false); });
    }

    function showModalError(message) {
        var $box = $('#hrpReqDetails').find('.hrp-modal-error');
        if (!$box.length) {
            $box = $('<div class="alert alert-danger py-2 small hrp-modal-error"></div>');
            $('#hrpReqDetails').prepend($box);
        }
        $box.text(message);
    }

    // ---------------- Events ----------------
    $(document).on('click', '#hrpTabs [data-hrp-tab]', function () { switchTab($(this).data('hrp-tab')); });

    $(document).on('click', '.hrp-filter-toggle', function () { $($(this).data('target')).slideToggle(200); });

    $(document).on('change', '.hrp-filter', function () { loadPane($(this).data('pane'), true); });
    $(document).on('input', 'input.hrp-filter[type="text"]', function () {
        var pane = $(this).data('pane');
        clearTimeout(state.filterTimer);
        state.filterTimer = setTimeout(function () { loadPane(pane, true); }, 400);
    });

    $(document).on('click', '.hrp-load-more', function () { loadPane($(this).data('pane'), false); });

    $(window).on('scroll.hrpPortal', function () {
        var list = state.lists[state.active];
        if (!list || !list.hasMore || list.loading) return;
        if ($(window).scrollTop() + $(window).height() >= $(document).height() - 200) {
            loadPane(state.active, false);
        }
    });

    $(document).on('click', '.hrp-req-view', function () { openRequest($(this).data('id')); });
    $(document).on('click', '.hrp-decide', function () { decide($(this).data('decision')); });

    $(document).on('click', '.hrp-dev-action', function () {
        if (state.submitting) return;
        var action = $(this).data('action');
        var id = $(this).data('id');
        if (!window.confirm(action === 'approve' ? cfg.trans.confirmApproveDevice : cfg.trans.confirmRevoke)) return;
        setSubmitting(true);
        ajax('POST', url(action === 'approve' ? 'deviceApprove' : 'deviceRevoke', id), {})
            .done(function (res) {
                showAlert('success', res.message);
                loadPane('devices', true);
            })
            .fail(function (xhr) { showAlert('error', errorMessage(xhr)); })
            .always(function () { setSubmitting(false); });
    });

    $(document).on('change', '.hrp-emp-toggle', function () {
        var $cb = $(this);
        var enabled = $cb.is(':checked');
        if (state.submitting) { $cb.prop('checked', !enabled); return; }
        setSubmitting(true);
        ajax('POST', url('employeeToggle', $cb.data('id')), { enabled: enabled ? 1 : 0 })
            .done(function (res) { showAlert('success', res.message); })
            .fail(function (xhr) {
                $cb.prop('checked', !enabled);
                showAlert('error', errorMessage(xhr));
            })
            .always(function () { setSubmitting(false); });
    });

    $(document).on('change', '#hrpEmpCheckAll', function () {
        $('.hrp-emp-check').prop('checked', $(this).is(':checked'));
    });

    $(document).on('click', '.hrp-bulk', function () {
        if (state.submitting) return;
        var ids = $('.hrp-emp-check:checked').map(function () { return this.value; }).get();
        if (!ids.length) { showAlert('error', cfg.trans.selectFirst); return; }
        setSubmitting(true);
        ajax('POST', cfg.routes.employeesBulk, { ids: ids, enabled: $(this).data('enabled') })
            .done(function (res) {
                showAlert('success', res.message);
                $('#hrpEmpCheckAll').prop('checked', false);
                loadPane('employees', true);
            })
            .fail(function (xhr) { showAlert('error', errorMessage(xhr)); })
            .always(function () { setSubmitting(false); });
    });

    $(document).on('click', '.hrp-emp-password', function () {
        state.passwordEmployeeId = $(this).data('id');
        $('#hrpPasswordName').text($(this).data('name'));
        $('#hrpPasswordForm')[0].reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('hrpPasswordModal')).show();
    });

    $(document).on('submit', '#hrpPasswordForm', function (e) {
        e.preventDefault();
        if (state.submitting || !state.passwordEmployeeId) return;
        setSubmitting(true);
        ajax('POST', url('employeePassword', state.passwordEmployeeId), { password: $(this).find('[name="password"]').val() })
            .done(function (res) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('hrpPasswordModal')).hide();
                showAlert('success', res.message);
                loadPane('employees', true);
            })
            .fail(function (xhr) { showAlert('error', errorMessage(xhr)); })
            .always(function () { setSubmitting(false); });
    });

    $(document).on('submit', '#hrpSettingsForm', function (e) {
        e.preventDefault();
        if (state.submitting || !cfg.can.editPortal) return;
        var $f = $(this);
        var data = { _method: 'PUT' };
        $f.find('input[type="checkbox"]').each(function () { data[this.name] = this.checked ? 1 : 0; });
        $f.find('input[type="text"], select').each(function () { data[this.name] = $(this).val(); });
        setSubmitting(true);
        ajax('POST', cfg.routes.settingsUpdate, data)
            .done(function (res) { showAlert('success', res.message); })
            .fail(function (xhr) { showAlert('error', errorMessage(xhr)); })
            .always(function () { setSubmitting(false); });
    });

    // Enter ينقل للحقل التالي في نموذج الإعدادات بدل الإرسال
    $(document).on('keydown', '#hrpSettingsForm input[type="text"]', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        var $fields = $('#hrpSettingsForm').find('input, select').filter(':visible:not([disabled])');
        var idx = $fields.index(this);
        if (idx > -1 && idx + 1 < $fields.length) $fields.eq(idx + 1).trigger('focus');
    });

    $(function () { switchTab(state.active); });
})(window.jQuery);
