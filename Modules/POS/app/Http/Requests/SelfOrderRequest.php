<?php

declare(strict_types=1);

namespace Modules\POS\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelfOrderRequest extends FormRequest
{
    /**
     * الجميع مسموح لهم بإرسال الطلب (راوت عام بدون auth)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق من بيانات الطلب
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items'            => ['required', 'array', 'min:1'],
            'items.*.id'       => ['required', 'integer', 'exists:items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:999'],
            'items.*.price'    => ['required', 'numeric', 'min:0'],
            'items.*.unit_id'  => ['nullable', 'integer'],
            'items.*.name'     => ['nullable', 'string', 'max:255'],
            'table_id'         => ['nullable', 'integer', 'exists:restaurant_tables,id'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * رسائل الخطأ المخصصة
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required'            => __('pos::pos.self_order.items_required'),
            'items.min'                 => __('pos::pos.self_order.items_min'),
            'items.*.id.required'       => __('pos::pos.self_order.item_id_required'),
            'items.*.id.exists'         => __('pos::pos.self_order.item_not_found'),
            'items.*.quantity.required' => __('pos::pos.self_order.quantity_required'),
            'items.*.quantity.min'      => __('pos::pos.self_order.quantity_min'),
            'items.*.quantity.max'      => __('pos::pos.self_order.quantity_max'),
            'items.*.price.required'    => __('pos::pos.self_order.price_required'),
            'items.*.price.min'         => __('pos::pos.self_order.price_min'),
            'table_id.exists'           => __('pos::pos.self_order.table_not_found'),
            'notes.max'                 => __('pos::pos.self_order.notes_max'),
        ];
    }
}
