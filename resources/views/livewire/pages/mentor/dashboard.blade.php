<?php

use App\Models\Course;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function with(): array
    {
        return [
            'courseCount' => Course::where('mentor_id', auth()->id())->count(),
            'publishedCount' => Course::where('mentor_id', auth()->id())->where('status', 'published')->count(),
        ];
    }
}; ?>

<div class="py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Mentor Area</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Dashboard mentor</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-600">Kelola fondasi kelas Anda. Fitur materi, penjualan, wallet, dan laporan akan tersedia di tahap berikutnya.</p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">Total kelas</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $courseCount }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">Published</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $publishedCount }}</p>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('mentor.courses.index') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" wire:navigate>Kelola kelas</a>
                <a href="{{ route('mentor.courses.create') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" wire:navigate>Buat kelas</a>
            </div>
        </div>
    </div>
</div>
