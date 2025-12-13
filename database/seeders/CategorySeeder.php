<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'إلكترونيات',
            'ملابس',
            'أغذية ومشروبات',
            'مستلزمات منزلية',
            'صحة وجمال',
            'رياضة وترفيه',
        ];

        foreach ($categories as $index => $name) {
            Category::create([
                'name_ar' => $name,
                'slug' => Str::slug($name),
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }

        echo "✅ Categories created successfully\n";
    }
}
