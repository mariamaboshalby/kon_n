<?php declare(strict_types=1); ?>

@extends('currencies::layout')

@section('content')
<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="currencies-icon-wrapper me-3">
                <i class="las la-balance-scale"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-bold">{{ __('currencies::currencies.cash_box_revaluation') }}</h5>
                <small class="text-muted">{{ __('currencies::currencies.revaluation_date') }}</small>
            </div>
        </div>
        <a href="{{ route('currencies.index') }}" class="btn btn-secondary">
            <i class="las la-arrow-left"></i> {{ __('common.back') }}
        </a>
    </div>

    <!-- Form Card -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="{{ route('currencies.perform_revaluation') }}" method="POST">
                @csrf

                <div class="row">
                    <!-- Currency -->
                    <div class="col-md-6 mb-3">
                        <label for="currency_id" class="form-label fw-bold">
                            {{ __('currencies::currencies.currency') }} <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('currency_id') is-invalid @enderror" 
                                id="currency_id" 
                                name="currency_id"
                                required>
                            <option value="">{{ __('common.select') }}</option>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}" {{ old('currency_id') == $currency->id ? 'selected' : '' }}>
                                    {{ $currency->name }} ({{ $currency->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('currency_id')
                            <div class="invalid-feedback d-block">
                                <i class="las la-exclamation-circle me-1"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Revaluation Date -->
                    <div class="col-md-6 mb-3">
                        <label for="revaluation_date" class="form-label fw-bold">
                            {{ __('currencies::currencies.revaluation_date') }} <span class="text-danger">*</span>
                        </label>
                        <input type="date" 
                               class="form-control @error('revaluation_date') is-invalid @enderror" 
                               id="revaluation_date" 
                               name="revaluation_date" 
                               value="{{ old('revaluation_date', now()->format('Y-m-d')) }}"
                               required>
                        @error('revaluation_date')
                            <div class="invalid-feedback d-block">
                                <i class="las la-exclamation-circle me-1"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                <!-- Buttons -->
                <div class="row mt-4">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="las la-balance-scale me-2"></i>{{ __('currencies::currencies.revalue') }}
                        </button>
                        <a href="{{ route('currencies.index') }}" class="btn btn-secondary btn-lg">
                            <i class="las la-times me-2"></i>{{ __('common.cancel') }}
                        </a>
                    </div>
                </div>
            </form>

            <!-- Information Section -->
            <hr class="my-4">
            <div class="alert alert-info">
                <h6 class="alert-heading mb-3">
                    <i class="las la-info-circle me-2"></i>{{ __('common.information') }}
                </h6>
                <p class="mb-0">
                    {{ __('currencies::currencies.cash_box_revaluation') }} 
                    {{ __('currencies::currencies.revaluation_history') }}
                </p>
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
