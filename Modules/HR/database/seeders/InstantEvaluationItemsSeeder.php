<?php

declare(strict_types=1);

namespace Modules\HR\database\seeders;

use Illuminate\Database\Seeder;
use Modules\HR\Models\InstantEvaluationItem;

final class InstantEvaluationItemsSeeder extends Seeder
{
    public function run(): void
    {
        $positive = [
            'حسن الاستضافة (Good hosting)',
            'المبادرة وحل المشكلات',
            'الالتزام بالمواعيد',
            'دقة وجودة العمل',
            'التعاون والعمل الجماعي',
            'الاستقلالية وتحمل المسؤولية',
            'الابتكار وتقديم المقترحات',
            'المرونة والتكيف',
            'التواصل الفعال',
            'توجيه الزملاء (Mentoring)',
        ];

        $negative = [
            'نشر الشائعات',
            'كثرة التأخير والغياب',
            'ضعف الإنتاجية',
            'تكرار الأخطاء',
            'التهرب من المسؤولية',
            'افتعال النزاعات',
            'رفض النقد البناء',
            'إهدار وقت وموارد العمل',
            'حجب المعلومات وضعف التواصل',
            'مخالفة سياسات الشركة',
        ];

        foreach ($positive as $index => $name) {
            InstantEvaluationItem::query()->firstOrCreate(
                [
                    'name' => $name,
                    'polarity' => InstantEvaluationItem::POLARITY_POSITIVE,
                ],
                [
                    'description' => null,
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                ]
            );
        }

        foreach ($negative as $index => $name) {
            InstantEvaluationItem::query()->firstOrCreate(
                [
                    'name' => $name,
                    'polarity' => InstantEvaluationItem::POLARITY_NEGATIVE,
                ],
                [
                    'description' => null,
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                ]
            );
        }

        $this->command?->info('Instant evaluation items seeded (10 positive + 10 negative).');
    }
}
