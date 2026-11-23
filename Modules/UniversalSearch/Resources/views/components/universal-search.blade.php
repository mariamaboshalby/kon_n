<style>
    .massar-us-btn:hover i { transform: scale(1.08); transition: transform .2s ease; }
    .massar-us-item:hover { background: rgba(52, 211, 163, 0.05) !important; }
    .massar-us-item.is-active { background: rgba(52, 211, 163, 0.12) !important; }
</style>

<li class="me-3">
    <button type="button"
            id="massarUsOpenBtn"
            class="btn btn-lg transition-base massar-us-btn"
            title="{{ __('بحث شامل') }} (Ctrl+K)"
            style="background: none; border: none; color: #34d3a3; cursor: pointer; padding: 8px 12px;"
            aria-label="{{ __('بحث شامل') }}">
        <i class="fas fa-search fa-2x" style="color: #34d3a3;"></i>
    </button>
</li>

<div class="offcanvas offcanvas-top"
     tabindex="-1"
     id="massarUsOffcanvas"
     aria-labelledby="massarUsLabel"
     style="height: 360px; z-index: 1060;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="massarUsLabel" style="color: #34d3a3;">
            <i class="fas fa-search me-2" style="color: #34d3a3;"></i>
            <i  style="color: #34d3a3;">{{ __('بحث شامل') }}</i>
            
        </h5>
        <div class="d-flex align-items-center gap-2" >
            <i  style="color: #34d3a3;" >Ctrl+K</i>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('إغلاق') }}"></button>
        </div>
    </div>

    <div class="offcanvas-body p-0">
        <div class="p-3 border-bottom">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text"
                       class="form-control"
                       id="massarUsInput"
                       autocomplete="off"
                       placeholder="{{ __('ابحث باسم الشاشة أو الراوت...') }}"
                       aria-label="{{ __('بحث شامل') }}">
            </div>
            <small class="text-muted d-block mt-2" style="font-size:.75rem;">
                {{ __('ابدأ بكتابة حرفين على الأقل — Enter للفتح — Esc للإغلاق') }}
            </small>
        </div>

        <div id="massarUsResults" style="max-height: 260px; overflow:auto;"></div>
    </div>
</div>

