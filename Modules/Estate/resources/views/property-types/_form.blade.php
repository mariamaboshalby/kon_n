@php
    $isEdit = isset($propertyType);
    $model  = $isEdit ? $propertyType : null;
@endphp

<div class="row g-3">
    {{-- Parent --}}
    <div class="col-md-6">
        <label class="form-label">{{ __('estate::estate.parent_type') }}</label>
        <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
            <option value="">— {{ __('estate::estate.root_type') }} —</option>
            @foreach($parents as $parent)
                <option value="{{ $parent->id }}"
                    {{ old('parent_id', $model?->parent_id ?? $selectedParent ?? '') == $parent->id ? 'selected' : '' }}>
                    {{ $parent->name }}
                </option>
            @endforeach
        </select>
        @error('parent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Sort Order --}}
    <div class="col-md-3">
        <label class="form-label">{{ __('estate::estate.sort_order') }}</label>
        <input type="number" name="sort_order" class="form-control" min="0"
               value="{{ old('sort_order', $model?->sort_order ?? 0) }}">
    </div>

    {{-- Active --}}
    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                   {{ old('is_active', $model?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">{{ __('common.active') }}</label>
        </div>
    </div>

    {{-- Name AR --}}
    <div class="col-md-6">
        <label class="form-label required">{{ __('estate::estate.name_ar') }}</label>
        <input type="text" name="name_ar" dir="rtl"
               class="form-control @error('name_ar') is-invalid @enderror"
               value="{{ old('name_ar', $model?->name_ar) }}" required>
        @error('name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Name EN --}}
    <div class="col-md-6">
        <label class="form-label required">{{ __('estate::estate.name_en') }}</label>
        <input type="text" name="name_en" dir="ltr"
               class="form-control @error('name_en') is-invalid @enderror"
               value="{{ old('name_en', $model?->name_en) }}" required>
        @error('name_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Slug --}}
    <div class="col-md-6">
        <label class="form-label required">{{ __('estate::estate.slug') }}</label>
        <input type="text" name="slug" dir="ltr"
               class="form-control @error('slug') is-invalid @enderror"
               value="{{ old('slug', $model?->slug) }}"
               placeholder="e.g. apartment" required>
        <small class="text-muted">{{ __('estate::estate.slug_hint') }}</small>
        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Icon --}}
    <div class="col-md-6">
        <label class="form-label">{{ __('estate::estate.icon') }}</label>
        <div class="input-group">
            <span class="input-group-text" id="icon-preview">
                <i class="{{ old('icon', $model?->icon ?? 'las la-building') }}"></i>
            </span>
            <input type="text" name="icon" dir="ltr"
                   class="form-control @error('icon') is-invalid @enderror"
                   value="{{ old('icon', $model?->icon ?? 'las la-building') }}"
                   placeholder="las la-building"
                   oninput="document.querySelector('#icon-preview i').className = this.value">
        </div>
        @error('icon') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Attributes --}}
    <div class="col-12">
        <label class="form-label">{{ __('estate::estate.type_attributes') }}</label>
        <div class="border rounded p-3">
            <div class="row g-2">
                @foreach($attributes as $key => $labels)
                    @php $checked = in_array($key, old('attributes', $model?->attributes ?? [])); @endphp
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check">
                            <input type="checkbox" name="attributes[]" value="{{ $key }}"
                                   class="form-check-input" id="attr_{{ $key }}"
                                   {{ $checked ? 'checked' : '' }}>
                            <label class="form-check-label" for="attr_{{ $key }}">
                                {{ app()->getLocale() === 'ar' ? $labels['ar'] : $labels['en'] }}
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <small class="text-muted">{{ __('estate::estate.attributes_hint') }}</small>
    </div>
</div>
