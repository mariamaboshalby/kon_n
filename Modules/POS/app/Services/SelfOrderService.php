<?php

declare(strict_types=1);

namespace Modules\POS\app\Services;

use App\Models\Item;
use App\Models\OperationItems;
use App\Models\OperHead;
use App\Models\Setting;
use App\Models\CashierSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\POS\app\Models\CashierTransaction;
use Modules\POS\Models\RestaurantTable;

class SelfOrderService
{
    /**
     * جلب بيانات القائمة للعرض (مع كاش 5 دقائق)
     *
     * @return array{categories: array<int, mixed>, items: array<int, mixed>, tables: \Illuminate\Database\Eloquent\Collection}
     */
    public function getMenuData(): array
    {
        $items = Cache::remember('pos.self_order.menu_data', 300, function (): array {
            return $this->fetchMenuItems();
        });

        $tables = RestaurantTable::orderBy('id')->get(['id', 'name']);

        return [
            'categories' => $this->extractCategories($items),
            'items'      => $items,
            'tables'     => $tables,
        ];
    }

    /**
     * حفظ طلب العميل كـ restaurant invoice (pro_type = 103)
     *
     * @param  array<string, mixed>  $validated
     * @return array{success: bool, order_number: int, message: string}
     *
     * @throws \Throwable
     */
    public function placeOrder(array $validated): array
    {
        $result = DB::transaction(function () use ($validated): array {
            return $this->doPlaceOrder($validated);
        });

        return [
            'success'      => true,
            'order_number' => $result['order_number'],
            'message'      => __('pos::pos.self_order.order_placed_success'),
        ];
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchMenuItems(): array
    {
        $items = Item::with(['units', 'prices', 'media'])
            ->where('is_active', 1)
            ->where('isdeleted', 0)
            ->get();

        $itemIds = $items->pluck('id');

        // جلب الفئات من item_notes
        $itemCategories = DB::table('item_notes')
            ->join('note_details', function ($join): void {
                $join->on('note_details.name', '=', 'item_notes.note_detail_name')
                    ->where('note_details.note_id', '=', 2);
            })
            ->whereIn('item_notes.item_id', $itemIds)
            ->select('item_notes.item_id', 'note_details.id as category_id', 'note_details.name as category_name')
            ->get()
            ->keyBy('item_id');

        return $items->map(function (Item $item) use ($itemCategories): array {
            $firstPrice = $item->prices->first()?->pivot->price ?? 0;
            $image      = $item->getFirstMediaUrl('item-images', 'thumb')
                ?: $item->getFirstMediaUrl('item-thumbnail', 'thumb')
                ?: '';

            return [
                'id'          => $item->id,
                'name'        => $item->name,
                'code'        => $item->code,
                'sale_price'  => (float) $firstPrice,
                'category_id' => $itemCategories->get($item->id)?->category_id ?? null,
                'image'       => $image,
                'units'       => $item->units->map(fn ($u) => [
                    'id'    => $u->id,
                    'name'  => $u->name,
                    'value' => $u->pivot->u_val ?? 1,
                ])->toArray(),
                'prices' => $item->prices->map(fn ($p) => [
                    'id'    => $p->id,
                    'name'  => $p->name,
                    'value' => $p->pivot->price ?? 0,
                ])->toArray(),
            ];
        })->values()->toArray();
    }

    /**
     * استخراج الفئات الفريدة من الأصناف
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{id: int|null, name: string}>
     */
    private function extractCategories(array $items): array
    {
        $categoryIds = array_unique(array_filter(array_column($items, 'category_id')));

        if (empty($categoryIds)) {
            return [];
        }

        return DB::table('note_details')
            ->whereIn('id', $categoryIds)
            ->where('note_id', 2)
            ->select('id', 'name')
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->name])
            ->toArray();
    }

    /**
     * المنطق الداخلي لحفظ الطلب (يُستدعى داخل transaction)
     *
     * @param  array<string, mixed>  $validated
     * @return array{order_number: int, cashier_transaction_id: int}
     */
    private function doPlaceOrder(array $validated): array
    {
        $settings       = Setting::first();
        $cashierSetting = CashierSetting::first();
        $branchId       = (int) ($settings?->branch_id ?? 1);
        $storeId        = (int) ($cashierSetting?->def_pos_store ?? 1);
        $notes          = $validated['notes'] ?? __('pos::pos.self_order.default_notes');

        // حساب الإجمالي
        $subtotal = 0.0;
        foreach ($validated['items'] as $item) {
            $subtotal += (float) $item['quantity'] * (float) $item['price'];
        }

        $nextProId = (int) (OperHead::max('pro_id') ?? 0) + 1;

        // إنشاء رأس العملية
        $operHead = OperHead::create([
            'pro_id'        => $nextProId,
            'pro_date'      => now()->format('Y-m-d'),
            'accural_date'  => now()->format('Y-m-d'),
            'pro_type'      => 103,
            'acc1'          => null,
            'acc2'          => $storeId,
            'store_id'      => $storeId,
            'emp_id'        => null,
            'fat_total'     => $subtotal,
            'fat_disc'      => 0,
            'fat_disc_per'  => 0,
            'fat_plus'      => 0,
            'fat_plus_per'  => 0,
            'fat_net'       => $subtotal,
            'pro_value'     => $subtotal,
            'paid_from_client' => 0,
            'info'          => $notes,
            'details'       => $notes,
            'isdeleted'     => 0,
            'is_stock'      => 1,
            'is_finance'    => 0,
            'is_journal'    => 0,
            'journal_type'  => 2,
            'user'          => null,
            'branch_id'     => $branchId,
            'table_id'      => $validated['table_id'] ?? null,
            'order_type'    => 'dining',
        ]);

        // إنشاء تفاصيل الأصناف
        foreach ($validated['items'] as $item) {
            $itemModel = Item::find($item['id']);
            $unitId    = $item['unit_id'] ?? $itemModel?->units()->first()?->id;
            $qty       = (float) $item['quantity'];
            $price     = (float) $item['price'];

            OperationItems::create([
                'pro_tybe'            => 103,
                'detail_store'        => $storeId,
                'pro_id'              => $operHead->id,
                'item_id'             => $item['id'],
                'unit_id'             => $unitId,
                'qty_in'              => 0,
                'qty_out'             => $qty,
                'item_price'          => $price,
                'cost_price'          => 0,
                'current_stock_value' => 0,
                'item_discount'       => 0,
                'additional'          => 0,
                'detail_value'        => $qty * $price,
                'profit'              => 0,
                'is_stock'            => 1,
                'isdeleted'           => 0,
                'branch_id'           => $branchId,
            ]);
        }

        // إنشاء CashierTransaction
        $cashierTx = CashierTransaction::create([
            'local_id'               => null,
            'server_id'              => $operHead->id,
            'pro_type_id'            => 103,
            'pro_id'                 => $nextProId,
            'pro_date'               => now()->format('Y-m-d'),
            'accural_date'           => now()->format('Y-m-d'),
            'customer_id'            => null,
            'store_id'               => $storeId,
            'cash_account_id'        => null,
            'employee_id'            => null,
            'subtotal'               => $subtotal,
            'discount'               => 0,
            'discount_percentage'    => 0,
            'additional'             => 0,
            'additional_percentage'  => 0,
            'total'                  => $subtotal,
            'payment_method'         => 'cash',
            'cash_amount'            => 0,
            'card_amount'            => 0,
            'paid_amount'            => 0,
            'notes'                  => $notes,
            'table_id'               => $validated['table_id'] ?? null,
            'items'                  => $validated['items'],
            'sync_status'            => 'synced',
            'synced_at'              => now(),
            'user_id'                => null,
            'branch_id'              => $branchId,
            'status'                 => 'completed',
        ]);

        Log::info('SelfOrder: تم حفظ طلب العميل', [
            'order_number'           => $nextProId,
            'cashier_transaction_id' => $cashierTx->id,
            'table_id'               => $validated['table_id'] ?? null,
            'items_count'            => count($validated['items']),
        ]);

        return [
            'order_number'           => $nextProId,
            'cashier_transaction_id' => $cashierTx->id,
        ];
    }
}