<script>
(function (ns) {
    'use strict';

    // Guard: run only once even if component is included multiple times
    if (ns.__initialized) { return; }
    ns.__initialized = true;

    var OFFCANVAS_ID  = 'massarUsOffcanvas';
    var INPUT_ID      = 'massarUsInput';
    var RESULTS_ID    = 'massarUsResults';
    var OPEN_BTN_ID   = 'massarUsOpenBtn';
    var ITEM_ATTR     = 'data-massar-us-item';
    var SEARCH_URL    = '{{ route("universalsearch.search") }}';

    var _offcanvas    = null;
    var _debounce     = null;
    var _reqId        = 0;
    var _activeIdx    = -1;
    var _items        = [];

    /* ── DOM helpers ─────────────────────────────────────── */
    function el(id) { return document.getElementById(id); }

    function offcanvas() {
        if (!_offcanvas) {
            var node = el(OFFCANVAS_ID);
            if (!node) { return null; }
            if (typeof bootstrap === 'undefined' || !bootstrap.Offcanvas) { return null; }
            _offcanvas = new bootstrap.Offcanvas(node);
        }
        return _offcanvas;
    }

    /* ── Public API ──────────────────────────────────────── */
    function open() {
        var oc = offcanvas();
        if (!oc) { return; }
        oc.show();
        setTimeout(function () {
            var input = el(INPUT_ID);
            if (input) { input.focus(); if (input.select) { input.select(); } }
        }, 120);
    }

    function close() {
        var oc = offcanvas();
        if (oc) { oc.hide(); }
    }

    /* ── Rendering ───────────────────────────────────────── */
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setActive(idx) {
        _activeIdx = idx;
        var container = el(RESULTS_ID);
        if (!container) { return; }
        var nodes = container.querySelectorAll('[' + ITEM_ATTR + ']');
        nodes.forEach(function (n) { n.classList.remove('is-active'); });
        if (_activeIdx >= 0 && _activeIdx < nodes.length) {
            nodes[_activeIdx].classList.add('is-active');
            nodes[_activeIdx].scrollIntoView({ block: 'nearest' });
        }
    }

    function render(items) {
        _items     = Array.isArray(items) ? items : [];
        _activeIdx = -1;
        var container = el(RESULTS_ID);
        if (!container) { return; }

        if (!_items.length) {
            container.innerHTML = '<div class="p-3 text-muted small">{{ __("لا توجد نتائج") }}</div>';
            return;
        }

        var html = '';
        _items.forEach(function (item, idx) {
            var title = escHtml(item.title || item.name || '');
            var group = escHtml(item.group || '');
            var uri   = escHtml(item.uri   || '');
            var url   = escHtml(item.url   || '#');

            html += '<a href="' + url + '" '
                  + 'class="d-flex align-items-start px-3 py-2 text-decoration-none border-bottom massar-us-item" '
                  + ITEM_ATTR + '="1" data-massar-us-idx="' + idx + '" '
                  + 'style="color:inherit;">'
                  + '<i class="fas fa-arrow-right me-2 text-muted small" style="margin-top:2px;"></i>'
                  + '<div style="min-width:0;">'
                  + '<div class="small fw-semibold text-dark" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + title + '</div>'
                  + '<div class="text-muted" style="font-size:.75rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'
                  + (group ? '<span class="me-2">' + group + '</span>' : '')
                  + '<span dir="ltr">' + uri + '</span>'
                  + '</div></div></a>';
        });

        container.innerHTML = html;

        container.querySelectorAll('[' + ITEM_ATTR + ']').forEach(function (a) {
            a.addEventListener('mouseenter', function () {
                var i = parseInt(this.getAttribute('data-massar-us-idx') || '-1', 10);
                if (!isNaN(i)) { setActive(i); }
            });
        });
    }

    /* ── Search ──────────────────────────────────────────── */
    function doSearch(query) {
        query = (query || '').trim();
        if (query.length < 2) { render([]); return; }

        var reqId = ++_reqId;

        fetch(SEARCH_URL + '?q=' + encodeURIComponent(query), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (reqId !== _reqId) { return; }
            render(data);
        })
        .catch(function () {
            if (reqId === _reqId) { render([]); }
        });
    }

    /* ── Event wiring ────────────────────────────────────── */
    var _wired = false;
    function wire() {
        if (_wired) { return; }
        _wired = true;

        var input   = el(INPUT_ID);
        var openBtn = el(OPEN_BTN_ID);

        if (openBtn) {
            openBtn.addEventListener('click', open);
        }

        if (!input) { return; }

        input.addEventListener('input', function () {
            var val = input.value;
            clearTimeout(_debounce);
            _debounce = setTimeout(function () { doSearch(val); }, 250);
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                input.value = '';
                render([]);
                close();
                return;
            }
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (_items.length) { setActive(Math.min(_activeIdx + 1, _items.length - 1)); }
                return;
            }
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (_items.length) { setActive(Math.max(_activeIdx - 1, 0)); }
                return;
            }
            if (e.key === 'Enter' && _activeIdx >= 0 && _items[_activeIdx]) {
                e.preventDefault();
                window.location.href = _items[_activeIdx].url;
            }
        });
    }

    /* ── Global Ctrl+K ───────────────────────────────────── */
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            open();
        }
    });

    /* Wait for bootstrap then wire everything */
    function waitForBootstrap(attempts) {
        attempts = attempts || 0;
        if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
            wire();
        } else if (attempts < 50) {
            setTimeout(function () { waitForBootstrap(attempts + 1); }, 100);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { waitForBootstrap(); });
    } else {
        waitForBootstrap();
    }

    /* Expose minimal public API under isolated namespace */
    ns.open  = open;
    ns.close = close;

}(window.__massarUs = window.__massarUs || {}));
</script>
