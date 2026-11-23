<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فاتورة نقاط البيع - {{ $pro_id }}</title>
    
    @php
        $s = $settings;
        $paperWidth  = $s->cashier_paper_width  ?? '78mm';
        $fontSize    = $s->cashier_font_size     ?? 11;
        $sepStyle    = $s->cashier_separator_style ?? 'dashed';
        $title       = $s->cashier_receipt_title   ?? 'فاتورة مبيعات';
        $subtitle    = $s->cashier_receipt_subtitle ?? '';
        $footer      = $s->cashier_receipt_footer   ?? 'شكراً لك!';
        $showLogo    = $s->cashier_show_logo         ?? true;
        $showOrderNo = $s->cashier_show_order_number ?? true;
        $showDatetime= $s->cashier_show_datetime     ?? true;
        $showSeller  = $s->cashier_show_seller       ?? true;
        $showBarcode = $s->cashier_show_barcode      ?? true;
        $showNotes   = $s->cashier_show_notes        ?? true;
        $showSubtotal= $s->cashier_show_subtotal     ?? true;
        $showDiscount= $s->cashier_show_discount     ?? true;
        $showAdditional = $s->cashier_show_additional ?? true;
        $showNet     = $s->cashier_show_net          ?? true;
        $showPayment = $s->cashier_show_payment_details ?? true;
    @endphp
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Cairo', sans-serif;
            font-size: {{ $fontSize }}px;
            line-height: 1.3;
            color: #000;
            background: white;
            direction: rtl;
        }
        
        .receipt {
            width: {{ $paperWidth }};
            max-width: {{ $paperWidth }};
            margin: 0 auto;
            padding: 5mm;
            background: white;
            border: 1px solid #ddd;
        }
        
        .receipt-header {
            text-align: center;
            border-bottom: 2px {{ $sepStyle }} #000;
            padding-bottom: 5px;
            margin-bottom: 5px;
        }
        
        .company-logo { font-size: {{ $fontSize + 5 }}px; font-weight: 700; margin-bottom: 3px; }
        .company-info { font-size: {{ $fontSize - 2 }}px; color: #333; margin-bottom: 2px; }
        .receipt-title { font-size: {{ $fontSize + 1 }}px; font-weight: 600; margin: 5px 0; }
        
        .receipt-info { margin-bottom: 5px; border-bottom: 1px {{ $sepStyle }} #ccc; padding-bottom: 5px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 2px; font-size: {{ $fontSize - 1 }}px; }
        .info-label { font-weight: 600; }
        .info-value { color: #333; }
        
        .receipt-items { margin-bottom: 5px; }
        .items-header { display: flex; justify-content: space-between; font-weight: 600; font-size: {{ $fontSize - 2 }}px; border-bottom: 1px solid #000; padding-bottom: 2px; margin-bottom: 3px; }
        .item-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 3px; font-size: {{ $fontSize - 2 }}px; padding-bottom: 2px; border-bottom: 1px dotted #ccc; }
        .item-name { flex: 1; font-weight: 500; padding-left: 3px; word-break: break-word; }
        .item-qty { width: 25px; text-align: center; }
        .item-price { width: 35px; text-align: left; }
        .item-total { width: 45px; text-align: left; font-weight: 600; }
        
        .receipt-totals { border-top: 2px solid #000; padding-top: 3px; margin-top: 5px; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 2px; font-size: {{ $fontSize }}px; }
        .total-row.grand-total { font-size: {{ $fontSize + 1 }}px; font-weight: 700; border-top: 1px solid #000; padding-top: 3px; margin-top: 3px; }
        
        .receipt-payment { margin-top: 5px; padding-top: 5px; border-top: 1px {{ $sepStyle }} #ccc; }
        .payment-row { display: flex; justify-content: space-between; margin-bottom: 2px; font-size: {{ $fontSize }}px; }
        
        .receipt-footer { text-align: center; margin-top: 10px; padding-top: 5px; border-top: 2px {{ $sepStyle }} #000; font-size: {{ $fontSize - 2 }}px; color: #333; }
        .thank-you { font-weight: 600; font-size: {{ $fontSize - 1 }}px; margin-bottom: 3px; }
        .barcode { text-align: center; margin: 5px 0; font-family: 'Courier New', monospace; font-size: {{ $fontSize + 1 }}px; font-weight: 600; }
        
        @media print {
            @page { size: {{ $paperWidth }} auto; margin: 0; }
            body { margin: 0; padding: 0; }
            .receipt { width: {{ $paperWidth }}; max-width: {{ $paperWidth }}; margin: 0; padding: 5mm; border: none; }
            .no-print { display: none !important; }
        }
        @media screen {
            body { padding: 20px; background: #f5f5f5; }
            .receipt { box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        }
    </style>
</head>
<body>
    <div class="receipt">
        {{-- رأس الفاتورة --}}
        <div class="receipt-header">
            @if($s->cashier_header_image)
                <div style="text-align:center;margin-bottom:4px">
                    <img src="{{ $s->cashier_header_image }}" style="max-width:100%;max-height:60px" alt="">
                </div>
            @endif
            @if($showLogo)
                <div class="company-logo">{{ config('app.name', 'نظام نقاط البيع') }}</div>
            @endif
            @if($subtitle)
                <div class="company-info">{{ $subtitle }}</div>
            @endif
            <div class="receipt-title">{{ $title }}</div>
        </div>

        {{-- معلومات الفاتورة --}}
        <div class="receipt-info">
            @if($showOrderNo)
                <div class="info-row">
                    <span class="info-label">رقم الفاتورة:</span>
                    <span class="info-value">{{ $pro_id }}</span>
                </div>
            @endif
            @if($showDatetime)
                <div class="info-row">
                    <span class="info-label">التاريخ:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($pro_date)->format('Y-m-d') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">الوقت:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($operation->created_at)->format('H:i:s') }}</span>
                </div>
            @endif
            @if($acc1List->count() > 0 && $acc1List->first()->aname !== 'عميل نقدي')
                <div class="info-row">
                    <span class="info-label">العميل:</span>
                    <span class="info-value">{{ $acc1List->first()->aname }}</span>
                </div>
            @endif
            @if($showSeller && $operation->user)
                <div class="info-row">
                    <span class="info-label">الكاشير:</span>
                    <span class="info-value">{{ $operation->user->name ?? 'غير محدد' }}</span>
                </div>
            @endif
        </div>

        {{-- عناصر الفاتورة --}}
        <div class="receipt-items">
            <div class="items-header">
                <span>الصنف</span><span>الكمية</span><span>السعر</span><span>المجموع</span>
            </div>
            @foreach($invoiceItems as $item)
                <div class="item-row">
                    <div class="item-name">{{ $item['item_name'] }}</div>
                    <div class="item-qty">{{ number_format($item['quantity'], 0) }}</div>
                    <div class="item-price">{{ number_format($item['price'], 2) }}</div>
                    <div class="item-total">{{ number_format($item['sub_value'], 2) }}</div>
                </div>
            @endforeach
        </div>

        {{-- ملاحظات --}}
        @if($showNotes && $notes)
            <div class="info-row" style="font-size:{{ $fontSize - 2 }}px;color:#555;margin-bottom:4px">
                <span class="info-label">ملاحظات:</span>
                <span>{{ $notes }}</span>
            </div>
        @endif

        {{-- المجاميع --}}
        <div class="receipt-totals">
            @if($showSubtotal)
                <div class="total-row">
                    <span>المجموع الفرعي:</span>
                    <span>{{ number_format($subtotal, 2) }}</span>
                </div>
            @endif
            @if($showDiscount && $discount_value > 0)
                <div class="total-row">
                    <span>الخصم:</span>
                    <span>-{{ number_format($discount_value, 2) }}</span>
                </div>
            @endif
            @if($showAdditional && $additional_value > 0)
                <div class="total-row">
                    <span>الإضافي:</span>
                    <span>+{{ number_format($additional_value, 2) }}</span>
                </div>
            @endif
            @if($showNet)
                <div class="total-row grand-total">
                    <span>الإجمالي:</span>
                    <span>{{ number_format($total_after_additional, 2) }}</span>
                </div>
            @endif
        </div>

        {{-- معلومات الدفع --}}
        @if($showPayment && $received_from_client > 0)
            <div class="receipt-payment">
                <div class="payment-row">
                    <span>المدفوع:</span>
                    <span>{{ number_format($received_from_client, 2) }}</span>
                </div>
                @if($received_from_client > $total_after_additional)
                    <div class="payment-row">
                        <span>المتبقي:</span>
                        <span>{{ number_format($received_from_client - $total_after_additional, 2) }}</span>
                    </div>
                @endif
            </div>
        @endif

        {{-- الباركود --}}
        @if($showBarcode)
            <div class="barcode">*{{ $pro_id }}*</div>
        @endif

        {{-- تذييل الفاتورة --}}
        <div class="receipt-footer">
            @if($s->cashier_footer_image)
                <div style="margin-bottom:4px">
                    <img src="{{ $s->cashier_footer_image }}" style="max-width:100%;max-height:50px" alt="">
                </div>
            @endif
            <div class="thank-you">{{ $footer }}</div>
            <div>{{ now()->format('Y-m-d H:i:s') }}</div>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() { window.print(); }, 500);
        };
    </script>
</body>
</html>
