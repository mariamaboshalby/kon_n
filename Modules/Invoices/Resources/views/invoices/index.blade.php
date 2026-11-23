@extends('admin.dashboard')

@section('sidebar')
    @if (in_array($invoiceType, [10, 12, 14, 16, 22, 26]))
        @include('components.sidebar.sales-invoices')
    @elseif (in_array($invoiceType, [11, 13, 15, 17, 24, 25]))
        @include('components.sidebar.purchases-invoices')
    @elseif (in_array($invoiceType, [18, 19, 20, 21]))
        @include('components.sidebar.inventory-invoices')
    @endif
@endsection
@section('content')
    @include('components.breadcrumb', [
        'title' => __('invoices::invoices.' . $invoiceTitle),
        'items' => [
            ['label' => __('invoices::invoices.dashboard'), 'url' => route('admin.dashboard')],
            ['label' => __('invoices::invoices.' . $currentSection)],
            ['label' => __('invoices::invoices.' . $invoiceTitle)],
        ],
    ])

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">{{ __('invoices::invoices.' . $invoiceTitle) }}</h5>

                        @can('create ' . $invoiceTitle)
                            <a href="{{ url('/invoices/create?type=' . $invoiceType . '&q=' . md5($invoiceType)) }}"
                                class="btn btn-main">
                                <i class="las la-plus me-1"></i>
                                {{ __('common.add') }} {{ __('invoices::invoices.' . $invoiceTitle) }}
                            </a>
                        @endcan
                    </div>
                    <form method="GET" action="{{ route('invoices.index') }}" class="row g-3 align-items-end">
                        <input type="hidden" name="type" value="{{ $invoiceType }}">

                        <div class="col-md-3">
                            <label for="start_date" class="form-label">{{ __('invoices::invoices.from_date') }}</label>
                            <input type="date" name="start_date" id="start_date" class="form-control"
                                value="{{ $startDate }}">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">{{ __('invoices::invoices.to_date') }}</label>
                            <input type="date" name="end_date" id="end_date" class="form-control"
                                value="{{ $endDate }}">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="las la-filter me-1"></i>
                                {{ __('invoices::invoices.filter') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row" x-data="invoiceFilter()">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">

                    <x-table-export-actions table-id="invoices-table" filename="{{ Str::slug($invoiceTitle) }}"
                        excel-label="{{ __('invoices::invoices.export_excel') }}"
                        pdf-label="{{ __('invoices::invoices.export_pdf') }}"
                        print-label="{{ __('invoices::invoices.print') }}" />

                    <div class="table-responsive" style="overflow-x: auto;">
                        <table id="invoices-table" class="table table-striped mb-0" style="min-width: 1200px;">
                            <thead class="table-light text-center align-middle">
                                <tr>
                                    <th class="font-hold fw-bold font-14 text-center">#</th>
                                    <th class="font-hold fw-bold font-14 text-center">{{ __('invoices::invoices.date') }}
                                    </th>
                                    @if (!in_array($invoiceType, [18, 19, 20, 21]))
                                        <th class="font-hold fw-bold font-14 text-center">
                                            {{ __('invoices::invoices.due_date') }}</th>
                                    @endif
                                    <th class="font-hold fw-bold font-14 text-center">
                                        {{ __('invoices::invoices.operation_name') }}</th>
                                    <th class="font-hold fw-bold font-14 text-center">
                                        {{ __('invoices::invoices.account') }}</th>
                                    <th class="font-hold fw-bold font-14 text-center">
                                        {{ $invoiceType == 21 ? __('invoices::invoices.counter_store') : __('invoices::invoices.counter_account') }}
                                    </th>
                                    <th class="font-hold fw-bold font-14 text-center">
                                        {{ __('invoices::invoices.employee') }}</th>
                                    <th class="font-hold fw-bold font-14 text-center">
                                        {{ __('invoices::invoices.financial_value') }}</th>
                                    @if (!in_array($invoiceType, [18, 19, 20, 21]))
                                        <th class="font-hold fw-bold font-14 text-center">
                                            {{ in_array($invoiceType, [11, 13, 15, 17]) ? __('invoices::invoices.paid_to_supplier') : __('invoices::invoices.paid_by_customer') }}
                                        </th>
                                    @endif
                                    <th class="font-hold fw-bold font-14 text-center">
                                        {{ __('invoices::invoices.net_operation') }}</th>
                                    @if (!in_array($invoiceType, [11, 13, 18, 19, 20, 21]))
                                        <th class="font-hold fw-bold font-14 text-center">
                                            {{ __('invoices::invoices.profit') }}</th>
                                    @endif
                                    <th class="font-hold fw-bold font-14 text-center">
                                        {{ __('invoices::invoices.payment_status') }}</th>
                                    <th class="font-hold fw-bold font-14 text-center">
                                        {{ __('invoices::invoices.actions') }}</th>
                                </tr>
                                <tr>
                                    <th><input type="text" x-model="filters.index" class="form-control form-control-sm"
                                            placeholder="#"></th>
                                    <th><input type="text" x-model="filters.date" class="form-control form-control-sm"
                                            placeholder="{{ __('invoices::invoices.date') }}"></th>
                                    @if (!in_array($invoiceType, [18, 19, 20, 21]))
                                        <th><input type="text" x-model="filters.dueDate"
                                                class="form-control form-control-sm"
                                                placeholder="{{ __('invoices::invoices.due_date') }}">
                                        </th>
                                    @endif
                                    <th><input type="text" x-model="filters.operationName"
                                            class="form-control form-control-sm"
                                            placeholder="{{ __('invoices::invoices.operation_name') }}">
                                    </th>
                                    <th><input type="text" x-model="filters.account" class="form-control form-control-sm"
                                            placeholder="{{ __('invoices::invoices.account') }}"></th>
                                    <th><input type="text" x-model="filters.counterAccount"
                                            class="form-control form-control-sm"
                                            placeholder="{{ $invoiceType == 21 ? __('invoices::invoices.counter_store') : __('invoices::invoices.counter_account') }}">
                                    </th>
                                    <th><input type="text" x-model="filters.employee"
                                            class="form-control form-control-sm"
                                            placeholder="{{ __('invoices::invoices.employee') }}"></th>
                                    <th><input type="text" x-model="filters.financialValue"
                                            class="form-control form-control-sm"
                                            placeholder="{{ __('invoices::invoices.financial_value') }}"></th>
                                    @if (!in_array($invoiceType, [18, 19, 20, 21]))
                                        <th><input type="text" x-model="filters.paidAmount"
                                                class="form-control form-control-sm"
                                                placeholder="{{ in_array($invoiceType, [11, 13, 15, 17]) ? __('invoices::invoices.paid_to_supplier') : __('invoices::invoices.paid_by_customer') }}">
                                        </th>
                                    @endif
                                    <th><input type="text" x-model="filters.netOperation"
                                            class="form-control form-control-sm"
                                            placeholder="{{ __('invoices::invoices.net_operation') }}">
                                    </th>
                                    @if (!in_array($invoiceType, [11, 13, 18, 19, 20, 21]))
                                        <th><input type="text" x-model="filters.profit"
                                                class="form-control form-control-sm"
                                                placeholder="{{ __('invoices::invoices.profit') }}">
                                        </th>
                                    @endif
                                    <th>
                                        <select x-model="filters.paymentStatus" class="form-select form-select-sm">
                                            <option value="">{{ __('invoices::invoices.all') }}</option>
                                            <option value="unpaid">{{ __('invoices::invoices.unpaid') }}</option>
                                            <option value="paid">{{ __('invoices::invoices.paid') }}</option>
                                            <option value="partial">{{ __('invoices::invoices.partial') }}</option>
                                        </select>
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $invoicesData = $invoices
                                        ->map(function ($invoice, $index) use ($invoiceType) {
                                            $totalAmount = $invoice->pro_value;
                                            $paidAmount = $invoice->paid_from_client;

                                            if ($paidAmount == 0) {
                                                $paymentStatus = 'unpaid';
                                                $paymentBadge =
                                                    '<span class="badge bg-danger">' .
                                                    __('invoices::invoices.unpaid') .
                                                    '</span>';
                                            } elseif ($paidAmount >= $totalAmount) {
                                                $paymentStatus = 'paid';
                                                $paymentBadge =
                                                    '<span class="badge bg-success">' .
                                                    __('invoices::invoices.paid') .
                                                    '</span>';
                                            } else {
                                                $paymentStatus = 'partial';
                                                $paymentBadge =
                                                    '<span class="badge bg-warning text-dark">' .
                                                    __('invoices::invoices.partial') .
                                                    '</span>';
                                            }

                                            return [
                                                'index' => $index + 1,
                                                'id' => $invoice->id,
                                                'pro_id' => $invoice->pro_id,
                                                'date' => \Carbon\Carbon::parse($invoice->pro_date)->format('Y-m-d'),
                                                'dueDate' => \Carbon\Carbon::parse($invoice->accural_date)->format(
                                                    'Y-m-d',
                                                ),
                                                'operationName' => $invoice->type->ptext ?? '',
                                                'account' => $invoice->acc1Head->aname ?? '',
                                                'counterAccount' => $invoice->acc2Head->aname ?? '',
                                                'employee' => $invoice->employee->aname ?? '',
                                                'financialValue' => $invoice->pro_value,
                                                'paidAmount' => $invoice->paid_from_client,
                                                'netOperation' => $invoice->fat_net,
                                                'profit' => $invoice->profit,
                                                'paymentStatus' => $paymentStatus,
                                                'paymentBadge' => $paymentBadge,
                                                'proType' => $invoice->pro_type,
                                                'accHeadId' => $invoice->acc1,
                                                'portalUrl' => route('invoice.portal', [
                                                    'id' => $invoice->id,
                                                    'hash' => sha1(md5($invoice->id)),
                                                ]),
                                                'invoice' => $invoice,
                                            ];
                                        })
                                        ->toArray();
                                @endphp

                                <template x-for="(invoice, idx) in filteredInvoices" :key="invoice.id">
                                    <tr>
                                        <td x-text="invoice.index"></td>
                                        <td>
                                            <span class="badge bg-light text-dark" x-text="invoice.date"></span>
                                        </td>
                                        @if (!in_array($invoiceType, [18, 19, 20, 21]))
                                            <td>
                                                <span class="badge bg-light text-dark" x-text="invoice.dueDate"></span>
                                            </td>
                                        @endif
                                        <td x-text="invoice.operationName"></td>
                                        <td><span class="badge bg-light text-dark" x-text="invoice.account"></span></td>
                                        <td><span class="badge bg-light text-dark" x-text="invoice.counterAccount"></span>
                                        </td>
                                        <td><span class="badge bg-light text-dark" x-text="invoice.employee"></span></td>
                                        <td x-text="invoice.financialValue"></td>
                                        @if (!in_array($invoiceType, [18, 19, 20, 21]))
                                            <td x-text="invoice.paidAmount"></td>
                                        @endif
                                        <td x-text="invoice.netOperation"></td>
                                        @if (!in_array($invoiceType, [11, 13, 18, 19, 20, 21]))
                                            <td x-text="invoice.profit"></td>
                                        @endif
                                        <td class="text-center" x-html="invoice.paymentBadge"></td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center flex-wrap gap-2">
                                                <template x-if="invoice.proType == 11">
                                                    <div class="d-flex gap-2">
                                                        <a class="btn btn-success btn-icon-square-sm"
                                                            :href="`{{ route('items.manage-prices') }}?invoice_id=${invoice.id}`"
                                                            :title="'{{ __('invoices::invoices.edit_selling_price') }}'">
                                                            <i class="las la-dollar-sign"></i>
                                                        </a>
                                                        <a class="btn btn-primary btn-icon-square-sm"
                                                            :href="`{{ url('invoices/barcode-report') }}/${invoice.id}`"
                                                            :title="'{{ __('invoices::invoices.print_barcode') }}'">
                                                            <i class="las la-barcode"></i>
                                                        </a>
                                                    </div>
                                                </template>
                                                <button class="btn btn-primary btn-icon-square-sm"
                                                    title="إرسال عبر الإيميل"
                                                    @click="
            if(confirm('هل أنتِ متأكدة من إرسال الرابط للعميل؟')) {
                // نستخدم مسار ثابت ونضيف إليه الـ id الخاص بـ Alpine.js
                let sendUrl = '{{ url('invoices') }}/' + invoice.id + '/send-portal';
                
                fetch(sendUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                })
                .catch(error => {
                    console.error(error);
                    alert('حدث خطأ أثناء الإرسال');
                });
            }
        ">
                                                    <i class="las la-paper-plane"></i>
                                                </button>

                                                <a class="btn btn-info btn-icon-square-sm"
                                                    :href="`{{ url('invoice/view') }}/${invoice.id}`"
                                                    :title="'{{ __('invoices::invoices.view') }}'">
                                                    <i class="las la-eye"></i>
                                                </a>

                                                @if (setting('enable_installment_from_invoice') && $invoiceType == 10)
                                                    <button type="button" class="btn btn-secondary btn-icon-square-sm"
                                                        :title="'{{ __('invoices::invoices.installment') }}'"
                                                        :disabled="_loadingInvoiceId === invoice.id"
                                                        @click="openInstallmentModal(invoice)">
                                                        <i class="las"
                                                            :class="_loadingInvoiceId === invoice.id ? 'la-spinner la-spin' :
                                                                'la-calendar-check'"></i>
                                                    </button>
                                                @endif

                                                <a class="btn btn-primary btn-icon-square-sm"
                                                    :href="`{{ url('invoice/print') }}/${invoice.id}`" target="_blank"
                                                    :title="'{{ __('invoices::invoices.print') }}'">
                                                    <i class="las la-print"></i>
                                                </a>

                                                <a class="btn btn-warning btn-icon-square-sm"
                                                    :href="`{{ url('invoices') }}/${invoice.id}/edit`"
                                                    :title="'{{ __('invoices::invoices.edit') }}'">
                                                    <i class="las la-edit"></i>
                                                </a>

                                                {{-- ✅ زرار نسخ الفاتورة --}}
                                                <button type="button"
                                                    class="btn btn-secondary btn-icon-square-sm"
                                                    :title="'{{ __('invoices::invoices.copy_as_title') }}'"
                                                    @click="openCopyModal(invoice.pro_id)">
                                                    <i class="las la-copy"></i>
                                                </button>

                                                <form :action="`{{ url('invoices') }}/${invoice.id}`" method="POST"
                                                    onsubmit="return confirm('{{ __('invoices::invoices.confirm_delete') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-icon-square-sm">
                                                        <i class="las la-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <template x-if="filteredInvoices.length === 0">
                                    <tr>
                                        <td colspan="15" class="text-center">
                                            <div class="alert alert-info py-3 mb-0"
                                                style="font-size: 1.2rem; font-weight: 500;">
                                                <i class="las la-info-circle me-2"></i>
                                                {{ __('invoices::invoices.no_results') }}
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ✅ موديل نسخ الفاتورة - كل الأنواع متاحة --}}
    @php
        $allInvoiceTypesForCopy = [
            ['type' => 10, 'label' => __('invoices::invoices.sales_invoice'),          'icon' => 'la-file-invoice-dollar', 'color' => 'success'],
            ['type' => 11, 'label' => __('invoices::invoices.purchase_invoice'),        'icon' => 'la-shopping-cart',       'color' => 'primary'],
            ['type' => 12, 'label' => __('invoices::invoices.sales_return'),            'icon' => 'la-undo',                'color' => 'warning'],
            ['type' => 13, 'label' => __('invoices::invoices.purchase_return'),         'icon' => 'la-undo',                'color' => 'warning'],
            ['type' => 14, 'label' => __('invoices::invoices.sales_order'),             'icon' => 'la-file-alt',            'color' => 'info'],
            ['type' => 15, 'label' => __('invoices::invoices.purchase_order'),          'icon' => 'la-file-alt',            'color' => 'info'],
            ['type' => 16, 'label' => __('invoices::invoices.quotation_to_customer'),   'icon' => 'la-file-invoice',        'color' => 'secondary'],
            ['type' => 17, 'label' => __('invoices::invoices.quotation_from_supplier'), 'icon' => 'la-file-invoice',        'color' => 'secondary'],
            ['type' => 18, 'label' => __('invoices::invoices.damaged_goods_invoice'),   'icon' => 'la-trash',               'color' => 'danger'],
            ['type' => 19, 'label' => __('invoices::invoices.dispatch_order'),          'icon' => 'la-minus-circle',        'color' => 'warning'],
            ['type' => 20, 'label' => __('invoices::invoices.addition_order'),          'icon' => 'la-plus-circle',         'color' => 'success'],
            ['type' => 21, 'label' => __('invoices::invoices.store_to_store_transfer'), 'icon' => 'la-exchange-alt',        'color' => 'dark'],
            ['type' => 22, 'label' => __('invoices::invoices.booking_order'),           'icon' => 'la-bookmark',            'color' => 'info'],
        ];
    @endphp

    <div class="modal fade" id="copyInvoiceModal" tabindex="-1"
         aria-labelledby="copyInvoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="copyInvoiceModalLabel">
                        <i class="las la-copy me-2 text-primary"></i>
                        {{ __('invoices::invoices.copy_as_title') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-2">
                    <p class="text-muted small mb-3">
                        {{ __('invoices::invoices.copy_as_hint') }}
                    </p>

                    <div class="row g-2">
                        @foreach ($allInvoiceTypesForCopy as $option)
                            {{-- الرابط يحمل source_pro_id فقط - الـ InvoiceFormController يجيب البيانات --}}
                            <div class="col-md-6">
                                <a href="#"
                                   class="btn btn-outline-{{ $option['color'] }} w-100 text-start d-flex align-items-center gap-2 px-3 py-2 copy-type-btn"
                                   data-type="{{ $option['type'] }}"
                                   data-base-url="{{ route('invoices.form.create') }}">
                                    <i class="las {{ $option['icon'] }} fs-5"></i>
                                    <span class="fw-semibold" style="font-size: 0.9rem;">{{ $option['label'] }}</span>
                                    <i class="las la-arrow-left ms-auto"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        {{ __('invoices::invoices.close') }}
                    </button>
                </div>

            </div>
        </div>
    </div>

    <script>
        // ✅ ربط زراير النسخ بالـ source_pro_id عند الضغط
        document.addEventListener('DOMContentLoaded', function () {
            const copyModal = document.getElementById('copyInvoiceModal');

            copyModal.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                if (btn && btn.dataset.invoiceProId) {
                    const proId = btn.dataset.invoiceProId;
                    // تحديث كل روابط الأنواع بالـ source_pro_id
                    copyModal.querySelectorAll('.copy-type-btn').forEach(function (link) {
                        const type    = link.dataset.type;
                        const baseUrl = link.dataset.baseUrl;
                        link.href = `${baseUrl}?type=${type}&source_pro_id=${proId}`;
                    });
                }
            });
        });
    </script>

    <script>
        function invoiceFilter() {
            return {
                invoices: @json($invoicesData ?? []),
                _loadingInvoiceId: null,
                filters: {
                    index: '',
                    date: '',
                    dueDate: '',
                    operationName: '',
                    account: '',
                    counterAccount: '',
                    employee: '',
                    financialValue: '',
                    paidAmount: '',
                    netOperation: '',
                    profit: '',
                    paymentStatus: ''
                },
                openInstallmentModal(invoice) {
                    this._loadingInvoiceId = invoice.id;
                    Livewire.dispatch('update-installment-data', {
                        invoiceTotal: invoice.financialValue,
                        paidAmount: invoice.paidAmount || 0,
                        clientAccountId: invoice.accHeadId,
                        invoiceId: invoice.id,
                    });
                    // reset after modal opens
                    setTimeout(() => {
                        this._loadingInvoiceId = null;
                    }, 1500);
                },

                // ✅ فتح موديل النسخ وتحديث الروابط بـ pro_id
                openCopyModal(proId) {
                    const baseUrl = '{{ route('invoices.form.create') }}';
                    document.querySelectorAll('#copyInvoiceModal .copy-type-btn').forEach(function (link) {
                        const type = link.dataset.type;
                        link.href = `${baseUrl}?type=${type}&source_pro_id=${proId}`;
                    });
                    const modal = new bootstrap.Modal(document.getElementById('copyInvoiceModal'));
                    modal.show();
                },
                get filteredInvoices() {
                    return this.invoices.filter(invoice => {
                        const matchIndex = !this.filters.index ||
                            invoice.index.toString().includes(this.filters.index);

                        const matchDate = !this.filters.date ||
                            invoice.date.toLowerCase().includes(this.filters.date.toLowerCase());

                        const matchDueDate = !this.filters.dueDate ||
                            invoice.dueDate.toLowerCase().includes(this.filters.dueDate.toLowerCase());

                        const matchOperationName = !this.filters.operationName ||
                            invoice.operationName.toLowerCase().includes(this.filters.operationName
                                .toLowerCase());

                        const matchAccount = !this.filters.account ||
                            invoice.account.toLowerCase().includes(this.filters.account.toLowerCase());

                        const matchCounterAccount = !this.filters.counterAccount ||
                            invoice.counterAccount.toLowerCase().includes(this.filters.counterAccount
                                .toLowerCase());

                        const matchEmployee = !this.filters.employee ||
                            invoice.employee.toLowerCase().includes(this.filters.employee.toLowerCase());

                        const matchFinancialValue = !this.filters.financialValue ||
                            invoice.financialValue.toString().includes(this.filters.financialValue);

                        const matchPaidAmount = !this.filters.paidAmount ||
                            invoice.paidAmount.toString().includes(this.filters.paidAmount);

                        const matchNetOperation = !this.filters.netOperation ||
                            invoice.netOperation.toString().includes(this.filters.netOperation);

                        const matchProfit = !this.filters.profit ||
                            invoice.profit.toString().includes(this.filters.profit);

                        const matchPaymentStatus = !this.filters.paymentStatus ||
                            invoice.paymentStatus === this.filters.paymentStatus;

                        return matchIndex && matchDate && matchDueDate && matchOperationName &&
                            matchAccount && matchCounterAccount && matchEmployee &&
                            matchFinancialValue && matchPaidAmount && matchNetOperation &&
                            matchProfit && matchPaymentStatus;
                    });
                }
            }
        }
    </script>
    {{-- <livewire:manufacturing::manufacturing-cost-modal /> --}}

    @if (setting('enable_installment_from_invoice') && $invoiceType == 10)
        @livewire('installments::create-installment-from-invoice', [
            'invoiceTotal' => 0,
            'clientAccountId' => null,
        ])
    @endif
@endsection
