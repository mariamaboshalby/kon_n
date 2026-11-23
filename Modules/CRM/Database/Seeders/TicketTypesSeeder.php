<?php

declare(strict_types=1);

namespace Modules\CRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\CRM\Models\TicketType;

class TicketTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'شكوى جودة المنتج',
            'تأخير التوصيل/الشحن',
            'نقص/زيادة في الكمية',
            'خطأ في الفاتورة/السعر',
            'استفسار تقني عن المنتج',
            'طلب زيارة/تدريب',
        ];
        foreach ($types as $title) {
            TicketType::firstOrCreate(['title' => $title]);
        }
    }
}
