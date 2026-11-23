<?php

use Livewire\Volt\Component;
use Modules\Accounts\Models\AccHead;
use App\Models\{OperHead, OperationItems, Item, Barcode, Price, ProType};

new class extends Component {
    public $operationId;
    public $invoice;
    public $invoiceItems = [];
    public $acc1Role;
    public $acc2Role;
    public $visibleColumns = [];
    public $availableColumns = [];

    public function mount($operationId)
    {
        $this->operationId = $operationId;
        $this->loadInvoice();
    }

    public function loadInvoice()
    {
        $this->invoice = OperHead::with(['operationItems.item.units', 'operationItems.unit', 'operationItems.fat_unit', 'acc1Head', 'acc2Head', 'employee', 'invoiceTemplate'])->findOrFail($this->operationId);

        // تحميل تفاصيل الفاتورة
        $this->invoiceItems = $this->invoice->operationItems
            ->map(function ($detail) {
                $item = $detail->item;
                $unit = $detail->fat_unit ?: $detail->unit;

                // 1. Get Base Quantity and Price (Stored in DB)
                $baseQty = $detail->qty_in > 0 ? $detail->qty_in : $detail->qty_out;
                $qty = $baseQty;
                $price = $detail->item_price; // Base price by default

                $unitName = $unit?->name ?? '---';
                $unitId = $detail->fat_unit_id ?: $detail->unit_id;

                // 2. Logic to Determine Display Quantity/Price
                // Priority 1: Check if 'fat_quantity' is saved (Display Qty)
                if (isset($detail->fat_quantity) && $detail->fat_quantity > 0) {
                     $qty = $detail->fat_quantity; // Use saved display qty
                     // fat_price is NOW the display price (already multiplied by uVal during save)
                     $price = $detail->fat_price ?? ($detail->item_price * ($baseQty > 0 ? ($baseQty / $qty) : 1));
                }
                // Priority 2: Check if 'unit_value' column exists and is valid
                elseif (isset($detail->unit_value) && $detail->unit_value > 0 && $detail->unit_value != 1) {
                     $uVal = $detail->unit_value;
                     $qty = $baseQty / $uVal;
                     // fat_price IS display price, or calculate from item_price (base)
                     $price = $detail->fat_price ?? ($detail->item_price * $uVal);
                }
                // Priority 3: Fallback to Relation (if Item/Unit exists)
                elseif ($unit && $item) {
                     $pivotUnit = $item->units->find($unit->id);
                     $uVal = $pivotUnit?->pivot?->u_val ?? 1;
                     if ($uVal > 0) {
                         $qty = $baseQty / $uVal;
                         $price = $detail->fat_price ?? ($detail->item_price * $uVal);
                     }
                }


                return [
                    'item_id'      => $item?->id,
                    'unit_id'      => $unitId,
                    'name'         => $item?->name ?? 'Unknown Item',
                    'code'         => $item?->code,
                    'quantity'     => $qty,
                    'price'        => $price,
                    'sub_value'    => $detail->detail_value ?? ($qty * $price),
                    'discount'     => $detail->item_discount ?? 0,
                    'unit_name'    => $unitName,
                    'unit'         => $unitName,
                    'barcode'      => Barcode::where('item_id', $item?->id)->where('unit_id', $unitId)->value('barcode') ?? '',
                    'item_image'   => $item?->getFirstMediaUrl('item-images', 'thumb') ?: asset('images/no-image.png'),
                    'batch_number' => $detail->batch_number ?? '',
                    'expiry_date'  => $detail->expiry_date ?? '',
                    'length'       => $detail->length ?? '--',
                    'width'        => $detail->width ?? '--',
                    'height'       => $detail->height ?? '--',
                    'density'      => $detail->density ?? '--',
                ];
            })
            ->toArray();

        // Determine Visible Columns for the View (from template)
        if ($this->invoice->template_id && $this->invoice->invoiceTemplate) {
            $this->visibleColumns = $this->invoice->invoiceTemplate->visible_columns ?? [];
        } else {
             // Default columns if no template is saved
            $this->visibleColumns = ['item_name', 'unit', 'quantity', 'price', 'discount', 'sub_value'];
        }

        $this->availableColumns = \Modules\Invoices\Models\InvoiceTemplate::availableColumns();

        // تحديد أدوار الحسابات
        $this->setAccountRoles();
    }

    public function setAccountRoles()
    {
        $type = $this->invoice->pro_type;

        // store keys only, translate in blade
        $map = [
            10 => ['acc1_role' => 'debit',  'acc2_role' => 'credit'],
            11 => ['acc1_role' => 'credit', 'acc2_role' => 'debit'],
            12 => ['acc1_role' => 'credit', 'acc2_role' => 'debit'],
            13 => ['acc1_role' => 'debit',  'acc2_role' => 'credit'],
            14 => ['acc1_role' => 'debit',  'acc2_role' => 'credit'],
            15 => ['acc1_role' => 'credit', 'acc2_role' => 'debit'],
            16 => ['acc1_role' => 'debit',  'acc2_role' => 'credit'],
            17 => ['acc1_role' => 'credit', 'acc2_role' => 'debit'],
            18 => ['acc1_role' => 'debit',  'acc2_role' => 'credit'],
            19 => ['acc1_role' => 'debit',  'acc2_role' => 'credit'],
            20 => ['acc1_role' => 'credit', 'acc2_role' => 'debit'],
            21 => ['acc1_role' => 'debit',  'acc2_role' => 'credit'],
            22 => ['acc1_role' => 'debit',  'acc2_role' => 'credit'],
        ];

        $this->acc1Role = $map[$type]['acc1_role'] ?? 'debit';
        $this->acc2Role = $map[$type]['acc2_role'] ?? 'credit';
    }

    public function getSubtotal()
    {
        return $this->invoice->fat_total ?? 0;
    }

    public function getDiscountValue()
    {
        return $this->invoice->fat_disc ?? 0;
    }

    public function getAdditionalValue()
    {
        return $this->invoice->fat_plus ?? 0;
    }

    public function getTotalAfterAdditional()
    {
        return $this->invoice->fat_net ?? 0;
    }

    public function printInvoice()
    {
        $printUrl = route('invoice.print', ['operation_id' => $this->operationId]);
        $this->dispatch('open-print-window', url: $printUrl);
    }

    public function editInvoice()
    {
        return redirect()->route('invoices.edit', ['invoice' => $this->operationId]);
    }

    public function backToList()
    {
        return redirect()->route('invoices.index');
    }

    public function sendPortalLink()
    {
        $email = $this->invoice->acc1Head->e_mail ?? null;
        if (!$email) {
            session()->flash('error', 'لا يوجد بريد إلكتروني مسجل للعميل.');
            return;
        }

        $hash = sha1(md5($this->operationId));
        $portalUrl = route('invoice.portal', ['id' => $this->operationId, 'hash' => $hash]);

        try {
            \Illuminate\Support\Facades\Mail::to($email)->send(
                new \App\Mail\InvoicePortalMail($this->invoice, $portalUrl)
            );
            session()->flash('success', 'تم إرسال الرابط للعميل بنجاح على: ' . $email);
        } catch (\Exception $e) {
            session()->flash('error', 'فشل الإرسال: ' . $e->getMessage());
        }
    }
}; ?>

