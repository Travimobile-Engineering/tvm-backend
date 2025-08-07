<?php

namespace App\Enum;

enum TransactionTitle: string
{
    case TOP_UP = 'Top Up';
    case WITHDRAWAL = 'Withdrawal';
    case REFERRAL = 'Referral';
    case REWARD = 'Reward';
    case PURCHASE = 'Purchase';
    case RECHARGE = 'Recharge';
    case PAYMENT = 'Payment';
    case REFUND = 'Refund';
    case CREDIT = 'Credit';
    case DEBIT = 'Debit';
    case COMMISSION = 'Commission';
    case OTHER = 'Other';
    case PREMIUM_HIRE = 'Premium Hire';
    case PREMIUM_HIRE_REFUND = 'Premium Hire Refund';
    case TRIP_BOOKING = 'Trip Booking';
    case CHARGE_WALLET = 'Charge Wallet';
    case CREDIT_WALLET = 'Credit Wallet';
    case AGENT_COMMISSION = 'Agent Commission';
    case DRIVER_CHARGE = 'Driver Charge';
    case TRANSFER_WALLET = 'Transfer from wallet';
    case SMS_CHARGE = 'SMS Charge';
    case ADMIN_CHARGE = 'Admin Charge';
    case VAT_CHARGE = 'VAT Charge';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getKeys(): array
    {
        return array_column(self::cases(), 'name');
    }
}
