@extends('admin.dashboard')

@section('sidebar')
    @if ($section === 'purchases')
        @include('components.sidebar.purchases-invoices')
    @else
        @include('components.sidebar.sales-invoices')
    @endif
@endsection

@section('styles')
<style>
/* ================================================================
   LAYOUT: savebar | [panel | right-col]
   right-col: preview-box (top, fixed) | editor (flex-1, scrollable)
================================================================ */
.bld-page  { display:flex; flex-direction:column; gap:.65rem; height:calc(100vh - 155px); min-height:580px; }

/* ── save bar ── */
.bld-savebar {
    display:flex; align-items:center; justify-content:space-between; flex-shrink:0;
    background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:.5rem 1rem; gap:.75rem;
}
.bld-savebar__title { font-weight:700; font-size:.88rem; display:flex; align-items:center; gap:.4rem; color:#1e293b; }
.bld-savebar__actions { display:flex; gap:.5rem; }
.bld-savebar .btn { font-size:.81rem; padding:.35rem .8rem; display:flex; align-items:center; gap:.3rem; }

/* ── main row ── */
.bld-main { display:flex; gap:.65rem; flex:1; min-height:0; overflow:hidden; }

/* ── LEFT PANEL ── */
.bld-panel {
    width:280px; flex-shrink:0; background:#fff; border:1px solid #e2e8f0; border-radius:12px;
    display:flex; flex-direction:column; overflow:hidden;
}
.bld-panel__head {
    padding:.55rem .9rem; background:#f8fafc; border-bottom:1px solid #e2e8f0;
    font-weight:700; font-size:.83rem; display:flex; align-items:center; gap:.35rem; color:#374151; flex-shrink:0;
}
.bld-panel__body { flex:1; overflow-y:auto; padding:.65rem; }
.bld-paper-row { display:flex; gap:.45rem; margin-bottom:.65rem; }
.bld-paper-row .form-label { font-size:.74rem; margin-bottom:2px; }
.bld-paper-row .form-control { font-size:.79rem; }
.bld-section-lbl {
    font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
    color:#94a3b8; margin:.55rem 0 .3rem;
}
.bld-add-btns { display:grid; grid-template-columns:1fr 1fr; gap:.3rem; margin-bottom:.55rem; }
.bld-add-btns .btn { font-size:.74rem; padding:.28rem .4rem; }
.bld-el-list { display:flex; flex-direction:column; gap:.28rem; min-height:28px; }
.bld-el-item {
    border:1.5px solid #e2e8f0; border-radius:7px; padding:.38rem .55rem;
    cursor:pointer; transition:border-color .12s,background .12s;
    display:flex; align-items:center; gap:.38rem;
}
.bld-el-item:hover  { border-color:#6366f1; background:#f5f3ff; }
.bld-el-item.active { border-color:#6366f1; background:#ede9fe; }
.bld-el-item__icon  { font-size:.85rem; color:#6366f1; flex-shrink:0; }
.bld-el-item__name  { font-size:.76rem; font-weight:600; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.bld-el-item__del   { color:#ef4444; font-size:.78rem; cursor:pointer; flex-shrink:0; padding:2px 4px; border-radius:4px; }
.bld-el-item__del:hover { background:#fee2e2; }
.bld-empty { text-align:center; color:#94a3b8; padding:.9rem .5rem; font-size:.76rem; }
.bld-empty i { font-size:1.5rem; display:block; margin-bottom:.28rem; }

/* props */
.bld-props { border-top:1px solid #e2e8f0; padding:.5rem .6rem; overflow-y:auto; flex-shrink:0; display:none; }
.bld-props.visible { display:block; }
.bld-props__head { font-weight:700; font-size:.76rem; margin-bottom:.4rem; display:flex; align-items:center; gap:.3rem; color:#374151; }
.bld-props .form-label { font-size:.66rem; margin-bottom:1px; color:#64748b; line-height:1.2; }
.bld-props .form-control { font-size:.73rem; padding:.16rem .28rem; height:25px; line-height:1; }
.bld-props-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:.25rem; margin-bottom:.35rem; }
.bld-props-full { margin-bottom:.35rem; }
.bld-props-full .form-control { height:27px; font-size:.76rem; padding:.2rem .38rem; }
.bld-props-checks { display:flex; gap:.65rem; margin-bottom:.35rem; align-items:center; }
.bld-props-checks .form-check { margin:0; }
.bld-props-checks .form-check-input { width:.78rem; height:.78rem; margin-top:1px; }
.bld-props-checks .form-check-label { font-size:.7rem; }

/* ── RIGHT COLUMN ── */
.bld-right { flex:1; display:flex; flex-direction:column; gap:.65rem; min-width:0; overflow:hidden; }

/* ── PREVIEW BOX (top, fixed height) ── */
.bld-preview-box {
    flex-shrink:0; background:#fff; border:1px solid #e2e8f0; border-radius:12px;
    display:flex; flex-direction:column; overflow:hidden;
    height:180px;          /* fixed height */
}
.bld-preview-box__head {
    padding:.45rem .9rem; background:#f8fafc; border-bottom:1px solid #e2e8f0;
    font-weight:700; font-size:.8rem; display:flex; align-items:center; gap:.35rem; flex-shrink:0; color:#374151;
}
.bld-preview-box__body {
    flex:1; display:flex; align-items:center; justify-content:center;
    background:#f1f5f9; overflow:hidden; padding:.5rem;
}
#bld-preview {
    background:#fff; box-shadow:0 2px 10px rgba(0,0,0,.13);
    position:relative; overflow:hidden; flex-shrink:0;
    /* size + scale set by JS */
}
#bld-preview .bld-el-box { cursor:default !important; pointer-events:none; }

/* ── EDITOR CANVAS (flex-1) ── */
.bld-editor-wrap {
    flex:1; background:#f1f5f9; border-radius:12px;
    display:flex; flex-direction:column; align-items:center; justify-content:flex-start;
    padding:1rem; overflow:auto; min-height:0;
}
.bld-area-lbl {
    font-size:.68rem; font-weight:700; color:#64748b; text-transform:uppercase;
    letter-spacing:.05em; margin-bottom:.5rem; align-self:flex-start;
    display:flex; align-items:center; gap:.28rem;
}
#bld-paper {
    background:#fff; box-shadow:0 4px 20px rgba(0,0,0,.12);
    position:relative; overflow:hidden; flex-shrink:0;
}

/* ── elements on canvas ── */
.bld-el-box {
    position:absolute; box-sizing:border-box; user-select:none;
    cursor:grab;
}
.bld-el-box:active { cursor:grabbing; }
.bld-el-box.selected { outline:2px solid #6366f1; }
.bld-el-box.inverted { background:#000 !important; color:#fff !important; }
.bld-el-box img { width:100%; height:100%; object-fit:contain; display:block; pointer-events:none; }

/* resize handles (8 dots) */
.bld-handle {
    position:absolute; width:8px; height:8px; background:#6366f1;
    border:1.5px solid #fff; border-radius:50%; box-shadow:0 0 0 1px #6366f1;
    z-index:10;
}
.bld-handle[data-h="nw"] { top:-4px;  left:-4px;  cursor:nw-resize; }
.bld-handle[data-h="n"]  { top:-4px;  left:calc(50% - 4px); cursor:n-resize; }
.bld-handle[data-h="ne"] { top:-4px;  right:-4px; cursor:ne-resize; }
.bld-handle[data-h="e"]  { top:calc(50% - 4px); right:-4px; cursor:e-resize; }
.bld-handle[data-h="se"] { bottom:-4px; right:-4px; cursor:se-resize; }
.bld-handle[data-h="s"]  { bottom:-4px; left:calc(50% - 4px); cursor:s-resize; }
.bld-handle[data-h="sw"] { bottom:-4px; left:-4px; cursor:sw-resize; }
.bld-handle[data-h="w"]  { top:calc(50% - 4px); left:-4px; cursor:w-resize; }

/* inline text editing */
.bld-el-box .bld-inline-edit {
    width:100%; height:100%; border:none; outline:none; background:transparent;
    font:inherit; color:inherit; padding:0; margin:0; resize:none;
    cursor:text; display:block;
}
</style>
@endsection

@section('content')
@include('components.breadcrumb', [
    'title' => __('invoices::barcode_label.page_title'),
    'items' => [
        ['label' => __('invoices::invoices.dashboard'), 'url' => route('admin.dashboard')],
        ['label' => $section === 'purchases' ? __('invoices::barcode_label.purchases_section') : __('invoices::barcode_label.sales_section')],
        ['label' => __('invoices::barcode_label.page_title')],
    ],
])

<div class="bld-page" id="bld-app">

    {{-- SAVE BAR --}}
    <div class="bld-savebar">
        <div class="bld-savebar__title">
            <i class="las la-tag text-primary"></i>
            {{ __('invoices::barcode_label.page_title') }}
            <span class="badge bg-light text-secondary fw-normal ms-1" style="font-size:.68rem;" id="bld-size-badge">60 × 40 mm</span>
        </div>
        <div class="bld-savebar__actions">
            <button class="btn btn-outline-secondary" id="btn-print">
                <i class="las la-print"></i> {{ __('invoices::barcode_label.print_preview') }}
            </button>
            <button class="btn btn-success" id="btn-save">
                <i class="las la-save"></i> {{ __('invoices::barcode_label.save_settings') }}
            </button>
        </div>
    </div>

    {{-- MAIN ROW --}}
    <div class="bld-main">

        {{-- LEFT PANEL --}}
        <div class="bld-panel">
            <div class="bld-panel__head">
                <i class="las la-sliders-h text-primary"></i>
                {{ __('invoices::barcode_label.paper_settings') }}
            </div>
            <div class="bld-panel__body">
                <div class="bld-paper-row">
                    <div class="flex-fill">
                        <label class="form-label">{{ __('invoices::barcode_label.paper_width') }}</label>
                        <input type="number" id="inp-pw" class="form-control form-control-sm" value="60" min="10" max="500">
                    </div>
                    <div class="flex-fill">
                        <label class="form-label">{{ __('invoices::barcode_label.paper_height') }}</label>
                        <input type="number" id="inp-ph" class="form-control form-control-sm" value="40" min="10" max="500">
                    </div>
                </div>

                <div class="bld-section-lbl">{{ __('invoices::barcode_label.elements') }}</div>
                <div class="bld-add-btns">
                    <button class="btn btn-outline-primary btn-sm" id="btn-add-text">
                        <i class="las la-font me-1"></i>{{ __('invoices::barcode_label.add_text') }}
                    </button>
                    <button class="btn btn-outline-success btn-sm" id="btn-add-img">
                        <i class="las la-image me-1"></i>{{ __('invoices::barcode_label.add_image') }}
                    </button>
                    <button class="btn btn-outline-warning btn-sm" id="btn-add-barcode">
                        <i class="las la-barcode me-1"></i>{{ __('invoices::barcode_label.add_barcode') }}
                    </button>
                    <button class="btn btn-outline-info btn-sm" id="btn-add-qr">
                        <i class="las la-qrcode me-1"></i>{{ __('invoices::barcode_label.add_qr') }}
                    </button>
                </div>
                <input type="file" accept="image/png" id="inp-img-file" class="d-none">

                <div class="bld-el-list" id="bld-el-list">
                    <div class="bld-empty" id="bld-empty-msg">
                        <i class="las la-layer-group"></i>
                        {{ __('invoices::barcode_label.no_elements') }}
                    </div>
                </div>
            </div>

            {{-- Props --}}
            <div class="bld-props" id="bld-props">
                <div class="bld-props__head">
                    <i class="las la-cog text-primary"></i>
                    {{ __('invoices::barcode_label.element_settings') }}
                </div>
                <div class="bld-props-full" id="props-content-row">
                    <label class="form-label">{{ __('invoices::barcode_label.content') }}</label>
                    <input type="text" id="props-content" class="form-control">
                </div>
                <div class="bld-props-grid">
                    <div>
                        <label class="form-label">{{ __('invoices::barcode_label.width') }}</label>
                        <input type="number" id="props-w" class="form-control" min="1">
                    </div>
                    <div>
                        <label class="form-label">{{ __('invoices::barcode_label.height') }}</label>
                        <input type="number" id="props-h" class="form-control" min="1">
                    </div>
                    <div>
                        <label class="form-label">{{ __('invoices::barcode_label.margin_top') }}</label>
                        <input type="number" id="props-top" class="form-control" min="0">
                    </div>
                    <div>
                        <label class="form-label">{{ __('invoices::barcode_label.margin_left') }}</label>
                        <input type="number" id="props-left" class="form-control" min="0">
                    </div>
                    <div id="props-fs-cell" style="display:none;">
                        <label class="form-label">{{ __('invoices::barcode_label.font_size') }}</label>
                        <input type="number" id="props-fs" class="form-control" min="6" max="72">
                    </div>
                </div>
                <div class="bld-props-checks">
                    <div class="form-check" id="props-bold-cell" style="display:none;">
                        <input class="form-check-input" type="checkbox" id="props-bold">
                        <label class="form-check-label" for="props-bold">{{ __('invoices::barcode_label.bold') }}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="props-invert">
                        <label class="form-check-label" for="props-invert">{{ __('invoices::barcode_label.invert') }}</label>
                    </div>
                </div>
                <button class="btn btn-danger btn-sm w-100" id="btn-del-el">
                    <i class="las la-trash me-1"></i>{{ __('invoices::barcode_label.delete_element') }}
                </button>
            </div>
        </div>

        {{-- RIGHT COLUMN --}}
        <div class="bld-right">

            {{-- PREVIEW BOX (top, fixed) --}}
            <div class="bld-preview-box">
                <div class="bld-preview-box__head">
                    <i class="las la-eye text-primary"></i>
                    {{ __('invoices::barcode_label.preview') }}
                    <span class="text-muted fw-normal ms-1" style="font-size:.7rem;">{{ __('invoices::barcode_label.preview_hint') ?? '— معاينة حقيقية' }}</span>
                </div>
                <div class="bld-preview-box__body" id="bld-preview-body">
                    <div id="bld-preview"></div>
                </div>
            </div>

            {{-- EDITOR --}}
            <div class="bld-editor-wrap">
                <div class="bld-area-lbl">
                    <i class="las la-pencil-alt"></i>
                    {{ __('invoices::barcode_label.editor') }}
                    <span class="text-muted fw-normal ms-1" style="font-size:.65rem; text-transform:none;">
                        {{ __('invoices::barcode_label.editor_hint') ?? '— اسحب للتحريك • اسحب الزوايا للتكبير • دبل-كليك للتعديل' }}
                    </span>
                </div>
                <div id="bld-paper"></div>
            </div>

        </div>{{-- /bld-right --}}
    </div>{{-- /bld-main --}}
</div>

<iframe id="bld-print-frame" style="display:none;"></iframe>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
(function () {
'use strict';

const MM = 3.7795275591;
const URLS = {
    config : '{{ route('barcode-label.config') }}',
    save   : '{{ route('barcode-label.save') }}',
    upload : '{{ route('barcode-label.upload-image') }}',
    csrf   : '{{ csrf_token() }}',
};
const LABELS = {
    image : '{{ __('invoices::barcode_label.image_element') }}',
    saved : '{{ __('invoices::barcode_label.saved_successfully') }}',
};
const HANDLES = ['nw','n','ne','e','se','s','sw','w'];

/* ── state ── */
let paper       = { width: 60, height: 40 };
let elements    = [];
let selectedIdx = null;

/* ── DOM ── */
const $        = id => document.getElementById(id);
const paperEl  = $('bld-paper');
const prevEl   = $('bld-preview');
const prevBody = $('bld-preview-body');
const elList   = $('bld-el-list');
const emptyMsg = $('bld-empty-msg');
const propsEl  = $('bld-props');
const badge    = $('bld-size-badge');
const btnSave  = $('btn-save');
const btnPrint = $('btn-print');
const pContent = $('props-content');
const pCRow    = $('props-content-row');
const pW       = $('props-w');
const pH       = $('props-h');
const pTop     = $('props-top');
const pLeft    = $('props-left');
const pFs      = $('props-fs');
const pBold    = $('props-bold');
const pInvert  = $('props-invert');

const mm  = v => v * MM;
const rpx = v => parseFloat((v / MM).toFixed(2));

/* ════════════════════════════════════════
   RENDER
════════════════════════════════════════ */
function renderAll() {
    badge.textContent = paper.width + ' × ' + paper.height + ' mm';
    /* editor */
    paperEl.style.width  = mm(paper.width)  + 'px';
    paperEl.style.height = mm(paper.height) + 'px';
    buildCanvas(paperEl, true);
    /* preview */
    const maxW  = prevBody.clientWidth  - 16 || 160;
    const maxH  = prevBody.clientHeight - 16 || 120;
    const scale = Math.min(1, maxW / mm(paper.width), maxH / mm(paper.height));
    prevEl.style.width           = mm(paper.width)  + 'px';
    prevEl.style.height          = mm(paper.height) + 'px';
    prevEl.style.transform       = 'scale(' + scale + ')';
    prevEl.style.transformOrigin = 'top left';
    buildCanvas(prevEl, false);
    /* list + props */
    buildList();
    buildProps();
}

/* ── build elements inside a container ── */
function buildCanvas(container, interactive) {
    container.querySelectorAll('.bld-el-box').forEach(n => n.remove());
    elements.forEach(function (el, idx) {
        const box = makeBox(el, idx, interactive);
        container.appendChild(box);
    });
}

function makeBox(el, idx, interactive) {
    const box = document.createElement('div');
    box.className = 'bld-el-box'
        + (el.invert ? ' inverted' : '')
        + (interactive && selectedIdx === idx ? ' selected' : '');
    box.dataset.idx = idx;
    box.style.cssText =
        'left:'   + mm(el.left)   + 'px;' +
        'top:'    + mm(el.top)    + 'px;' +
        'width:'  + mm(el.width)  + 'px;' +
        'height:' + mm(el.height) + 'px;';

    fillBox(box, el);

    if (interactive) {
        /* single click → select */
        box.addEventListener('mousedown', function (e) {
            if (e.target.classList.contains('bld-handle')) return;
            if (e.target.classList.contains('bld-inline-edit')) return;
            e.stopPropagation();
            selectEl(idx);
            startDrag(e, idx, box);
        });

        /* double-click → inline edit (text only) */
        if (el.type === 'text') {
            box.addEventListener('dblclick', function (e) {
                e.stopPropagation();
                startInlineEdit(box, idx);
            });
        }

        /* resize handles */
        if (selectedIdx === idx) {
            HANDLES.forEach(function (h) {
                const dot = document.createElement('div');
                dot.className = 'bld-handle';
                dot.dataset.h = h;
                dot.addEventListener('mousedown', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    startResize(e, idx, h, box);
                });
                box.appendChild(dot);
            });
        }
    }
    return box;
}

function fillBox(box, el) {
    /* clear previous content (keep handles) */
    box.querySelectorAll(':not(.bld-handle)').forEach(n => n.remove());
    box.childNodes.forEach(n => { if (n.nodeType === 3) n.remove(); });

    if (el.type === 'text') {
        box.style.fontSize   = (el.fontSize || 10) + 'pt';
        box.style.fontWeight = el.bold ? 'bold' : 'normal';
        box.style.display    = 'flex';
        box.style.alignItems = 'center';
        box.style.overflow   = 'hidden';
        box.style.lineHeight = '1.2';
        const span = document.createElement('span');
        span.style.cssText = 'pointer-events:none;width:100%;overflow:hidden;';
        span.textContent = el.content || '';
        box.insertBefore(span, box.firstChild);

    } else if (el.type === 'image') {
        const img = document.createElement('img');
        img.src = el.src || '';
        box.insertBefore(img, box.firstChild);

    } else if (el.type === 'barcode') {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.style.cssText = 'width:100%;height:100%;pointer-events:none;';
        box.insertBefore(svg, box.firstChild);
        try {
            JsBarcode(svg, el.content || '123456', {
                format: 'CODE128', displayValue: true, fontSize: 10, margin: 2,
                background: el.invert ? '#000' : '#fff',
                lineColor:  el.invert ? '#fff' : '#000',
            });
        } catch (e) {}

    } else if (el.type === 'qr') {
        box.style.display = 'flex';
        box.style.alignItems = 'center';
        box.style.justifyContent = 'center';
        const qrDiv = document.createElement('div');
        qrDiv.style.pointerEvents = 'none';
        box.insertBefore(qrDiv, box.firstChild);
        try {
            new QRCode(qrDiv, {
                text:       el.content || 'QR',
                width:      mm(el.width)  - 4,
                height:     mm(el.height) - 4,
                colorDark:  el.invert ? '#fff' : '#000',
                colorLight: el.invert ? '#000' : '#fff',
            });
        } catch (e) {}
    }
}

/* ════════════════════════════════════════
   DRAG
════════════════════════════════════════ */
function startDrag(e, idx, box) {
    e.preventDefault();
    const sX = e.clientX, sY = e.clientY;
    const sL = elements[idx].left, sT = elements[idx].top;

    function onMove(ev) {
        elements[idx].left = Math.max(0, parseFloat((sL + (ev.clientX - sX) / MM).toFixed(2)));
        elements[idx].top  = Math.max(0, parseFloat((sT + (ev.clientY - sY) / MM).toFixed(2)));
        /* move box directly for smooth feel */
        box.style.left = mm(elements[idx].left) + 'px';
        box.style.top  = mm(elements[idx].top)  + 'px';
        syncProps();
        buildCanvas(prevEl, false);
    }
    function onUp() {
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup',   onUp);
        renderAll();
    }
    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup',   onUp);
}

/* ════════════════════════════════════════
   RESIZE
════════════════════════════════════════ */
function startResize(e, idx, handle, box) {
    e.preventDefault();
    const sX = e.clientX, sY = e.clientY;
    const el = elements[idx];
    const sL = el.left, sT = el.top, sW = el.width, sH = el.height;

    function onMove(ev) {
        const dx = (ev.clientX - sX) / MM;
        const dy = (ev.clientY - sY) / MM;
        if (handle.includes('e'))  { el.width  = Math.max(2, sW + dx); }
        if (handle.includes('s'))  { el.height = Math.max(2, sH + dy); }
        if (handle.includes('w'))  { el.left   = sL + dx; el.width  = Math.max(2, sW - dx); }
        if (handle.includes('n'))  { el.top    = sT + dy; el.height = Math.max(2, sH - dy); }
        box.style.left   = mm(el.left)   + 'px';
        box.style.top    = mm(el.top)    + 'px';
        box.style.width  = mm(el.width)  + 'px';
        box.style.height = mm(el.height) + 'px';
        syncProps();
        buildCanvas(prevEl, false);
    }
    function onUp() {
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup',   onUp);
        renderAll();
    }
    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup',   onUp);
}

/* ════════════════════════════════════════
   INLINE TEXT EDIT
════════════════════════════════════════ */
function startInlineEdit(box, idx) {
    const el = elements[idx];
    /* remove span, add textarea */
    box.querySelectorAll('span').forEach(n => n.remove());
    const ta = document.createElement('textarea');
    ta.className = 'bld-inline-edit';
    ta.value = el.content || '';
    ta.style.fontSize   = (el.fontSize || 10) + 'pt';
    ta.style.fontWeight = el.bold ? 'bold' : 'normal';
    ta.style.color      = el.invert ? '#fff' : '#000';
    box.insertBefore(ta, box.firstChild);
    ta.focus();
    ta.select();

    function commit() {
        el.content = ta.value;
        pContent.value = el.content;
        renderAll();
    }
    ta.addEventListener('blur',  commit);
    ta.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { ta.value = el.content; ta.blur(); }
        e.stopPropagation();
    });
}

/* ════════════════════════════════════════
   ELEMENT LIST
════════════════════════════════════════ */
function elIcon(type) {
    return { text:'las la-font', image:'las la-image', barcode:'las la-barcode', qr:'las la-qrcode' }[type] || 'las la-shapes';
}
function elLabel(el) {
    if (el.type === 'image') return LABELS.image;
    return el.content ? el.content.substring(0, 20) : el.type;
}

function buildList() {
    elList.querySelectorAll('.bld-el-item').forEach(n => n.remove());
    emptyMsg.style.display = elements.length === 0 ? 'block' : 'none';
    elements.forEach(function (el, idx) {
        const item = document.createElement('div');
        item.className = 'bld-el-item' + (selectedIdx === idx ? ' active' : '');
        item.innerHTML =
            '<span class="bld-el-item__icon"><i class="' + elIcon(el.type) + '"></i></span>' +
            '<span class="bld-el-item__name">' + elLabel(el) + '</span>' +
            '<span class="bld-el-item__del"><i class="las la-trash"></i></span>';
        item.addEventListener('click', function () { selectEl(idx); });
        item.querySelector('.bld-el-item__del').addEventListener('click', function (e) {
            e.stopPropagation(); deleteEl(idx);
        });
        elList.appendChild(item);
    });
}

/* ════════════════════════════════════════
   PROPS PANEL
════════════════════════════════════════ */
function buildProps() {
    if (selectedIdx === null || !elements[selectedIdx]) {
        propsEl.classList.remove('visible'); return;
    }
    propsEl.classList.add('visible');
    syncProps();
}

function syncProps() {
    if (selectedIdx === null || !elements[selectedIdx]) return;
    const el = elements[selectedIdx];
    pCRow.style.display = el.type === 'image' ? 'none' : 'block';
    pContent.value = el.content || '';
    pW.value    = parseFloat(el.width.toFixed(1));
    pH.value    = parseFloat(el.height.toFixed(1));
    pTop.value  = parseFloat(el.top.toFixed(1));
    pLeft.value = parseFloat(el.left.toFixed(1));
    $('props-fs-cell').style.display   = el.type === 'text' ? 'block' : 'none';
    $('props-bold-cell').style.display = el.type === 'text' ? 'flex'  : 'none';
    pFs.value       = el.fontSize || 10;
    pBold.checked   = !!el.bold;
    pInvert.checked = !!el.invert;
}

function bindProps() {
    function upd(fn) { return function () { if (selectedIdx === null) return; fn(elements[selectedIdx]); renderAll(); }; }
    pContent.addEventListener('input', upd(function (el) { el.content  = pContent.value; }));
    pW.addEventListener('input',       upd(function (el) { el.width    = parseFloat(pW.value)    || 1; }));
    pH.addEventListener('input',       upd(function (el) { el.height   = parseFloat(pH.value)   || 1; }));
    pTop.addEventListener('input',     upd(function (el) { el.top      = parseFloat(pTop.value)  || 0; }));
    pLeft.addEventListener('input',    upd(function (el) { el.left     = parseFloat(pLeft.value) || 0; }));
    pFs.addEventListener('input',      upd(function (el) { el.fontSize = parseInt(pFs.value)     || 10; }));
    pBold.addEventListener('change',   upd(function (el) { el.bold     = pBold.checked; }));
    pInvert.addEventListener('change', upd(function (el) { el.invert   = pInvert.checked; }));
    $('btn-del-el').addEventListener('click', function () { if (selectedIdx !== null) deleteEl(selectedIdx); });
}

/* ════════════════════════════════════════
   SELECT / ADD / DELETE
════════════════════════════════════════ */
function selectEl(idx) { selectedIdx = idx; renderAll(); }

function addElement(type) {
    elements.push({
        id: Date.now(), type: type,
        content:  type === 'text' ? 'نص' : (type === 'barcode' ? '123456789' : 'https://example.com'),
        width:    type === 'text' ? 30 : 20,
        height:   type === 'text' ? 8  : 20,
        top: 2, left: 2, fontSize: 10, bold: false, invert: false, src: '',
    });
    selectedIdx = elements.length - 1;
    renderAll();
}

function deleteEl(idx) {
    elements.splice(idx, 1);
    selectedIdx = null;
    renderAll();
}

/* ════════════════════════════════════════
   IMAGE UPLOAD
════════════════════════════════════════ */
var imgInput = $('inp-img-file');
$('btn-add-img').addEventListener('click', function () { imgInput.click(); });
imgInput.addEventListener('change', async function () {
    var file = imgInput.files[0];
    if (!file) return;
    var fd = new FormData();
    fd.append('image', file);
    fd.append('_token', URLS.csrf);
    try {
        var res  = await fetch(URLS.upload, { method:'POST', body:fd });
        var data = await res.json();
        if (data.success) {
            elements.push({ id:Date.now(), type:'image', src:data.url, width:20, height:20, top:2, left:2, invert:false, content:'', fontSize:10, bold:false });
            selectedIdx = elements.length - 1;
            renderAll();
        }
    } catch (e) { console.error(e); }
    imgInput.value = '';
});

/* ════════════════════════════════════════
   SAVE / PRINT
════════════════════════════════════════ */
btnSave.addEventListener('click', async function () {
    btnSave.disabled = true;
    btnSave.innerHTML = '<i class="las la-spinner la-spin"></i> ...';
    try {
        var res  = await fetch(URLS.save, {
            method:'POST',
            headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN':URLS.csrf },
            body: JSON.stringify({ paper:paper, elements:elements }),
        });
        var data = await res.json();
        if (data.success) {
            var t = document.createElement('div');
            t.className = 'alert alert-success d-flex align-items-center gap-2 position-fixed bottom-0 end-0 m-3 shadow-sm';
            t.style.cssText = 'z-index:9999;min-width:220px;border-radius:10px;font-size:.84rem;';
            t.innerHTML = '<i class="las la-check-circle fs-5"></i><span>' + data.message + '</span>';
            document.body.appendChild(t);
            setTimeout(function () { t.remove(); }, 3000);
        }
    } catch (e) { console.error(e); }
    btnSave.disabled = false;
    btnSave.innerHTML = '<i class="las la-save"></i> {{ __('invoices::barcode_label.save_settings') }}';
});

btnPrint.addEventListener('click', function () {
    var clone = paperEl.cloneNode(true);
    clone.querySelectorAll('.bld-handle').forEach(n => n.remove());
    clone.querySelectorAll('.selected').forEach(n => n.classList.remove('selected'));
    clone.querySelectorAll('textarea.bld-inline-edit').forEach(function (ta) {
        var span = document.createElement('span');
        span.textContent = ta.value;
        span.style.cssText = ta.style.cssText;
        ta.parentNode.replaceChild(span, ta);
    });
    var html = '<!DOCTYPE html><html><head><style>'
        + '@page{margin:0;size:' + paper.width + 'mm ' + paper.height + 'mm;}'
        + 'body{margin:0;padding:0;}'
        + '#bld-paper{position:relative;width:' + paper.width + 'mm;height:' + paper.height + 'mm;background:#fff;overflow:hidden;}'
        + '.bld-el-box{position:absolute;box-sizing:border-box;}'
        + '.bld-el-box.inverted{background:#000;color:#fff;}'
        + '.bld-el-box img,.bld-el-box svg{width:100%;height:100%;display:block;}'
        + '</style></head><body>' + clone.outerHTML
        + '<script>window.onload=function(){window.print();};<\/script></body></html>';
    var frame = $('bld-print-frame');
    frame.contentDocument.open();
    frame.contentDocument.write(html);
    frame.contentDocument.close();
});

/* ════════════════════════════════════════
   PAPER SIZE + DESELECT
════════════════════════════════════════ */
$('inp-pw').addEventListener('change', function () { paper.width  = parseFloat(this.value) || 60; renderAll(); });
$('inp-ph').addEventListener('change', function () { paper.height = parseFloat(this.value) || 40; renderAll(); });
paperEl.addEventListener('click', function () { selectedIdx = null; renderAll(); });
$('btn-add-text').addEventListener('click',    function () { addElement('text'); });
$('btn-add-barcode').addEventListener('click', function () { addElement('barcode'); });
$('btn-add-qr').addEventListener('click',      function () { addElement('qr'); });

/* ════════════════════════════════════════
   BOOT
════════════════════════════════════════ */
async function loadConfig() {
    try {
        var res  = await fetch(URLS.config);
        var data = await res.json();
        paper    = data.paper    || { width:60, height:40 };
        elements = data.elements || [];
        $('inp-pw').value = paper.width;
        $('inp-ph').value = paper.height;
    } catch (e) { console.error(e); }
}

bindProps();
loadConfig().then(function () { renderAll(); });

})();
</script>
@endsection
