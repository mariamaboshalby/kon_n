@extends('pos::layouts.master')

@push('styles')
<style>
.toggle-card{border:1px solid #dee2e6;border-radius:8px;padding:10px 14px;transition:border-color .2s,background .2s}
.toggle-card:has(input:checked){border-color:#0d6efd;background:#f0f6ff}
.toggle-card .form-check-label{cursor:pointer;font-size:.88rem}
.preview-box{font-family:'Cairo',sans-serif;direction:rtl;background:#fff;border:1px solid #ccc;border-radius:6px;padding:10px;min-height:220px}
.preview-box .p-row{display:flex;justify-content:space-between;font-size:.82em;margin-bottom:2px}
.preview-box .p-sep{border:none;border-top:1px dashed #000;margin:4px 0}
.preview-box .p-sep2{border:none;border-top:2px solid #000;margin:4px 0}
.preview-box .p-item{display:flex;justify-content:space-between;font-size:.78em;border-bottom:1px dotted #ccc;padding-bottom:2px;margin-bottom:3px}
.preview-box .p-center{text-align:center}
.preview-box .p-bold{font-weight:700}
.preview-img-thumb{max-height:60px;border-radius:4px;border:1px solid #dee2e6}
.section-title{font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6c757d;border-bottom:1px solid #e9ecef;padding-bottom:4px;margin-bottom:12px}

/* ── Dark Mode ─────────────────────────────────────────────────────── */
body.dark-mode { background:#111827!important; color:#f9fafb!important; }
body.dark-mode .bg-white,
body.dark-mode .tab-content { background:#1f2937!important; color:#f9fafb!important; }
body.dark-mode .nav-tabs { border-color:#374151; }
body.dark-mode .nav-link { color:#9ca3af; }
body.dark-mode .nav-link.active { background:#1f2937!important; border-color:#374151 #374151 #1f2937!important; color:#f9fafb!important; }
body.dark-mode .nav-link:hover { color:#f9fafb; }
body.dark-mode .form-control,
body.dark-mode .form-select { background:#111827!important; border-color:#374151!important; color:#f9fafb!important; }
body.dark-mode .form-control::placeholder { color:#6b7280; }
body.dark-mode .toggle-card { background:#111827; border-color:#374151; color:#f9fafb; }
body.dark-mode .toggle-card:has(input:checked) { background:#1e3a5f!important; border-color:#3b82f6!important; }
body.dark-mode .toggle-card .form-check-label { color:#d1d5db; }
body.dark-mode .section-title { color:#9ca3af; border-color:#374151; }
body.dark-mode .preview-box { background:#111827!important; border-color:#374151!important; color:#f9fafb!important; }
body.dark-mode .preview-box .p-sep  { border-color:#6b7280; }
body.dark-mode .preview-box .p-sep2 { border-color:#9ca3af; }
body.dark-mode .preview-box .p-item { border-color:#374151; }
body.dark-mode .preview-img-thumb { border-color:#374151; }
body.dark-mode h5, body.dark-mode label { color:#f9fafb; }
body.dark-mode .btn-outline-secondary { color:#9ca3af; border-color:#4b5563; }
body.dark-mode .btn-outline-secondary:hover { background:#374151; color:#f9fafb; }
body.dark-mode .border { border-color:#374151!important; }
body.dark-mode .shadow-sm { box-shadow:0 1px 3px rgba(0,0,0,.4)!important; }
body.dark-mode .card { background:#1f2937!important; border-color:#374151!important; color:#f9fafb!important; }
body.dark-mode .card .text-muted { color:#9ca3af!important; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('pos.settings') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-right me-1"></i> الاعدادات العامة
        </a>
        <h5 class="mb-0 fw-bold"><i class="fas fa-print me-2 text-primary"></i>اعدادات الطباعة</h5>
        <div></div>
    </div>

    {{-- Desktop Agent Download Section --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="las la-desktop fs-4 text-primary"></i>
                    <h6 class="mb-0 fw-bold">{{ __('pos::pos.desktop_agent_title') }}</h6>
                    <span class="badge bg-secondary">{{ __('pos::pos.desktop_agent_version') }} v1.0.0</span>
                </div>
                @if(file_exists(module_path('POS', 'public/downloads/pos-printer-config-setup.exe')))
                    <a href="{{ route('pos.desktop-agent.download') }}" class="btn btn-primary btn-sm">
                        <i class="las la-download me-1"></i> {{ __('pos::pos.desktop_agent_download_btn') }}
                    </a>
                @else
                    <div class="alert alert-warning mb-0 py-1 px-3 small">
                        <i class="las la-exclamation-triangle me-1"></i> {{ __('pos::pos.desktop_agent_unavailable') }}
                    </div>
                @endif
            </div>
            <p class="text-muted small mb-3">{{ __('pos::pos.desktop_agent_description') }}</p>
            <div class="section-title">{{ __('pos::pos.desktop_agent_install_steps') }}</div>
            <ol class="list-group list-group-numbered list-group-flush small">
                <li class="list-group-item border-0 px-0 py-1">{{ __('pos::pos.desktop_agent_step_1') }}</li>
                <li class="list-group-item border-0 px-0 py-1">{{ __('pos::pos.desktop_agent_step_2') }}</li>
                <li class="list-group-item border-0 px-0 py-1">{{ __('pos::pos.desktop_agent_step_3') }}</li>
            </ol>
        </div>
    </div>

    <form id="printSettingsForm" enctype="multipart/form-data">
        @csrf
        {{-- إعدادات الطباعة العامة --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="section-title mb-3">إعدادات الطباعة العامة</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="toggle-card">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="pos_enable_print" name="pos_enable_print" value="1" {{ ($s->pos_enable_print ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="pos_enable_print">
                                    <i class="fas fa-print text-primary me-1"></i> تفعيل الطباعة التلقائية
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="toggle-card">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="pos_silent_print" name="pos_silent_print" value="1" {{ ($s->pos_silent_print ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="pos_silent_print">
                                    <i class="fas fa-volume-mute text-secondary me-1"></i> طباعة صامتة (بدون نافذة معاينة)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-0" id="printTabs">
            <li class="nav-item">
                <button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#tab-cashier" type="button">
                    <i class="fas fa-receipt me-1 text-primary"></i> فاتورة الكاشير
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#tab-kitchen" type="button">
                    <i class="fas fa-utensils me-1 text-danger"></i> ورقة التحضير
                </button>
            </li>
        </ul>

        <div class="tab-content border border-top-0 rounded-bottom bg-white shadow-sm">
            {{-- TAB 1: فاتورة الكاشير --}}
            <div class="tab-pane fade show active p-4" id="tab-cashier">
                <div class="row g-4">
                    <div class="col-lg-7">

                        <div class="section-title">النصوص</div>
                        <div class="row g-2 mb-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">عنوان الفاتورة</label>
                                <input type="text" class="form-control form-control-sm" name="cashier_receipt_title" value="{{ $s->cashier_receipt_title ?? '' }}" placeholder="فاتورة مبيعات">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">عنوان فرعي</label>
                                <input type="text" class="form-control form-control-sm" name="cashier_receipt_subtitle" value="{{ $s->cashier_receipt_subtitle ?? '' }}" placeholder="شكرا لزيارتكم">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">نص التذييل</label>
                                <input type="text" class="form-control form-control-sm" name="cashier_receipt_footer" value="{{ $s->cashier_receipt_footer ?? '' }}" placeholder="نرحب بزيارتكم مرة اخرى">
                            </div>
                        </div>

                        <div class="section-title">الورق والخط</div>
                        <div class="row g-2 mb-4">
                            <div class="col-4">
                                <label class="form-label small fw-semibold">عرض الورق</label>
                                <select class="form-select form-select-sm" name="cashier_paper_width">
                                    @foreach(['58mm','72mm','78mm','80mm'] as $w)
                                        <option value="{{ $w }}" {{ ($s->cashier_paper_width ?? '78mm') === $w ? 'selected' : '' }}>{{ $w }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-semibold">حجم الخط (px)</label>
                                <input type="number" class="form-control form-control-sm" name="cashier_font_size" value="{{ $s->cashier_font_size ?? 11 }}" min="8" max="18">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-semibold">نمط الفاصل</label>
                                <select class="form-select form-select-sm" name="cashier_separator_style">
                                    <option value="dashed" {{ ($s->cashier_separator_style ?? 'dashed') === 'dashed' ? 'selected' : '' }}>متقطع</option>
                                    <option value="solid"  {{ ($s->cashier_separator_style ?? '') === 'solid'  ? 'selected' : '' }}>صلب</option>
                                    <option value="dotted" {{ ($s->cashier_separator_style ?? '') === 'dotted' ? 'selected' : '' }}>منقط</option>
                                </select>
                            </div>
                        </div>

                        <div class="section-title">صور الهيدر والفوتر</div>
                        <div class="row g-2 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">صورة الهيدر</label>
                                <div class="mb-1"><img src="{{ $s->cashier_header_image ?? '' }}" class="preview-img-thumb {{ $s->cashier_header_image ? '' : 'd-none' }}" id="cashier_header_preview"></div>
                                <input type="file" class="form-control form-control-sm" name="cashier_header_image_file" accept="image/*" onchange="previewImg(this,'cashier_header_preview')">
                                <input type="hidden" name="cashier_header_image" value="{{ $s->cashier_header_image ?? '' }}">
                                @if($s->cashier_header_image)
                                <div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="cashier_header_image_remove" value="1" id="rm_ch"><label class="form-check-label small" for="rm_ch">حذف الصورة</label></div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">صورة الفوتر</label>
                                <div class="mb-1"><img src="{{ $s->cashier_footer_image ?? '' }}" class="preview-img-thumb {{ $s->cashier_footer_image ? '' : 'd-none' }}" id="cashier_footer_preview"></div>
                                <input type="file" class="form-control form-control-sm" name="cashier_footer_image_file" accept="image/*" onchange="previewImg(this,'cashier_footer_preview')">
                                <input type="hidden" name="cashier_footer_image" value="{{ $s->cashier_footer_image ?? '' }}">
                                @if($s->cashier_footer_image)
                                <div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="cashier_footer_image_remove" value="1" id="rm_cf"><label class="form-check-label small" for="rm_cf">حذف الصورة</label></div>
                                @endif
                            </div>
                        </div>

                        <div class="section-title">عناصر الفاتورة</div>
                        @php
                        $cashierToggles = [
                            ['cashier_show_logo',            'fas fa-store text-info',         'شعار / اسم الشركة',              true],
                            ['cashier_show_order_number',    'fas fa-hashtag text-secondary',  'رقم الطلب (ID تسلسلي)',          true],
                            ['cashier_show_bon_number',      'fas fa-file-invoice text-dark',  'رقم البون (pro_id يومي)',         true],
                            ['cashier_show_datetime',        'fas fa-clock text-primary',      'التاريخ والوقت',                  true],
                            ['cashier_show_seller',          'fas fa-user text-secondary',     'البائع (الكاشير)',                true],
                            ['cashier_show_order_type',      'fas fa-tag text-success',        'نوع الطلب',                      true],
                            ['cashier_show_price_source',    'fas fa-layer-group text-info',   'مصدر الطلب (نوع السعر)',         false],
                            ['cashier_show_table',           'fas fa-chair text-success',      'رقم الطاولة',                    true],
                            ['cashier_show_barcode',         'fas fa-barcode text-dark',       'الباركود',                       true],
                            ['cashier_show_notes',           'fas fa-sticky-note text-warning','ملاحظات الاصناف',                true],
                            ['cashier_show_subtotal',        'fas fa-calculator text-muted',   'الاجمالي',                       true],
                            ['cashier_show_discount',        'fas fa-percent text-danger',     'الخصم',                          true],
                            ['cashier_show_additional',      'fas fa-plus-circle text-warning','الاضافي',                        true],
                            ['cashier_show_net',             'fas fa-coins text-success',      'الصافي',                         true],
                            ['cashier_show_payment_details', 'fas fa-money-bill text-success', 'تفاصيل الدفع (مدفوع/باقي)',      true],
                        ];
                        @endphp
                        <div class="row g-2">
                            @foreach($cashierToggles as [$field, $icon, $label, $default])
                            <div class="col-md-4 col-6">
                                <div class="toggle-card">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1" {{ ($s->$field ?? $default) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="{{ $field }}"><i class="{{ $icon }} me-1"></i>{{ $label }}</label>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                    </div>{{-- end col-settings --}}

                    <div class="col-lg-5">
                        <div class="section-title">معاينة مباشرة</div>
                        <div class="d-flex justify-content-center">
                            <div id="cashier-preview" class="preview-box" style="width:78mm">
                                <div id="cprev-header-img" class="p-center mb-1" style="display:none"><img src="" style="max-width:100%;max-height:50px"></div>
                                <div id="cprev-logo" class="p-center p-bold" style="font-size:1.15em">{{ config('app.name') }}</div>
                                <div id="cprev-subtitle" class="p-center" style="font-size:.8em">فاتورة مبيعات</div>
                                <hr id="cprev-sep1" class="p-sep">
                                <div id="cprev-order-num"  class="p-row"><span>رقم الطلب:</span><span>#1042</span></div>
                                <div id="cprev-bon-num"    class="p-row"><span>رقم البون:</span><span>15</span></div>
                                <div id="cprev-datetime"   class="p-row"><span>التاريخ:</span><span>{{ now()->format('Y-m-d H:i') }}</span></div>
                                <div id="cprev-seller"     class="p-row"><span>البائع:</span><span>{{ auth()->user()->name }}</span></div>
                                <div id="cprev-order-type" class="p-row"><span>نوع الطلب:</span><span>طاولة</span></div>
                                <div id="cprev-price-src"  class="p-row" style="display:none"><span>مصدر الطلب:</span><span>سعر المطعم</span></div>
                                <div id="cprev-table"      class="p-row"><span>الطاولة:</span><span>5</span></div>
                                <hr id="cprev-sep2" class="p-sep">
                                <div class="p-row p-bold" style="font-size:.75em;border-bottom:1px solid #000;padding-bottom:2px"><span>الصنف</span><span>الكمية</span><span>المجموع</span></div>
                                <div class="p-item"><span>برجر لحم</span><span>2</span><span>30.00</span></div>
                                <div class="p-item"><span>عصير برتقال</span><span>1</span><span>8.00</span></div>
                                <div id="cprev-notes" class="p-row" style="font-size:.75em;color:#666"><span>ملاحظة: بدون بصل</span></div>
                                <hr id="cprev-sep3" class="p-sep2">
                                <div id="cprev-subtotal"   class="p-row"><span>الاجمالي:</span><span>38.00</span></div>
                                <div id="cprev-discount"   class="p-row"><span>الخصم:</span><span>-3.00</span></div>
                                <div id="cprev-additional" class="p-row"><span>الاضافي:</span><span>+2.00</span></div>
                                <div id="cprev-net"        class="p-row p-bold"><span>الصافي:</span><span>37.00</span></div>
                                <div id="cprev-payment"    class="p-row"><span>المدفوع:</span><span>40.00</span></div>
                                <div id="cprev-barcode"    class="p-center" style="font-family:monospace;font-size:.9em;margin:3px 0">*15*</div>
                                <hr id="cprev-sep4" class="p-sep">
                                <div id="cprev-footer-img" class="p-center mb-1" style="display:none"><img src="" style="max-width:100%;max-height:40px"></div>
                                <div id="cprev-footer"     class="p-center" style="font-size:.78em;color:#555">شكرا لك!</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>{{-- end tab-cashier --}}
            {{-- TAB 2: ورقة التحضير --}}
            <div class="tab-pane fade p-4" id="tab-kitchen">
                <div class="row g-4">
                    <div class="col-lg-7">

                        <div class="section-title">النصوص</div>
                        <div class="row g-2 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">عنوان ورقة التحضير</label>
                                <input type="text" class="form-control form-control-sm" name="kitchen_receipt_title" value="{{ $s->kitchen_receipt_title ?? '' }}" placeholder="طلب مطبخ">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">نص التذييل</label>
                                <input type="text" class="form-control form-control-sm" name="kitchen_receipt_footer" value="{{ $s->kitchen_receipt_footer ?? '' }}" placeholder="يرجى التحضير فورا">
                            </div>
                        </div>

                        <div class="section-title">الورق والخط</div>
                        <div class="row g-2 mb-4">
                            <div class="col-3">
                                <label class="form-label small fw-semibold">عرض الورق</label>
                                <select class="form-select form-select-sm" name="kitchen_paper_width">
                                    @foreach(['58mm','72mm','78mm','80mm'] as $w)
                                        <option value="{{ $w }}" {{ ($s->kitchen_paper_width ?? '78mm') === $w ? 'selected' : '' }}>{{ $w }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-3">
                                <label class="form-label small fw-semibold">حجم الخط (px)</label>
                                <input type="number" class="form-control form-control-sm" name="kitchen_font_size" value="{{ $s->kitchen_font_size ?? 14 }}" min="10" max="22">
                            </div>
                            <div class="col-3">
                                <label class="form-label small fw-semibold">نمط الفاصل</label>
                                <select class="form-select form-select-sm" name="kitchen_separator_style">
                                    <option value="solid"  {{ ($s->kitchen_separator_style ?? 'solid') === 'solid'  ? 'selected' : '' }}>صلب</option>
                                    <option value="dashed" {{ ($s->kitchen_separator_style ?? '') === 'dashed' ? 'selected' : '' }}>متقطع</option>
                                    <option value="dotted" {{ ($s->kitchen_separator_style ?? '') === 'dotted' ? 'selected' : '' }}>منقط</option>
                                </select>
                            </div>
                            <div class="col-3">
                                <label class="form-label small fw-semibold">عدد النسخ</label>
                                <input type="number" class="form-control form-control-sm" name="kitchen_copies" value="{{ $s->kitchen_copies ?? 1 }}" min="1" max="5">
                            </div>
                        </div>

                        <div class="section-title">صور الهيدر والفوتر</div>
                        <div class="row g-2 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">صورة الهيدر</label>
                                <div class="mb-1"><img src="{{ $s->kitchen_header_image ?? '' }}" class="preview-img-thumb {{ $s->kitchen_header_image ? '' : 'd-none' }}" id="kitchen_header_preview"></div>
                                <input type="file" class="form-control form-control-sm" name="kitchen_header_image_file" accept="image/*" onchange="previewImg(this,'kitchen_header_preview')">
                                <input type="hidden" name="kitchen_header_image" value="{{ $s->kitchen_header_image ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">صورة الفوتر</label>
                                <div class="mb-1"><img src="{{ $s->kitchen_footer_image ?? '' }}" class="preview-img-thumb {{ $s->kitchen_footer_image ? '' : 'd-none' }}" id="kitchen_footer_preview"></div>
                                <input type="file" class="form-control form-control-sm" name="kitchen_footer_image_file" accept="image/*" onchange="previewImg(this,'kitchen_footer_preview')">
                                <input type="hidden" name="kitchen_footer_image" value="{{ $s->kitchen_footer_image ?? '' }}">
                            </div>
                        </div>

                        <div class="section-title">عناصر ورقة التحضير</div>
                        @php
                        $kitchenToggles = [
                            ['kitchen_enable_print',      'fas fa-print text-primary',       'تفعيل طباعة التحضير',            true],
                            ['kitchen_show_order_number', 'fas fa-hashtag text-secondary',  'رقم الطلب (ID تسلسلي)',          true],
                            ['kitchen_show_bon_number',   'fas fa-file-invoice text-dark',  'رقم البون (pro_id يومي)',         true],
                            ['kitchen_show_datetime',     'fas fa-clock text-primary',      'التاريخ والوقت',                  true],
                            ['kitchen_show_table',        'fas fa-chair text-success',      'رقم الطاولة',                    true],
                            ['kitchen_show_cashier_name', 'fas fa-user text-secondary',     'اسم الكاشير',                    true],
                            ['kitchen_show_order_type',   'fas fa-tag text-success',        'نوع الطلب',                      true],
                            ['kitchen_show_price_source', 'fas fa-layer-group text-info',   'مصدر الطلب (نوع السعر)',         false],
                            ['kitchen_show_order_time',   'fas fa-stopwatch text-info',     'وقت الطلب',                      true],
                            ['kitchen_show_notes',        'fas fa-sticky-note text-warning','ملاحظات الاصناف',                true],
                            ['kitchen_show_prices',       'fas fa-coins text-danger',       'الاسعار',                        false],
                        ];
                        @endphp
                        <div class="row g-2">
                            @foreach($kitchenToggles as [$field, $icon, $label, $default])
                            <div class="col-md-4 col-6">
                                <div class="toggle-card">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1" {{ ($s->$field ?? $default) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="{{ $field }}"><i class="{{ $icon }} me-1"></i>{{ $label }}</label>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                    </div>{{-- end col-settings --}}

                    <div class="col-lg-5">
                        <div class="section-title">معاينة مباشرة</div>
                        <div class="d-flex justify-content-center">
                            <div id="kitchen-preview" class="preview-box" style="width:78mm;font-size:14px">
                                <div id="kprev-header-img" class="p-center mb-1" style="display:none"><img src="" style="max-width:100%;max-height:50px"></div>
                                <div id="kprev-title" class="p-center p-bold" style="font-size:1.2em">طلب مطبخ</div>
                                <hr id="kprev-sep1" class="p-sep2">
                                <div id="kprev-order-num"  class="p-row"><span>رقم الطلب:</span><span>#1042</span></div>
                                <div id="kprev-bon-num"    class="p-row"><span>رقم البون:</span><span>15</span></div>
                                <div id="kprev-datetime"   class="p-row"><span>التاريخ والوقت:</span><span>{{ now()->format('H:i') }}</span></div>
                                <div id="kprev-table"      class="p-row p-bold"><span>الطاولة:</span><span>5</span></div>
                                <div id="kprev-cashier"    class="p-row"><span>الكاشير:</span><span>{{ auth()->user()->name }}</span></div>
                                <div id="kprev-order-type" class="p-row"><span>نوع الطلب:</span><span>طاولة</span></div>
                                <div id="kprev-price-src"  class="p-row" style="display:none"><span>مصدر الطلب:</span><span>سعر المطعم</span></div>
                                <hr id="kprev-sep2" class="p-sep2">
                                <div class="p-row p-bold" style="font-size:1.05em">
                                    <span>2x برجر لحم</span>
                                    <span id="kprev-price">30.00</span>
                                </div>
                                <div id="kprev-notes" class="p-row" style="font-size:.8em;color:#666"><span>ملاحظة: بدون بصل</span></div>
                                <hr id="kprev-sep3" class="p-sep2">
                                <div id="kprev-footer-img" class="p-center mb-1" style="display:none"><img src="" style="max-width:100%;max-height:40px"></div>
                                <div id="kprev-footer" class="p-center" style="font-size:.8em;color:#555">يرجى التحضير فورا</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>{{-- end tab-kitchen --}}

        </div>{{-- end tab-content --}}

        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary px-5" id="saveBtn">
                <i class="fas fa-save me-2"></i> حفظ الاعدادات
            </button>
        </div>
    </form>
</div>
@endsection
@push('scripts')
<script>
(function(){
'use strict';

function previewImg(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.classList.remove('d-none'); };
        reader.readAsDataURL(input.files[0]);
    }
}
window.previewImg = previewImg;

// ── Cashier Preview ──────────────────────────────────────────────────────
function updateCashierPreview() {
    const w   = document.querySelector('[name=cashier_paper_width]')?.value || '78mm';
    const fs  = document.querySelector('[name=cashier_font_size]')?.value   || 11;
    const sep = document.querySelector('[name=cashier_separator_style]')?.value || 'dashed';
    const p   = document.getElementById('cashier-preview');
    if (!p) return;
    p.style.width    = w;
    p.style.fontSize = fs + 'px';
    ['cprev-sep1','cprev-sep2','cprev-sep3','cprev-sep4'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.borderTopStyle = sep;
    });
    const toggleMap = {
        'cashier_show_logo':            ['cprev-logo','cprev-subtitle'],
        'cashier_show_order_number':    ['cprev-order-num'],
        'cashier_show_bon_number':      ['cprev-bon-num'],
        'cashier_show_datetime':        ['cprev-datetime'],
        'cashier_show_seller':          ['cprev-seller'],
        'cashier_show_order_type':      ['cprev-order-type'],
        'cashier_show_price_source':    ['cprev-price-src'],
        'cashier_show_table':           ['cprev-table'],
        'cashier_show_barcode':         ['cprev-barcode'],
        'cashier_show_notes':           ['cprev-notes'],
        'cashier_show_subtotal':        ['cprev-subtotal'],
        'cashier_show_discount':        ['cprev-discount'],
        'cashier_show_additional':      ['cprev-additional'],
        'cashier_show_net':             ['cprev-net'],
        'cashier_show_payment_details': ['cprev-payment'],
    };
    Object.entries(toggleMap).forEach(([field, ids]) => {
        const checked = document.getElementById(field)?.checked ?? true;
        ids.forEach(id => { const el = document.getElementById(id); if (el) el.style.display = checked ? '' : 'none'; });
    });
    const title  = document.querySelector('[name=cashier_receipt_title]')?.value;
    const footer = document.querySelector('[name=cashier_receipt_footer]')?.value;
    if (title)  { const el = document.getElementById('cprev-subtitle'); if (el) el.textContent = title; }
    if (footer) { const el = document.getElementById('cprev-footer');   if (el) el.textContent = footer; }
}

// ── Kitchen Preview ──────────────────────────────────────────────────────
function updateKitchenPreview() {
    const w   = document.querySelector('[name=kitchen_paper_width]')?.value || '78mm';
    const fs  = document.querySelector('[name=kitchen_font_size]')?.value   || 14;
    const sep = document.querySelector('[name=kitchen_separator_style]')?.value || 'solid';
    const p   = document.getElementById('kitchen-preview');
    if (!p) return;
    p.style.width    = w;
    p.style.fontSize = fs + 'px';
    ['kprev-sep1','kprev-sep2','kprev-sep3'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.borderTopStyle = sep;
    });
    const toggleMap = {
        'kitchen_show_order_number': ['kprev-order-num'],
        'kitchen_show_bon_number':   ['kprev-bon-num'],
        'kitchen_show_datetime':     ['kprev-datetime'],
        'kitchen_show_table':        ['kprev-table'],
        'kitchen_show_cashier_name': ['kprev-cashier'],
        'kitchen_show_order_type':   ['kprev-order-type'],
        'kitchen_show_price_source': ['kprev-price-src'],
        'kitchen_show_notes':        ['kprev-notes'],
        'kitchen_show_prices':       ['kprev-price'],
    };
    Object.entries(toggleMap).forEach(([field, ids]) => {
        const checked = document.getElementById(field)?.checked ?? true;
        ids.forEach(id => { const el = document.getElementById(id); if (el) el.style.display = checked ? '' : 'none'; });
    });
    const title  = document.querySelector('[name=kitchen_receipt_title]')?.value;
    const footer = document.querySelector('[name=kitchen_receipt_footer]')?.value;
    if (title)  { const el = document.getElementById('kprev-title');  if (el) el.textContent = title; }
    if (footer) { const el = document.getElementById('kprev-footer'); if (el) el.textContent = footer; }
}

// ── Listeners ────────────────────────────────────────────────────────────
const form = document.getElementById('printSettingsForm');
form.addEventListener('input',  e => { const n = e.target.name||''; if(n.startsWith('cashier_')||n.startsWith('pos_')) updateCashierPreview(); if(n.startsWith('kitchen_')) updateKitchenPreview(); });
form.addEventListener('change', e => { const n = e.target.name||''; if(n.startsWith('cashier_')||n.startsWith('pos_')) updateCashierPreview(); if(n.startsWith('kitchen_')) updateKitchenPreview(); });

updateCashierPreview();
updateKitchenPreview();

// ── All checkbox fields ───────────────────────────────────────────────────
const allCheckboxes = [
    'pos_enable_print','pos_silent_print',
    'cashier_show_logo','cashier_show_order_number','cashier_show_bon_number',
    'cashier_show_datetime','cashier_show_seller','cashier_show_order_type',
    'cashier_show_price_source','cashier_show_table','cashier_show_barcode',
    'cashier_show_notes','cashier_show_subtotal','cashier_show_discount',
    'cashier_show_additional','cashier_show_net','cashier_show_payment_details',
    'kitchen_show_order_number','kitchen_show_bon_number','kitchen_show_datetime',
    'kitchen_show_table','kitchen_show_cashier_name','kitchen_show_order_type',
    'kitchen_show_price_source','kitchen_show_order_time','kitchen_show_notes','kitchen_show_prices',
    'kitchen_enable_print',
];

// ── Save ─────────────────────────────────────────────────────────────────
form.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('saveBtn');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري الحفظ...';

    const fd = new FormData(this);
    allCheckboxes.forEach(f => { if (!document.getElementById(f)?.checked) fd.set(f, '0'); });

    fetch('{{ route("pos.api.print-settings.update") }}', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: fd,
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            Swal.fire({ icon:'success', title:'تم الحفظ', text: d.message, timer:2000, showConfirmButton:false, toast:true, position:'top-end' });
        } else {
            Swal.fire({ icon:'error', title:'خطا', text: d.message });
        }
    })
    .catch(() => Swal.fire({ icon:'error', title:'خطا', text:'تعذر الاتصال بالسيرفر' }))
    .finally(() => { btn.disabled = false; btn.innerHTML = orig; });
});

})();
</script>
@endpush