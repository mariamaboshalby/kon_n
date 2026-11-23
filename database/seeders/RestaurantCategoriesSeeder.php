<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Note;
use App\Models\NoteDetails;
use Illuminate\Database\Seeder;

/**
 * يُنشئ مجموعات (تصنيفات) المطعم في note_details مرتبطة بـ note_id = 2 (التصنيفات).
 * هذه المجموعات تُستخدم في POS المطعم لتصنيف الأصناف.
 */
class RestaurantCategoriesSeeder extends Seeder
{
    /** @return array<string> */
    public static function categories(): array
    {
        return [
            'وجبات رئيسية',
            'ساندوتشات',
            'مشروبات ساخنة',
            'مشروبات باردة',
            'عصائر',
            'حلويات',
            'سلطات',
            'مقبلات',
            'بيتزا',
            'مأكولات بحرية',
        ];
    }

    public function run(): void
    {
        // note_id = 2 هو "التصنيفات" — المستخدم في POS المطعم
        $note = Note::where('name', 'التصنيفات')->first();

        if (! $note) {
            $this->command->warn('Note "التصنيفات" not found — run NoteSeeder first.');
            return;
        }

        foreach (self::categories() as $category) {
            NoteDetails::firstOrCreate(
                ['note_id' => $note->id, 'name' => $category]
            );
        }

        $this->command->info('Restaurant categories seeded: ' . count(self::categories()) . ' categories.');
    }
}
