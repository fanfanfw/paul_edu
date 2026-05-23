<?php

namespace App\Filament\Widgets;

use App\Enums\CourseStatus;
use App\Enums\WalletTransactionType;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $platformWallet = Wallet::where('owner_type', 'platform')->where('owner_id', 0)->first();

        return [
            Stat::make('Total users', User::count()),
            Stat::make('Total mentors', User::role('mentor')->count()),
            Stat::make('Published courses', Course::where('status', CourseStatus::Published)->count()),
            Stat::make('Total orders', Order::count()),
            Stat::make('Dummy topup', 'Rp '.number_format((int) WalletTransaction::where('type', WalletTransactionType::Topup)->sum('amount'), 0, ',', '.')),
            Stat::make('Platform revenue', 'Rp '.number_format((int) WalletTransaction::where('type', WalletTransactionType::PlatformFee)->sum('amount'), 0, ',', '.')),
            Stat::make('Platform balance', 'Rp '.number_format((int) ($platformWallet?->balance ?? 0), 0, ',', '.')),
        ];
    }
}
