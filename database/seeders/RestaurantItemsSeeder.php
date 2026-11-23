<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Note;
use App\Models\NoteDetails;
use App\Models\Unit;
use App\Models\Price;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * يُنشئ أصناف مطعم واقعية مع ربطها بالمجموعات والوحدات والأسعار.
 *
 * يعتمد على:
 * - UnitSeeder      (وحدة "قطعه")
 * - PriceSeeder     (قائمة أسعار "قطاعى")
 * - NoteSeeder      (note "التصنيفات")
 * - RestaurantCategoriesSeeder (مجموعات المطعم)
 */
class RestaurantItemsSeeder extends Seeder
{
    /**
     * بيانات الأصناف: [اسم، تكلفة، سعر بيع، مجموعة]
     *
     * @return array<int, array{name: string, cost: float, price: float, category: string}>
     */
    private function items(): array
    {
        return [
            // ── وجبات رئيسية ──────────────────────────────────────────────
            ['name' => 'وجبة دجاج مشوي',        'cost' => 25.0,  'price' => 45.0,  'category' => 'وجبات رئيسية'],
            ['name' => 'وجبة كباب مشكل',         'cost' => 35.0,  'price' => 60.0,  'category' => 'وجبات رئيسية'],
            ['name' => 'وجبة برجر لحم',          'cost' => 30.0,  'price' => 55.0,  'category' => 'وجبات رئيسية'],
            ['name' => 'وجبة أرز بالدجاج',       'cost' => 20.0,  'price' => 38.0,  'category' => 'وجبات رئيسية'],
            ['name' => 'وجبة مكرونة بالصلصة',    'cost' => 15.0,  'price' => 30.0,  'category' => 'وجبات رئيسية'],
            ['name' => 'وجبة شاورما دجاج',       'cost' => 22.0,  'price' => 40.0,  'category' => 'وجبات رئيسية'],
            ['name' => 'وجبة شاورما لحم',        'cost' => 32.0,  'price' => 55.0,  'category' => 'وجبات رئيسية'],
            ['name' => 'وجبة فراخ مقلية',        'cost' => 28.0,  'price' => 50.0,  'category' => 'وجبات رئيسية'],

            // ── ساندوتشات ──────────────────────────────────────────────────
            ['name' => 'ساندوتش فلافل',          'cost' => 5.0,   'price' => 10.0,  'category' => 'ساندوتشات'],
            ['name' => 'ساندوتش طعمية',          'cost' => 4.0,   'price' => 8.0,   'category' => 'ساندوتشات'],
            ['name' => 'ساندوتش كفتة',           'cost' => 12.0,  'price' => 22.0,  'category' => 'ساندوتشات'],
            ['name' => 'ساندوتش دجاج مشوي',      'cost' => 15.0,  'price' => 28.0,  'category' => 'ساندوتشات'],
            ['name' => 'ساندوتش تونة',           'cost' => 10.0,  'price' => 18.0,  'category' => 'ساندوتشات'],
            ['name' => 'ساندوتش بيض وجبن',       'cost' => 8.0,   'price' => 15.0,  'category' => 'ساندوتشات'],
            ['name' => 'ساندوتش سجق',            'cost' => 9.0,   'price' => 16.0,  'category' => 'ساندوتشات'],

            // ── مشروبات ساخنة ──────────────────────────────────────────────
            ['name' => 'قهوة عربية',             'cost' => 3.0,   'price' => 8.0,   'category' => 'مشروبات ساخنة'],
            ['name' => 'قهوة أمريكانو',          'cost' => 5.0,   'price' => 12.0,  'category' => 'مشروبات ساخنة'],
            ['name' => 'كابتشينو',               'cost' => 7.0,   'price' => 15.0,  'category' => 'مشروبات ساخنة'],
            ['name' => 'لاتيه',                  'cost' => 8.0,   'price' => 16.0,  'category' => 'مشروبات ساخنة'],
            ['name' => 'شاي أحمر',               'cost' => 2.0,   'price' => 6.0,   'category' => 'مشروبات ساخنة'],
            ['name' => 'شاي بالنعناع',           'cost' => 2.5,   'price' => 7.0,   'category' => 'مشروبات ساخنة'],
            ['name' => 'شاي بالحليب',            'cost' => 3.0,   'price' => 8.0,   'category' => 'مشروبات ساخنة'],
            ['name' => 'نسكافيه',                'cost' => 4.0,   'price' => 10.0,  'category' => 'مشروبات ساخنة'],

            // ── مشروبات باردة ──────────────────────────────────────────────
            ['name' => 'مياه معدنية',            'cost' => 1.5,   'price' => 4.0,   'category' => 'مشروبات باردة'],
            ['name' => 'كولا',                   'cost' => 3.0,   'price' => 7.0,   'category' => 'مشروبات باردة'],
            ['name' => 'بيبسي',                  'cost' => 3.0,   'price' => 7.0,   'category' => 'مشروبات باردة'],
            ['name' => 'سبرايت',                 'cost' => 3.0,   'price' => 7.0,   'category' => 'مشروبات باردة'],
            ['name' => 'ميرندا برتقال',          'cost' => 3.0,   'price' => 7.0,   'category' => 'مشروبات باردة'],
            ['name' => 'ريد بول',                'cost' => 8.0,   'price' => 15.0,  'category' => 'مشروبات باردة'],

            // ── عصائر ──────────────────────────────────────────────────────
            ['name' => 'عصير برتقال طازج',       'cost' => 8.0,   'price' => 18.0,  'category' => 'عصائر'],
            ['name' => 'عصير مانجو',             'cost' => 10.0,  'price' => 20.0,  'category' => 'عصائر'],
            ['name' => 'عصير فراولة',            'cost' => 9.0,   'price' => 18.0,  'category' => 'عصائر'],
            ['name' => 'عصير ليمون بالنعناع',    'cost' => 7.0,   'price' => 15.0,  'category' => 'عصائر'],
            ['name' => 'عصير تفاح',              'cost' => 8.0,   'price' => 16.0,  'category' => 'عصائر'],
            ['name' => 'كوكتيل فواكه',           'cost' => 12.0,  'price' => 22.0,  'category' => 'عصائر'],

            // ── حلويات ──────────────────────────────────────────────────────
            ['name' => 'كنافة بالجبن',           'cost' => 10.0,  'price' => 20.0,  'category' => 'حلويات'],
            ['name' => 'أم علي',                 'cost' => 12.0,  'price' => 22.0,  'category' => 'حلويات'],
            ['name' => 'بسبوسة',                 'cost' => 5.0,   'price' => 12.0,  'category' => 'حلويات'],
            ['name' => 'تشيز كيك',               'cost' => 15.0,  'price' => 28.0,  'category' => 'حلويات'],
            ['name' => 'آيس كريم كوب',           'cost' => 8.0,   'price' => 15.0,  'category' => 'حلويات'],
            ['name' => 'وافل بالشوكولاتة',       'cost' => 12.0,  'price' => 22.0,  'category' => 'حلويات'],

            // ── سلطات ──────────────────────────────────────────────────────
            ['name' => 'سلطة خضراء',             'cost' => 8.0,   'price' => 15.0,  'category' => 'سلطات'],
            ['name' => 'سلطة سيزر',              'cost' => 12.0,  'price' => 22.0,  'category' => 'سلطات'],
            ['name' => 'سلطة فتوش',              'cost' => 9.0,   'price' => 16.0,  'category' => 'سلطات'],
            ['name' => 'سلطة تبولة',             'cost' => 8.0,   'price' => 15.0,  'category' => 'سلطات'],
            ['name' => 'سلطة كول سلو',           'cost' => 6.0,   'price' => 12.0,  'category' => 'سلطات'],

            // ── مقبلات ──────────────────────────────────────────────────────
            ['name' => 'حمص بالطحينة',           'cost' => 6.0,   'price' => 12.0,  'category' => 'مقبلات'],
            ['name' => 'متبل',                   'cost' => 6.0,   'price' => 12.0,  'category' => 'مقبلات'],
            ['name' => 'بابا غنوج',              'cost' => 7.0,   'price' => 13.0,  'category' => 'مقبلات'],
            ['name' => 'فطائر جبن',              'cost' => 10.0,  'price' => 18.0,  'category' => 'مقبلات'],
            ['name' => 'سمبوسة لحم',             'cost' => 8.0,   'price' => 15.0,  'category' => 'مقبلات'],
            ['name' => 'بطاطس مقلية',            'cost' => 5.0,   'price' => 10.0,  'category' => 'مقبلات'],

            // ── بيتزا ──────────────────────────────────────────────────────
            ['name' => 'بيتزا مارجريتا',         'cost' => 20.0,  'price' => 40.0,  'category' => 'بيتزا'],
            ['name' => 'بيتزا دجاج',             'cost' => 25.0,  'price' => 48.0,  'category' => 'بيتزا'],
            ['name' => 'بيتزا لحم',              'cost' => 30.0,  'price' => 55.0,  'category' => 'بيتزا'],
            ['name' => 'بيتزا خضار',             'cost' => 18.0,  'price' => 35.0,  'category' => 'بيتزا'],
            ['name' => 'بيتزا مشكلة',            'cost' => 35.0,  'price' => 65.0,  'category' => 'بيتزا'],

            // ── مأكولات بحرية ──────────────────────────────────────────────
            ['name' => 'سمك مشوي',               'cost' => 40.0,  'price' => 75.0,  'category' => 'مأكولات بحرية'],
            ['name' => 'جمبري مقلي',             'cost' => 45.0,  'price' => 85.0,  'category' => 'مأكولات بحرية'],
            ['name' => 'كاليماري',               'cost' => 35.0,  'price' => 65.0,  'category' => 'مأكولات بحرية'],
            ['name' => 'سمك فيليه',              'cost' => 38.0,  'price' => 70.0,  'category' => 'مأكولات بحرية'],
        ];
    }

