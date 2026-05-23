<?php

use App\Enums\WalletTransactionType;
use App\Services\WalletService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function with(WalletService $walletService): array
    {
        $wallet = $walletService->getOrCreateUserWallet(auth()->user());

        return [
            'wallet' => $wallet->refresh(),
            'incomeTransactions' => $wallet->transactions()
                ->where('type', WalletTransactionType::CourseSaleIncome)
                ->latest()
                ->get(),
        ];
    }
}; ?>

<div class="py-10">
    <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Mentor Wallet</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">Saldo mentor</h1>
            <p class="mt-3 text-slate-600">Pendapatan kelas masuk ke wallet akun user mentor. Tidak ada payout pada MVP ini.</p>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Saldo saat ini</p>
            <p class="mt-2 text-4xl font-bold text-slate-900">Rp {{ number_format($wallet->balance, 0, ',', '.') }}</p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-slate-900">Income dari penjualan</h2>
            <div class="mt-4 space-y-3">
                @forelse ($incomeTransactions as $transaction)
                    <div class="rounded-xl bg-slate-50 p-4">
                        <div class="flex flex-wrap justify-between gap-3">
                            <p class="font-medium text-slate-900">{{ $transaction->description }}</p>
                            <p class="font-bold text-emerald-700">+ Rp {{ number_format($transaction->amount, 0, ',', '.') }}</p>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ $transaction->created_at->format('d M Y H:i') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada income.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
