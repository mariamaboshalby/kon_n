@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.reports')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('reports::reports.pos_slow_movers_report'),
        'breadcrumb_items' => [
            ['label' => __('reports::reports.home'), 'url' => route('admin.dashboard')],
            ['label' => __('reports::reports.sales_reports')],
            ['label' => __('reports::reports.pos_slow_movers_report')],
        ],
    ])

    <div class="container-fluid">

        {{-- فلاتر --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">{{ __('reports::reports.idle_days_threshold') }}</label>
                        <div class="input-group">
                            <input type="number" name="idle_days" class="form-control" value="{{ $idleDays }}" min="1" max="365">
                            <span class="input-group-text">{{ __('reports::reports.days') }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">{{ __('reports::reports.branch') }}</label>
                        <select name="branch_id" class="form-select">
                            <option value="">{{ __('reports::reports.all') }}</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-search me-1"></i> {{ __('reports::reports.search') }}
                        </button>
                        <a href="{{ route('reports.pos.slow-movers') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- تنبيه --}}
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
            <div>
                {{ __('reports::reports.slow_movers_alert', ['days' => $idleDays, 'date' => $cutoffDate]) }}
                <strong>{{ number_format($slowMovers->total()) }}</strong> {{ __('reports::reports.items') }}
            </div>
        </div>

        {{-- الجدول --}}
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-pause-circle text-warning me-2"></i>{{ __('reports::reports.pos_slow_movers_report') }}
                <span class="badge bg-warning text-dark ms-2">{{ number_format($slowMovers->total()) }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="slowMoversTable" class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('reports::reports.code') }}</th>
                                <th>{{ __('reports::reports.item_name') }}</th>
                                <th class="text-end">{{ __('reports::reports.average_cost') }}</th>
                                <th class="text-center">{{ __('reports::reports.last_sale_date') }}</th>
                                <th class="text-end">{{ __('reports::reports.idle_days') }}</th>
                                <th class="text-end">{{ __('reports::reports.total_quantity') }}</th>
                                <th class="text-end">{{ __('reports::reports.invoices_count') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($slowMovers as $index => $item)
                                @php
                                    $idleDaysCount = $item->last_sale_date
                                        ? \Carbon\Carbon::parse($item->last_sale_date)->diffInDays(today())
                                        : null;
                                    $rowClass = match(true) {
                                        $idleDaysCount === null       => 'table-danger',
                                        $idleDaysCount > 90           => 'table-warning',
                                        default                       => '',
                                    };
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td>{{ $slowMovers->firstItem() + $index }}</td>
                                    <td><code>{{ $item->code ?? '---' }}</code></td>
                                    <td>{{ $item->name }}</td>
                                    <td class="text-end">{{ number_format($item->average_cost ?? 0, 2) }}</td>
                                    <td class="text-center">
                                        @if($item->last_sale_date)
                                            {{ $item->last_sale_date }}
                                        @else
                                            <span class="badge bg-danger">{{ __('reports::reports.never_sold') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">
                                        @if($idleDaysCount !== null)
                                            <span class="badge {{ $idleDaysCount > 90 ? 'bg-danger' : 'bg-warning text-dark' }}">
                                                {{ $idleDaysCount }}
                                            </span>
                                        @else
                                            <span class="badge bg-danger">∞</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($item->total_sold ?? 0, 2) }}</td>
                                    <td class="text-end">{{ number_format($item->invoices_count ?? 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
                                        <p class="text-muted">{{ __('reports::reports.no_idle_items') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($slowMovers->hasPages())
                <div class="card-footer">{{ $slowMovers->appends(request()->query())->links() }}</div>
            @endif
        </div>

    </div>
@endsection
