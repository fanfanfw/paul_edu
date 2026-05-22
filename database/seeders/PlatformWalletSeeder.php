<?php

namespace Database\Seeders;

use App\Models\Wallet;
use Illuminate\Database\Seeder;

class PlatformWalletSeeder extends Seeder
{
    /**
     * Seed platform wallet.
     */
    public function run(): void
    {
        Wallet::updateOrCreate(
            ['owner_type' => 'platform', 'owner_id' => 0],
            ['balance' => 0, 'currency' => 'IDR', 'status' => 'active']
        );
    }
}
