<?php

declare(strict_types=1);

namespace Modules\POS\app\Services;

use App\Models\Barcode;
use App\Models\Client;
use App\Models\Item;
use App\Models\OperationItems;
use App\Models\OperHead;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Models\AccHead;
use Modules\POS\app\Models\KitchenPrinterStation;
use Modules\POS\Models\DeliveryArea;
use Modules\POS\Models\Driver;
use Modules\POS\Models\RestaurantTable;

class POSService
{
    /**
     * جلب إحصائيات اليوم لنظام POS
     */
    public function getTodayStats(int $userId): array
    {
        return [
            'total_sales' => OperHead::where('pro_type', 102)
                ->whereDate('created_at', today())
                ->sum('fat_net') ?? 0,
            'transactions_count' => OperHead::where('pro_type', 102)
                ->whereDate('created_at', today())
                ->count(),
            'items_sold' => OperHead::where('pro_type', 102)
                ->whereDate('created_at', today())
                ->withSum('operationItems', 'qty_out')
                ->get()
                ->sum('operation_items_sum_qty_out') ?? 0,
        ];
    }

    /**
     * جلب المعاملات الأخيرة للمستخدم
     */
    public function getRecentTransactions(int $userId, int $limit = 10)
    {
        return OperHead::with(['acc1Head', 'acc2Head', 'employee'])
            ->where('pro_type', 102)
            ->where('user', $userId)
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * جلب البيانات الأساسية لإنشاء فاتورة POS
     */
    public function getBaseCreateData(): array
    {
        $nextProId = OperHead::max('pro_id') + 1 ?? 1;

        $clientsAccounts = AccHead::where('isdeleted', 0)
            ->where('is_basic', 0)
            ->where('code', 'like', '1103%')
            ->select('id', 'aname')
            ->get();

        $stores = AccHead::where('isdeleted', 0)
            ->where('is_basic', 0)
            ->where('code', 'like', '1104%')
            ->select('id', 'aname')
            ->get();

        $employees = AccHead::where('isdeleted', 0)
            ->where('is_basic', 0)
            ->where('code', 'like', '2102%')
            ->select('id', 'aname')
            ->get();

        $cashAccounts = AccHead::where('isdeleted', 0)
            ->where('is_basic', 0)
            ->where('is_fund', 1)
            ->select('id', 'aname')
            ->get();

        $bankAccounts = AccHead::where('isdeleted', 0)
            ->where('is_basic', 0)
            ->where('code', 'like', '1102%')
            ->select('id', 'aname')
            ->get();

        $expenseAccounts = AccHead::where('isdeleted', 0)
            ->where('is_basic', 0)
            ->where(function ($q) {
                $q->where('code', 'like', '5101%')
                    ->orWhere('code', 'like', '5102%')
                    ->orWhere('code', 'like', '5103%')
                    ->orWhere('code', 'like', '5104%');
            })
            ->select('id', 'aname')
            ->orderBy('code')
            ->get();

        $categories = DB::table('note_details')
            ->join('notes', 'note_details.note_id', '=', 'notes.id')
            ->select('note_details.id', 'note_details.name', 'notes.name as parent_name')
            ->where('note_details.note_id', '=', 2)
            ->get();

        return compact(
            'nextProId',
            'clientsAccounts',
            'stores',
            'employees',
            'cashAccounts',
            'bankAccounts',
            'expenseAccounts',
            'categories'
        );
    }

    /**
     * جلب بيانات الأصناف لنظام POS
     */
    public function getItemsData(int $take = 50): array
    {
        $itemsQuery = Item::with(['units' => fn ($q) => $q->orderBy('pivot_u_val'), 'prices', 'media'])
            ->where('is_active', 1);

        if ($take > 0) {
            $itemsQuery->take($take);
        }

        $items = $itemsQuery->get();
        $itemIds = $items->pluck('id');

        $barcodes = Barcode::whereIn('item_id', $itemIds)
            ->where('isdeleted', 0)
            ->select('item_id', 'unit_id', 'barcode')
            ->get()
            ->groupBy('item_id');

        $itemsData = $items->map(function ($item) use ($barcodes) {
            $itemBarcodes = $barcodes->get($item->id, collect());

            return [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'notes' => $item->notes,
                'sale_price' => $item->sale_price ?? 0,
                'cost_price' => $item->cost_price ?? 0,
                'available_quantity' => $item->available_quantity ?? 0,
                'is_weight_scale' => $item->is_weight_scale ?? false,
                'scale_plu_code' => $item->scale_plu_code ?? null,
                'image' => $this->getItemImage($item),
                'barcodes' => $itemBarcodes->map(function ($barcode) {
                    return [
                        'barcode' => $barcode->barcode,
                        'unit_id' => $barcode->unit_id,
                    ];
                })->toArray(),
                'units' => $item->units->map(function ($unit) {
                    return [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'value' => $unit->pivot->u_val ?? 1,
                    ];
                })->toArray(),
                'prices' => $item->prices->map(function ($price) {
                    return [
                        'id' => $price->id,
                        'name' => $price->name,
                        'value' => $price->pivot->price ?? 0,
                    ];
                })->toArray(),
            ];
        })->keyBy('id');

        $initialProductsData = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'sale_price' => $item->sale_price ?? 0,
                'image' => $this->getItemImage($item),
            ];
        })->values();

        return [
            'items' => $items,
            'itemsData' => $itemsData,
            'initialProductsData' => $initialProductsData,
        ];
    }

    /**
     * جلب بيانات المطعم لنظام POS
     */
    public function getRestaurantData(): array
    {
        $baseData = $this->getBaseCreateData();

        $items = Item::with(['units' => fn ($q) => $q->orderBy('pivot_u_val'), 'prices', 'media'])
            ->where('is_active', 1)->get();

        $itemIds = $items->pluck('id');
        $barcodes = Barcode::whereIn('item_id', $itemIds)->where('isdeleted', 0)
            ->select('item_id', 'unit_id', 'barcode')->get()->groupBy('item_id');

        $itemCategories = DB::table('item_notes')
            ->join('note_details', function ($join) {
                $join->on('note_details.name', '=', 'item_notes.note_detail_name')
                    ->where('note_details.note_id', '=', 2);
            })
            ->whereIn('item_notes.item_id', $itemIds)
            ->select('item_notes.item_id', 'note_details.id as category_id')
            ->get()->keyBy('item_id');

        $itemsData = $items->map(function ($item) use ($barcodes, $itemCategories) {
            $itemBarcodes = $barcodes->get($item->id, collect());
            $firstPrice = $item->prices->first()?->pivot->price ?? 0;

            return [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'notes' => $item->notes,
                'sale_price' => (float) $firstPrice,
                'cost_price' => $item->cost_price ?? 0,
                'available_quantity' => $item->available_quantity ?? 0,
                'is_weight_scale' => $item->is_weight_scale ?? false,
                'scale_plu_code' => $item->scale_plu_code ?? null,
                'category_id' => $itemCategories->get($item->id)?->category_id ?? null,
                'image' => $this->getItemImage($item),
                'barcodes' => $itemBarcodes->map(fn ($b) => ['barcode' => $b->barcode, 'unit_id' => $b->unit_id])->toArray(),
                'units' => $item->units->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'value' => $u->pivot->u_val ?? 1])->toArray(),
                'prices' => $item->prices->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'value' => $p->pivot->price ?? 0])->toArray(),
            ];
        })->keyBy('id');

        $initialProductsData = $items->map(function ($item) use ($itemCategories) {
            $firstPrice = $item->prices->first()?->pivot->price ?? 0;

            return [
                'id' => $item->id, 'name' => $item->name,
                'code' => $item->code, 'sale_price' => (float) $firstPrice,
                'category_id' => $itemCategories->get($item->id)?->category_id ?? null,
                'image' => $this->getItemImage($item),
            ];
        })->values();

        $drivers = Driver::where('is_available', 1)->get();
        $deliveryAreas = DeliveryArea::where('is_active', 1)->get();
        $restaurantTables = RestaurantTable::all();
        $priceGroups = DB::table('prices')->select('id', 'name')->get();
        $printerStations = KitchenPrinterStation::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'printer_name', 'is_default']);

        return array_merge($baseData, [
            'items' => $items,
            'itemsData' => $itemsData,
            'initialProductsData' => $initialProductsData,
            'drivers' => $drivers,
            'deliveryAreas' => $deliveryAreas,
            'restaurantTables' => $restaurantTables,
            'priceGroups' => $priceGroups,
            'printerStations' => $printerStations,
            'settings' => Setting::first() ?? new Setting,
        ]);
    }

    /**
     * جلب صورة الصنف
     */
    public function getItemImage(Item $item): string
    {
        $url = $item->getFirstMediaUrl('item-images', 'thumb')
            ?: $item->getFirstMediaUrl('item-thumbnail', 'thumb');

        return str_contains($url, 'no-image') ? '' : $url;
    }

    /**
     * البحث عن الأصناف
     */
    public function searchItems(string $searchTerm)
    {
        return Item::where('is_active', 1)
            ->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('code', 'like', "%{$searchTerm}%");
            })
            ->with(['units', 'prices'])
            ->take(20)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'code' => $item->code,
                ];
            });
    }

    /**
     * البحث عن الأصناف بالباركود
     */
    public function searchByBarcode(string $barcode): array
    {
        $barcodeRecord = Barcode::where('barcode', $barcode)
            ->where('isdeleted', 0)
            ->with(['item' => function ($q) {
                $q->where('is_active', 1)
                    ->select('id', 'name', 'code');
            }])
            ->first();

        if ($barcodeRecord && $barcodeRecord->item) {
            return [
                'items' => [[
                    'id' => $barcodeRecord->item->id,
                    'name' => $barcodeRecord->item->name,
                    'code' => $barcodeRecord->item->code,
                    'unit_id' => $barcodeRecord->unit_id,
                ]],
                'exact_match' => true,
            ];
        }

        $barcodeRecords = Barcode::where('barcode', 'like', "%{$barcode}%")
            ->where('isdeleted', 0)
            ->with(['item' => function ($q) {
                $q->where('is_active', 1)
                    ->select('id', 'name', 'code');
            }])
            ->take(10)
            ->get()
            ->filter(fn ($br) => $br->item !== null)
            ->map(fn ($br) => [
                'id' => $br->item->id,
                'name' => $br->item->name,
                'code' => $br->item->code,
                'unit_id' => $br->unit_id,
            ]);

        return [
            'items' => $barcodeRecords,
            'exact_match' => false,
        ];
    }

    /**
     * جلب تفاصيل الصنف بالمعرف
     */
    public function getItemDetails(int $id)
    {
        $item = Item::with(['units' => fn ($q) => $q->orderBy('pivot_u_val'), 'prices'])
            ->where('id', $id)
            ->where('is_active', 1)
            ->first();

        if (! $item) {
            return null;
        }

        return [
            'id' => $item->id,
            'name' => $item->name,
            'code' => $item->code,
            'is_weight_scale' => $item->is_weight_scale ?? false,
            'scale_plu_code' => $item->scale_plu_code ?? null,
            'units' => $item->units->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'value' => $u->pivot->u_val ?? 1]),
            'prices' => $item->prices->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'value' => $p->pivot->price ?? 0]),
        ];
    }

    /**
     * جلب بيانات السعر بالباركود
     */
    public function getPriceByBarcode(string $barcode): array
    {
        $barcodeRecord = Barcode::where('barcode', $barcode)
            ->where('isdeleted', 0)
            ->with(['item' => fn ($q) => $q->where('is_active', 1)])
            ->first();

        if (! $barcodeRecord || ! $barcodeRecord->item) {
            return ['success' => false, 'message' => 'لم يتم العثور على صنف بهذا الباركود'];
        }

        $item = $barcodeRecord->item;
        $item->load(['units' => fn ($q) => $q->orderBy('pivot_u_val'), 'prices']);

        $pricesByUnit = [];
        foreach ($item->units as $unit) {
            $unitPrices = [];
            foreach ($item->prices as $price) {
                $itemPrice = DB::table('item_prices')
                    ->where('item_id', $item->id)
                    ->where('price_id', $price->id)
                    ->where('unit_id', $unit->id)
                    ->first();

                if ($itemPrice) {
                    $unitPrices[] = [
                        'id' => $price->id,
                        'name' => $price->name,
                        'price' => (float) $itemPrice->price,
                        'discount' => (float) ($itemPrice->discount ?? 0),
                        'tax_rate' => (float) ($itemPrice->tax_rate ?? 0),
                    ];
                }
            }
            $pricesByUnit[] = [
                'unit_id' => $unit->id,
                'unit_name' => $unit->name,
                'prices' => $unitPrices,
            ];
        }

        return [
            'success' => true,
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'barcode' => $barcode,
                'unit_id' => $barcodeRecord->unit_id,
                'units' => $item->units->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'value' => $u->pivot->u_val ?? 1]),
                'prices_by_unit' => $pricesByUnit,
            ],
        ];
    }

    /**
     * جلب كافة الأصناف مع التفاصيل الكاملة
     */
    public function getAllItemsDetails(): array
    {
        $items = Item::with(['units' => fn ($q) => $q->orderBy('pivot_u_val'), 'prices', 'media'])
            ->where('is_active', 1)
            ->get();

        $itemIds = $items->pluck('id');
        $barcodes = Barcode::whereIn('item_id', $itemIds)
            ->where('isdeleted', 0)
            ->select('item_id', 'unit_id', 'barcode')
            ->get()
            ->groupBy('item_id');

        $itemCategories = DB::table('item_notes')
            ->join('note_details', function ($join) {
                $join->on('note_details.name', '=', 'item_notes.note_detail_name')
                    ->where('note_details.note_id', '=', 2);
            })
            ->whereIn('item_notes.item_id', $itemIds)
            ->select('item_notes.item_id', 'note_details.id as category_id')
            ->get()->keyBy('item_id');

        return $items->map(function ($item) use ($barcodes, $itemCategories) {
            $itemBarcodes = $barcodes->get($item->id, collect());

            return [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'notes' => $item->notes,
                'sale_price' => $item->sale_price ?? 0,
                'cost_price' => $item->cost_price ?? 0,
                'available_quantity' => $item->available_quantity ?? 0,
                'is_weight_scale' => $item->is_weight_scale ?? false,
                'scale_plu_code' => $item->scale_plu_code ?? null,
                'category_id' => $itemCategories->get($item->id)?->category_id ?? null,
                'image' => $this->getItemImage($item),
                'barcodes' => $itemBarcodes->map(fn ($b) => ['barcode' => $b->barcode, 'unit_id' => $b->unit_id])->toArray(),
                'units' => $item->units->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'value' => $u->pivot->u_val ?? 1])->toArray(),
                'prices' => $item->prices->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'value' => $p->pivot->price ?? 0])->toArray(),
            ];
        })->keyBy('id')->toArray();
    }

    /**
     * جلب أصناف التصنيف
     */
    public function getCategoryItems(int $categoryId)
    {
        $categoryName = DB::table('note_details')->where('id', $categoryId)->value('name');
        if (! $categoryName) {
            return collect();
        }

        return DB::table('item_notes')
            ->join('items', 'item_notes.item_id', '=', 'items.id')
            ->where('item_notes.note_detail_name', $categoryName)
            ->where('items.is_active', 1)
            ->select('items.id', 'items.name', 'items.code')
            ->orderBy('items.name')
            ->get();
    }

    /**
     * جلب بيانات التقارير
     */
    public function getReportsData(): array
    {
        $baseQuery = fn () => OperHead::where('pro_type', 10)
            ->whereDate('created_at', today())
            ->where('isdeleted', 0);

        $totalSales = $baseQuery()->sum('fat_net');
        $transactionsCount = $baseQuery()->count();

        $itemsSold = OperationItems::whereHas('operhead', fn ($q) => $q
            ->where('pro_type', 10)
            ->whereDate('created_at', today())
            ->where('isdeleted', 0)
        )->sum('qty_out');

        $avgTransaction = $transactionsCount > 0 ? $totalSales / $transactionsCount : 0;

        $lastTransaction = $baseQuery()->latest()->value('created_at');

        // أكثر الأصناف مبيعاً اليوم
        $topItems = OperationItems::query()
            ->select('item_id', \DB::raw('SUM(qty_out) as total_qty'), \DB::raw('SUM(detail_value) as total_value'))
            ->whereHas('operhead', fn ($q) => $q
                ->where('pro_type', 10)
                ->whereDate('created_at', today())
                ->where('isdeleted', 0)
            )
            ->with('item:id,name,code')
            ->groupBy('item_id')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'item_name' => $row->item?->name ?? __('pos.unknown_item'),
                'item_code' => $row->item?->code ?? '-',
                'total_qty' => (float) $row->total_qty,
                'total_value' => (float) $row->total_value,
                'avg_price' => $row->total_qty > 0 ? round($row->total_value / $row->total_qty, 2) : 0,
            ]);

        // الفواتير اليومية مرتبة بالتاريخ
        $invoices = $baseQuery()
            ->with('acc1Head:id,aname')
            ->select('id', 'pro_id', 'pro_date', 'fat_net', 'fat_disc', 'acc1', 'created_at', 'user')
            ->latest()
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'pro_id' => $inv->pro_id,
                'pro_date' => $inv->pro_date,
                'customer_name' => $inv->acc1Head?->aname ?? __('pos.cash_customer'),
                'fat_net' => (float) $inv->fat_net,
                'fat_disc' => (float) $inv->fat_disc,
                'created_at' => $inv->created_at,
            ]);

        // تحليل طرق الدفع (paid_from_client = مدفوع، الباقي = آجل)
        $cashTotal = $baseQuery()->whereColumn('paid_from_client', '>=', 'fat_net')->sum('fat_net');
        $partialTotal = $baseQuery()->whereColumn('paid_from_client', '<', 'fat_net')->sum('fat_net');
        $deferredTotal = $baseQuery()->where('paid_from_client', 0)->sum('fat_net');

        return [
            'total_sales' => $totalSales,
            'transactions_count' => $transactionsCount,
            'items_sold' => $itemsSold,
            'avg_transaction' => $avgTransaction,
            'last_transaction' => $lastTransaction,
            'top_items' => $topItems,
            'invoices' => $invoices,
            'payment_analysis' => [
                'cash_total' => $cashTotal,
                'partial_total' => $partialTotal,
                'deferred_total' => $deferredTotal,
            ],
        ];
    }

    /**
     * جلب إعدادات الميزان
     */
    public function getScaleSettings(): array
    {
        $settings = Setting::first() ?? new Setting;

        return [
            'success' => true,
            'enable_scale_items' => $settings->enable_scale_items ?? false,
            'scale_code_prefix' => $settings->scale_code_prefix ?? '',
            'scale_code_digits' => $settings->scale_code_digits ?? 5,
            'scale_quantity_digits' => $settings->scale_quantity_digits ?? 5,
            'scale_quantity_divisor' => $settings->scale_quantity_divisor ?? 100,
        ];
    }

    /**
     * جلب بيانات صفحة الإعدادات
     */
    public function getSettingsData(): array
    {
        $settings = Setting::first() ?? new Setting;
        $clientsAccounts = AccHead::where('isdeleted', 0)->where('is_basic', 0)->where('code', 'like', '1103%')->select('id', 'aname')->get();
        $stores = AccHead::where('isdeleted', 0)->where('is_basic', 0)->where('code', 'like', '1104%')->select('id', 'aname')->get();
        $employees = AccHead::where('isdeleted', 0)->where('is_basic', 0)->where('code', 'like', '2102%')->select('id', 'aname')->get();
        $cashAccounts = AccHead::where('isdeleted', 0)->where('is_basic', 0)->where('is_fund', 1)->select('id', 'aname')->get();
        $bankAccounts = AccHead::where('isdeleted', 0)->where('is_basic', 0)->where('code', 'like', '1102%')->select('id', 'aname')->get();
        $allAccounts = AccHead::where('isdeleted', 0)->where('is_basic', 0)->select('id', 'aname', 'code')->orderBy('code')->get();
        $priceGroups = DB::table('prices')->select('id', 'name')->get();

        return compact('settings', 'clientsAccounts', 'stores', 'employees', 'cashAccounts', 'bankAccounts', 'allAccounts', 'priceGroups');
    }

    /**
     * البحث عن العميل بالتليفون
     */
    public function searchCustomerByPhone(string $phone, bool $loadAll = false): array
    {
        if ($loadAll) {
            $customers = Client::where('isdeleted', 0)->where('is_active', 1)->select('id', 'cname', 'phone', 'phone2', 'address', 'address2')->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->cname, 'phone' => $c->phone ?? $c->phone2 ?? '', 'phone2' => $c->phone2 ?? '', 'address' => $c->address ?? '', 'address2' => $c->address2 ?? '', 'address3' => '']);

            return ['customers' => $customers];
        }

        if (strlen($phone) < 3) {
            return ['customers' => []];
        }

        $customers = Client::where('isdeleted', 0)->where('is_active', 1)->where(fn ($q) => $q->where('phone', 'like', "%{$phone}%")->orWhere('phone2', 'like', "%{$phone}%"))
            ->select('id', 'cname', 'phone', 'phone2', 'address', 'address2')->limit(10)->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->cname, 'phone' => $c->phone ?? $c->phone2 ?? '', 'address' => $c->address ?? '', 'address2' => $c->address2 ?? '', 'address3' => '']);

        return ['customers' => $customers];
    }

    /**
     * جلب رصيد العميل
     */
    public function getCustomerBalance(int $customerId): array
    {
        $customer = AccHead::findOrFail($customerId);
        $balance = DB::table('journal_details')->where('account_id', $customerId)->where('isdeleted', 0)->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as balance')->value('balance') ?? 0;
        $totalBalance = ($customer->start_balance ?? 0) + $balance;

        return ['success' => true, 'balance' => $totalBalance, 'customer_name' => $customer->aname];
    }

    /**
     * جلب ترشيحات العميل (آخر طلبات)
     */
    public function getCustomerRecommendations(int $customerId, int $branchId): array
    {
        $orders = OperHead::with(['operationItems.item'])->where('acc1', $customerId)->where('isdeleted', 0)->where('branch_id', $branchId)->whereIn('pro_type', [102, 103])->orderBy('created_at', 'desc')->take(3)->get()
            ->map(fn ($order) => ['pro_id' => $order->pro_id, 'pro_date' => $order->pro_date, 'total' => (float) ($order->fat_net ?? $order->pro_value ?? 0), 'items' => $order->operationItems->map(fn ($oi) => ['id' => $oi->item_id, 'name' => $oi->item?->name ?? $oi->item_name ?? 'صنف', 'quantity' => (float) ($oi->qty_out ?? 1), 'price' => (float) ($oi->price ?? 0)])->values()]);

        return ['success' => true, 'orders' => $orders];
    }

    /**
     * جلب تفاصيل فاتورة محددة
     */
    public function getInvoiceDetails(int $proId): ?array
    {
        $invoice = OperHead::with(['operationItems.item', 'operationItems.unit', 'acc1Head'])->where('pro_type', 102)->where('pro_id', $proId)->where('isdeleted', 0)->first();
        if (! $invoice) {
            return null;
        }

        return [
            'id' => $invoice->id, 'pro_id' => $invoice->pro_id, 'pro_date' => $invoice->pro_date, 'customer_name' => $invoice->acc1Head->aname ?? 'عميل نقدي',
            'total' => (float) ($invoice->fat_net ?? $invoice->pro_value ?? 0),
            'items' => $invoice->operationItems->map(fn ($item) => ['item_id' => $item->item_id, 'item_name' => $item->item->name ?? 'غير محدد', 'unit_name' => $item->unit->name ?? 'قطعة', 'quantity' => (float) $item->qty_out, 'price' => (float) $item->item_price, 'total' => (float) $item->detail_value]),
        ];
    }

    /**
     * جلب بيانات التحرير للمعاملة
     */
    public function getEditData(int $id): array
    {
        $transaction = OperHead::with(['operationItems.item', 'operationItems.unit'])
            ->where('pro_type', 102)
            ->where('isdeleted', 0)
            ->findOrFail($id);

        $baseData = $this->getBaseCreateData();

        $items = Item::with(['units' => fn ($q) => $q->orderBy('pivot_u_val'), 'prices'])
            ->whereIn('id', $transaction->operationItems->pluck('item_id'))
            ->get();

        $itemsData = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'sale_price' => $item->sale_price ?? 0,
                'cost_price' => $item->cost_price ?? 0,
                'available_quantity' => $item->available_quantity ?? 0,
                'units' => $item->units->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'value' => $u->pivot->u_val ?? 1])->toArray(),
                'prices' => $item->prices->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'value' => $p->pivot->price ?? 0])->toArray(),
            ];
        })->keyBy('id');

        return array_merge($baseData, [
            'transaction' => $transaction,
            'items' => $items,
            'itemsData' => $itemsData,
        ]);
    }

    /**
     * جلب التصنيفات من note_details حيث note_id = 2
     *
     * @return Collection<int, object>
     */
    public function getCategories(): Collection
    {
        return DB::table('note_details')
            ->select('note_details.id', 'note_details.name')
            ->where('note_details.note_id', '=', 2)
            ->get();
    }

    /**
     * جلب بيانات أوقات الذروة لشهر معين
     *
     * @return array{calendar: array, stats: array}
     */
    public function getPeakHoursData(int $year, int $month): array
    {
        $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        // جلب عدد الطلبات لكل يوم في الشهر
        $dailyCounts = OperHead::query()
            ->where('pro_type', 102)
            ->where('isdeleted', 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as orders_count')
            ->groupBy('day')
            ->pluck('orders_count', 'day')
            ->toArray();

        $maxDaily = ! empty($dailyCounts) ? max($dailyCounts) : 1;

        // بناء مصفوفة التقويم
        $calendar = [];
        $daysInMonth = $startDate->daysInMonth;

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateKey = $startDate->copy()->day($d)->format('Y-m-d');
            $count   = $dailyCounts[$dateKey] ?? 0;
            $ratio   = $maxDaily > 0 ? $count / $maxDaily : 0;

            $calendar[] = [
                'day'          => $d,
                'date'         => $dateKey,
                'orders_count' => $count,
                'ratio'        => round($ratio, 2),
                'day_of_week'  => \Carbon\Carbon::parse($dateKey)->dayOfWeek, // 0=Sunday
            ];
        }

        // إحصائيات الشهر
        $totalOrders = array_sum($dailyCounts);
        $daysWithOrders = count(array_filter($dailyCounts));
        $avgPerDay = $daysWithOrders > 0 ? round($totalOrders / $daysInMonth, 1) : 0;

        $busiestDay = ! empty($dailyCounts) ? array_search(max($dailyCounts), $dailyCounts) : null;

        // أكثر ساعة ازدحاماً في الشهر
        $hourlyAll = OperHead::query()
            ->where('pro_type', 102)
            ->where('isdeleted', 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as cnt')
            ->groupBy('hour')
            ->orderByDesc('cnt')
            ->first();

        return [
            'calendar'     => $calendar,
            'days_in_month' => $daysInMonth,
            'first_day_of_week' => (int) $startDate->dayOfWeek,
            'stats'        => [
                'total_orders'  => $totalOrders,
                'avg_per_day'   => $avgPerDay,
                'busiest_day'   => $busiestDay,
                'busiest_hour'  => $hourlyAll ? (int) $hourlyAll->hour : null,
                'max_daily'     => $maxDaily,
            ],
        ];
    }

    /**
     * جلب توزيع الطلبات بالساعات ليوم معين
     *
     * @return array<int, int>
     */
    public function getHourlyOrdersForDay(string $date): array
    {
        $counts = OperHead::query()
            ->where('pro_type', 102)
            ->where('isdeleted', 0)
            ->whereDate('created_at', $date)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as cnt')
            ->groupBy('hour')
            ->pluck('cnt', 'hour')
            ->toArray();

        // بناء مصفوفة 24 ساعة
        $hours = [];
        for ($h = 0; $h < 24; $h++) {
            $hours[$h] = (int) ($counts[$h] ?? 0);
        }

        return $hours;
    }
}
