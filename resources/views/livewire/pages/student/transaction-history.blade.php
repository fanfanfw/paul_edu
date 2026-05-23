<?php

use App\Services\WalletService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function with(WalletService $walletService): array
    {
        $wallet = $walletService->getOrCreateUserWallet(auth()->user());

        return [
            'wallet' => $wallet,
            'transactions' => $wallet->transactions()->latest()->get(),
        ];
    }
}; ?>

<div class="py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Ledger</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">Riwayat transaksi saldo</h1>
            <p class="mt-3 text-slate-600">Menampilkan transaksi wallet akun Anda saja.</p>
        </div>

        <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Direction</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Before</th>
                            <th class="px-4 py-3">After</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3">Reference</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td class="px-4 py-3 text-slate-600">{{ $transaction->created_at->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $transaction->type->value }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $transaction->direction->value }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-900">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-slate-600">Rp {{ number_format($transaction->balance_before, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-slate-600">Rp {{ number_format($transaction->balance_after, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $transaction->description }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ class_basename($transaction->reference_type) }} #{{ $transaction->reference_id }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-slate-500">Belum ada transaksi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
