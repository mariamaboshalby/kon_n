<?php

declare(strict_types=1);

namespace Modules\Estate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Estate\Enums\FinishingLevel;
use Modules\Estate\Enums\ListingType;
use Modules\Estate\Enums\PaymentMethod;
use Modules\Estate\Enums\PropertyStatus;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Cast integer fields from string before validation runs.
     */
    protected function prepareForValidation(): void
    {
        $intFields = [
            'rooms_count', 'bathrooms_count', 'floor_number',
            'build_year', 'furnishing_percentage', 'installment_period_months',
        ];

        $merge = [];
        foreach ($intFields as $field) {
            if ($this->filled($field)) {
                $merge[$field] = (int) $this->input($field);
            }
        }

        if ($merge) {
            $this->merge($merge);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('property')?->id;

        return [
            // ── Identity ──────────────────────────────────────────────────────
            'title' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('properties', 'sku')->ignore($id)],
            'property_type_id' => ['required', 'exists:property_types,id'],
            'listing_type' => ['required', Rule::enum(ListingType::class)],
            'status' => ['required', Rule::enum(PropertyStatus::class)],

            // ── Stakeholders ──────────────────────────────────────────────────
            'owners' => ['nullable', 'array'],
            'owners.*' => ['exists:clients,id'],
            'agents' => ['nullable', 'array'],
            'agents.*' => ['exists:employees,id'],

            // ── Location ──────────────────────────────────────────────────────
            'city' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'address_details' => ['nullable', 'string'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            // ── Specs ─────────────────────────────────────────────────────────
            'area' => ['required', 'numeric', 'min:0.01'],
            'roof_area' => ['nullable', 'numeric', 'min:0'],
            'garden_area' => ['nullable', 'numeric', 'min:0'],
            'floor_number' => ['nullable', 'integer', 'min:0'],
            'rooms_count' => ['nullable', 'integer', 'min:0'],
            'bathrooms_count' => ['nullable', 'integer', 'min:0'],
            'finishing_level' => ['nullable', Rule::enum(FinishingLevel::class)],
            'build_year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 5)],

            // ── Furnishing ────────────────────────────────────────────────────
            'is_furnished' => ['nullable', 'boolean'],
            'furnishing_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'neighbor_notes' => ['nullable', 'string'],
            'reputation_notes' => ['nullable', 'string'],

            // ── Commercial ────────────────────────────────────────────────────
            'facade_width' => ['nullable', 'numeric', 'min:0'],
            'is_corner' => ['nullable', 'boolean'],

            // ── Land ──────────────────────────────────────────────────────────
            'land_license_type' => ['nullable', 'string', 'max:100'],
            'street_width' => ['nullable', 'numeric', 'min:0'],
            'land_shape' => ['nullable', 'string', 'max:100'],

            // ── Financials ────────────────────────────────────────────────────
            'price' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'installment_period_months' => [
                'nullable', 'integer', 'min:1',
                Rule::requiredIf(fn () => in_array($this->input('payment_method'), ['installments', 'both'])),
            ],
            'commission' => ['nullable', 'numeric', 'min:0'],

            // ── Media ─────────────────────────────────────────────────────────
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required' => __('estate::estate.validation_title_required'),
            'sku.required' => __('estate::estate.validation_sku_required'),
            'sku.unique' => __('estate::estate.validation_sku_unique'),
            'property_type_id.required' => __('estate::estate.validation_property_type_required'),
            'listing_type.required' => __('estate::estate.validation_listing_type_required'),
            'status.required' => __('estate::estate.validation_status_required'),
            'city.required' => __('estate::estate.validation_city_required'),
            'district.required' => __('estate::estate.validation_district_required'),
            'area.required' => __('estate::estate.validation_area_required'),
            'area.min' => __('estate::estate.validation_area_min'),
            'price.required' => __('estate::estate.validation_price_required'),
            'price.min' => __('estate::estate.validation_price_min'),
            'payment_method.required' => __('estate::estate.validation_payment_method_required'),
            'installment_period_months.required' => __('estate::estate.validation_installment_period_required'),
            'images.*.image' => __('estate::estate.validation_images_must_be_images'),
            'images.*.max' => __('estate::estate.validation_images_max_size'),
        ];
    }
}
