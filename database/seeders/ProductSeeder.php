<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = Vendor::all();
        $categories = Category::all();

        if ($vendors->isEmpty() || $categories->isEmpty()) {
            echo "⚠️ No vendors or categories found. Please seed vendors and categories first.\n";
            return;
        }

        $productTemplates = [
            ['name_ar' => 'هاتف ذكي', 'description_ar' => 'هاتف ذكي بشاشة عالية الدقة', 'price_range' => [1000, 3000], 'unit' => 'قطعة'],
            ['name_ar' => 'لابتوب', 'description_ar' => 'لابتوب عالي الأداء', 'price_range' => [3000, 6000], 'unit' => 'قطعة'],
            ['name_ar' => 'سماعات', 'description_ar' => 'سماعات لاسلكية عالية الجودة', 'price_range' => [200, 800], 'unit' => 'زوج'],
            ['name_ar' => 'ساعة ذكية', 'description_ar' => 'ساعة ذكية بمميزات متقدمة', 'price_range' => [500, 1500], 'unit' => 'قطعة'],
            ['name_ar' => 'شاحن', 'description_ar' => 'شاحن سريع', 'price_range' => [50, 300], 'unit' => 'قطعة'],
            ['name_ar' => 'كيبورد', 'description_ar' => 'لوحة مفاتيح احترافية', 'price_range' => [200, 800], 'unit' => 'قطعة'],
            ['name_ar' => 'ماوس', 'description_ar' => 'ماوس بصري عالي الدقة', 'price_range' => [50, 400], 'unit' => 'قطعة'],
            ['name_ar' => 'كاميرا', 'description_ar' => 'كاميرا عالية الدقة', 'price_range' => [1500, 5000], 'unit' => 'قطعة'],
            ['name_ar' => 'حقيبة', 'description_ar' => 'حقيبة عملية', 'price_range' => [100, 500], 'unit' => 'قطعة'],
            ['name_ar' => 'شاشة', 'description_ar' => 'شاشة LED عالية الدقة', 'price_range' => [1000, 3000], 'unit' => 'قطعة'],
        ];

        $vegetableProducts = [
            ['name_ar' => 'طماطم', 'description_ar' => 'طماطم طازجة', 'price' => 8.50, 'unit' => 'كجم'],
            ['name_ar' => 'خيار', 'description_ar' => 'خيار طازج', 'price' => 6.00, 'unit' => 'كجم'],
            ['name_ar' => 'بطاطس', 'description_ar' => 'بطاطس طازجة', 'price' => 5.50, 'unit' => 'كجم'],
            ['name_ar' => 'بصل', 'description_ar' => 'بصل أحمر', 'price' => 7.00, 'unit' => 'كجم'],
            ['name_ar' => 'جزر', 'description_ar' => 'جزر طازج', 'price' => 6.50, 'unit' => 'كجم'],
            ['name_ar' => 'فلفل رومي', 'description_ar' => 'فلفل رومي ملون', 'price' => 12.00, 'unit' => 'كجم'],
            ['name_ar' => 'كوسة', 'description_ar' => 'كوسة خضراء', 'price' => 7.50, 'unit' => 'كجم'],
            ['name_ar' => 'باذنجان', 'description_ar' => 'باذنجان أسود', 'price' => 9.00, 'unit' => 'كجم'],
            ['name_ar' => 'خس', 'description_ar' => 'خس طازج', 'price' => 5.00, 'unit' => 'حزمة'],
            ['name_ar' => 'بقدونس', 'description_ar' => 'بقدونس أخضر', 'price' => 3.00, 'unit' => 'حزمة'],
        ];

        $productCount = 0;

        // Get food category
        $foodCategory = $categories->firstWhere('slug', 'aghthy-omshrobat');

        // Create vegetable products for food category
        if ($foodCategory) {
            foreach ($vegetableProducts as $i => $vegProduct) {
                Product::create([
                    'vendor_id' => $vendors->random()->id,
                    'category_id' => $foodCategory->id,
                    'name_ar' => $vegProduct['name_ar'],
                    'slug' => Str::slug($vegProduct['name_ar'] . '-' . $foodCategory->name_ar),
                    'description_ar' => $vegProduct['description_ar'] . ' - طازجة من المزرعة',
                    'unit' => $vegProduct['unit'],
                    'price' => $vegProduct['price'],
                    'compare_price' => null,
                    'stock' => rand(50, 300),
                    'images' => ["https://picsum.photos/300/300?random=" . (100 + $productCount)],
                    'is_active' => true,
                    'is_featured' => $i < 3,
                    'sort_order' => $productCount,
                ]);
                
                $productCount++;
            }
        }

        // Create 10 products for each category
        foreach ($categories as $category) {
            // Skip food category as we already added vegetables
            if ($category->slug === 'aghthy-omshrobat') {
                continue;
            }

            for ($i = 0; $i < 10; $i++) {
                $template = $productTemplates[$i % count($productTemplates)];
                $price = rand($template['price_range'][0], $template['price_range'][1]);
                $comparePrice = $price > 500 ? $price + rand(100, 500) : null;

                $product = Product::create([
                    'vendor_id' => $vendors->random()->id,
                    'category_id' => $category->id,
                    'name_ar' => $template['name_ar'] . ' - ' . $category->name_ar . ' ' . ($i + 1),
                    'slug' => Str::slug($template['name_ar'] . ' ' . $category->name_ar . ' ' . ($i + 1)),
                    'description_ar' => $template['description_ar'] . ' من فئة ' . $category->name_ar,
                    'unit' => $template['unit'],
                    'price' => $price,
                    'compare_price' => $comparePrice,
                    'stock' => rand(10, 200),
                    'images' => ["https://picsum.photos/300/300?random=" . (100 + $productCount)],
                    'is_active' => true,
                    'is_featured' => $i < 2,
                    'sort_order' => $productCount,
                ]);

                // Add variations for phones (هاتف ذكي)
                if ($template['name_ar'] === 'هاتف ذكي' && $i < 2) {
                    $colors = ['أسود', 'أبيض', 'أزرق', 'ذهبي'];
                    $storages = ['128GB', '256GB', '512GB'];
                    
                    foreach ($colors as $colorIndex => $color) {
                        foreach ($storages as $storageIndex => $storage) {
                            $variationPrice = $price + ($storageIndex * 200);
                            ProductVariation::create([
                                'product_id' => $product->id,
                                'name_ar' => "{$color} - {$storage}",
                                'attributes' => [
                                    'color' => $color,
                                    'storage' => $storage,
                                ],
                                'price' => $variationPrice,
                                'stock' => rand(5, 30),
                                'sku' => 'PHN-' . $product->id . '-C' . $colorIndex . '-S' . $storageIndex,
                                'is_active' => true,
                            ]);
                        }
                    }
                }
                
                $productCount++;
            }
        }

        echo "✅ Products created successfully ({$productCount} products)\n";
        echo "   - 10 products per category\n";
        echo "   - Distributed across " . $vendors->count() . " vendors\n";
    }
}
