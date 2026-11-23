<?php

declare(strict_types=1);

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Accounts\Models\AccHead;

class PortalController extends Controller
{
    public function show(string $hash)
    {
        // البحث عن الحساب باستخدام الـ hash (MD5 الخاص بالـ ID)
        $client = AccHead::withoutGlobalScopes()
            ->get()
            ->first(function ($account) use ($hash) {
                return md5((string) $account->id) === $hash;
            });

        if (!$client) {
            abort(404, __('common.invalid_link'));
        }

        // جلب جميع الأقساط (الدفعات) الخاصة بهذا الحساب
        $installments = $client->installmentPayments()
            ->orderBy('due_date', 'asc')
            ->get();

        // حساب الإحصائيات
        $totalAmount = $installments->sum('amount_due');
        $paidAmount = $installments->where('status', 'paid')->sum('amount_paid');
        $partialAmount = $installments->where('status', 'partial')->sum('amount_paid');
        $totalPaid = $paidAmount + $partialAmount;
        $remaining = $totalAmount - $totalPaid;
        $progressPercentage = $totalAmount > 0 ? round(($totalPaid / $totalAmount) * 100) : 0;

        return view('accounts::portal.show', compact(
            'client',
            'installments',
            'totalAmount',
            'totalPaid',
            'remaining',
            'progressPercentage'
        ));
    }
}
