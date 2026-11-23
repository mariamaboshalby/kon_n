<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أقساطي | {{ $client->aname }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/images/favicon.ico') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
    <style>
        body { background-color: #f8faff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1a1a1a; padding-top: 30px; }
        
        /* Card Styling */
        .main-card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); background: #fff; height: 100%; transition: transform 0.2s; }
        .installment-card { position: relative; border: 1px solid #edf2f7; }
        
        /* Status Badges */
        .status-badge { font-size: 0.75rem; padding: 4px 12px; border-radius: 50px; font-weight: 500; display: inline-flex; align-items: center; gap: 4px; }
        .status-paid { background: #e6fcf5; color: #20c997; }
        .status-pending { background: #f1f3f5; color: #868e96; }
        .status-late { background: #fff5f5; color: #ff6b6b; }

        /* Typography */
        .text-muted-custom { color: #94a3b8; font-size: 0.85rem; }
        .amount-text { font-size: 1.5rem; font-weight: 700; color: #1e293b; }
        .currency { font-size: 0.9rem; color: #64748b; margin-right: 4px; }

        /* Buttons */
        .btn-pay-now { background-color: #e03131; color: white; border: none; padding: 10px; border-radius: 8px; width: 100%; font-weight: 600; margin-top: 15px; }
        .btn-pay-early { background-color: transparent; color: #1a1a1a; border: 1px solid #dee2e6; padding: 10px; border-radius: 8px; width: 100%; font-weight: 500; margin-top: 15px; }
        .btn-view-receipt { background-color: #f8f9fa; color: #495057; border: 1px solid #dee2e6; padding: 10px; border-radius: 8px; width: 100%; font-weight: 500; margin-top: 15px; }

        /* Progress Bar */
        .progress { height: 8px; border-radius: 10px; background-color: #e9ecef; }
        .progress-bar { background-color: #087f5b; border-radius: 10px; }

        /* Sidebar Stats */
        .stat-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-left: 8px; }

        .late-border { border-right: 4px solid #fa5252; }
    </style>
</head>
<body>

<div class="container">
    <div class="row g-4">
        
        <div class="col-lg-8 order-2 order-lg-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold m-0">قائمة الأقساط</h4>
            </div>

            <div class="row g-3">
                @forelse($installments as $ins)
                    @php
                        $is_late = ($ins->status !== 'paid' && $ins->due_date < now());
                        $is_paid = ($ins->status === 'paid');
                    @endphp
                    
                    <div class="col-md-6">
                        <div class="main-card installment-card p-4 {{ $is_late ? 'late-border' : '' }}">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <i class="las la-ellipsis-v text-muted cursor-pointer"></i>
                                
                                @if($is_paid)
                                    <span class="status-badge status-paid"><i class="las la-check-circle"></i> مدفوع</span>
                                @elseif($is_late)
                                    <span class="status-badge status-late"><i class="las la-exclamation-triangle"></i> متأخر</span>
                                @else
                                    <span class="status-badge status-pending"><i class="las la-clock"></i> قيد الانتظار</span>
                                @endif
                            </div>

                            <h6 class="fw-bold mb-1">{{ $ins->notes ?? 'القسط الشهري - ' . ($ins->due_date?->translatedFormat('F') ?? '') }}</h6>
                            <p class="text-muted-custom mb-3">تاريخ الاستحقاق: <span class="{{ $is_late ? 'text-danger' : '' }}">{{ $ins->due_date?->format('d F Y') ?? '---' }}</span></p>

                            <div class="mt-4">
                                <span class="amount-text">{{ number_format($ins->amount_due, 0) }}</span>
                                <span class="currency">ج.م</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">لا توجد أقساط حالياً</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="col-lg-4 order-1 order-lg-2">
            <div class="main-card p-4 mb-3">
                <h6 class="fw-bold mb-1">الملخص المالي</h6>
                <p class="text-muted-custom small mb-4">إجمالي المبالغ المستحقة والمدفوعة</p>

                <div class="text-center my-4">
                    <div class="d-flex align-items-center justify-content-center">
                        <span class="amount-text" style="font-size: 2.5rem;">{{ number_format($totalAmount, 0) }}</span>
                        <span class="currency ms-2">ج.م</span>
                    </div>
                    <p class="text-muted-custom">إجمالي الأقساط</p>
                </div>

                <div class="stat-items mt-4">
                    <div class="stat-item">
                        <span class="text-muted-custom"><span class="dot" style="background: #20c997;"></span>المدفوع</span>
                        <span class="fw-bold">{{ number_format($totalPaid, 0) }} <small class="text-muted-custom">ج.م</small></span>
                    </div>
                    <div class="stat-item">
                        <span class="text-muted-custom"><span class="dot" style="background: #74c0fc;"></span>المتبقي</span>
                        <span class="fw-bold">{{ number_format($remaining, 0) }} <small class="text-muted-custom">ج.م</small></span>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-success fw-bold">{{ $progressPercentage }}%</span>
                        <span class="text-muted">التقدم</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: {{ $progressPercentage }}%"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>