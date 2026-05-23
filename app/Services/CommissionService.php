<?php

namespace App\Services;

use App\Models\PlatformSetting;
use InvalidArgumentException;

class CommissionService
{
    public function getCurrentMentorCommissionRate(): int
    {
        $setting = PlatformSetting::where('key', 'mentor_commission_rate')->first();

        if (! $setting) {
            return 60;
        }

        return max(0, min(100, (int) $setting->value));
    }

    public function setMentorCommissionRate(int $rate): PlatformSetting
    {
        if ($rate < 0 || $rate > 100) {
            throw new InvalidArgumentException('Commission rate must be between 0 and 100.');
        }

        return PlatformSetting::updateOrCreate(
            ['key' => 'mentor_commission_rate'],
            [
                'value' => (string) $rate,
                'type' => 'integer',
                'description' => 'Default mentor commission percentage',
            ]
        );
    }

    public function calculateSplit(int $price, int $commissionRate): array
    {
        if ($price < 0) {
            throw new InvalidArgumentException('Price must not be negative.');
        }

        if ($commissionRate < 0 || $commissionRate > 100) {
            throw new InvalidArgumentException('Commission rate must be between 0 and 100.');
        }

        $mentorAmount = (int) floor($price * $commissionRate / 100);

        return [
            'mentor_amount' => $mentorAmount,
            'platform_amount' => $price - $mentorAmount,
        ];
    }
}
