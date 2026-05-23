<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('courses.index') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-200">
                    <p class="font-semibold text-slate-900">Katalog</p>
                    <p class="mt-1 text-sm text-slate-500">Cari kelas digital.</p>
                </a>
                <a href="{{ route('student.courses') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-200">
                    <p class="font-semibold text-slate-900">Kelas saya</p>
                    <p class="mt-1 text-sm text-slate-500">Lanjut belajar.</p>
                </a>
                <a href="{{ route('wallet') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-200">
                    <p class="font-semibold text-slate-900">Wallet</p>
                    <p class="mt-1 text-sm text-slate-500">Topup dummy.</p>
                </a>
                <a href="{{ route('transactions') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-200">
                    <p class="font-semibold text-slate-900">Transaksi</p>
                    <p class="mt-1 text-sm text-slate-500">Riwayat saldo.</p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
