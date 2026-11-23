<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\ItemType;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ItemsApiController extends Controller
{
    /**
     * Get lightweight list of items for client-side search.
     * Caches results for 30 minutes based on filters.
     * If no term provided, returns all items (for client-side fuzzy search).
     */
    public function lite(Request $request)
    {
        $term = trim((string)$request->input('term', ''));
        $branchIdParam = $request->input('branch_id');
        $branchId = ($branchIdParam === 'null' || $branchIdParam === '') ? null : $branchIdParam;
        
        $typeParam = $request->input('type');
        $type = ($typeParam === 'null' || $typeParam === '') ? null : $typeParam;

        $query = Item::query()
            ->select(['id', 'name', 'code', 'average_cost'])
            ->with([
                'barcodes:id,item_id,barcode',
                'units:id,name',
                'prices',
            ])
            ->where('isdeleted', 0);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            });
        }

        if (strlen($term) >= 1) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', '%' . $term . '%');
                if (is_numeric($term)) {
                    $q->orWhere('code', $term)
                        ->orWhereHas('barcodes', fn($bq) => $bq->where('barcode', $term));
                }
            });
            $items = $query->limit(50)->get();
        } else {
            $items = $query->limit(8000)->get();
        }

        $isPurchase = in_array($type, [11, 13, 15, 17, 24, 25]);

        return $items->map(function ($item) use ($isPurchase) {
            $priceRecord = $item->prices->where('pivot.price_id', 1)->first() ?? $item->prices->first();
            $price = $priceRecord ? ($priceRecord->pivot->price ?? 0) : 0;

            return [
                'id'                  => $item->id,
                'name'                => $item->name,
                'code'                => $item->code,
                'barcode'             => $item->barcodes->pluck('barcode')->toArray(),
                'price'               => $isPurchase ? ($item->last_purchase_price ?? $price) : $price,
                'last_purchase_price' => $item->last_purchase_price ?? 0,
                'average_cost'        => $item->average_cost ?? 0,
                'default_unit_id'     => $item->units->first()?->id,
                'units'               => $item->units->map(fn($u) => [
                    'id'    => $u->id,
                    'name'  => $u->name,
                    'u_val' => $u->pivot->u_val ?? 1,
                ]),
            ];
        });
    }

    /**
     * Get full details for a single item via AJAX on selection.
     */
    public function details($id)
    {
        $item = Item::with(['barcodes:id,item_id,barcode', 'units:id,name', 'prices'])->find($id);

        if (!$item || $item->isdeleted) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        // Map data to match the JS expected format
        $priceRecord = $item->prices->where('pivot.price_id', 1)->first() ?? $item->prices->first();
        $price = $priceRecord ? ($priceRecord->pivot->price ?? 0) : 0;

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'barcodes' => $item->barcodes->pluck('barcode')->toArray(),
                'price' => $price,
                'average_cost' => $item->average_cost,
                'units' => $item->units->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'u_val' => $u->pivot->u_val ?? 1,
                        'cost' => $u->pivot->cost ?? 0,
                    ];
                }),
            ]
        ]);
    }
}
