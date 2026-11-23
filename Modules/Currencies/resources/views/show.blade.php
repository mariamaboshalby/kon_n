<?php declare(strict_types=1); ?>

@extends('currencies::layout')

@section('content')
<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="currencies-icon-wrapper me-3">
                <i class="las la-eye"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-bold">{{ $currency->name }}</h5>
                <small class="text-muted">{{ $currency->code }} • {{ __('currencies::currencies.currency_details') }}</small>
            </div>
        </div>
        <div class="btn-group" role="group">
            <a href="{{ route('currencies.edit', $currency) }}" class="btn btn-primary">
                <i class="las la-edit me-2"></i>{{ __('common.edit') }}
            </a>
            <a href="{{ route('currencies.index') }}" class="btn btn-secondary">
                <i class="las la-arrow-left me-2"></i>{{ __('common.back') }}
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Currency Details -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="las la-info-circle me-2"></i>{{ __('currencies::currencies.currency_details') }}
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-5">
                            <strong>{{ __('currencies::currencies.name') }}:</strong>
                        </div>
                        <div class="col-sm-7">
                            {{ $currency->name }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-5">
                            <strong>{{ __('currencies::currencies.code') }}:</strong>
                        </div>
                        <div class="col-sm-7">
                            <code class="bg-light px-2 py-1 rounded">{{ $currency->code }}</code>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-5">
                            <strong>{{ __('currencies::currencies.symbol') }}:</strong>
                        </div>
                        <div class="col-sm-7">
                            {{ $currency->symbol ?? '-' }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-5">
                            <strong>{{ __('currencies::currencies.decimal_places') }}:</strong>
                        </div>
                        <div class="col-sm-7">
                            <span class="badge bg-light text-dark">{{ $currency->decimal_places }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-5">
                            <strong>{{ __('currencies::currencies.rate_mode') }}:</strong>
                        </div>
                        <div class="col-sm-7">
                            <span class="badge bg-{{ $currency->rate_mode === 'automatic' ? 'primary' : 'warning' }}">
                                {{ __("currencies::currencies.rate_mode_{$currency->rate_mode}") }}
                            </span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-5">
                            <strong>{{ __('common.status') }}:</strong>
                        </div>
                        <div class="col-sm-7">
                            @if ($currency->is_active)
                                <span class="badge bg-success">
                                    <i class="las la-check-circle me-1"></i>{{ __('common.active') }}
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    <i class="las la-times-circle me-1"></i>{{ __('common.inactive') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    @if ($currency->is_default)
                        <div class="row mb-3">
                            <div class="col-sm-5">
                                <strong>{{ __('currencies::currencies.default') }}:</strong>
                            </div>
                            <div class="col-sm-7">
                                <span class="badge bg-success">
                                    <i class="las la-star me-1"></i>{{ __('common.yes') }}
                                </span>
                            </div>
                        </div>
                    @endif

                    <hr>

                    <div class="row">
                        <div class="col-sm-5">
                            <strong>{{ __('common.created_at') }}:</strong>
                        </div>
                        <div class="col-sm-7">
                            <small class="text-muted">{{ $currency->created_at->format('Y-m-d H:i') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exchange Rate History -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="las la-history me-2"></i>{{ __('currencies::currencies.exchange_rate_history') }}
                    </h6>
                </div>
                <div class="card-body">
                    @if ($currency->exchangeRates->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fw-bold">{{ __('currencies::currencies.date') }}</th>
                                        <th class="fw-bold text-end">{{ __('currencies::currencies.rate') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($currency->exchangeRates->sortByDesc('rate_date')->take(10) as $rate)
                                        <tr>
                                            <td>
                                                <small>{{ $rate->rate_date->format('Y-m-d') }}</small>
                                            </td>
                                            <td class="text-end">
                                                <code class="bg-light px-2 py-1 rounded">{{ number_format((float) $rate->rate, 8) }}</code>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted d-block mt-3">
                            <i class="las la-info-circle me-1"></i>
                            {{ __('currencies::currencies.showing_latest_rates', ['count' => min(10, $currency->exchangeRates->count())]) }}
                        </small>
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="las la-info-circle me-2"></i>{{ __('currencies::currencies.no_exchange_rates') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .currencies-icon-wrapper {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #34d3a3 0%, #34d3a3 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.3rem;
    }
</style>
@endsection
