@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.installments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('installments::installments.all_installments'),
        'breadcrumb_items' => [
            ['label' => __('installments::installments.dashboard'), 'url' => route('admin.dashboard')],
            ['label' => __('installments::installments.all_installments')],
        ],
    ])
    
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <!-- Filters Section -->
                    <div class="row mb-4" dir="rtl">
                        <div class="col-12">
                            <div class="row g-3">
                                <!-- Client Filter with Live Search -->
                                <div class="col-md-3">
                                    <label for="client_search" class="form-label">{{ __('installments::installments.client') }}</label>
                                    <input 
                                        type="text" 
                                        id="client_search"
                                        class="form-control" 
                                        value="{{ request('client_search') }}"
                                        placeholder="{{ __('installments::installments.search') }}..."
                                        x-data
                                        @input.debounce.500ms="window.location.href = '{{ route('installments.payments.all') }}?client_search=' + $el.value + '&status={{ request('status') }}&date_from={{ request('date_from') }}&date_to={{ request('date_to') }}&plan_id={{ request('plan_id') }}'">
                                </div>

                                <form method="GET" action="{{ route('installments.payments.all') }}" class="col-md-9">
                                    <input type="hidden" name="client_search" value="{{ request('client_search') }}">
                                    <div class="row g-3">
                                        <!-- Status Filter -->
                                        <div class="col-md-2">
                                            <label for="status" class="form-label">{{ __('installments::installments.status') }}</label>
                                            <select name="status" id="status" class="form-select">
                                                <option value="">{{ __('installments::installments.all') }}</option>
                                                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>{{ __('installments::installments.paid') }}</option>
                                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('installments::installments.pending') }}</option>
                                                <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>{{ __('installments::installments.overdue') }}</option>
                                            </select>
                                        </div>

                                        <!-- Plan ID Filter -->
                                        <div class="col-md-2">
                                            <label for="plan_id" class="form-label">{{ __('installments::installments.plan_number') }}</label>
                                            <input 
                                                type="number" 
                                                name="plan_id" 
                                                id="plan_id"
                                                class="form-control" 
                                                value="{{ request('plan_id') }}"
                                                placeholder="{{ __('installments::installments.plan_number') }}">
                                        </div>

                                        <!-- Date From -->
                                        <div class="col-md-2">
                                            <label for="date_from" class="form-label">{{ __('installments::installments.from_date') }}</label>
                                            <input 
                                                type="date" 
                                                name="date_from" 
                                                id="date_from"
                                                class="form-control" 
                                                value="{{ request('date_from') }}">
                                        </div>

                                        <!-- Date To -->
                                        <div class="col-md-2">
                                            <label for="date_to" class="form-label">{{ __('installments::installments.to_date') }}</label>
                                            <input 
                                                type="date" 
                                                name="date_to" 
                                                id="date_to"
                                                class="form-control" 
                                                value="{{ request('date_to') }}">
                                        </div>

                                        <!-- Buttons -->
                                        <div class="col-md-3 d-flex align-items-end gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="las la-search"></i>
                                                {{ __('installments::installments.search') }}
                                            </button>
                                            <a href="{{ route('installments.payments.all') }}" class="btn btn-secondary">
                                                <i class="las la-redo-alt"></i>
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-light text-center align-middle">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('installments::installments.plan_number') }}</th>
                                    <th>{{ __('installments::installments.client') }}</th>
                                    <th>{{ __('installments::installments.installment_number') }}</th>
                                    <th>{{ __('installments::installments.amount_due') }}</th>
                                    <th>{{ __('installments::installments.amount_paid') }}</th>
                                    <th>{{ __('installments::installments.due_date') }}</th>
                                    <th>{{ __('installments::installments.payment_date') }}</th>
                                    <th>{{ __('installments::installments.status') }}</th>
                                    <th>{{ __('installments::installments.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($payments as $payment)
                                    <tr class="text-center">
                                        <td>{{ $payment->id }}</td>
                                        <td>
                                            <a href="{{ route('installments.plans.show', $payment->plan->id) }}" class="text-primary fw-bold">
                                                #{{ $payment->plan->id }}
                                            </a>
                                        </td>
                                        <td>{{ $payment->plan->account->aname ?? __('installments::installments.not_applicable') }}</td>
                                        <td>
                                            <span class="badge bg-info">
                                                {{ __('installments::installments.installment') }} {{ $payment->installment_number }}
                                            </span>
                                        </td>
                                        <td>{{ number_format($payment->amount_due, 2) }}</td>
                                        <td>{{ number_format($payment->amount_paid ?? 0, 2) }}</td>
                                        <td>{{ $payment->due_date->format('Y-m-d') }}</td>
                                        <td>{{ $payment->payment_date ? $payment->payment_date->format('Y-m-d') : '-' }}</td>
                                        <td>
                                            @if($payment->status === 'paid')
                                                <span class="badge bg-success">{{ __('installments::installments.paid') }}</span>
                                            @elseif($payment->status === 'overdue')
                                                <span class="badge bg-danger">{{ __('installments::installments.overdue') }}</span>
                                            @else
                                                <span class="badge bg-warning">{{ __('installments::installments.pending') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @can('view Installment Plans')
                                                <a class="btn btn-info btn-sm"
                                                    href="{{ route('installments.plans.show', $payment->plan->id) }}"
                                                    title="{{ __('installments::installments.view_plan') }}">
                                                    <i class="las la-eye"></i>
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">
                                            <div class="alert alert-info py-3 mb-0">
                                                <i class="las la-info-circle me-2"></i>
                                                {{ __('installments::installments.no_data_available') }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-3">
                        {{ $payments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
