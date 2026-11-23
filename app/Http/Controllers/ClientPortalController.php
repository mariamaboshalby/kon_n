<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\JournalDetail;
use App\Models\OperHead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Accounts\Models\AccHead;

class ClientPortalController extends Controller
{
    public function login(): View
    {
        return view('portal.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $request->validate([
            'phone'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // العملاء في acc_head تحت كود 1103، لديهم phone + portal_password
        $account = AccHead::withoutGlobalScopes()
            ->where('phone', $request->phone)
            ->whereNotNull('portal_password')
            ->where(function ($q) {
                $q->where('code', 'like', '1103%');
            })
            ->first();

        if (! $account || $account->portal_password !== $request->password) {
            return back()->withErrors(['phone' => __('portal.invalid_credentials')])->withInput();
        }

        session(['portal_account_id' => $account->id]);

        return redirect()->route('portal.statement');
    }

    public function statement(Request $request): View|RedirectResponse
    {
        if (! session('portal_account_id')) {
            return redirect()->route('portal.login');
        }

        $account = AccHead::withoutGlobalScopes()->findOrFail(session('portal_account_id'));

        $fromDate = $request->input('from_date', now()->startOfMonth()->toDateString());
        $toDate   = $request->input('to_date', now()->endOfMonth()->toDateString());

        $balanceBefore = JournalDetail::where('account_id', $account->id)
            ->whereDate('crtime', '<', $fromDate)
            ->sum('debit')
            - JournalDetail::where('account_id', $account->id)
            ->whereDate('crtime', '<', $fromDate)
            ->sum('credit');

        $movements = JournalDetail::where('account_id', $account->id)
            ->whereDate('crtime', '>=', $fromDate)
            ->whereDate('crtime', '<=', $toDate)
            ->orderBy('crtime')
            ->paginate(50);

        return view('portal.statement', compact('account', 'movements', 'balanceBefore', 'fromDate', 'toDate'));
    }

    public function operationDetails(int $opId): JsonResponse
    {
        if (! session('portal_account_id')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $accountId = session('portal_account_id');

        // التحقق أن هذه العملية تخص الحساب الحالي فعلاً
        $exists = JournalDetail::where('account_id', $accountId)
            ->where('op_id', $opId)
            ->exists();

        if (! $exists) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $operation = OperHead::withoutGlobalScopes()
            ->with(['type', 'operationItems.item', 'operationItems.unit'])
            ->findOrFail($opId);

        $items = $operation->operationItems->map(fn ($item) => [
            'name'     => $item->item?->aname ?? '-',
            'qty'      => $item->qty,
            'unit'     => $item->unit?->aname ?? '-',
            'price'    => number_format((float) $item->price, 2),
            'total'    => number_format((float) ($item->qty * $item->price), 2),
        ]);

        return response()->json([
            'id'          => $operation->id,
            'type'        => $operation->getOperationTypeText(),
            'date'        => $operation->pro_date,
            'value'       => number_format((float) $operation->pro_value, 2),
            'notes'       => $operation->notes,
            'items'       => $items,
        ]);
    }

    public function logout(): RedirectResponse
    {
        session()->forget('portal_account_id');

        return redirect()->route('portal.login');
    }
}
