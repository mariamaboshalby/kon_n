<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('portal.account_statement') }} - {{ $account->aname }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    @if(app()->getLocale() === 'ar')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    @endif
    <style>
        .btn-op-link { font-size: .8rem; padding: 2px 8px; white-space: nowrap; }
        #operationModal .modal-header { background: #f8f9fa; }
        .items-table th { background: #f1f3f5; font-size: .85rem; }
        .items-table td { font-size: .85rem; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-light bg-white border-bottom mb-4">
    <div class="container">
        <span class="navbar-brand fw-bold">{{ __('portal.client_portal') }}</span>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted">{{ $account->aname }}</span>
            <form method="POST" action="{{ route('portal.logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('portal.logout') }}</button>
            </form>
        </div>
    </div>
</nav>

<div class="container">

    {{-- Balance summary --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted small">{{ __('portal.current_balance') }}</div>
                    <div class="fs-4 fw-bold {{ $account->balance < 0 ? 'text-danger' : 'text-success' }}">
                        {{ number_format($account->balance, 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Date filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('portal.statement') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">{{ __('portal.from_date') }}</label>
                    <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('portal.to_date') }}</label>
                    <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">{{ __('portal.filter') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Movements table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('portal.date') }}</th>
                            <th>{{ __('portal.description') }}</th>
                            <th class="text-end">{{ __('portal.debit') }}</th>
                            <th class="text-end">{{ __('portal.credit') }}</th>
                            <th class="text-end">{{ __('portal.balance') }}</th>
                            <th class="text-center">{{ __('portal.operation') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Opening balance row --}}
                        <tr class="table-secondary">
                            <td colspan="4" class="fw-bold">{{ __('portal.opening_balance') }}</td>
                            <td class="text-end fw-bold {{ $balanceBefore < 0 ? 'text-danger' : '' }}">
                                {{ number_format($balanceBefore, 2) }}
                            </td>
                            <td></td>
                        </tr>

                        @php $running = $balanceBefore; @endphp

                        @forelse($movements as $movement)
                            @php $running += $movement->debit - $movement->credit; @endphp
                            <tr>
                                <td>{{ $movement->crtime?->format('Y-m-d') }}</td>
                                <td>{{ $movement->notes ?? '#' . $movement->op_id }}</td>
                                <td class="text-end">
                                    @if($movement->debit > 0)
                                        {{ number_format($movement->debit, 2) }}
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($movement->credit > 0)
                                        {{ number_format($movement->credit, 2) }}
                                    @endif
                                </td>
                                <td class="text-end fw-bold {{ $running < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($running, 2) }}
                                </td>
                                <td class="text-center">
                                    @if($movement->op_id)
                                        <button class="btn btn-sm btn-outline-primary btn-op-link"
                                                data-op-id="{{ $movement->op_id }}"
                                                onclick="portalShowOperation({{ $movement->op_id }})">
                                            #{{ $movement->op_id }}
                                        </button>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    {{ __('portal.no_movements') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($movements->hasPages())
        <div class="card-footer">
            {{ $movements->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

</div>

{{-- Operation Details Modal --}}
<div class="modal fade" id="operationModal" tabindex="-1" aria-labelledby="operationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="operationModalLabel">{{ __('portal.operation_details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">

                {{-- Loading state --}}
                <div id="portalOpLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('portal.loading') }}</span>
                    </div>
                </div>

                {{-- Error state --}}
                <div id="portalOpError" class="alert alert-danger d-none"></div>

                {{-- Content --}}
                <div id="portalOpContent" class="d-none">
                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <span class="text-muted small">{{ __('portal.operation_number') }}</span>
                            <div class="fw-bold" id="portalOpId"></div>
                        </div>
                        <div class="col-sm-4">
                            <span class="text-muted small">{{ __('portal.operation_type') }}</span>
                            <div class="fw-bold" id="portalOpType"></div>
                        </div>
                        <div class="col-sm-4">
                            <span class="text-muted small">{{ __('portal.date') }}</span>
                            <div class="fw-bold" id="portalOpDate"></div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <span class="text-muted small">{{ __('portal.total_value') }}</span>
                            <div class="fw-bold text-primary" id="portalOpValue"></div>
                        </div>
                        <div class="col-sm-8">
                            <span class="text-muted small">{{ __('portal.notes') }}</span>
                            <div id="portalOpNotes"></div>
                        </div>
                    </div>

                    {{-- Items table --}}
                    <div id="portalOpItemsSection">
                        <hr>
                        <h6 class="mb-2">{{ __('portal.items') }}</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered items-table mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('portal.item_name') }}</th>
                                        <th class="text-center">{{ __('portal.qty') }}</th>
                                        <th class="text-center">{{ __('portal.unit') }}</th>
                                        <th class="text-end">{{ __('portal.price') }}</th>
                                        <th class="text-end">{{ __('portal.total') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="portalOpItemsBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
(function () {
    'use strict';

    var portalOpModal = new bootstrap.Modal(document.getElementById('operationModal'));
    var portalOpBaseUrl = '{{ rtrim(route("portal.operation.details", ["opId" => "__ID__"]), "") }}';

    window.portalShowOperation = function (opId) {
        // Reset state
        $('#portalOpLoading').removeClass('d-none');
        $('#portalOpContent').addClass('d-none');
        $('#portalOpError').addClass('d-none').text('');

        portalOpModal.show();

        var url = portalOpBaseUrl.replace('__ID__', opId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (data) {
                $('#portalOpId').text('#' + data.id);
                $('#portalOpType').text(data.type || '-');
                $('#portalOpDate').text(data.date || '-');
                $('#portalOpValue').text(data.value || '0.00');
                $('#portalOpNotes').text(data.notes || '-');

                var $tbody = $('#portalOpItemsBody').empty();

                if (data.items && data.items.length > 0) {
                    $.each(data.items, function (i, item) {
                        $tbody.append(
                            '<tr>' +
                            '<td>' + $('<span>').text(item.name).html() + '</td>' +
                            '<td class="text-center">' + $('<span>').text(item.qty).html() + '</td>' +
                            '<td class="text-center">' + $('<span>').text(item.unit).html() + '</td>' +
                            '<td class="text-end">' + $('<span>').text(item.price).html() + '</td>' +
                            '<td class="text-end">' + $('<span>').text(item.total).html() + '</td>' +
                            '</tr>'
                        );
                    });
                    $('#portalOpItemsSection').removeClass('d-none');
                } else {
                    $('#portalOpItemsSection').addClass('d-none');
                }

                $('#portalOpLoading').addClass('d-none');
                $('#portalOpContent').removeClass('d-none');
            },
            error: function (xhr) {
                $('#portalOpLoading').addClass('d-none');
                var msg = (xhr.responseJSON && xhr.responseJSON.error)
                    ? xhr.responseJSON.error
                    : '{{ __("portal.error_loading") }}';
                $('#portalOpError').removeClass('d-none').text(msg);
            }
        });
    };
}());
</script>
</body>
</html>
