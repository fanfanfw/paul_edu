<?php

use App\Enums\WalletTransactionType;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public int $amount = 50000;

    public function topup(WalletService $walletService): void
    {
        $validated = $this->validate([
            'amount' => ['required', 'integer', 'min:10000', 'max:10000000'],
        ]);

        DB::transaction(function () use ($walletService, $validated): void {
            $wallet = $walletService->getOrCreateUserWallet(auth()->user());

            $walletService->credit(
                $wallet,
                (int) $validated['amount'],
                WalletTransactionType::Topup,
                null,
                'Dummy topup saldo',
                ['source' => 'dummy_topup']
            );
        });

        session()->flash('status', 'Topup dummy berhasil.');
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function with(WalletService $walletService): array
    {
        $wallet = $walletService->getOrCreateUserWallet(auth()->user());

        return [
            'wallet' => $wallet->refresh(),
            'transactions' => $wallet->transactions()->latest()->limit(10)->get(),
        ];
    }
}; ?>

<div class="py-10">
    <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Wallet</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">Saldo saya</h1>
            <p class="mt-3 text-slate-600">Topup ini masih dummy untuk kebutuhan MVP. Tidak ada transaksi bank/payment gateway.</p>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500">Saldo saat ini</p>
                <p class="mt-2 text-4xl font-bold text-slate-900">Rp {{ number_format($wallet->balance, 0, ',', '.') }}</p>

                <form wire:submit="topup" class="mt-8 space-y-4">
                    <div>
                        <x-input-label for="amount" value="Nominal topup" />
                        <x-text-input wire:model="amount" id="amount" type="number" min="10000" max="10000000" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ([50000, 100000, 250000, 500000] as $quickAmount)
                            <button type="button" wire:click="setAmount({{ $quickAmount }})" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Rp {{ number_format($quickAmount, 0, ',', '.') }}</button>
                        @endforeach
                    </div>
                    <x-primary-button>Konfirmasi topup dummy</x-primary-button>
                </form>
            </section>

            <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold text-slate-900">Riwayat terbaru</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($transactions as $transaction)
                        <div class="rounded-xl bg-slate-50 p-3">
                            <div class="flex justify-between gap-3 text-sm">
                                <span class="font-medium text-slate-800">{{ $transaction->type->value }}</span>
                                <span class="font-semibold {{ $transaction->direction->value === 'credit' ? 'text-emerald-700' : 'text-rose-700' }}">{{ $transaction->direction->value === 'credit' ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ $transaction->created_at->format('d M Y H:i') }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada transaksi.</p>
                    @endforelse
                </div>
            </aside>
        </div>
    </div>
</div>
