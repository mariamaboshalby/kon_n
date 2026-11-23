<?php declare(strict_types=1); ?>

@extends('currencies::layout')

@section('content')
<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="currencies-icon-wrapper me-3">
                <i class="las la-plus-circle"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-bold">{{ __('currencies::currencies.add_currency') }}</h5>
                <small class="text-muted">{{ __('currencies::currencies.create_new_currency') }}</small>
            </div>
        </div>
        <a href="{{ route('currencies.index') }}" class="btn btn-secondary">
            <i class="las la-arrow-left"></i> {{ __('common.back') }}
        </a>
    </div>

    <!-- Form Card -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="{{ route('currencies.store') }}" method="POST">
                @csrf

                <div class="row">
                    <!-- Name -->
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label fw-bold">
                            {{ __('currencies::currencies.name') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}"
                               placeholder="{{ __('currencies::currencies.name_placeholder') }}"
                               required>
                        @error('name')
                            <div class="invalid-feedback d-block">
                                <i class="las la-exclamation-circle me-1"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Code -->
                    <div class="col-md-6 mb-3">
                        <label for="code" class="form-label fw-bold">
                            {{ __('currencies::currencies.code') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('code') is-invalid @enderror" 
                               id="code" 
                               name="code" 
                               value="{{ old('code') }}"
                               placeholder="USD"
                               maxlength="3"
                               style="text-transform: uppercase;"
                               required>
                        <small class="form-text text-muted d-block mt-1">
                            <i class="las la-info-circle me-1"></i>{{ __('currencies::currencies.code_help') }}
                        </small>
                        @error('code')
                            <div class="invalid-feedback d-block">
                                <i class="las la-exclamation-circle me-1"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <!-- Symbol -->
                    <div class="col-md-6 mb-3">
                        <label for="symbol" class="form-label fw-bold">{{ __('currencies::currencies.symbol') }}</label>
                        <input type="text" 
                               class="form-control @error('symbol') is-invalid @enderror" 
                               id="symbol" 
                               name="symbol" 
                               value="{{ old('symbol') }}"
                               placeholder="$">
                        @error('symbol')
                            <div class="invalid-feedback d-block">
                                <i class="las la-exclamation-circle me-1"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Decimal Places -->
                    <div class="col-md-6 mb-3">
                        <label for="decimal_places" class="form-label fw-bold">
                            {{ __('currencies::currencies.decimal_places') }} <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control @error('decimal_places') is-invalid @enderror" 
                               id="decimal_places" 
                               name="decimal_places" 
                               value="{{ old('decimal_places', 2) }}"
                               min="0"
                               max="8"
                               required>
                        @error('decimal_places')
                            <div class="invalid-feedback d-block">
                                <i class="las la-exclamation-circle me-1"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <!-- Rate Mode -->
                    <div class="col-md-6 mb-3">
                        <label for="rate_mode" class="form-label fw-bold">
                            {{ __('currencies::currencies.rate_mode') }} <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('rate_mode') is-invalid @enderror" 
                                id="rate_mode" 
                                name="rate_mode"
                                required>
                            <option value="">{{ __('common.select') }}</option>
                            <option value="automatic" {{ old('rate_mode') === 'automatic' ? 'selected' : '' }}>
                                {{ __('currencies::currencies.rate_mode_automatic') }}
                            </option>
                            <option value="manual" {{ old('rate_mode') === 'manual' ? 'selected' : '' }}>
                                {{ __('currencies::currencies.rate_mode_manual') }}
                            </option>
                        </select>
                        @error('rate_mode')
                            <div class="invalid-feedback d-block">
                                <i class="las la-exclamation-circle me-1"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                <hr class="my-4">

                <!-- Checkboxes -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="is_default" 
                                   name="is_default" 
                                   value="1"
                                   {{ old('is_default') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_default">
                                <i class="las la-star me-1"></i>{{ __('currencies::currencies.set_as_default') }}
                            </label>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="is_active" 
                                   name="is_active" 
                                   value="1"
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                <i class="las la-check-circle me-1"></i>{{ __('common.active') }}
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="row mt-4">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="las la-save me-2"></i>{{ __('common.save') }}
                        </button>
                        <a href="{{ route('currencies.index') }}" class="btn btn-secondary btn-lg">
                            <i class="las la-times me-2"></i>{{ __('common.cancel') }}
                        </a>
                    </div>
                </div>
            </form>
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
