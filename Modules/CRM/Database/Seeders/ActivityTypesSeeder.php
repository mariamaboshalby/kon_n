<?php

declare(strict_types=1);

namespace Modules\CRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\CRM\Models\ActivityType;

class ActivityTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'مكالمة',
            'رسالة',
            'اجتماع',
        ];
        foreach ($types as $title) {
            ActivityType::firstOrCreate(['title' => $title]);
        }
    }
}
