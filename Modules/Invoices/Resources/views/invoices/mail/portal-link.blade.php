<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #ffffff; color: #333; line-height: 1.6; margin: 0; padding: 40px 20px; }
        .container { max-width: 500px; margin: 0 auto; text-align: right; }
        .brand { font-size: 22px; font-weight: bold; color: #198754; margin-bottom: 30px; border-bottom: 2px solid #f8f9fa; padding-bottom: 10px; }
        .content { margin-bottom: 30px; }
        .details { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .details div { margin-bottom: 10px; display: flex; justify-content: space-between; }
        .label { color: #666; font-size: 14px; }
        .value { font-weight: bold; color: #000; }
        .btn { display: inline-block; background: #198754; color: #ffffff !important; padding: 12px 25px; border-radius: 5px; text-decoration: none; font-weight: bold; margin-top: 10px; }
        .footer { margin-top: 40px; font-size: 12px; color: #999; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="brand">
            {{ config('app.name') }}
        </div>

        <div class="content">
            <p>مرحباً <strong>{{ $invoice->acc1Head->aname ?? 'عميلنا العزيز' }}</strong>،</p>
            <p>إليك تفاصيل فاتورتك الجديدة المرفقة:</p>

            <div class="details">
                <div>
                    <span class="label">رقم الفاتورة:</span>
                    <span class="value">#{{ $invoice->pro_id }}</span>
                </div>
                <div>
                    <span class="label">تاريخ الصدور:</span>
                    <span class="value">{{ $invoice->pro_date }}</span>
                </div>
                <div>
                    <span class="label">المبلغ الإجمالي:</span>
                    <span class="value">{{ number_format($invoice->fat_net ?? $invoice->pro_value ?? 0, 2) }}</span>
                </div>
            </div>

            <a href="{{ $portalUrl }}" class="btn">عرض الفاتورة وتفاصيلها</a>
        </div>

        <div class="footer">
            <p>شكراً لتعاملك مع {{ config('app.name') }}</p>
            <p style="direction: ltr;">{{ $portalUrl }}</p>
        </div>
    </div>
</body>
</html>
