<?php

namespace App\Enums;

enum WalletTransactionType: string
{
    case Topup = 'topup';
    case CoursePurchase = 'course_purchase';
    case CourseSaleIncome = 'course_sale_income';
    case PlatformFee = 'platform_fee';
    case Adjustment = 'adjustment';
}
