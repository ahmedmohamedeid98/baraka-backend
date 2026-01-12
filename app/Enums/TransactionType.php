<?php

namespace App\Enums;

enum TransactionType: string
{
    case CHARGE = 'charge';
    case SUBSCRIPTION = 'subscription';
    case GIFT = 'gift';
    case COMMISSION = 'commission';
    case REFUND = 'refund';
    case ORDER_PAYMENT = 'order_payment';
    case TRANSFER = 'transfer';

    public function label(): string
    {
        return match($this) {
            self::CHARGE => 'شحن رصيد',
            self::SUBSCRIPTION => 'اشتراك',
            self::GIFT => 'هدية',
            self::COMMISSION => 'عمولة',
            self::REFUND => 'استرداد',
            self::ORDER_PAYMENT => 'دفع طلب',
            self::TRANSFER => 'تحويل',
        };
    }

    public function isCredit(): bool
    {
        return in_array($this, [
            self::CHARGE,
            self::GIFT,
            self::REFUND,
            self::TRANSFER,
        ]);
    }

    public function isDebit(): bool
    {
        return in_array($this, [
            self::SUBSCRIPTION,
            self::COMMISSION,
            self::ORDER_PAYMENT,
        ]);
    }
}
