@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.reports')
@endsection

@section('content')
<style>
    .journal-voucher {
        border: 2px solid #333;
        border-radius: 4px;
        margin-bottom: 1.5rem;
        font-family: 'Segoe UI', Tahoma, sans-serif;
        direction: rtl;
    }
    .journal-voucher .voucher-header {
        background: #f0f0f0;
        border-bottom: 2px solid #333;
        padding: 8px 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }
    .journal-voucher .voucher-header .entry-num {
        font-size: 1rem;
        font-weight: 700;
        color: #1a1a1a;
    }
    .journal-voucher .voucher-header .entry-meta {
        font-size: 0.82rem;
        color: #555;
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }
    .journal-voucher .voucher-header .entry-meta span {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .journal-voucher .voucher-body {
        display: grid;
        grid-template-columns: 1fr 1px 1fr;
    }
    .journal-voucher .voucher-divider {
        background: #333;
        width: 1px;
    }
    .journal-voucher .side-header {
        padding: 6px 12px;
        font-weight: 700;
        font-size: 0.85rem;
        border-bottom: 1px solid #ccc;
        text-align: center;
        letter-spacing: 0.5px;
    }
    .journal-voucher .side-debit .side-header  { background: #e8f5e9; color: #2e7d32; }
    .journal-voucher .side-credit .side-header { background: #fce4ec; color: #c62828; }
    .journal-voucher .side-debit,
    .journal-voucher .side-credit {
        display: flex;
        flex-direction: column;
    }
    .journal-voucher .acc-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 14px;
        border-bottom: 1px dashed #ddd;
        font-size: 0.88rem;
    }
    .journal-voucher .acc-row:last-child { border-bottom: none; }
    .journal-voucher .acc-name { color: #222; }
    .journal-voucher .acc-amount-debit  { font-weight: 700; color: #2e7d32; white-space: nowrap; }
    .journal-voucher .acc-amount-credit { font-weight: 700; color: #c62828; white-space: nowrap; }
    .journal-voucher .voucher-footer {
        border-top: 2px solid #333;
        background: #fafafa;
        padding: 5px 14px;
        display: flex;
        justify-content: space-between;
        font-size: 0.82rem;
        font-weight: 600;
    }
    .journal-voucher .voucher-footer .total-debit  { color: #2e7d32; }
    .journal-voucher .voucher-footer .total-credit { color: #c62828; }
    .journal-voucher .voucher-footer .total-balanced { color: #888; font-size: 0.78rem; font-weight: 400; }

    @media print {
        .card > .card-body > .mb-3,
        #rowsCount { display: none !important; }
        .journal-voucher { page-break-inside: avoid; }
    }
</style>

<div class="card">
    <div class="card-body">

        {{-- Filters --}}
        <div class="mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-sm-2">
                    <label class="form-label">{{ __('reports::reports.from_date') }}</label>
                    <input type="date" id="filterDateFrom" class="form-control" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-sm-2">
                    <label class="form-label">{{ __('reports::reports.to_date') }}</label>
                    <input type="date" id="filterDateTo" class="form-control" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-sm-2">
                    <label class="form-label">{{ __('reports::reports.entry_number') }}</label>
                    <input type="text" id="filterJournalId" class="form-control" placeholder="e.g. 1024">
                </div>
                <div class="col-sm-3">
                    <label class="form-label">{{ __('reports::reports.account_name') }}</label>
                    <input type="text" id="filterAccount" class="form-control"
                        placeholder="{{ __('reports::reports.search_by_account_name') }}">
                </div>
                <div class="col-sm-2">
                    <label class="form-label">{{ __('reports::reports.operation_type') }}</label>
                    <input type="text" id="filterType" class="form-control"
                        placeholder="{{ __('reports::reports.search_by_operation_type') }}">
                </div>
                <div class="col-sm-1">
                    <label class="form-label">{{ __('reports::reports.movement') }}</label>
                    <select id="filterDC" class="form-select">
                        <option value="">{{ __('reports::reports.all') }}</option>
                        <option value="debit">{{ __('reports::reports.debit') }}</option>
                        <option value="credit">{{ __('reports::reports.credit') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="small text-muted mb-3" id="rowsCount"></div>

        {{-- Journal Entries --}}
        <div id="journalContainer">
            @forelse ($journalHeads as $i => $head)
                @php
                    $totalDebit  = $head->dets->sum('debit');
                    $totalCredit = $head->dets->sum('credit');
                    $debitDets   = $head->dets->where('debit', '>', 0)->values();
                    $creditDets  = $head->dets->where('credit', '>', 0)->values();
                    $entryType   = $head->oper?->type?->ptext ?? '';
                    $balanced    = abs($totalDebit - $totalCredit) < 0.01;
                @endphp

                <div class="journal-voucher journal-entry"
                     data-journal-id="{{ $head->journal_id }}"
                     data-type="{{ $entryType }}"
                     data-date="{{ $head->date }}"
                     data-accnames="{{ $head->dets->pluck('accHead.aname')->filter()->implode('|') }}"
                     data-has-debit="{{ $debitDets->count() > 0 ? '1' : '0' }}"
                     data-has-credit="{{ $creditDets->count() > 0 ? '1' : '0' }}">

                    {{-- Header --}}
                    <div class="voucher-header">
                        <div class="entry-num">
                            <i class="las la-file-invoice me-1"></i>
                            {{ __('reports::reports.entry_number') }}: {{ $head->journal_id }}
                        </div>
                        <div class="entry-meta">
                            <span><i class="las la-calendar"></i> {{ $head->date }}</span>
                            @if($entryType)
                                <span><i class="las la-tag"></i> {{ $entryType }}</span>
                            @endif
                            @if($head->details)
                                <span><i class="las la-align-left"></i> {{ $head->details }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Body: من حـ/ | إلى حـ/ --}}
                    <div class="voucher-body">

                        {{-- من حـ/ (مدين) --}}
                        <div class="side-debit">
                            <div class="side-header">من حـ/ &nbsp;({{ __('reports::reports.debit') }})</div>
                            @foreach($debitDets as $det)
                                <div class="acc-row">
                                    <span class="acc-name">{{ $det->accHead->aname ?? '-' }}</span>
                                    <span class="acc-amount-debit">{{ number_format($det->debit, 2) }}</span>
                                </div>
                            @endforeach
                            @if($debitDets->isEmpty())
                                <div class="acc-row text-muted">-</div>
                            @endif
                        </div>

                        <div class="voucher-divider"></div>

                        {{-- إلى حـ/ (دائن) --}}
                        <div class="side-credit">
                            <div class="side-header">إلى حـ/ &nbsp;({{ __('reports::reports.credit') }})</div>
                            @foreach($creditDets as $det)
                                <div class="acc-row">
                                    <span class="acc-name">{{ $det->accHead->aname ?? '-' }}</span>
                                    <span class="acc-amount-credit">{{ number_format($det->credit, 2) }}</span>
                                </div>
                            @endforeach
                            @if($creditDets->isEmpty())
                                <div class="acc-row text-muted">-</div>
                            @endif
                        </div>

                    </div>

                    {{-- Footer totals --}}
                    <div class="voucher-footer">
                        <span class="total-debit">
                            إجمالي المدين: {{ number_format($totalDebit, 2) }}
                        </span>
                        @if($balanced)
                            <span class="total-balanced"><i class="las la-check-circle"></i> متوازن</span>
                        @else
                            <span class="text-warning fw-bold"><i class="las la-exclamation-triangle"></i> غير متوازن</span>
                        @endif
                        <span class="total-credit">
                            إجمالي الدائن: {{ number_format($totalCredit, 2) }}
                        </span>
                    </div>

                </div>
            @empty
                <div class="alert alert-info text-center">
                    <i class="las la-info-circle me-2"></i>
                    {{ __('reports::reports.no_data_available') }}
                </div>
            @endforelse
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const els = {
            from: document.getElementById('filterDateFrom'),
            to:   document.getElementById('filterDateTo'),
            jid:  document.getElementById('filterJournalId'),
            acc:  document.getElementById('filterAccount'),
            type: document.getElementById('filterType'),
            dc:   document.getElementById('filterDC'),
            cnt:  document.getElementById('rowsCount'),
        };

        if (Object.values(els).some(el => !el)) return;

        const today = new Date().toISOString().split('T')[0];
        els.from.value = els.from.value || today;
        els.to.value   = els.to.value   || today;

        function inRange(dateStr, fromStr, toStr) {
            if (!dateStr) return false;
            const d = new Date(dateStr);
            if (fromStr) { const f = new Date(fromStr); f.setHours(0,0,0,0); if (d < f) return false; }
            if (toStr)   { const t = new Date(toStr);   t.setHours(23,59,59,999); if (d > t) return false; }
            return true;
        }

        function applyFilters() {
            const fFrom = els.from.value;
            const fTo   = els.to.value;
            const fJid  = els.jid.value.trim().toLowerCase();
            const fAcc  = els.acc.value.trim().toLowerCase();
            const fType = els.type.value.trim().toLowerCase();
            const fDC   = els.dc.value;

            let visible = 0;

            document.querySelectorAll('.journal-entry').forEach(card => {
                const jid      = (card.dataset.journalId || '').toLowerCase();
                const typ      = (card.dataset.type      || '').toLowerCase();
                const dat      =  card.dataset.date      || '';
                const accnames = (card.dataset.accnames  || '').toLowerCase();
                const hasDebit  = card.dataset.hasDebit  === '1';
                const hasCredit = card.dataset.hasCredit === '1';

                let ok = true;
                if (fFrom || fTo) ok = ok && inRange(dat, fFrom, fTo);
                if (fJid)  ok = ok && jid.includes(fJid);
                if (fAcc)  ok = ok && accnames.includes(fAcc);
                if (fType) ok = ok && typ.includes(fType);
                if (fDC === 'debit')  ok = ok && hasDebit;
                if (fDC === 'credit') ok = ok && hasCredit;

                card.style.display = ok ? '' : 'none';
                if (ok) visible++;
            });

            els.cnt.textContent = `عدد القيود الظاهرة: ${visible}`;
        }

        ['jid', 'acc', 'type'].forEach(k => els[k].addEventListener('input', applyFilters));
        ['from', 'to', 'dc'].forEach(k => els[k].addEventListener('change', applyFilters));

        applyFilters();
    });
</script>
@endsection
