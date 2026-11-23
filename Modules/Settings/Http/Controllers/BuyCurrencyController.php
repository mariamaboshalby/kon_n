<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\{Auth, DB};
use Modules\Accounts\Models\AccHead;
use Modules\Settings\Http\Requests\CurrencyExchangeRequest;
use Modules\Settings\Models\Currency;
use App\Models\{CostCenter, JournalDetail, JournalHead, OperHead};

class BuyCurrencyController extends Controller
{
    private const PRO_TYPE = 80;

    public function __construct()
    {
        $this->middleware('permission:view Currency Exchange')->only('index');
        $this->middleware('permission:create Currency Exchange')->only(['create', 'store']);
        $this->middleware('permission:edit Currency Exchange')->only(['edit', 'update']);
        $this->middleware('permission:delete Currency Exchange')->only('destroy');
    }

    public function index()
    {
        $exchanges = OperHead::where('pro_type', self::PRO_TYPE)
            ->where('isdeleted', 0)
            ->with(['acc1Head', 'acc2Head', 'currency', 'user'])
            ->orderByDesc('pro_date')
            ->get();

        return view('settings::buy.index', compact('exchanges'));
    }

    public function create()
    {
        [$cashAccounts, $currencies, $branches, $costCenters] = $this->getFormData();

        $newProId = (OperHead::where('pro_type', self::PRO_TYPE)->max('pro_id') ?? 0) + 1;

        return view('settings::buy.create', compact(
            'cashAccounts', 'currencies', 'branches', 'costCenters', 'newProId'
        ));
    }

