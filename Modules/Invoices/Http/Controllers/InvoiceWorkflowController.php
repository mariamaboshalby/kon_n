<?php

namespace Modules\Invoices\Http\Controllers;

use App\Models\OperHead;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class InvoiceWorkflowController extends Controller
{
    public function index(Request $request)
    {
        $trackableTypes = [25, 15, 17, 11, 19];

        $query = OperHead::whereIn('pro_type', $trackableTypes);

        // فلتر نوع الفاتورة
        if ($request->filled('type')) {
            $query->where('pro_type', (int) $request->input('type'));
        }

        // فلتر حالة الـ workflow
        if ($request->filled('workflow_state')) {
            $query->where('workflow_state', (int) $request->input('workflow_state'));
        }

        // فلتر من تاريخ
        if ($request->filled('from_date')) {
            $query->whereDate('pro_date', '>=', $request->input('from_date'));
        }

        // فلتر إلى تاريخ
        if ($request->filled('to_date')) {
            $query->whereDate('pro_date', '<=', $request->input('to_date'));
        }

        // فلتر رقم المستند
        if ($request->filled('pro_id')) {
            $query->where('pro_id', (int) $request->input('pro_id'));
        }

        $requests = $query->orderBy('pro_date', 'desc')->paginate(20)->withQueryString();

        return view('invoices::invoices.requests-index', ['requests' => $requests]);
    }

    public function show(string $id)
    {
        // allow passing either primary id or pro_id
        $root = OperHead::find($id);
        if (! $root) {
            $root = OperHead::where('pro_id', $id)->first();
        }
        if (! $root) {
            abort(404);
        }

        // تحديد نقطة البداية بناءً على نوع الفاتورة
        $allStageDefinitions = [
            ['name' => 'طلب احتياج',           'type' => 25, 'icon' => 'fa-list-check'],
            ['name' => 'عرض سعر من مورد',       'type' => 17, 'icon' => 'fa-file-invoice-dollar'],
            ['name' => 'أمر شراء',              'type' => 15, 'icon' => 'fa-shopping-cart'],
            ['name' => 'فاتورة مشتريات',        'type' => 11, 'icon' => 'fa-file-invoice'],
            ['name' => 'إذن صرف / تحويل مخزن', 'type' => 19, 'icon' => 'fa-warehouse'],
        ];

        // ابحث عن الـ root الأصلي (origin_id أو نفسه)
        $originId = $root->origin_id ?: $root->id;
        $origin   = OperHead::find($originId) ?? $root;

        // ابدأ المراحل من نوع الـ origin
        $startTypeIndex = 0;
        foreach ($allStageDefinitions as $i => $def) {
            if ($def['type'] === (int) $origin->pro_type) {
                $startTypeIndex = $i;
                break;
            }
        }
        $stageDefinitions = array_slice($allStageDefinitions, $startTypeIndex);

        $stages = [];

        foreach ($stageDefinitions as $index => $def) {
            $completed = false;
            $details   = '';
            $stageRecord = null;

            if ($index === 0) {
                // المرحلة الأولى هي الـ origin نفسه
                $completed   = true;
                $stageRecord = $origin;
                $date        = $origin->pro_date
                    ? \Carbon\Carbon::parse($origin->pro_date)->format('d/m/Y')
                    : now()->format('d/m/Y');
                $details = $origin->info ?: ("#{$origin->id} - " . $date);
            } else {
                // ابحث عن فاتورة مرتبطة بالـ origin
                $child = OperHead::where(function ($q) use ($origin) {
                    $q->where('origin_id', $origin->id)
                        ->orWhere('parent_id', $origin->id)
                        ->orWhere('op2', $origin->id);
                })
                    ->where('pro_type', $def['type'])
                    ->orderBy('id', 'desc')
                    ->first();

                if ($child) {
                    $completed   = true;
                    $stageRecord = $child;
                    $date        = $child->pro_date
                        ? \Carbon\Carbon::parse($child->pro_date)->format('d/m/Y')
                        : now()->format('d/m/Y');
                    $details = $child->info ?: ("#{$child->id} - " . $date);
                } else {
                    $details = 'لم يتم إنشاء هذه المرحلة بعد';
                }
            }

            $stages[] = [
                'name'           => $def['name'],
                'status'         => $completed ? 'completed' : 'pending',
                'id'             => $stageRecord?->id,
                'details'        => $details,
                'icon'           => $def['icon'],
                'workflow_state' => $stageRecord?->workflow_state,
            ];
        }

        return view('invoices::invoices.track', ['stages' => $stages, 'root' => $origin]);
    }

    public function search(Request $request): \Illuminate\Http\Response|\Illuminate\View\View
    {
        return $this->index($request);
    }

    public function confirm(Request $request, string $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['next_stage' => 'required|integer']);

        $root = OperHead::find($id) ?? OperHead::where('pro_id', $id)->firstOrFail();

        // المراحل الكاملة بالترتيب
        $allStages = [
            1 => ['type' => 25, 'name' => 'طلب احتياج'],
            2 => ['type' => 17, 'name' => 'عرض سعر من مورد'],
            3 => ['type' => 15, 'name' => 'أمر شراء'],
            4 => ['type' => 11, 'name' => 'فاتورة مشتريات'],
            5 => ['type' => 19, 'name' => 'إذن صرف'],
        ];

        // ابحث عن موضع الـ root في المراحل
        $rootStageIndex = null;
        foreach ($allStages as $stageNum => $stageDef) {
            if ($stageDef['type'] === (int) $root->pro_type) {
                $rootStageIndex = $stageNum;
                break;
            }
        }

        if ($rootStageIndex === null) {
            return back()->with('error', 'نوع الفاتورة غير مدعوم في نظام التتبع');
        }

        // next_stage هو الـ index النسبي في الـ view (1-based من بداية المراحل المعروضة)
        // المرحلة التالية الفعلية = rootStageIndex + next_stage - 1
        $nextStageInput = (int) $request->input('next_stage');
        $absoluteNextStage = $rootStageIndex + $nextStageInput - 1;

        if (! isset($allStages[$absoluteNextStage])) {
            return back()->with('error', 'المرحلة المطلوبة غير صالحة');
        }

        $nextType         = $allStages[$absoluteNextStage]['type'];
        $nextStageName    = $allStages[$absoluteNextStage]['name'];

        // تحقق إذا المرحلة موجودة مسبقاً
        $existingStage = OperHead::where(function ($q) use ($root) {
            $q->where('origin_id', $root->id)
                ->orWhere('parent_id', $root->id)
                ->orWhere('op2', $root->id);
        })->where('pro_type', $nextType)->first();

        if ($existingStage) {
            return redirect()->route('invoices.edit', $existingStage->id)
                ->with('info', 'تم فتح المرحلة الموجودة مسبقاً للتعديل');
        }

        // تحديث workflow_state
        $root->update([
            'workflow_state' => $absoluteNextStage,
            'is_locked'      => 1,
        ]);

        // حفظ بيانات الـ workflow في الـ session لتعبئة الفاتورة الجديدة
        session([
            'workflow_prefill' => [
                'op2'           => $root->id,
                'parent_id'     => $root->id,
                'origin_id'     => $root->origin_id ?: $root->id,
                'acc1'          => $root->acc1,
                'acc2'          => $root->acc2,
                'emp_id'        => $root->emp_id,
                'pro_value'     => $root->pro_value,
                'fat_total'     => $root->fat_total,
                'branch_id'     => $root->branch_id,
                'source_pro_id' => $root->pro_id,
                'info'          => 'تم إنشاء من ' . ($this->titles[$root->pro_type] ?? '') . ' #' . $root->id,
            ],
        ]);

        return redirect()->route('invoices.form.create', [
            'type' => $nextType,
            'q'    => md5($nextType),
        ])->with('info', 'يرجى مراجعة البيانات قبل الحفظ');
    }

    // ✅ أضف array الـ titles كـ property في الـ Controller
    protected $titles = [
        25 => 'طلب احتياج',
        17 => 'عرض سعر من مورد',
        15 => 'أمر شراء',
        11 => 'فاتورة شراء',
        19 => 'إذن صرف'
    ];
}
