<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (auth()->user()->hasRole('admin'))
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-wide text-amber-600">Admin</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Monitoring platform via Filament</h3>
                    <p class="mt-2 max-w-2xl text-sm text-slate-600">Kelola kategori, pantau course, order, wallet, ledger, review, dan pengaturan komisi dari admin panel. Area buyer seperti wallet dan my courses sengaja tidak ditampilkan untuk admin.</p>
                    <a href="{{ url('/admin') }}" class="mt-5 inline-flex rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Buka Admin Panel</a>
                </div>
            @else
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
                    @if (auth()->user()->hasRole('mentor'))
                        <a href="{{ route('mentor.dashboard') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-200">
                            <p class="font-semibold text-slate-900">Mentor Dashboard</p>
                            <p class="mt-1 text-sm text-slate-500">Ringkasan aktivitas mentor.</p>
                        </a>
                        <a href="{{ route('mentor.courses.index') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-200">
                            <p class="font-semibold text-slate-900">Kelas Mentor</p>
                            <p class="mt-1 text-sm text-slate-500">Kelola kelas dan materi.</p>
                        </a>
                        <a href="{{ route('mentor.wallet') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-200">
                            <p class="font-semibold text-slate-900">Mentor Wallet</p>
                            <p class="mt-1 text-sm text-slate-500">Lihat income kelas.</p>
                        </a>
                        <a href="{{ route('mentor.sales') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-200">
                            <p class="font-semibold text-slate-900">Sales</p>
                            <p class="mt-1 text-sm text-slate-500">Order kelas mentor.</p>
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
