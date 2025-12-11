<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            ['name_ar' => 'العريش', 'name_en' => 'Al-Arish', 'delivery_fee' => 20],
            ['name_ar' => 'الشيخ زويد', 'name_en' => 'Sheikh Zuweid', 'delivery_fee' => 30],
            ['name_ar' => 'رفح', 'name_en' => 'Rafah', 'delivery_fee' => 35],
            ['name_ar' => 'بئر العبد', 'name_en' => 'Bir al-Abed', 'delivery_fee' => 25],
            ['name_ar' => 'الحسنة', 'name_en' => 'Al-Hasana', 'delivery_fee' => 30],
            ['name_ar' => 'نخل', 'name_en' => 'Nakhl', 'delivery_fee' => 40],
        ];

        foreach ($areas as $index => $area) {
            Area::create([
                'name_ar' => $area['name_ar'],
                'name_en' => $area['name_en'],
                'delivery_fee' => $area['delivery_fee'],
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }

        echo "✅ Areas created successfully\n";
    }
}