<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">
                            <i class="fas fa-file-invoice me-2"></i>
                            {{ $invoice->pro_type ? ProType::find($invoice->pro_type)->ptext ?? __('invoices::invoices.invoice') : __('invoices::invoices.invoice') }}
                        </h1>
                    </div>
                    <div class="col-sm-6">
                        <div class="float-left">
                            <button type="button" class="btn btn-info" wire:click="printInvoice">
                                <i class="fas fa-print"></i> {{ __('invoices::invoices.print') }}
                            </button>
                            <button type="button" class="btn btn-warning" wire:click="editInvoice">
                                <i class="fas fa-edit"></i> {{ __('invoices::invoices.edit') }}
                            </button>
                            <button type="button" class="btn btn-success" wire:click="sendPortalLink"
                                onclick="return confirm('إرسال رابط الفاتورة للعميل؟')">
                                <i class="fas fa-envelope"></i> إرسال للعميل
                            </button>
                            <button type="button" class="btn btn-secondary" wire:click="backToList">
                                <i class="fas fa-arrow-right"></i> {{ __('invoices::invoices.view') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="content">
            <div class="container-fluid">
                <!-- بطاقة معلومات الفاتورة -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-info-circle"></i> {{ __('invoices::invoices.invoice_information') }}
                                </h3>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-5">{{ __('invoices::invoices.invoice_number') }}:</dt>
                                    <dd class="col-sm-7">
                                        <span class="badge badge-primary">{{ $invoice->pro_id ?? __('invoices::invoices.not_specified') }}</span>
                                    </dd>

                                    <dt class="col-sm-5">{{ __('invoices::invoices.invoice_date') }}:</dt>
                                    <dd class="col-sm-7">
                                        {{ $invoice->pro_date ? \Carbon\Carbon::parse($invoice->pro_date)->format('Y-m-d') : __('invoices::invoices.not_specified') }}
                                    </dd>

                                    <dt class="col-sm-5">{{ __('invoices::invoices.due_date') }}:</dt>
                                    <dd class="col-sm-7">
                                        {{ $invoice->accural_date ? \Carbon\Carbon::parse($invoice->accural_date)->format('Y-m-d') : __('invoices::invoices.not_specified') }}
                                    </dd>

                                    <dt class="col-sm-5">{{ __('invoices::invoices.employee') }}:</dt>
                                    <dd class="col-sm-7">{{ $invoice->employee->aname ?? __('invoices::invoices.not_specified') }}</dd>

                                    <dt class="col-sm-5">{{ __('invoices::invoices.price_category') }}:</dt>
                                    <dd class="col-sm-7">
                                        {{ $invoice->price_list ? Price::find($invoice->price_list)->name ?? __('invoices::invoices.not_specified') : __('invoices::invoices.not_specified') }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card card-success card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-university"></i> {{ __('invoices::invoices.account_information') }}
                                </h3>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-5">{{ __('invoices::invoices.' . $acc1Role) }}:</dt>
                                    <dd class="col-sm-7">
                                        <span>{{ $invoice->acc1Head->aname ?? __('invoices::invoices.not_specified') }}</span>
                                    </dd>

                                    <dt class="col-sm-5">{{ __('invoices::invoices.' . $acc2Role) }}:</dt>
                                    <dd class="col-sm-7">
                                        <span>{{ $invoice->acc2Head->aname ?? __('invoices::invoices.not_specified') }}</span>
                                    </dd>

                                    <dt class="col-sm-5">{{ __('invoices::invoices.cash_box') }}:</dt>
                                    <dd class="col-sm-7">
                                        {{ $invoice->acc_fund ? AccHead::find($invoice->acc_fund)->aname ?? __('invoices::invoices.not_specified') : __('invoices::invoices.not_specified') }}
                                    </dd>

                                    <dt class="col-sm-5">
                                        @if (in_array($invoice->pro_type, [10, 12, 14, 16, 18, 21, 22]))
                                            {{ __('invoices::invoices.paid_by_customer') }}:
                                        @elseif(in_array($invoice->pro_type, [11, 13, 15, 17, 20]))
                                            {{ __('invoices::invoices.paid_to_supplier') }}:
                                        @else
                                            {{ __('invoices::invoices.paid') }}:
                                        @endif
                                    </dt>
                                    <dd class="col-sm-7">
                                        <span>{{ number_format($invoice->pro_value ?? 0, 2) }}</span>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- جدول الأصناف (الأعمدة حسب النموذج فقط) -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-list"></i> {{ __('invoices::invoices.invoice_details') }}
                                    <span class="badge badge-primary">{{ count($invoiceItems) }} {{ __('invoices::invoices.item_name') }}</span>
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th class="text-center" width="50">#</th>
                                                <th>{{ __('invoices::invoices.item_name') }}</th>
                                                @if(in_array('item_image', $visibleColumns))
                                                    <th class="text-center">{{ __('invoices::invoices.image') }}</th>
                                                @endif
                                                @php
                                                    $colLabels = [
                                                        'code'                => __('invoices::invoices.code'),
                                                        'unit'                => __('invoices::invoices.unit'),
                                                        'quantity'            => __('invoices::invoices.quantity'),
                                                        'price'               => __('invoices::invoices.price'),
                                                        'discount'            => __('invoices::invoices.discount'),
                                                        'discount_percentage' => __('invoices::invoices.discount_percentage'),
                                                        'discount_value'      => __('invoices::invoices.discount_value'),
                                                        'sub_value'           => __('invoices::invoices.value'),
                                                        'barcode'             => __('invoices::invoices.barcode'),
                                                        'batch_number'        => __('invoices::invoices.batch_number'),
                                                        'expiry_date'         => __('invoices::invoices.expiry_date'),
                                                        'length'              => __('invoices::invoices.length'),
                                                        'width'               => __('invoices::invoices.width'),
                                                        'height'              => __('invoices::invoices.height'),
                                                        'density'             => __('invoices::invoices.density'),
                                                    ];
                                                @endphp
                                                @foreach($visibleColumns as $col)
                                                    @continue($col === 'item_name' || $col === 'item_image')
                                                    <th class="text-center">{{ $colLabels[$col] ?? $col }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($invoiceItems as $index => $item)
                                                <tr>
                                                    <td class="text-center"><strong>{{ $index + 1 }}</strong></td>
                                                    <td>
                                                        <strong>{{ $item['name'] }}</strong>
                                                        @if ($item['code'])
                                                            <br><small class="text-muted">{{ __('invoices::invoices.code') }}: {{ $item['code'] }}</small>
                                                        @endif
                                                    </td>

                                                    @if(in_array('item_image', $visibleColumns))
                                                        <td class="text-center">
                                                            <img src="{{ $item['item_image'] }}" alt="Item Image" class="rounded border" style="max-height: 40px; max-width: 40px; object-fit: contain;">
                                                        </td>
                                                    @endif

                                                    @foreach($visibleColumns as $col)
                                                        @continue($col === 'item_name' || $col === 'item_image')
                                                        @php
                                                            $val = $item[$col] ?? '';
                                                            if(is_numeric($val) && in_array($col, ['price', 'sub_value', 'discount'])) $val = number_format($val, 2);
                                                            elseif(is_numeric($val)) $val = number_format($val);
                                                        @endphp
                                                        <td class="text-center">
                                                            <span>{{ $val }}</span>
                                                        </td>
                                                    @endforeach

                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="{{ count($visibleColumns) + 3 }}" class="text-center py-4">
                                                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                                        <p class="text-muted">{{ __('invoices::invoices.no_items') }}</p>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- الإجماليات -->
                <div class="row">
                    <div class="col-md-6 offset-md-6">
                        <div class="card card-warning">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-calculator"></i> {{ __('invoices::invoices.totals') }}
                                </h3>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-7">{{ __('invoices::invoices.subtotal') }}:</dt>
                                    <dd class="col-sm-5 text-left">
                                        <strong>{{ number_format($this->getSubtotal(), 2) }} </strong>
                                    </dd>

                                    <dt class="col-sm-7">{{ __('invoices::invoices.discount') }}:</dt>
                                    <dd class="col-sm-5 text-left text-danger">
                                        <strong>- {{ number_format($this->getDiscountValue(), 2) }} </strong>
                                    </dd>

                                    <dt class="col-sm-7">{{ __('invoices::invoices.additional') }}:</dt>
                                    <dd class="col-sm-5 text-left text-success">
                                        <strong>+ {{ number_format($this->getAdditionalValue(), 2) }} </strong>
                                    </dd>

                                    <dt class="col-sm-12">
                                        <hr class="my-2">
                                    </dt>

                                    <dt class="col-sm-7 h5">{{ __('invoices::invoices.grand_total') }}:</dt>
                                    <dd class="col-sm-5 text-left h4 text-primary">
                                        <strong>{{ number_format($this->getTotalAfterAdditional(), 2) }} </strong>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- الملاحظات -->
                @if ($invoice->info)
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-sticky-note"></i> {{ __('invoices::invoices.notes') }}
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0">{{ $invoice->info }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('open-print-window', (event) => {
                    const url = event.url;
                    const printWindow = window.open(url, '_blank');
                    if (printWindow) {
                        printWindow.onload = function() {
                            printWindow.print();
                        };
                    } else {
                        alert('{{ __('invoices::invoices.allow_popups') }}');
                    }
                });
            });
        </script>
    @endpush
</div>


