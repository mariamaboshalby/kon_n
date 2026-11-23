<?php

declare(strict_types=1);

namespace Modules\POS\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\POS\app\Http\Requests\SelfOrderRequest;
use Modules\POS\app\Services\SelfOrderService;
use Modules\POS\Models\RestaurantTable;

class SelfOrderController extends Controller
{
    public function __construct(
        private readonly SelfOrderService $selfOrderService
    ) {}

    /**
     * عرض صفحة القائمة العامة للعميل
     *
     * @param  string|null  $table  رقم الطاولة من الـ URL (اختياري)
     */
    public function show(?string $table = null): View
    {
        $menuData = $this->selfOrderService->getMenuData();

        // التحقق من وجود الطاولة - تجاهلها إن لم تكن موجودة
        $tableId = null;
        if ($table !== null) {
            $exists = RestaurantTable::where('id', (int) $table)->exists();
            $tableId = $exists ? (int) $table : null;
        }

        return view('pos::self-order.menu', array_merge($menuData, [
            'table_id' => $tableId,
        ]));
    }

    /**
     * استقبال طلب العميل وحفظه
     */
    public function placeOrder(SelfOrderRequest $request): JsonResponse
    {
        try {
            $result = $this->selfOrderService->placeOrder($request->validated());

            return response()->json($result, 201);
        } catch (\Throwable $e) {
            \Log::error('SelfOrder: خطأ في حفظ الطلب', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('pos::pos.self_order.order_failed'),
            ], 500);
        }
    }
}
