<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'name_en' => 'Cash',
                'name_ar' => 'نقدي',
                'code' => 'cash',
                'description_en' => 'Pay with cash on delivery',
                'description_ar' => 'الدفع نقداً عند الاستلام',
                'icon' => 'cash',
                'is_active' => true,
                'sort_order' => 1,
                'discount_type' => 'percentage',
                'discount_amount' => 0,
            ],
            [
                'name_en' => 'Vodafone Cash',
                'name_ar' => 'فودافون كاش',
                'code' => 'vodafone_cash',
                'description_en' => 'Pay using Vodafone Cash wallet',
                'description_ar' => 'الدفع عبر محفظة فودافون كاش',
                'icon' => 'vodafone-cash',
                'is_active' => true,
                'sort_order' => 2,
                'discount_type' => 'percentage',
                'discount_amount' => 0,
            ],
            [
                'name_en' => 'Instapay',
                'name_ar' => 'إنستاباي',
                'code' => 'instapay',
                'description_en' => 'Pay instantly via Instapay',
                'description_ar' => 'الدفع الفوري عبر إنستاباي',
                'icon' => 'instapay',
                'is_active' => true,
                'sort_order' => 3,
                'discount_type' => 'percentage',
                'discount_amount' => 0,
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }
}

