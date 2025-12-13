<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name_ar' => 'الباقة المجانية',
                'description_ar' => 'باقة مجانية للتجربة',
                'pricing_type' => 'fixed',
                'price' => 0,
                'duration_days' => 30,
                'features' => ['5 منتجات كحد أقصى', 'دعم فني أساسي'],
                'max_products' => 5,
                'max_orders_per_month' => 50,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 0,
            ],
            [
                'name_ar' => 'الباقة الأساسية',
                'description_ar' => 'باقة شهرية مناسبة للمتاجر الصغيرة',
                'pricing_type' => 'fixed',
                'price' => 99,
                'duration_days' => 30,
                'features' => ['50 منتج كحد أقصى', 'دعم فني على مدار الساعة', 'تقارير أساسية'],
                'max_products' => 50,
                'max_orders_per_month' => 200,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
            ],
            [
                'name_ar' => 'الباقة الاحترافية',
                'description_ar' => 'باقة شهرية للمتاجر المتوسطة',
                'pricing_type' => 'fixed',
                'price' => 199,
                'duration_days' => 30,
                'features' => ['منتجات غير محدودة', 'دعم فني مميز', 'تقارير متقدمة', 'أولوية في الظهور'],
                'max_products' => null,
                'max_orders_per_month' => null,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name_ar' => 'باقة العمولة',
                'description_ar' => 'ادفع نسبة من كل طلب بدلاً من اشتراك شهري - نسبة متدرجة حسب قيمة الطلب',
                'pricing_type' => 'percentage',
                'price' => 5, // Default percentage if no tier matches
                'percentage_tiers' => [
                    ['min' => 0, 'max' => 1000, 'percentage' => 5],
                    ['min' => 1000, 'max' => 3000, 'percentage' => 3],
                    ['min' => 3000, 'max' => null, 'percentage' => 1],
                ],
                'duration_days' => 365,
                'features' => ['منتجات غير محدودة', 'نسبة متدرجة حسب قيمة الطلب', 'دعم فني كامل', 'لا يوجد رسوم شهرية'],
                'max_products' => null,
                'max_orders_per_month' => null,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($packages as $package) {
            Package::create($package);
        }

        echo "✅ Packages created successfully\n";
    }
}
