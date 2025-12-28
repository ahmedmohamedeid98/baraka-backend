<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\PaymentInstruction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentInstructionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get payment methods
        $vodafoneCash = PaymentMethod::where('code', 'vodafone_cash')->first();
        $instapay = PaymentMethod::where('code', 'instapay')->first();

        if ($vodafoneCash) {
            // Clear existing instructions
            $vodafoneCash->instructions()->delete();

            // Vodafone Cash Instructions
            $vodafoneInstructions = [
                [
                    'instruction_en' => 'Open your Vodafone Cash app on your mobile phone',
                    'instruction_ar' => 'افتح تطبيق فودافون كاش على هاتفك المحمول',
                    'font_size' => 14,
                    'is_bold' => false,
                    'color' => '#000000',
                    'sort_order' => 1,
                ],
                [
                    'instruction_en' => 'Select "Send Money" or "Transfer"',
                    'instruction_ar' => 'اختر "إرسال الأموال" أو "تحويل"',
                    'font_size' => 14,
                    'is_bold' => false,
                    'color' => '#000000',
                    'sort_order' => 2,
                ],
                [
                    'instruction_en' => 'Send the total amount to: 01234567890',
                    'instruction_ar' => 'أرسل المبلغ الإجمالي إلى: 01234567890',
                    'font_size' => 16,
                    'is_bold' => true,
                    'color' => '#E60000',
                    'sort_order' => 3,
                ],
                [
                    'instruction_en' => 'Take a screenshot of the transaction confirmation',
                    'instruction_ar' => 'التقط صورة من شاشة تأكيد العملية',
                    'font_size' => 14,
                    'is_bold' => false,
                    'color' => '#000000',
                    'sort_order' => 4,
                ],
                [
                    'instruction_en' => 'Upload the screenshot before completing your order',
                    'instruction_ar' => 'قم برفع الصورة قبل إتمام الطلب',
                    'font_size' => 14,
                    'is_bold' => true,
                    'color' => '#FF6B00',
                    'sort_order' => 5,
                ],
            ];

            foreach ($vodafoneInstructions as $instruction) {
                $vodafoneCash->instructions()->create($instruction);
            }

            // Update to require transaction screenshot
            $vodafoneCash->update(['required_transaction_screenshot' => true]);
        }

        if ($instapay) {
            // Clear existing instructions
            $instapay->instructions()->delete();

            // Instapay Instructions
            $instapayInstructions = [
                [
                    'instruction_en' => 'Open your Instapay app or mobile banking app',
                    'instruction_ar' => 'افتح تطبيق إنستاباي أو تطبيق البنك الخاص بك',
                    'font_size' => 14,
                    'is_bold' => false,
                    'color' => '#000000',
                    'sort_order' => 1,
                ],
                [
                    'instruction_en' => 'Select Instapay transfer option',
                    'instruction_ar' => 'اختر خيار التحويل عبر إنستاباي',
                    'font_size' => 14,
                    'is_bold' => false,
                    'color' => '#000000',
                    'sort_order' => 2,
                ],
                [
                    'instruction_en' => 'Send to Instapay ID: bareket@instapay',
                    'instruction_ar' => 'أرسل إلى معرف إنستاباي: bareket@instapay',
                    'font_size' => 16,
                    'is_bold' => true,
                    'color' => '#0066CC',
                    'sort_order' => 3,
                ],
                [
                    'instruction_en' => 'Enter the exact order amount',
                    'instruction_ar' => 'أدخل مبلغ الطلب بالضبط',
                    'font_size' => 14,
                    'is_bold' => false,
                    'color' => '#000000',
                    'sort_order' => 4,
                ],
                [
                    'instruction_en' => 'Take a screenshot of the successful transaction',
                    'instruction_ar' => 'التقط صورة من شاشة تأكيد العملية الناجحة',
                    'font_size' => 14,
                    'is_bold' => false,
                    'color' => '#000000',
                    'sort_order' => 5,
                ],
                [
                    'instruction_en' => 'Upload the transaction screenshot to complete your order',
                    'instruction_ar' => 'قم برفع صورة العملية لإتمام طلبك',
                    'font_size' => 14,
                    'is_bold' => true,
                    'color' => '#FF6B00',
                    'sort_order' => 6,
                ],
            ];

            foreach ($instapayInstructions as $instruction) {
                $instapay->instructions()->create($instruction);
            }

            // Update to require transaction screenshot
            $instapay->update(['required_transaction_screenshot' => true]);
        }
    }
}