    public function run(): void
    {
        $unit  = Unit::where('name', 'قطعه')->first();
        $price = Price::where('name', 'قطاعى')->first();
        $note  = Note::where('name', 'التصنيفات')->first();

        if (! $unit || ! $price || ! $note) {
            $this->command->error('Run UnitSeeder, PriceSeeder, NoteSeeder, and RestaurantCategoriesSeeder first.');
            return;
        }

        // تحميل المجموعات مرة واحدة
        $categories = NoteDetails::where('note_id', $note->id)
            ->pluck('id', 'name');

        $lastCode = (int) (DB::table('items')->max('code') ?? 5000);
        $created  = 0;

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->items() as $data) {
            // تجنب التكرار
            if (Item::where('name', $data['name'])->exists()) {
                continue;
            }

            $item = Item::create([
                'name'               => $data['name'],
                'code'               => ++$lastCode,
                'average_cost'       => $data['cost'],
                'is_active'          => 1,
                'isdeleted'          => 0,
                'type'               => 1,
                'tenant'             => 1,
                'branch_id'          => null,
                'min_order_quantity' => 1,
                'max_order_quantity' => 100,
            ]);

            // ربط الوحدة
            DB::table('item_units')->insert([
                'item_id'    => $item->id,
                'unit_id'    => $unit->id,
                'u_val'      => 1,
                'cost'       => $data['cost'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ربط السعر
            DB::table('item_prices')->insert([
                'item_id'    => $item->id,
                'price_id'   => $price->id,
                'unit_id'    => $unit->id,
                'price'      => $data['price'],
                'discount'   => 0,
                'tax_rate'   => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ربط المجموعة (category)
            $categoryId = $categories->get($data['category']);
            if ($categoryId) {
                DB::table('item_notes')->insert([
                    'item_id'          => $item->id,
                    'note_id'          => $note->id,
                    'note_detail_name' => $data['category'],
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            $created++;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info("Restaurant items seeded: {$created} items created.");
    }
}
