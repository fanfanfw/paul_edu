<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingSeeder extends Seeder
{
    /**
     * Seed platform settings.
     */
    public function run(): void
    {
        PlatformSetting::updateOrCreate(
            ['key' => 'mentor_commission_rate'],
            [
                'value' => '60',
                'type' => 'integer',
                'description' => 'Default mentor commission percentage',
            ]
        );
    }
}
