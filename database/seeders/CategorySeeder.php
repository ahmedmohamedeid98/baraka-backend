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
            ['name_ar' => 'إلكترونيات', 'name_en' => 'Electronics'],
            ['name_ar' => 'ملابس', 'name_en' => 'Clothing'],
            ['name_ar' => 'أغذية ومشروبات', 'name_en' => 'Food & Beverages'],
            ['name_ar' => 'مستلزمات منزلية', 'name_en' => 'Home Essentials'],
            ['name_ar' => 'صحة وجمال', 'name_en' => 'Health & Beauty'],
            ['name_ar' => 'رياضة وترفيه', 'name_en' => 'Sports & Recreation'],
        ];

        foreach ($categories as $index => $category) {
            Category::create([
                'name_ar' => $category['name_ar'],
                'name_en' => $category['name_en'],
                'slug' => Str::slug($category['name_en']),
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }

        echo "✅ Categories created successfully\n";
    }
}