    public function store(CurrencyExchangeRequest $request)
    {
        $validated = $request->validated();

        if (!isMultiCurrencyEnabled()) {
            return back()->withErrors(['error' => __('settings::settings.multi_currency_not_enabled')])->withInput();
        }

        $acc1 = AccHead::find($validated['acc1']);
        if ($acc1->currency_id != $validated['currency_id']) {
            return back()->withErrors([
                'currency_mismatch' => __('settings::settings.currency_must_match_receiving_account'),
            ])->withInput();
        }

        $baseValue = (float) $validated['pro_value'] * (float) $validated['currency_rate'];

        try {
            DB::beginTransaction();

            $newProId = (OperHead::where('pro_type', self::PRO_TYPE)->max('pro_id') ?? 0) + 1;

            $oper = OperHead::create([
                'pro_id'       => $newProId,
                'pro_date'     => $validated['pro_date'],
                'pro_type'     => self::PRO_TYPE,
                'acc1'         => $validated['acc1'],
                'acc2'         => $validated['acc2'],
                'pro_value'    => $baseValue,
                'currency_id'  => $validated['currency_id'],
                'currency_rate'=> $validated['currency_rate'],
                'details'      => $validated['details'] ?? null,
                'pro_num'      => $validated['pro_num'] ?? null,
                'isdeleted'    => 0,
                'tenant'       => 0,
                'branch'       => 1,
                'is_finance'   => 1,
                'is_journal'   => 1,
                'journal_type' => 2,
                'acc1_before'  => 0,
                'acc1_after'   => 0,
                'acc2_before'  => 0,
                'acc2_after'   => 0,
                'cost_center'  => $validated['cost_center'] ?? null,
                'user'         => Auth::id(),
                'branch_id'    => $validated['branch_id'],
            ]);

            $newJournalId = (JournalHead::max('journal_id') ?? 0) + 1;
            $journalHead = JournalHead::create([
                'journal_id' => $newJournalId,
                'total'      => $baseValue,
                'date'       => $validated['pro_date'],
                'op_id'      => $oper->id,
                'pro_type'   => self::PRO_TYPE,
                'details'    => $validated['details'] ?? null,
                'user'       => Auth::id(),
                'branch_id'  => $validated['branch_id'],
            ]);

            JournalDetail::create([
                'journal_id' => $journalHead->journal_id,
                'account_id' => $validated['acc1'],
                'debit'      => $baseValue,
                'credit'     => 0,
                'type'       => 0,
                'info'       => $validated['details'] ?? null,
                'op_id'      => $oper->id,
                'isdeleted'  => 0,
                'branch_id'  => $validated['branch_id'],
            ]);

            JournalDetail::create([
                'journal_id' => $journalHead->journal_id,
                'account_id' => $validated['acc2'],
                'debit'      => 0,
                'credit'     => $baseValue,
                'type'       => 1,
                'info'       => $validated['details'] ?? null,
                'op_id'      => $oper->id,
                'isdeleted'  => 0,
                'branch_id'  => $validated['branch_id'],
            ]);

            DB::commit();

            return redirect()->route('settings.buy-currency.index')
                ->with('success', __('settings::settings.currency_exchange_saved_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => __('settings::settings.error_saving')])->withInput();
        }
    }

    public function edit(int $id)
    {
        $exchange = OperHead::where('pro_type', self::PRO_TYPE)->findOrFail($id);

        [$cashAccounts, $currencies, $branches, $costCenters] = $this->getFormData();

        return view('settings::buy.edit', compact(
            'exchange', 'cashAccounts', 'currencies', 'branches', 'costCenters'
        ));
    }

    public function update(CurrencyExchangeRequest $request, int $id)
    {
        $exchange = OperHead::where('pro_type', self::PRO_TYPE)->findOrFail($id);
        $validated = $request->validated();

        if (!isMultiCurrencyEnabled()) {
            return back()->withErrors(['error' => __('settings::settings.multi_currency_not_enabled')])->withInput();
        }

        $acc1 = AccHead::find($validated['acc1']);
        if ($acc1->currency_id != $validated['currency_id']) {
            return back()->withErrors([
                'currency_mismatch' => __('settings::settings.currency_must_match_receiving_account'),
            ])->withInput();
        }

        $baseValue = (float) $validated['pro_value'] * (float) $validated['currency_rate'];

        try {
            DB::beginTransaction();

            $exchange->update([
                'pro_date'     => $validated['pro_date'],
                'pro_num'      => $validated['pro_num'] ?? null,
                'acc1'         => $validated['acc1'],
                'acc2'         => $validated['acc2'],
                'pro_value'    => $baseValue,
                'currency_id'  => $validated['currency_id'],
                'currency_rate'=> $validated['currency_rate'],
                'details'      => $validated['details'] ?? null,
                'cost_center'  => $validated['cost_center'] ?? null,
                'user'         => Auth::id(),
                'branch_id'    => $validated['branch_id'],
            ]);

            $journalHead = JournalHead::where('op_id', $exchange->id)->first();
            if ($journalHead) {
                $journalHead->update([
                    'total'     => $baseValue,
                    'date'      => $validated['pro_date'],
                    'details'   => $validated['details'] ?? null,
                    'user'      => Auth::id(),
                    'branch_id' => $validated['branch_id'],
                ]);

                JournalDetail::where('journal_id', $journalHead->journal_id)->delete();

                JournalDetail::create([
                    'journal_id' => $journalHead->journal_id,
                    'account_id' => $validated['acc1'],
                    'debit'      => $baseValue,
                    'credit'     => 0,
                    'type'       => 0,
                    'info'       => $validated['details'] ?? null,
                    'op_id'      => $exchange->id,
                    'isdeleted'  => 0,
                    'branch_id'  => $validated['branch_id'],
                ]);

                JournalDetail::create([
                    'journal_id' => $journalHead->journal_id,
                    'account_id' => $validated['acc2'],
                    'debit'      => 0,
                    'credit'     => $baseValue,
                    'type'       => 1,
                    'info'       => $validated['details'] ?? null,
                    'op_id'      => $exchange->id,
                    'isdeleted'  => 0,
                    'branch_id'  => $validated['branch_id'],
                ]);
            }

            DB::commit();

            return redirect()->route('settings.buy-currency.index')
                ->with('success', __('settings::settings.currency_exchange_updated_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => __('settings::settings.an_error_occurred')])->withInput();
        }
    }

    public function destroy(int $id)
    {
        try {
            DB::beginTransaction();

            $exchange = OperHead::where('pro_type', self::PRO_TYPE)->findOrFail($id);

            $journalHead = JournalHead::where('op_id', $exchange->id)->first();
            if ($journalHead) {
                JournalDetail::where('journal_id', $journalHead->journal_id)->delete();
                $journalHead->delete();
            }

            $exchange->delete();

            DB::commit();

            return redirect()->route('settings.buy-currency.index')
                ->with('success', __('settings::settings.currency_exchange_deleted_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => __('settings::settings.error_deleting')]);
        }
    }

    /**
     * @return array{0: \Illuminate\Database\Eloquent\Collection, 1: \Illuminate\Database\Eloquent\Collection, 2: mixed, 3: \Illuminate\Database\Eloquent\Collection}
     */
    private function getFormData(): array
    {
        $cashAccounts = AccHead::where('isdeleted', 0)
            ->where('is_basic', 0)
            ->where('code', 'like', '1101%')
            ->with('currency:id,name,symbol')
            ->select('id', 'aname', 'balance', 'currency_id', 'code')
            ->orderBy('code')
            ->get();

        $currencies = Currency::active()->with('latestRate')->get();
        $branches   = userBranches();
        $costCenters = CostCenter::where('deleted', 0)->get();

        return [$cashAccounts, $currencies, $branches, $costCenters];
    }
}
