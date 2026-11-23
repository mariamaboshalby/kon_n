<?php

declare(strict_types=1);

namespace Modules\CRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\CRM\Models\ReturnType;

class ReturnTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'استرداد',
            'استبدال',
            'إشعار دائن',
        ];
        foreach ($types as $title) {
            ReturnType::firstOrCreate(['title' => $title]);
        }
    }
}
