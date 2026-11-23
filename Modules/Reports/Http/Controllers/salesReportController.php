<?php

declare(strict_types=1);

namespace Modules\Reports\Http\Controllers;

use App\Models\OperHead;
use App\Models\OperationItems;
use App\Models\PosShift;
use App\Models\User;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Branches\Models\Branch;

class salesReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Sales Report');
    }

    public function generalSalesItemsReport(): \Illuminate\View\View
    {
        $fromDate   = request('from_date', today()->format('Y-m-d'));
        $toDate     = request('to_date',   today()->format('Y-m-d'));
        $saleType   = request('sale_type'); // null=all, 10=sales, 102=cashier, 103=restaurant
        $branchId   = request('branch_id');

        $posTypes = $saleType ? [(int) $saleType] : [10, 102, 103];

        $baseQuery = fn() => OperationItems::whereHas('operhead', function ($q) use ($posTypes, $fromDate, $toDate, $branchId) {
            $q->whereIn('pro_type', $posTypes)
              ->whereDate('pro_date', '>=', $fromDate)
              ->whereDate('pro_date', '<=', $toDate)
              ->when($branchId, fn($q2) => $q2->where('branch_id', $branchId));
        });

        $salesItems = $baseQuery()
            ->selectRaw('item_id, SUM(qty_out) as total_quantity, SUM(qty_out * item_price) as total_sales, COUNT(DISTINCT pro_id) as invoices_count')
            ->groupBy('item_id')
            ->with('item')
            ->orderBy('total_sales', 'desc')
            ->paginate(50);

        $allData = $baseQuery()->selectRaw('SUM(qty_out) as total_quantity, SUM(qty_out * item_price) as total_sales, COUNT(DISTINCT item_id) as total_items')->first();

        $totalQuantity          = $allData->total_quantity ?? 0;
        $totalSales             = $allData->total_sales    ?? 0;
        $totalItems             = $allData->total_items    ?? 0;
        $averagePrice           = $totalQuantity > 0 ? $totalSales / $totalQuantity : 0;
        $topSellingItem         = $salesItems->first()?->item?->name ?? '---';
        $averageQuantityPerItem = $totalItems > 0 ? $totalQuantity / $totalItems : 0;
        $averageSalesPerItem    = $totalItems > 0 ? $totalSales    / $totalItems : 0;

        $branches = Branch::active()->orderBy('name')->get();

        return view('reports::sales.general-sales-items', compact(
            'fromDate', 'toDate', 'salesItems', 'branches',
            'totalQuantity', 'totalSales', 'averagePrice', 'totalItems',
            'topSellingItem', 'averageQuantityPerItem', 'averageSalesPerItem'
        ));
    }

    public function generalSalesTotalReport(): \Illuminate\View\View
    {
        $fromDate = request('from_date', today()->format('Y-m-d'));
        $toDate   = request('to_date',   today()->format('Y-m-d'));
        $branchId = request('branch_id');
        $userId   = request('user_id');

        $posTypes = [102, 103];

        $base = fn() => OperHead::whereIn('pro_type', $posTypes)
            ->whereDate('pro_date', '>=', $fromDate)
            ->whereDate('pro_date', '<=', $toDate)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($userId,   fn($q) => $q->where('user', $userId));

        $totals = $base()->selectRaw('
            COUNT(*)                                                      AS invoices_count,
            SUM(fat_total)                                                AS gross_total,
            SUM(fat_disc)                                                 AS total_discount,
            SUM(fat_net)                                                  AS net_total,
            SUM(CASE WHEN pro_type=102 THEN fat_net ELSE 0 END)          AS cashier_total,
            SUM(CASE WHEN pro_type=103 THEN fat_net ELSE 0 END)          AS restaurant_total,
            SUM(CASE WHEN pro_type=102 THEN 1       ELSE 0 END)          AS cashier_count,
            SUM(CASE WHEN pro_type=103 THEN 1       ELSE 0 END)          AS restaurant_count
        ')->first();

        $daily = $base()->selectRaw('
            DATE(pro_date)                                                AS day,
            SUM(fat_net)                                                  AS net_total,
            SUM(CASE WHEN pro_type=102 THEN fat_net ELSE 0 END)          AS cashier_total,
            SUM(CASE WHEN pro_type=103 THEN fat_net ELSE 0 END)          AS restaurant_total,
            COUNT(*)                                                      AS invoices_count
        ')->groupBy('day')->orderBy('day')->get();

        $topItems = DB::table('operation_items')
            ->join('operhead', 'operation_items.pro_id', '=', 'operhead.id')
            ->join('items', 'operation_items.item_id', '=', 'items.id')
            ->whereIn('operhead.pro_type', $posTypes)
            ->whereDate('operhead.pro_date', '>=', $fromDate)
            ->whereDate('operhead.pro_date', '<=', $toDate)
            ->when($branchId, fn($q) => $q->where('operhead.branch_id', $branchId))
            ->when($userId,   fn($q) => $q->where('operhead.user', $userId))
            ->selectRaw('items.name AS name, SUM(operation_items.qty_out) AS qty, SUM(operation_items.qty_out * operation_items.item_price) AS total')
            ->groupBy('items.id', 'items.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $avgInvoice = ($totals->invoices_count > 0) ? $totals->net_total / $totals->invoices_count : 0;

        $branches = Branch::active()->orderBy('name')->get();
        $users    = User::orderBy('name')->get();

        return view('reports::sales.general-sales-total', compact(
            'fromDate', 'toDate', 'totals', 'daily', 'topItems', 'avgInvoice', 'branches', 'users'
        ));
    }

    public function salesByRepresentativeReport(): \Illuminate\View\View
    {
        $fromDate         = request('from_date', today()->format('Y-m-d'));
        $toDate           = request('to_date',   today()->format('Y-m-d'));
        $representativeId = request('representative_id');
        $branchId         = request('branch_id');

        $base = fn() => OperHead::where('pro_type', 10)
            ->whereDate('pro_date', '>=', $fromDate)
            ->whereDate('pro_date', '<=', $toDate)
            ->when($representativeId, fn($q) => $q->where('emp_id', $representativeId))
            ->when($branchId,         fn($q) => $q->where('branch_id', $branchId));

        $salesByRep = $base()
            ->selectRaw('emp_id, COUNT(DISTINCT id) as invoices_count, SUM(fat_total) as total_sales, SUM(fat_disc) as total_discount, SUM(fat_net) as net_sales')
            ->groupBy('emp_id')
            ->orderBy('net_sales', 'desc')
            ->paginate(50);

        $grandTotals = $base()->selectRaw('COUNT(DISTINCT id) as total_invoices, SUM(fat_total) as grand_total_sales, SUM(fat_disc) as grand_total_discount, SUM(fat_net) as grand_net_sales')->first();

        $totalInvoices      = $grandTotals->total_invoices      ?? 0;
        $grandTotalSales    = $grandTotals->grand_total_sales    ?? 0;
        $grandTotalDiscount = $grandTotals->grand_total_discount ?? 0;
        $grandNetSales      = $grandTotals->grand_net_sales      ?? 0;
        $totalReps          = $salesByRep->total();

        $averageSalesPerRep       = $totalReps > 0 ? $grandNetSales  / $totalReps : 0;
        $averageInvoicesPerRep    = $totalReps > 0 ? $totalInvoices  / $totalReps : 0;
        $averageInvoiceValue      = $totalInvoices > 0 ? $grandNetSales / $totalInvoices : 0;

        $topRep     = $salesByRep->first();
        $topRepName = $topRep?->employee?->aname ?? '---';
        $topRepSales = $topRep?->net_sales ?? 0;

        $representatives = DB::table('acc_head')->where('code', 'like', '210201%')->select('id', 'aname')->orderBy('aname')->get();
        $branches        = Branch::active()->orderBy('name')->get();

        return view('reports::sales.sales-by-representative', compact(
            'fromDate', 'toDate', 'salesByRep', 'representatives', 'branches',
            'totalInvoices', 'grandTotalSales', 'grandTotalDiscount', 'grandNetSales',
            'totalReps', 'averageSalesPerRep', 'averageInvoicesPerRep', 'averageInvoiceValue',
            'topRepName', 'topRepSales'
        ));
    }
    // public function daily(Request $request)
    // {
    //     $customers = AccHead::where('code', 'like', '1103%')
    //         ->where('isdeleted', 0)
    //         ->orderBy('aname')
    //         ->get();

    //     $fromDate = $request->input('from_date', today()->format('Y-m-d'));
    //     $toDate = $request->input('to_date', today()->format('Y-m-d'));
    //     $customerId = $request->input('customer_id');

    //     // جلب الفواتير مع الأصناف
    //     $sales = OperHead::where('pro_type', 10)
    //         ->with(['acc1Head', 'operationItems'])
    //         ->whereDate('pro_date', '>=', $fromDate)
    //         ->whereDate('pro_date', '<=', $toDate)
    //         ->when($customerId, fn($q) => $q->where('acc1', $customerId))
    //         ->orderBy('pro_date', 'desc')
    //         ->orderBy('pro_num', 'desc')
    //         ->paginate(50)
    //         ->appends($request->query());

    //     // === الحسابات من الفواتير (OperHead) ===
    //     $totalSales = $sales->sum('fat_total');
    //     $totalDiscount = $sales->sum('fat_disc');
    //     $totalNetSales = $sales->sum('fat_net');
    //     $totalInvoices = $sales->count();
    //     $averageInvoiceValue = $totalInvoices > 0 ? $totalNetSales / $totalInvoices : 0;

    //     // === الحسابات من الأصناف (OperationItems) ===
    //     $totalQuantity = 0;
    //     $totalItemsCount = 0;

    //     foreach ($sales as $sale) {
    //         if ($sale->items) {
    //             $totalQuantity += $sale->items->sum('fat_quantity'); // الكمية الفعلية
    //             $totalItemsCount += $sale->items->count();
    //         }
    //     }

    //     return view('reports::sales.daily', compact(
    //         'customers',
    //         'sales',
    //         'fromDate',
    //         'toDate',
    //         'customerId',
    //         'totalQuantity',
    //         'totalSales',
    //         'totalDiscount',
    //         'totalNetSales',
    //         'totalInvoices',
    //         'averageInvoiceValue',
    //         'totalItemsCount'
    //     ));
    // }

    // public function generalSalesDailyReport(Request $request)
    // {
    //     $customers = AccHead::where('code', 'like', '1103%')
    //         ->where('isdeleted', 0)
    //         ->orderBy('aname')
    //         ->get();

    //     $fromDate = $request->input('from_date', today()->format('Y-m-d'));
    //     $toDate = $request->input('to_date', today()->format('Y-m-d'));
    //     $customerId = $request->input('customer_id');

    //     // جلب الفواتير مع الأصناف
    //     $query = OperHead::where('pro_type', 10)
    //         ->with(['acc1Head', 'operationItems'])
    //         ->whereDate('pro_date', '>=', $fromDate)
    //         ->whereDate('pro_date', '<=', $toDate)
    //         ->when($customerId, fn($q) => $q->where('acc1', $customerId));

    //     // حساب عدد الأصناف لكل فاتورة
    //     $sales = $query->get()->map(function ($sale) {
    //         $sale->items_count = $sale->operationItems->count() ?? 0;
    //         $sale->total_quantity = $sale->operationItems->sum('qty_out') ?? 0;
    //         $sale->total_sales = $sale->fat_total ?? 0;
    //         $sale->net_sales = $sale->fat_net ?? 0;
    //         $sale->status = $sale->isdeleted == 0 ? 'completed' : 'pending';
    //         return $sale;
    //     });

    //     // حساب الإجماليات من جميع البيانات (قبل pagination)
    //     $totalQuantity = $sales->sum('total_quantity');
    //     $totalSalesAmount = $sales->sum('total_sales');
    //     $totalDiscount = $sales->sum('fat_disc') ?? 0;
    //     $totalNetSales = $sales->sum('net_sales');
    //     $totalInvoices = $sales->count();
    //     $averageInvoiceValue = $totalInvoices > 0 ? $totalNetSales / $totalInvoices : 0;

    //     // Pagination manual
    //     $perPage = 50;
    //     $currentPage = $request->input('page', 1);
    //     $items = $sales->slice(($currentPage - 1) * $perPage, $perPage)->values();
    //     $paginatedSales = new \Illuminate\Pagination\LengthAwarePaginator(
    //         $items,
    //         $sales->count(),
    //         $perPage,
    //         $currentPage,
    //         ['path' => $request->url(), 'query' => $request->query()]
    //     );

    //     return view('reports::sales.general-sales-daily-report', [
    //         'customers' => $customers,
    //         'sales' => $paginatedSales,
    //         'fromDate' => $fromDate,
    //         'toDate' => $toDate,
    //         'customerId' => $customerId,
    //         'totalQuantity' => $totalQuantity,
    //         'totalSales' => $totalSalesAmount,
    //         'totalDiscount' => $totalDiscount,
    //         'totalNetSales' => $totalNetSales,
    //         'totalInvoices' => $totalInvoices,
    //         'averageInvoiceValue' => $averageInvoiceValue
    //     ]);
    // }

    public function generalSalesReport(): \Illuminate\View\View
    {
        return view('reports::sales.general-sales-report');
    }

    /**
     * تقرير المبيعات الشامل مع الإحصائيات
     */
    public function comprehensiveSalesReport(): \Illuminate\View\View
    {
        $fromDate = request('from_date', today()->startOfMonth()->format('Y-m-d'));
        $toDate   = request('to_date',   today()->format('Y-m-d'));
        $branchId = request('branch_id');
        $saleType = request('sale_type'); // null=all, 10=sales, 102=cashier, 103=restaurant

        $salesTypes   = $saleType ? [(int) $saleType] : [10, 102, 103];
        $returnsTypes = [12, 112];

        // ── الفترة السابقة للمقارنة ──────────────────────────────────────────
        $from = \Carbon\Carbon::parse($fromDate);
        $to   = \Carbon\Carbon::parse($toDate);
        $days = $from->diffInDays($to) + 1;
        $prevFrom = $from->copy()->subDays($days)->format('Y-m-d');
        $prevTo   = $from->copy()->subDay()->format('Y-m-d');

        $base = fn(array $types, string $f, string $t) => OperHead::whereIn('pro_type', $types)
            ->whereDate('pro_date', '>=', $f)
            ->whereDate('pro_date', '<=', $t)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        // ── إجماليات الفترة الحالية ──────────────────────────────────────────
        $totals = $base($salesTypes, $fromDate, $toDate)->selectRaw('
            COUNT(*)                                                              AS invoices_count,
            SUM(fat_total)                                                        AS gross_total,
            SUM(fat_disc)                                                         AS total_discount,
            SUM(fat_net)                                                          AS net_total,
            SUM(profit)                                                           AS total_profit,
            SUM(fat_tax)                                                          AS total_tax,
            AVG(fat_net)                                                          AS avg_invoice,
            SUM(CASE WHEN pro_type=10  THEN fat_net ELSE 0 END)                  AS sales_total,
            SUM(CASE WHEN pro_type=102 THEN fat_net ELSE 0 END)                  AS cashier_total,
            SUM(CASE WHEN pro_type=103 THEN fat_net ELSE 0 END)                  AS restaurant_total,
            SUM(CASE WHEN pro_type=10  THEN 1 ELSE 0 END)                        AS sales_count,
            SUM(CASE WHEN pro_type=102 THEN 1 ELSE 0 END)                        AS cashier_count,
            SUM(CASE WHEN pro_type=103 THEN 1 ELSE 0 END)                        AS restaurant_count
        ')->first();

        // ── إجماليات الفترة السابقة للمقارنة ────────────────────────────────
        $prevTotals = $base($salesTypes, $prevFrom, $prevTo)->selectRaw('
            SUM(fat_net) AS net_total,
            COUNT(*)     AS invoices_count
        ')->first();

        // ── المرتجعات ────────────────────────────────────────────────────────
        $returnTotals = $base($returnsTypes, $fromDate, $toDate)->selectRaw('
            COUNT(*)     AS returns_count,
            SUM(fat_net) AS returns_value
        ')->first();

        // ── الاتجاه اليومي ───────────────────────────────────────────────────
        $daily = $base($salesTypes, $fromDate, $toDate)->selectRaw('
            DATE(pro_date)                                                        AS day,
            SUM(fat_net)                                                          AS net_total,
            SUM(CASE WHEN pro_type=10  THEN fat_net ELSE 0 END)                  AS sales_total,
            SUM(CASE WHEN pro_type=102 THEN fat_net ELSE 0 END)                  AS cashier_total,
            SUM(CASE WHEN pro_type=103 THEN fat_net ELSE 0 END)                  AS restaurant_total,
            COUNT(*)                                                              AS invoices_count,
            SUM(profit)                                                           AS daily_profit
        ')->groupBy('day')->orderBy('day')->get();

        // ── أفضل 10 أصناف ────────────────────────────────────────────────────
        $topItems = DB::table('operation_items')
            ->join('operhead', 'operation_items.pro_id', '=', 'operhead.id')
            ->join('items', 'operation_items.item_id', '=', 'items.id')
            ->whereIn('operhead.pro_type', $salesTypes)
            ->whereDate('operhead.pro_date', '>=', $fromDate)
            ->whereDate('operhead.pro_date', '<=', $toDate)
            ->when($branchId, fn($q) => $q->where('operhead.branch_id', $branchId))
            ->selectRaw('
                items.id,
                items.name,
                items.code,
                SUM(operation_items.qty_out)                                      AS qty,
                SUM(operation_items.qty_out * operation_items.item_price)         AS total,
                SUM(operation_items.profit)                                       AS profit,
                COUNT(DISTINCT operhead.id)                                       AS invoices_count
            ')
            ->groupBy('items.id', 'items.name', 'items.code')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // ── أفضل 10 عملاء ────────────────────────────────────────────────────
        $topCustomers = $base([10], $fromDate, $toDate)
            ->selectRaw('acc1, COUNT(*) AS invoices_count, SUM(fat_net) AS net_total, SUM(fat_disc) AS total_discount')
            ->groupBy('acc1')
            ->orderByDesc('net_total')
            ->with('acc1Head')
            ->limit(10)
            ->get();

        // ── المبيعات حسب المندوب ─────────────────────────────────────────────
        $salesByRep = $base([10], $fromDate, $toDate)
            ->whereNotNull('emp_id')
            ->selectRaw('emp_id, COUNT(*) AS invoices_count, SUM(fat_net) AS net_total, SUM(profit) AS total_profit')
            ->groupBy('emp_id')
            ->orderByDesc('net_total')
            ->with('employee')
            ->limit(10)
            ->get();

        // ── توزيع حسب الساعة (POS فقط) ──────────────────────────────────────
        $hourly = $base([102, 103], $fromDate, $toDate)->selectRaw('
            HOUR(pro_date) AS hour,
            COUNT(*)       AS orders_count,
            SUM(fat_net)   AS net_sales
        ')->groupBy('hour')->orderBy('hour')->get();

        // ── المبيعات حسب يوم الأسبوع ─────────────────────────────────────────
        $weekday = $base($salesTypes, $fromDate, $toDate)->selectRaw('
            DAYOFWEEK(pro_date) AS dow,
            DAYNAME(pro_date)   AS day_name,
            COUNT(*)            AS orders_count,
            SUM(fat_net)        AS net_sales
        ')->groupBy('dow', 'day_name')->orderBy('dow')->get();

        // ── الخصومات حسب المستخدم ────────────────────────────────────────────
        $discountByUser = $base($salesTypes, $fromDate, $toDate)
            ->where('fat_disc', '>', 0)
            ->selectRaw('`user` AS user_id, COUNT(*) AS invoices_count, SUM(fat_disc) AS total_discount, AVG(fat_disc_per) AS avg_pct')
            ->groupBy('user')
            ->orderByDesc('total_discount')
            ->with('user')
            ->limit(10)
            ->get();

        // ── نسبة التغيير ─────────────────────────────────────────────────────
        $prevNet  = (float) ($prevTotals->net_total ?? 0);
        $currNet  = (float) ($totals->net_total ?? 0);
        $growthPct = $prevNet > 0 ? (($currNet - $prevNet) / $prevNet) * 100 : null;

        $profitMarginPct = $currNet > 0 ? ((float) ($totals->total_profit ?? 0) / $currNet) * 100 : 0;
        $discountPct     = (float) ($totals->gross_total ?? 0) > 0
            ? ((float) ($totals->total_discount ?? 0) / (float) $totals->gross_total) * 100
            : 0;

        $branches = Branch::active()->orderBy('name')->get();
        $users    = User::orderBy('name')->get();

        return view('reports::sales.comprehensive-sales-report', compact(
            'fromDate', 'toDate', 'branchId', 'saleType',
            'totals', 'prevTotals', 'returnTotals',
            'daily', 'topItems', 'topCustomers', 'salesByRep',
            'hourly', 'weekday', 'discountByUser',
            'growthPct', 'profitMarginPct', 'discountPct',
            'branches', 'users', 'days'
        ));
    }

    public function salesReportByAddress(): \Illuminate\View\View
    {
        return view('reports::sales.manage-sales-report-by-adress');
    }

    public function manageItemSales(): \Illuminate\View\View
    {
        return view('reports::sales.manage-item-sales');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // تقارير POS التحليلية الجديدة
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * تقرير أداء الكاشير - يجمع بيانات الورديات مع المبيعات
     */
    public function cashierPerformanceReport(): \Illuminate\View\View
    {
        $fromDate = request('from_date', today()->format('Y-m-d'));
        $toDate   = request('to_date',   today()->format('Y-m-d'));
        $userId   = request('user_id');
        $branchId = request('branch_id');

        // بيانات الورديات مع إجماليات المبيعات
        $shifts = PosShift::with(['user', 'branch'])
            ->whereBetween('opened_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
            ->when($userId,   fn($q) => $q->where('user_id', $userId))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('opened_at', 'desc')
            ->paginate(30);

        // مبيعات كل كاشير في الفترة
        $cashierSales = OperHead::where('pro_type', 102)
            ->whereDate('pro_date', '>=', $fromDate)
            ->whereDate('pro_date', '<=', $toDate)
            ->when($userId,   fn($q) => $q->where('user', $userId))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->selectRaw('`user` as user_id, COUNT(*) as invoices_count, SUM(fat_net) as net_sales, SUM(fat_disc) as total_discount, AVG(fat_net) as avg_invoice')
            ->groupBy('user')
            ->with('user')
            ->get();

        // إجماليات الورديات
        $shiftTotals = PosShift::whereBetween('opened_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
            ->when($userId,   fn($q) => $q->where('user_id', $userId))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->selectRaw('COUNT(*) as total_shifts, SUM(opening_balance) as total_opening, SUM(closing_balance) as total_closing, SUM(closing_balance - opening_balance) as total_cash_diff')
            ->first();

        $users    = User::orderBy('name')->get();
        $branches = Branch::active()->orderBy('name')->get();

        return view('reports::sales.cashier-performance', compact(
            'fromDate', 'toDate', 'shifts', 'cashierSales', 'shiftTotals', 'users', 'branches'
        ));
    }

    /**
     * تقرير تحليل المطعم
     */
    public function restaurantAnalyticsReport(): \Illuminate\View\View
    {
        $fromDate = request('from_date', today()->format('Y-m-d'));
        $toDate   = request('to_date',   today()->format('Y-m-d'));
        $branchId = request('branch_id');

        $base = fn() => OperHead::where('pro_type', 103)
            ->whereDate('pro_date', '>=', $fromDate)
            ->whereDate('pro_date', '<=', $toDate)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        // إجماليات المطعم
        $totals = $base()->selectRaw('
            COUNT(*)                                                    AS total_orders,
            SUM(fat_net)                                                AS net_sales,
            SUM(fat_disc)                                               AS total_discount,
            AVG(fat_net)                                                AS avg_order,
            SUM(CASE WHEN table_id IS NOT NULL THEN 1 ELSE 0 END)      AS dine_in_count,
            SUM(CASE WHEN driver_id IS NOT NULL THEN 1 ELSE 0 END)     AS delivery_count,
            SUM(CASE WHEN table_id IS NULL AND driver_id IS NULL THEN 1 ELSE 0 END) AS takeaway_count,
            SUM(CASE WHEN table_id IS NOT NULL THEN fat_net ELSE 0 END)  AS dine_in_sales,
            SUM(CASE WHEN driver_id IS NOT NULL THEN fat_net ELSE 0 END) AS delivery_sales
        ')->first();

        // مبيعات حسب الساعة
        $hourly = $base()->selectRaw('HOUR(pro_date) AS hour, COUNT(*) AS orders_count, SUM(fat_net) AS net_sales')
            ->groupBy('hour')->orderBy('hour')->get();

        // مبيعات حسب اليوم
        $daily = $base()->selectRaw('DATE(pro_date) AS day, COUNT(*) AS orders_count, SUM(fat_net) AS net_sales, AVG(fat_net) AS avg_order')
            ->groupBy('day')->orderBy('day')->get();

        // أفضل 10 أصناف في المطعم
        $topItems = DB::table('operation_items')
            ->join('operhead', 'operation_items.pro_id', '=', 'operhead.id')
            ->join('items', 'operation_items.item_id', '=', 'items.id')
            ->where('operhead.pro_type', 103)
            ->whereDate('operhead.pro_date', '>=', $fromDate)
            ->whereDate('operhead.pro_date', '<=', $toDate)
            ->when($branchId, fn($q) => $q->where('operhead.branch_id', $branchId))
            ->selectRaw('items.name, SUM(operation_items.qty_out) AS qty, SUM(operation_items.qty_out * operation_items.item_price) AS total')
            ->groupBy('items.id', 'items.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $branches = Branch::active()->orderBy('name')->get();

        return view('reports::sales.restaurant-analytics', compact(
            'fromDate', 'toDate', 'totals', 'hourly', 'daily', 'topItems', 'branches'
        ));
    }

    /**
     * تقرير مقارنة القنوات (كاشير vs مطعم vs مبيعات)
     */
    public function channelComparisonReport(): \Illuminate\View\View
    {
        $fromDate = request('from_date', today()->subDays(29)->format('Y-m-d'));
        $toDate   = request('to_date',   today()->format('Y-m-d'));
        $branchId = request('branch_id');

        $posTypes = [10, 102, 103];

        $base = fn() => OperHead::whereIn('pro_type', $posTypes)
            ->whereDate('pro_date', '>=', $fromDate)
            ->whereDate('pro_date', '<=', $toDate)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        // إجماليات كل قناة
        $channelTotals = $base()->selectRaw('
            SUM(fat_net)                                                          AS grand_total,
            SUM(CASE WHEN pro_type=10  THEN fat_net ELSE 0 END)                  AS sales_total,
            SUM(CASE WHEN pro_type=102 THEN fat_net ELSE 0 END)                  AS cashier_total,
            SUM(CASE WHEN pro_type=103 THEN fat_net ELSE 0 END)                  AS restaurant_total,
            COUNT(*)                                                              AS grand_count,
            SUM(CASE WHEN pro_type=10  THEN 1 ELSE 0 END)                        AS sales_count,
            SUM(CASE WHEN pro_type=102 THEN 1 ELSE 0 END)                        AS cashier_count,
            SUM(CASE WHEN pro_type=103 THEN 1 ELSE 0 END)                        AS restaurant_count,
            AVG(CASE WHEN pro_type=10  THEN fat_net END)                         AS sales_avg,
            AVG(CASE WHEN pro_type=102 THEN fat_net END)                         AS cashier_avg,
            AVG(CASE WHEN pro_type=103 THEN fat_net END)                         AS restaurant_avg,
            SUM(CASE WHEN pro_type=10  THEN fat_disc ELSE 0 END)                 AS sales_discount,
            SUM(CASE WHEN pro_type=102 THEN fat_disc ELSE 0 END)                 AS cashier_discount,
            SUM(CASE WHEN pro_type=103 THEN fat_disc ELSE 0 END)                 AS restaurant_discount
        ')->first();

        // اتجاه يومي لكل قناة
        $dailyTrend = $base()->selectRaw('
            DATE(pro_date)                                                        AS day,
            SUM(CASE WHEN pro_type=10  THEN fat_net ELSE 0 END)                  AS sales_total,
            SUM(CASE WHEN pro_type=102 THEN fat_net ELSE 0 END)                  AS cashier_total,
            SUM(CASE WHEN pro_type=103 THEN fat_net ELSE 0 END)                  AS restaurant_total
        ')->groupBy('day')->orderBy('day')->get();

        // أفضل أصناف لكل قناة
        $topItemsByChannel = [];
        foreach ([10 => 'sales', 102 => 'cashier', 103 => 'restaurant'] as $type => $label) {
            $topItemsByChannel[$label] = DB::table('operation_items')
                ->join('operhead', 'operation_items.pro_id', '=', 'operhead.id')
                ->join('items', 'operation_items.item_id', '=', 'items.id')
                ->where('operhead.pro_type', $type)
                ->whereDate('operhead.pro_date', '>=', $fromDate)
                ->whereDate('operhead.pro_date', '<=', $toDate)
                ->when($branchId, fn($q) => $q->where('operhead.branch_id', $branchId))
                ->selectRaw('items.name, SUM(operation_items.qty_out * operation_items.item_price) AS total')
                ->groupBy('items.id', 'items.name')
                ->orderByDesc('total')
                ->limit(5)
                ->get();
        }

        $branches = Branch::active()->orderBy('name')->get();

        return view('reports::sales.channel-comparison', compact(
            'fromDate', 'toDate', 'channelTotals', 'dailyTrend', 'topItemsByChannel', 'branches'
        ));
    }

    /**
     * تقرير الأصناف الراكدة في POS
     */
    public function posSlowMoversReport(): \Illuminate\View\View
    {
        $idleDays = (int) request('idle_days', 30);
        $branchId = request('branch_id');
        $cutoffDate = today()->subDays($idleDays)->format('Y-m-d');

        $posTypes = [10, 102, 103];

        // أصناف بيعت في POS لكن آخر بيع قبل cutoff
        $slowMovers = DB::table('items')
            ->leftJoin('operation_items', 'items.id', '=', 'operation_items.item_id')
            ->leftJoin('operhead', function ($join) use ($posTypes) {
                $join->on('operation_items.pro_id', '=', 'operhead.id')
                     ->whereIn('operhead.pro_type', $posTypes)
                     ->where('operhead.isdeleted', 0);
            })
            ->when($branchId, fn($q) => $q->where('operhead.branch_id', $branchId))
            ->where('items.is_active', 1)
            ->where('items.isdeleted', 0)
            ->selectRaw('
                items.id,
                items.code,
                items.name,
                items.average_cost,
                MAX(DATE(operhead.pro_date))    AS last_sale_date,
                SUM(operation_items.qty_out)    AS total_sold,
                COUNT(DISTINCT operhead.id)     AS invoices_count
            ')
            ->groupBy('items.id', 'items.code', 'items.name', 'items.average_cost')
            ->havingRaw('MAX(DATE(operhead.pro_date)) IS NULL OR MAX(DATE(operhead.pro_date)) < ?', [$cutoffDate])
            ->orderByRaw('CASE WHEN MAX(DATE(operhead.pro_date)) IS NULL THEN 0 ELSE 1 END ASC, MAX(DATE(operhead.pro_date)) ASC')
            ->paginate(50);

        $branches = Branch::active()->orderBy('name')->get();

        return view('reports::sales.pos-slow-movers', compact(
            'slowMovers', 'idleDays', 'cutoffDate', 'branches'
        ));
    }

    /**
     * تقرير الخصومات والمرتجعات
     */
    public function discountsAndReturnsReport(): \Illuminate\View\View
    {
        $fromDate = request('from_date', today()->format('Y-m-d'));
        $toDate   = request('to_date',   today()->format('Y-m-d'));
        $branchId = request('branch_id');
        $userId   = request('user_id');

        $salesTypes   = [10, 102, 103];
        $returnsTypes = [12, 112]; // مرتجع مبيعات + مرتجع كاشير

        $base = fn(array $types) => OperHead::whereIn('pro_type', $types)
            ->whereDate('pro_date', '>=', $fromDate)
            ->whereDate('pro_date', '<=', $toDate)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($userId,   fn($q) => $q->where('user', $userId));

        // إجماليات الخصومات
        $discountTotals = $base($salesTypes)->selectRaw('
            SUM(fat_disc)                                                         AS total_discount,
            SUM(fat_net)                                                          AS net_sales,
            COUNT(*)                                                              AS invoices_count,
            SUM(CASE WHEN fat_disc > 0 THEN 1 ELSE 0 END)                        AS discounted_invoices,
            AVG(CASE WHEN fat_disc > 0 THEN fat_disc_per END)                    AS avg_discount_pct,
            SUM(CASE WHEN pro_type=102 THEN fat_disc ELSE 0 END)                 AS cashier_discount,
            SUM(CASE WHEN pro_type=103 THEN fat_disc ELSE 0 END)                 AS restaurant_discount,
            SUM(CASE WHEN pro_type=10  THEN fat_disc ELSE 0 END)                 AS sales_discount
        ')->first();

        // إجماليات المرتجعات
        $returnTotals = $base($returnsTypes)->selectRaw('
            COUNT(*)                                                              AS returns_count,
            SUM(fat_net)                                                          AS returns_value,
            SUM(CASE WHEN pro_type=12  THEN fat_net ELSE 0 END)                  AS sales_returns,
            SUM(CASE WHEN pro_type=112 THEN fat_net ELSE 0 END)                  AS cashier_returns
        ')->first();

        // خصومات حسب المستخدم
        $discountByUser = $base($salesTypes)
            ->where('fat_disc', '>', 0)
            ->selectRaw('`user` as user_id, COUNT(*) as invoices_count, SUM(fat_disc) as total_discount, AVG(fat_disc_per) as avg_pct, SUM(fat_net) as net_sales')
            ->groupBy('user')
            ->orderByDesc('total_discount')
            ->with('user')
            ->get();

        // يومي للخصومات
        $dailyDiscounts = $base($salesTypes)->selectRaw('DATE(pro_date) AS day, SUM(fat_disc) AS discount, SUM(fat_net) AS net_sales')
            ->groupBy('day')->orderBy('day')->get();

        $users    = User::orderBy('name')->get();
        $branches = Branch::active()->orderBy('name')->get();

        return view('reports::sales.discounts-returns', compact(
            'fromDate', 'toDate', 'discountTotals', 'returnTotals',
            'discountByUser', 'dailyDiscounts', 'users', 'branches'
        ));
    }

    /**
     * تقرير ساعات الذروة
     */
    public function peakHoursReport(): \Illuminate\View\View
    {
        $fromDate = request('from_date', today()->subDays(29)->format('Y-m-d'));
        $toDate   = request('to_date',   today()->format('Y-m-d'));
        $saleType = request('sale_type'); // null=all, 102=cashier, 103=restaurant
        $branchId = request('branch_id');

        $posTypes = $saleType ? [(int) $saleType] : [102, 103];

        $base = fn() => OperHead::whereIn('pro_type', $posTypes)
            ->whereDate('pro_date', '>=', $fromDate)
            ->whereDate('pro_date', '<=', $toDate)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        // توزيع حسب الساعة
        $hourly = $base()->selectRaw('
            HOUR(created_at)                                                      AS hour,
            COUNT(*)                                                              AS orders_count,
            SUM(fat_net)                                                          AS net_sales,
            AVG(fat_net)                                                          AS avg_order,
            SUM(CASE WHEN pro_type=102 THEN 1 ELSE 0 END)                        AS cashier_count,
            SUM(CASE WHEN pro_type=103 THEN 1 ELSE 0 END)                        AS restaurant_count
        ')->groupBy('hour')->orderBy('hour')->get();

        // توزيع حسب يوم الأسبوع
        $weekday = $base()->selectRaw('
            DAYOFWEEK(pro_date)                                                   AS dow,
            DAYNAME(pro_date)                                                     AS day_name,
            COUNT(*)                                                              AS orders_count,
            SUM(fat_net)                                                          AS net_sales,
            AVG(fat_net)                                                          AS avg_order
        ')->groupBy('dow', 'day_name')->orderBy('dow')->get();

        // إجماليات
        $totals = $base()->selectRaw('COUNT(*) AS total_orders, SUM(fat_net) AS net_sales, AVG(fat_net) AS avg_order')->first();

        $peakHour    = $hourly->sortByDesc('orders_count')->first();
        $peakWeekday = $weekday->sortByDesc('orders_count')->first();

        $branches = Branch::active()->orderBy('name')->get();

        return view('reports::sales.peak-hours', compact(
            'fromDate', 'toDate', 'hourly', 'weekday', 'totals',
            'peakHour', 'peakWeekday', 'branches'
        ));
    }
}
