@extends('pos::layouts.master')

@section('styles')
<style>
    /* ===== LAYOUT ===== */
    .ph-page { padding: 1.5rem; max-width: 1200px; margin: 0 auto; }

    /* ===== HEADER ===== */
    .ph-header {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;
    }
    .ph-header__title { font-size: 1.4rem; font-weight: 700; margin: 0; }
    .ph-header__sub   { font-size: 0.82rem; color: var(--pos-gray-500); margin-top: 2px; }

    /* ===== STATS CARDS ===== */
    .ph-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    @media (max-width: 768px) { .ph-stats { grid-template-columns: repeat(2, 1fr); } }
    .ph-stat {
        background: #fff; border: 1.5px solid var(--pos-gray-200);
        border-radius: 12px; padding: 1rem 1.2rem;
        display: flex; align-items: center; gap: .9rem;
    }
    .ph-stat__icon {
        width: 44px; height: 44px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; flex-shrink: 0;
    }
    .ph-stat__val  { font-size: 1.3rem; font-weight: 700; line-height: 1.2; }
    .ph-stat__lbl  { font-size: 0.75rem; color: var(--pos-gray-500); }

    /* ===== MONTH PICKER ===== */
    .ph-picker {
        background: #fff; border: 1.5px solid var(--pos-gray-200);
        border-radius: 12px; padding: .75rem 1.2rem;
        display: flex; align-items: center; gap: .75rem;
        margin-bottom: 1.5rem; flex-wrap: wrap;
    }
    .ph-picker label { font-size: .85rem; font-weight: 600; margin: 0; white-space: nowrap; }
    .ph-picker select { border: 1.5px solid var(--pos-gray-200); border-radius: 8px; padding: .35rem .75rem; font-family: Cairo, sans-serif; font-size: .85rem; }
    .ph-picker .btn   { font-family: Cairo, sans-serif; font-size: .85rem; }

    /* ===== CALENDAR ===== */
    .ph-card {
        background: #fff; border: 1.5px solid var(--pos-gray-200);
        border-radius: 14px; overflow: hidden;
    }
    .ph-card__head {
        padding: .85rem 1.2rem; border-bottom: 1px solid var(--pos-gray-100);
        font-weight: 700; font-size: .95rem;
        display: flex; align-items: center; gap: .5rem;
    }

    .ph-calendar-grid {
        display: grid; grid-template-columns: repeat(7, 1fr); gap: 0;
        padding: 0;
    }
    .ph-day-header {
        text-align: center; font-size: .75rem; font-weight: 700;
        color: var(--pos-gray-500); padding: 10px 4px;
        background: #f8fafc; border-bottom: 1px solid var(--pos-gray-100);
        border-left: 1px solid var(--pos-gray-100);
    }
    .ph-day-header:first-child { border-right: 1px solid var(--pos-gray-100); }

    .ph-day-cell {
        border: 1px solid rgba(0,0,0,.06);
        padding: 8px 6px; text-align: center;
        cursor: pointer; transition: transform .15s, box-shadow .15s, filter .15s;
        min-height: 72px; display: flex; flex-direction: column;
        align-items: center; justify-content: flex-start; gap: 4px;
        position: relative; background: #fff;
    }
    .ph-day-cell:hover:not(.ph-day-cell--empty) {
        transform: scale(1.04); box-shadow: 0 6px 20px rgba(0,0,0,.15);
        z-index: 2; filter: brightness(1.05);
    }
    .ph-day-cell--empty {
        cursor: default; background: #fafafa !important;
        pointer-events: none;
    }

    .ph-day-cell__num {
        font-size: .82rem; font-weight: 700; line-height: 1;
        width: 26px; height: 26px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin-top: 2px;
    }
    .ph-day-cell__count {
        font-size: .68rem; font-weight: 700;
        padding: 1px 6px; border-radius: 10px;
        background: rgba(0,0,0,.12);
    }
    .ph-day-cell__label {
        font-size: .6rem; opacity: .75; font-weight: 600;
    }

    /* ===== COLOR LEVELS ===== */
    /* 0 orders */
    .ph-lvl-0 { background: #f1f5f9; color: #94a3b8; }
    .ph-lvl-0 .ph-day-cell__num { background: #e2e8f0; color: #64748b; }

    /* 1-20% — light green */
    .ph-lvl-1 { background: #dcfce7; color: #166534; }
    .ph-lvl-1 .ph-day-cell__num { background: #bbf7d0; color: #15803d; }

    /* 21-40% — green */
    .ph-lvl-2 { background: #86efac; color: #14532d; }
    .ph-lvl-2 .ph-day-cell__num { background: #4ade80; color: #14532d; }

    /* 41-60% — yellow-orange */
    .ph-lvl-3 { background: #fef08a; color: #713f12; }
    .ph-lvl-3 .ph-day-cell__num { background: #fde047; color: #713f12; }

    /* 61-80% — orange */
    .ph-lvl-4 { background: #fdba74; color: #7c2d12; }
    .ph-lvl-4 .ph-day-cell__num { background: #fb923c; color: #7c2d12; }

    /* 81-100% — red */
    .ph-lvl-5 { background: #fca5a5; color: #7f1d1d; }
    .ph-lvl-5 .ph-day-cell__num { background: #f87171; color: #7f1d1d; }

    /* ===== LEGEND ===== */
    .ph-legend {
        display: flex; align-items: center; gap: .5rem;
        padding: .75rem 1rem; border-top: 1px solid var(--pos-gray-100);
        flex-wrap: wrap; font-size: .75rem; color: var(--pos-gray-500);
    }
    .ph-legend__item { display: flex; align-items: center; gap: 4px; }
    .ph-legend__dot {
        width: 16px; height: 16px; border-radius: 4px; display: inline-block;
        border: 1px solid rgba(0,0,0,.1);
    }

    /* ===== HOURS MODAL ===== */
    .ph-hours-grid {
        display: grid; grid-template-columns: repeat(6, 1fr); gap: 6px;
        margin-bottom: 1rem;
    }
    @media (max-width: 576px) { .ph-hours-grid { grid-template-columns: repeat(4, 1fr); } }

    .ph-hour-cell {
        border-radius: 8px; padding: 8px 4px; text-align: center;
        border: 1.5px solid transparent; transition: transform .15s;
    }
    .ph-hour-cell:hover { transform: scale(1.08); }
    .ph-hour-cell__time { font-size: .7rem; font-weight: 700; }
    .ph-hour-cell__cnt  { font-size: .85rem; font-weight: 800; line-height: 1.2; }
    .ph-hour-cell__lbl  { font-size: .6rem; opacity: .8; }

    /* Hour color levels */
    .ph-h-0 { background: #f1f5f9; color: #94a3b8; border-color: #e2e8f0; }
    .ph-h-1 { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
    .ph-h-2 { background: #86efac; color: #14532d; border-color: #4ade80; }
    .ph-h-3 { background: #fef08a; color: #713f12; border-color: #fde047; }
    .ph-h-4 { background: #fdba74; color: #7c2d12; border-color: #fb923c; }
    .ph-h-5 { background: #fca5a5; color: #7f1d1d; border-color: #f87171; }

    .ph-back { font-size: .85rem; }
</style>
@endsection

@section('content')
<div class="ph-page">

    {{-- Header --}}
    <div class="ph-header">
        <div>
            <h4 class="ph-header__title">
                <i class="fas fa-fire text-danger me-2"></i>
                {{ __('pos.peak_hours_report') }}
            </h4>
            <div class="ph-header__sub">{{ __('pos.peak_hours_subtitle') }}</div>
        </div>
        <a href="{{ route('pos.index') }}" class="btn btn-outline-secondary btn-sm ph-back">
            <i class="fas fa-arrow-right me-1"></i>{{ __('pos.back_to_home') }}
        </a>
    </div>

    {{-- Month Picker --}}
    <form method="GET" action="{{ route('pos.peak-hours') }}" class="ph-picker">
        <label for="ph-month"><i class="fas fa-calendar-alt me-1"></i>{{ __('pos.select_month') }}</label>
        <select name="month" id="ph-month">
            @for ($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" @selected($m === $month)>
                    {{ \Carbon\Carbon::createFromDate($year, $m, 1)->translatedFormat('F') }}
                </option>
            @endfor
        </select>
        <select name="year">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
            @endfor
        </select>
        <button type="submit" class="btn btn-primary btn-sm text-white">
            <i class="fas fa-search me-1"></i>{{ __('pos.search') ?? 'عرض' }}
        </button>
    </form>

    {{-- Stats --}}
    <div class="ph-stats">
        <div class="ph-stat">
            <div class="ph-stat__icon" style="background:#dbeafe;color:#1d4ed8">
                <i class="fas fa-receipt"></i>
            </div>
            <div>
                <div class="ph-stat__val">{{ number_format($stats['total_orders']) }}</div>
                <div class="ph-stat__lbl">{{ __('pos.total_orders_month') }}</div>
            </div>
        </div>
        <div class="ph-stat">
            <div class="ph-stat__icon" style="background:#d1fae5;color:#065f46">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <div class="ph-stat__val">{{ $stats['avg_per_day'] }}</div>
                <div class="ph-stat__lbl">{{ __('pos.avg_orders_day') }}</div>
            </div>
        </div>
        <div class="ph-stat">
            <div class="ph-stat__icon" style="background:#fee2e2;color:#991b1b">
                <i class="fas fa-fire"></i>
            </div>
            <div>
                <div class="ph-stat__val">
                    {{ $stats['busiest_day']
                        ? \Carbon\Carbon::parse($stats['busiest_day'])->format('d/m')
                        : '—' }}
                </div>
                <div class="ph-stat__lbl">{{ __('pos.busiest_day') }}</div>
            </div>
        </div>
        <div class="ph-stat">
            <div class="ph-stat__icon" style="background:#fef3c7;color:#92400e">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <div class="ph-stat__val">
                    {{ $stats['busiest_hour'] !== null
                        ? str_pad($stats['busiest_hour'], 2, '0', STR_PAD_LEFT) . ':00'
                        : '—' }}
                </div>
                <div class="ph-stat__lbl">{{ __('pos.busiest_hour') }}</div>
            </div>
        </div>
    </div>

    {{-- Calendar --}}
    <div class="ph-card">
        <div class="ph-card__head">
            <i class="fas fa-calendar-alt text-primary"></i>
            {{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}
            <span class="badge bg-light text-dark border ms-auto" style="font-size:.75rem">
                {{ __('pos.orders_count') }}: {{ number_format($stats['total_orders']) }}
            </span>
        </div>

        <div class="ph-calendar-grid">
            {{-- Day headers --}}
            @foreach(['أحد','اثنين','ثلاثاء','أربعاء','خميس','جمعة','سبت'] as $dh)
                <div class="ph-day-header">{{ $dh }}</div>
            @endforeach

            {{-- Empty cells before first day --}}
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
                     data-date="{{ $day['date'] }}"
                     data-count="{{ $day['orders_count'] }}"
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

        {{-- Legend --}}
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

{{-- ===== HOURS MODAL ===== --}}
<div class="modal fade" id="phDayModal" tabindex="-1" aria-labelledby="phDayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:16px;overflow:hidden">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="phDayModalLabel">
                    <i class="fas fa-clock text-warning me-2"></i>
                    <span id="phModalTitle">{{ __('pos.hour_detail_title') }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('pos.close') }}"></button>
            </div>
            <div class="modal-body pt-2">

                {{-- Summary row --}}
                <div class="d-flex gap-3 mb-3 flex-wrap">
                    <div class="badge bg-primary px-3 py-2 text-white" style="font-size:.82rem">
                        <i class="fas fa-receipt me-1"></i>
                        {{ __('pos.orders_count') }}: <strong id="phModalTotal">0</strong>
                    </div>
                    <div class="badge bg-danger bg-opacity-10 text-danger px-3 py-2" style="font-size:.82rem">
                        <i class="fas fa-fire me-1"></i>
                        {{ __('pos.peak_hour') }}: <strong id="phModalPeakHour">—</strong>
                    </div>
                </div>

                {{-- Loading --}}
                <div id="phModalLoading" class="text-center py-4" style="display:none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('pos.loading') ?? 'جاري التحميل...' }}</span>
                    </div>
                </div>

                {{-- No data --}}
                <div id="phModalEmpty" class="text-center py-4 text-muted" style="display:none">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    {{ __('pos.no_orders') }}
                </div>

                {{-- Hours grid --}}
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
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const AJAX_URL = '{{ route('pos.api.peak-hours-day') }}';
    const CSRF     = '{{ csrf_token() }}';
    let modal;

    document.addEventListener('DOMContentLoaded', function () {
        modal = new bootstrap.Modal(document.getElementById('phDayModal'));
    });

    function getHourLevel(pct) {
        if (pct === 0)       return 0;
        if (pct <= 20)       return 1;
        if (pct <= 40)       return 2;
        if (pct <= 60)       return 3;
        if (pct <= 80)       return 4;
        return 5;
    }

    const hourLabels = ['', 'منخفض', 'متوسط', 'مرتفع', 'عالي', 'ذروة'];

    window.phOpenDay = function (date, count) {
        const d = new Date(date);
        const label = d.toLocaleDateString('ar-SA', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });

        document.getElementById('phModalTitle').textContent = label;
        document.getElementById('phModalTotal').textContent = count;
        document.getElementById('phModalPeakHour').textContent = '—';

        // Reset UI
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

            // Peak hour
            const peakIdx = vals.indexOf(Math.max.apply(null, vals));
            document.getElementById('phModalPeakHour').textContent =
                String(peakIdx).padStart(2, '0') + ':00';

            // Build hours grid
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
