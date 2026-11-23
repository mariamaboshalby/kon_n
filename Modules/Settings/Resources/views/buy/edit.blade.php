@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.currency-exchange')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('settings::settings.edit_currency_exchange'),
        'breadcrumb_items' => [
            ['label' => __('settings::settings.home'), 'url' => route('admin.dashboard')],
            ['label' => __('settings::settings.buy_currency'), 'url' => route('settings.buy-currency.index')],
            ['label' => __('settings::settings.edit_operation')],
        ],
    ])

    <div class="content-wrapper">
        <section class="content">
            <form action="{{ route('settings.buy-currency.update', $exchange->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card col-md-10 container">
                    <div class="card-header bg-success bg-opacity-10 border-bottom border-success">
                        <h2 class="card-title d-flex align-items-center gap-2">
                            <i class="las la-edit text-success fs-3"></i>
                            {{ __('settings::settings.edit_currency_exchange') }} — {{ __('settings::settings.buy_currency') }}
                        </h2>
                    </div>

                    <div class="card-body">
                        @include('settings::partials._exchange_form_fields', [
                            'isEdit'      => true,
                            'accentColor' => 'success',
                        ])
                    </div>

                    <div class="card-footer d-flex gap-2">
                        <button class="btn btn-success" type="submit">
                            <i class="las la-save me-1"></i>{{ __('settings::settings.save') }}
                        </button>
                        <a href="{{ route('settings.buy-currency.index') }}" class="btn btn-secondary">
                            <i class="las la-times me-1"></i>{{ __('settings::settings.cancel') }}
                        </a>
                    </div>
                </div>
            </form>
        </section>
    </div>
@endsection
