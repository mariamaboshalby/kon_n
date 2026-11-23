@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.reports')
@endsection

@section('styles')
<style>
    /* ===== CALENDAR ===== */
    .ph-calendar-grid {
        display: grid; grid-template-columns: repeat(7, 1fr); gap: 0;
    }
    .ph-day-header {
        text-align: center; font-size: .75rem; font-weight: 700;
        color: #6b7280; padding: 10px 4px;
        background: #f8fafc; border-bottom: 1px solid #e5e7eb;
        border-left: 1px solid #e5e7eb;
    }
    .ph-day-cell {
        border: 1px solid rgba(0,0,0,.06); padding: 8px 6px; text-align: center;
        cursor: pointer; transition: transform .15s, box-shadow .15s, filter .15s;
        min-height: 72px; display: flex; flex-direction: column;
        align-items: center; justify-content: flex-start; gap: 4px;
        position: relative;
    }
    .ph-day-cell:hover:not(.ph-day-cell--empty) {
        transform: scale(1.04); box-shadow: 0 6px 20px rgba(0,0,0,.15);
        z-index: 2; filter: brightness(1.05);
    }
    .ph-day-cell--empty { cursor: default; background: #fafafa !important; pointer-events: none; }
    .ph-day-cell__num {
        font-size: .82rem; font-weight: 700; line-height: 1;
        width: 26px; height: 26px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; margin-top: 2px;
    }
    .ph-day-cell__count { font-size: .68rem; font-weight: 700; padding: 1px 6px; border-radius: 10px; background: rgba(0,0,0,.12); }
    .ph-day-cell__label { font-size: .6rem; opacity: .75; font-weight: 600; }

    /* Color levels */
    .ph-lvl-0 { background: #f1f5f9; color: #94a3b8; }
    .ph-lvl-0 .ph-day-cell__num { background: #e2e8f0; color: #64748b; }
    .ph-lvl-1 { background: #dcfce7; color: #166534; }
    .ph-lvl-1 .ph-day-cell__num { background: #bbf7d0; color: #15803d; }
    .ph-lvl-2 { background: #86efac; color: #14532d; }
    .ph-lvl-2 .ph-day-cell__num { background: #4ade80; color: #14532d; }
    .ph-lvl-3 { background: #fef08a; color: #713f12; }
    .ph-lvl-3 .ph-day-cell__num { background: #fde047; color: #713f12; }
    .ph-lvl-4 { background: #fdba74; color: #7c2d12; }
    .ph-lvl-4 .ph-day-cell__num { background: #fb923c; color: #7c2d12; }
    .ph-lvl-5 { background: #fca5a5; color: #7f1d1d; }
    .ph-lvl-5 .ph-day-cell__num { background: #f87171; color: #7f1d1d; }

    /* Legend */
    .ph-legend { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; font-size: .75rem; color: #6b7280; }
    .ph-legend__item { display: flex; align-items: center; gap: 4px; }
    .ph-legend__dot { width: 16px; height: 16px; border-radius: 4px; display: inline-block; border: 1px solid rgba(0,0,0,.1); }

    /* Hours grid in modal */
    .ph-hours-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 6px; margin-bottom: 1rem; }
    @media (max-width: 576px) { .ph-hours-grid { grid-template-columns: repeat(4, 1fr); } }
    .ph-hour-cell { border-radius: 8px; padding: 8px 4px; text-align: center; border: 1.5px solid transparent; transition: transform .15s; }
    .ph-hour-cell:hover { transform: scale(1.08); }
    .ph-hour-cell__time { font-size: .7rem; font-weight: 700; }
    .ph-hour-cell__cnt  { font-size: .85rem; font-weight: 800; line-height: 1.2; }
    .ph-hour-cell__lbl  { font-size: .6rem; opacity: .8; }
    .ph-h-0 { background: #f1f5f9; color: #94a3b8; border-color: #e2e8f0; }
    .ph-h-1 { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
    .ph-h-2 { background: #86efac; color: #14532d; border-color: #4ade80; }
    .ph-h-3 { background: #fef08a; color: #713f12; border-color: #fde047; }
    .ph-h-4 { background: #fdba74; color: #7c2d12; border-color: #fb923c; }
    .ph-h-5 { background: #fca5a5; color: #7f1d1d; border-color: #f87171; }
</style>
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('reports::reports.peak_hours_report'),
        'breadcrumb_items' => [
            ['label' => __('reports::reports.home'), 'url' => route('admin.dashboard')],
            ['label' => __('reports::reports.sales_reports')],
            ['label' => __('reports::reports.peak_hours_report')],
        ],
    ])

    <div class="container-fluid">

        {{-- Month Picker --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-calendar-alt me-1"></i>{{ __('pos.select_month') }}
                        </label>
                        <select name="month" class="form-select">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected($m === $month)>
                                    {{ \Carbon\Carbon::createFromDate($year, $m, 1)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">{{ __('reports::reports.year') ?? 'السنة' }}</label>
                        <select name="year" class="form-select">
                            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                                <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-search me-1"></i>{{ __('reports::reports.search') }}
                        </button>
                        <a href="{{ route('reports.pos.peak-hours-calender') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Stats --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">{{ __('pos.total_orders_month') }}</div>
                        <div class="fs-3 fw-bold text-primary">{{ number_format($stats['total_orders']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">{{ __('pos.avg_orders_day') }}</div>
                        <div class="fs-3 fw-bold text-success">{{ $stats['avg_per_day'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">
                            <i class="fas fa-fire text-danger me-1"></i>{{ __('pos.busiest_day') }}
                        </div>
                        <div class="fs-3 fw-bold text-danger">
                            {{ $stats['busiest_day'] ? \Carbon\Carbon::parse($stats['busiest_day'])->format('d/m') : '—' }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">
                            <i class="fas fa-clock text-warning me-1"></i>{{ __('pos.busiest_hour') }}
                        </div>
                        <div class="fs-3 fw-bold text-warning">
                            {{ $stats['busiest_hour'] !== null ? str_pad($stats['busiest_hour'], 2, '0', STR_PAD_LEFT) . ':00' : '—' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Calendar --}}
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex align-items-center gap-2">
                <i class="fas fa-calendar-alt text-primary"></i>
                <span class="fw-bold">
                    {{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}
                </span>
                <span class="badge bg-light text-dark border ms-auto" style="font-size:.75rem">
                    {{ __('pos.orders_count') }}: {{ number_format($stats['total_orders']) }}
                </span>
            </div>

            <div class="card-body p-0">
                <div class="ph-calendar-grid">
                    {{-- Day headers --}}
                    @foreach(['أحد','اثنين','ثلاثاء','أربعاء','خميس','جمعة','سبت'] as $dh)
                        <div class="ph-day-header">{{ $dh }}</div>
                    @endforeach

                    {{-- Empty cells --}}
                    @for ($e = 0; $e < $first_day_of_week; $e++)
                        <div class="ph-day-cell ph-day-cell--empty"></div>
                    @endfor

                    {{-- Day cells --}}
                    @foreach ($calendar as $day)
                        @php
                            $ratio = $day['ratio'];
                            if ($day['orders_count'] === 0)      $lvl = 0;
                            elseif ($ratio <= 0.20)              $lvl = 1;
                            elseif ($ratio <= 0.40)              $lvl = 2;
                            elseif ($ratio <= 0.60)              $lvl = 3;
                            elseif ($ratio <= 0.80)              $lvl = 4;
                            else                                 $lvl = 5;
                            $lvlLabels = ['', 'منخفض', 'متوسط', 'مرتفع', 'عالي', 'ذروة'];
                        @endphp
                        <div class="ph-day-cell ph-lvl-{{ $lvl }}"
                             onclick="phOpenDay('{{ $day['date'] }}', {{ $day['orders_count'] }})">
                            <span class="ph-day-cell__num">{{ $day['day'] }}</span>
                            @if ($day['orders_count'] > 0)
                                <span class="ph-day-cell__count">{{ $day['orders_count'] }}</span>
                                <span class="ph-day-cell__label">{{ $lvlLabels[$lvl] }}</span>
                            @endif
                        </div>
                    @endforeach

                    {{-- Trailing empty cells to complete last row --}}
                    @php $trailing = (7 - (($first_day_of_week + $days_in_month) % 7)) % 7; @endphp
                    @for ($t = 0; $t < $trailing; $t++)
                        <div class="ph-day-cell ph-day-cell--empty"></div>
                    @endfor
                </div>
            </div>

            <div class="card-footer bg-white">
                <div class="ph-legend">
                    <span class="fw-semibold me-1">{{ __('pos.orders_count') }}:</span>
                    <div class="ph-legend__item"><span class="ph-legend__dot" style="background:#e2e8f0"></span><span>لا يوجد</span></div>
                    <div class="ph-legend__item"><span class="ph-legend__dot" style="background:#86efac"></span><span>منخفض</span></div>
                    <div class="ph-legend__item"><span class="ph-legend__dot" style="background:#fef08a"></span><span>متوسط</span></div>
                    <div class="ph-legend__item"><span class="ph-legend__dot" style="background:#fdba74"></span><span>مرتفع</span></div>
                    <div class="ph-legend__item"><span class="ph-legend__dot" style="background:#fca5a5"></span><span>ذروة</span></div>
                </div>
            </div>
        </div>

    </div>
@endsection

{{-- ===== HOURS MODAL ===== --}}
@push('modals')
<div class="modal fade" id="phDayModal" tabindex="-1" aria-labelledby="phDayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:16px;overflow:hidden">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="phDayModalLabel">
                    <i class="fas fa-clock text-warning me-2"></i>
                    <span id="phModalTitle"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="d-flex gap-3 mb-3 flex-wrap">
                    <div class="badge bg-primary bg-opacity-10 text-primary px-3 py-2" style="font-size:.82rem">
                        <i class="fas fa-receipt me-1"></i>
                        {{ __('pos.orders_count') }}: <strong id="phModalTotal">0</strong>
                    </div>
                    <div class="badge bg-danger bg-opacity-10 text-danger px-3 py-2" style="font-size:.82rem">
                        <i class="fas fa-fire me-1"></i>
                        {{ __('pos.peak_hour') }}: <strong id="phModalPeakHour">—</strong>
                    </div>
                </div>

                <div id="phModalLoading" class="text-center py-4" style="display:none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('pos.loading') ?? 'جاري التحميل...' }}</span>
                    </div>
                </div>

                <div id="phModalEmpty" class="text-center py-4 text-muted" style="display:none">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    {{ __('pos.no_orders') }}
                </div>

                <div id="phModalChart" style="display:none">
                    <div class="ph-hours-grid" id="phHoursGrid"></div>
                    <div class="ph-legend mt-2">
                        <span class="fw-semibold me-1">الطلبات:</span>
                        <div class="ph-legend__item"><span class="ph-legend__dot" style="background:#e2e8f0"></span><span>لا يوجد</span></div>
                        <div class="ph-legend__item"><span class="ph-legend__dot" style="background:#86efac"></span><span>منخفض</span></div>
                        <div class="ph-legend__item"><span class="ph-legend__dot" style="background:#fef08a"></span><span>متوسط</span></div>
                        <div class="ph-legend__item"><span class="ph-legend__dot" style="background:#fdba74"></span><span>مرتفع</span></div>
                        <div class="ph-legend__item"><span class="ph-legend__dot" style="background:#fca5a5"></span><span>ذروة</span></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>{{ __('pos.close') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';

    const AJAX_URL = '{{ route('reports.pos.peak-hours-calender-day') }}';
    const CSRF     = '{{ csrf_token() }}';
    let modal;

    document.addEventListener('DOMContentLoaded', function () {
        modal = new bootstrap.Modal(document.getElementById('phDayModal'));
    });

    function getHourLevel(pct) {
        if (pct === 0)  return 0;
        if (pct <= 20)  return 1;
        if (pct <= 40)  return 2;
        if (pct <= 60)  return 3;
        if (pct <= 80)  return 4;
        return 5;
    }

    const hourLabels = ['', 'منخفض', 'متوسط', 'مرتفع', 'عالي', 'ذروة'];

    window.phOpenDay = function (date, count) {
        const d = new Date(date);
        document.getElementById('phModalTitle').textContent = d.toLocaleDateString('ar-SA', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
        document.getElementById('phModalTotal').textContent = count;
        document.getElementById('phModalPeakHour').textContent = '—';

        document.getElementById('phModalLoading').style.display = 'block';
        document.getElementById('phModalEmpty').style.display   = 'none';
        document.getElementById('phModalChart').style.display   = 'none';

        modal.show();

        if (count === 0) {
            document.getElementById('phModalLoading').style.display = 'none';
            document.getElementById('phModalEmpty').style.display   = 'block';
            return;
        }

        fetch(AJAX_URL + '?date=' + date, {
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            document.getElementById('phModalLoading').style.display = 'none';

            const hours = res.hours || {};
            const vals  = Object.values(hours).map(Number);
            const maxV  = Math.max.apply(null, vals.concat([1]));
            const peakIdx = vals.indexOf(Math.max.apply(null, vals));

            document.getElementById('phModalPeakHour').textContent =
                String(peakIdx).padStart(2, '0') + ':00';

            const grid = document.getElementById('phHoursGrid');
            grid.innerHTML = '';

            for (var h = 0; h < 24; h++) {
                var v   = Number(hours[h] || 0);
                var pct = maxV > 0 ? Math.round((v / maxV) * 100) : 0;
                var lvl = getHourLevel(pct);

                var cell = document.createElement('div');
                cell.className = 'ph-hour-cell ph-h-' + lvl;
                cell.title = String(h).padStart(2, '0') + ':00 — ' + v + ' طلب';
                cell.innerHTML =
                    '<div class="ph-hour-cell__time">' + String(h).padStart(2, '0') + ':00</div>' +
                    '<div class="ph-hour-cell__cnt">' + v + '</div>' +
                    (v > 0 ? '<div class="ph-hour-cell__lbl">' + hourLabels[lvl] + '</div>' : '');

                grid.appendChild(cell);
            }

            document.getElementById('phModalChart').style.display = 'block';
        })
        .catch(function () {
            document.getElementById('phModalLoading').style.display = 'none';
            document.getElementById('phModalEmpty').style.display   = 'block';
        });
    };
})();
</script>
@endpush
