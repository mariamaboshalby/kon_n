<?php declare(strict_types=1); ?>

@extends('currencies::layout')

@section('content')
<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="currencies-icon-wrapper me-3">
                <i class="las la-exchange-alt"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-bold">{{ __('currencies::currencies.convert') }}</h5>
                <small class="text-muted">{{ __('currencies::currencies.currency_conversion') }}</small>
            </div>
        </div>
        <a href="{{ route('currencies.index') }}" class="btn btn-secondary">
            <i class="las la-arrow-left"></i> {{ __('common.back') }}
        </a>
    </div>

    <!-- Form Card -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="{{ route('currencies.perform_convert') }}" method="POST">
                @csrf

                <div class="row">
                    <!-- From Currency -->
                    <div class="col-md-6 mb-3">
                        <label for="from_currency_id" class="form-label fw-bold">
                            {{ __('currencies::currencies.from_currency') }} <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('from_currency_id') is-invalid @enderror" 
                                id="from_currency_id" 
                                name="from_currency_id"
                                required>
                            <option value="">{{ __('common.select') }}</option>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}" {{ old('from_currency_id') == $currency->id ? 'selected' : '' }}>
                                    {{ $currency->name }} ({{ $currency->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('from_currency_id')
                            <div class="invalid-feedback d-block">
                                <i class="las la-exclamation-circle me-1"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- To Currency -->
                    <div class="col-md-6 mb-3">
                        <label for="to_currency_id" class="form-label fw-bold">
                            {{ __('currencies::currencies.to_currency') }} <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('to_currency_id') is-invalid @enderror" 
                                id="to_currency_id" 
                                name="to_currency_id"
                                required>
                            <option value="">{{ __('common.select') }}</option>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}" {{ old('to_currency_id') == $currency->id ? 'selected' : '' }}>
                                    {{ $currency->name }} ({{ $currency->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('to_currency_id')
                            <div class="invalid-feedback d-block">
                                <i class="las la-exclamation-circle me-1"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <!-- Amount -->
                    <div class="col-md-6 mb-3">
                        <label for="amount" class="form-label fw-bold">
                            {{ __('currencies::currencies.amount') }} <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control @error('amount') is-invalid @enderror" 
                               id="amount" 
                               name="amount" 
                               value="{{ old('amount') }}"
                               step="0.01"
                               min="0.01"
                               placeholder="0.00"
                               required>
                        @error('amount')
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
                            <i class="las la-exchange-alt me-2"></i>{{ __('currencies::currencies.convert') }}
                        </button>
                        <a href="{{ route('currencies.index') }}" class="btn btn-secondary btn-lg">
                            <i class="las la-times me-2"></i>{{ __('common.cancel') }}
                        </a>
                    </div>
                </div>
            </form>

            <!-- Conversion Result -->
            @if (session()->has('conversion_result'))
                <hr class="my-4">
                <div class="alert alert-success">
                    <h6 class="alert-heading mb-3">
                        <i class="las la-check-circle me-2"></i>{{ __('currencies::currencies.conversion_rate') }}
                    </h6>
                    @php $result = session('conversion_result'); @endphp
                    <div class="row">
                        <div class="col-md-6">
                            <strong>{{ __('currencies::currencies.from_currency') }}:</strong>
                            <p>{{ $result['from_currency'] }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>{{ __('currencies::currencies.to_currency') }}:</strong>
                            <p>{{ $result['to_currency'] }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>{{ __('currencies::currencies.amount') }}:</strong>
                            <p>{{ number_format($result['amount'], 2) }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>{{ __('currencies::currencies.converted_amount') }}:</strong>
                            <p>{{ number_format($result['converted_amount'], 2) }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>{{ __('currencies::currencies.conversion_rate') }}:</strong>
                            <p>{{ number_format($result['rate'], 8) }}</p>
                        </div>
                    </div>
                </div>
            @endif
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
