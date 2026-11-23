@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.currency-exchange')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('settings::settings.buy_currency'),
        'breadcrumb_items' => [
            ['label' => __('settings::settings.home'), 'url' => route('admin.dashboard')],
            ['label' => __('settings::settings.buy_currency')],
        ],
    ])

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center bg-success bg-opacity-10 border-bottom border-success">
            <h2 class="d-flex align-items-center gap-2 mb-0">
                <i class="las la-shopping-cart text-success fs-3"></i>
                {{ __('settings::settings.buy_currency_operations') }}
            </h2>
            @can('create Currency Exchange')
                <a href="{{ route('settings.buy-currency.create') }}" class="btn btn-success">
                    <i class="las la-plus me-1"></i>
                    {{ __('settings::settings.add_buy_operation') }}
                </a>
            @endcan
        </div>

        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-success">
                        <tr>
                            <th>#</th>
                            <th>{{ __('settings::settings.date') }}</th>
                            <th>{{ __('settings::settings.bond_number') }}</th>
                            <th>{{ __('settings::settings.from_fund') }}</th>
                            <th>{{ __('settings::settings.to_fund') }}</th>
                            <th>{{ __('settings::settings.currency_label') }}</th>
                            <th>{{ __('settings::settings.original_amount') }}</th>
                            <th>{{ __('settings::settings.converted_amount') }}</th>
                            <th>{{ __('settings::settings.description') }}</th>
                            <th>{{ __('settings::settings.user') }}</th>
                            <th class="text-center">{{ __('settings::settings.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($exchanges as $exchange)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $exchange->pro_date }}</td>
                                <td>{{ $exchange->pro_id }}</td>
                                <td>{{ $exchange->acc2Head->aname ?? '—' }}</td>
                                <td>{{ $exchange->acc1Head->aname ?? '—' }}</td>
                                <td><span class="badge bg-success">{{ $exchange->currency->name ?? '—' }}</span></td>
                                <td class="fw-bold">
                                    {{ $exchange->currency_rate > 0 ? number_format($exchange->pro_value / $exchange->currency_rate, 2) : number_format($exchange->pro_value, 2) }}
                                </td>
                                <td class="fw-bold text-success">{{ number_format($exchange->pro_value, 2) }}</td>
                                <td>{{ $exchange->details ?? '—' }}</td>
                                <td>{{ $exchange->user->name ?? '—' }}</td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        @can('edit Currency Exchange')
                                            <a href="{{ route('settings.buy-currency.edit', $exchange->id) }}"
                                                class="btn btn-sm btn-success" title="{{ __('settings::settings.edit_operation') }}">
                                                <i class="las la-edit"></i>
                                            </a>
                                        @endcan
                                        @can('delete Currency Exchange')
                                            <form action="{{ route('settings.buy-currency.destroy', $exchange->id) }}"
                                                method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger"
                                                    onclick="return confirm('{{ __('settings::settings.confirm_delete_operation') }}')"
                                                    title="{{ __('settings::settings.remove') }}">
                                                    <i class="las la-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center">
                                    <div class="alert alert-info py-4 mb-0">
                                        <i class="las la-info-circle me-2"></i>
                                        <strong>{{ __('settings::settings.no_buy_operations') }}</strong>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
