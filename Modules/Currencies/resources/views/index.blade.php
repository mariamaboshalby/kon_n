<?php declare(strict_types=1); ?>

@extends('currencies::layout')

@section('content')
<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="currencies-icon-wrapper me-3">
                <i class="las la-dollar-sign"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-bold">{{ __('currencies::currencies.currencies') }}</h5>
                <small class="text-muted">{{ __('currencies::currencies.manage_currencies') }}</small>
            </div>
        </div>
        <a href="{{ route('currencies.create') }}" class="btn btn-primary">
            <i class="las la-plus"></i> {{ __('currencies::currencies.add_currency') }}
        </a>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="las la-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="las la-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Currencies Table -->
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="fw-bold">{{ __('currencies::currencies.name') }}</th>
                        <th class="fw-bold">{{ __('currencies::currencies.code') }}</th>
                        <th class="fw-bold">{{ __('currencies::currencies.symbol') }}</th>
                        <th class="fw-bold">{{ __('currencies::currencies.decimal_places') }}</th>
                        <th class="fw-bold">{{ __('currencies::currencies.current_rate') }}</th>
                        <th class="fw-bold">{{ __('currencies::currencies.rate_mode') }}</th>
                        <th class="fw-bold">{{ __('common.status') }}</th>
                        <th class="fw-bold text-center">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($currencies as $currency)
                        <tr>
                            <td>
                                <strong>{{ $currency->name }}</strong>
                                @if ($currency->is_default)
                                    <span class="badge bg-success ms-2">{{ __('currencies::currencies.default') }}</span>
                                @endif
                            </td>
                            <td>
                                <code class="bg-light px-2 py-1 rounded">{{ $currency->code }}</code>
                            </td>
                            <td>{{ $currency->symbol ?? '-' }}</td>
                            <td>
                                <span class="badge bg-light text-dark">{{ $currency->decimal_places }}</span>
                            </td>
                            <td>
                                @if ($currency->latestRate)
                                    <span class="badge bg-info">{{ number_format((float) $currency->latestRate->rate, 8) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $currency->rate_mode === 'automatic' ? 'primary' : 'warning' }}">
                                    {{ __("currencies::currencies.rate_mode_{$currency->rate_mode}") }}
                                </span>
                            </td>
                            <td>
                                @if ($currency->is_active)
                                    <span class="badge bg-success">
                                        <i class="las la-check-circle me-1"></i>{{ __('common.active') }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="las la-times-circle me-1"></i>{{ __('common.inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('currencies.show', $currency) }}" 
                                       class="btn btn-outline-info" 
                                       title="{{ __('common.view') }}"
                                       data-bs-toggle="tooltip">
                                        <i class="las la-eye"></i>
                                    </a>
                                    <a href="{{ route('currencies.edit', $currency) }}" 
                                       class="btn btn-outline-primary" 
                                       title="{{ __('common.edit') }}"
                                       data-bs-toggle="tooltip">
                                        <i class="las la-edit"></i>
                                    </a>
                                    @if (!$currency->is_default)
                                        <form action="{{ route('currencies.destroy', $currency) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="btn btn-outline-danger btn-sm"
                                                    title="{{ __('common.delete') }}"
                                                    data-bs-toggle="tooltip"
                                                    onclick="return confirm('{{ __('common.confirm_delete') }}')">
                                                <i class="las la-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="las la-inbox la-3x mb-3 d-block opacity-50"></i>
                                {{ __('currencies::currencies.no_currencies') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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

    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
</style>
@endsection
