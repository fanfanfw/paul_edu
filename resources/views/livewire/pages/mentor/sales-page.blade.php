<?php

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function with(): array
    {
        return [
            'orders' => Order::with(['user', 'course'])
                ->where('mentor_id', auth()->id())
                ->latest('paid_at')
                ->latest()
                ->get(),
        ];
    }
}; ?>

<div class="py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Sales</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">Penjualan kelas</h1>
            <p class="mt-3 text-slate-600">Daftar order untuk kelas milik Anda. Tidak ada payout pada MVP ini.</p>
        </div>

        <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Order</th>
                            <th class="px-4 py-3">Buyer</th>
                            <th class="px-4 py-3">Course</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Mentor Amount</th>
                            <th class="px-4 py-3">Platform Amount</th>
                            <th class="px-4 py-3">Paid At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($orders as $order)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $order->order_number }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $order->user?->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $order->course_title_snapshot }}</td>
                                <td class="px-4 py-3 text-slate-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-emerald-700">Rp {{ number_format($order->mentor_amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-slate-600">Rp {{ number_format($order->platform_amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $order->paid_at?->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada penjualan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
