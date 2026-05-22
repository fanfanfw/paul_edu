<?php

use App\Enums\CourseStatus;
use App\Models\Course;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public Course $course;

    public function mount(Course $course): void
    {
        abort_unless($course->status === CourseStatus::Published, 404);

        $this->course = $course->load(['mentor', 'category']);
    }
}; ?>

<div class="min-h-screen bg-slate-50">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-5 sm:px-6 lg:px-8">
            <a href="{{ route('courses.index') }}" class="text-sm font-semibold text-indigo-600" wire:navigate>← Katalog</a>
            <div class="flex items-center gap-3 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="font-medium text-slate-700 hover:text-indigo-600" wire:navigate>Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="font-medium text-slate-700 hover:text-indigo-600" wire:navigate>Login</a>
                    <a href="{{ route('register') }}" class="rounded-xl bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700" wire:navigate>Daftar</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_360px] lg:px-8">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $course->category?->name }}</span>
            <h1 class="mt-4 text-3xl font-bold text-slate-900">{{ $course->title }}</h1>
            <p class="mt-3 text-sm font-medium text-slate-500">Mentor: {{ $course->mentor?->name }}</p>
            <p class="mt-6 text-base leading-7 text-slate-700">{{ $course->description ?: $course->short_description }}</p>

            <div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5">
                <h2 class="font-semibold text-slate-900">Materi kelas</h2>
                <p class="mt-2 text-sm text-slate-600">Preview kurikulum dan file materi belum tersedia pada tahap ini.</p>
            </div>
        </section>

        <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-6">
            <div class="flex h-44 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-100 via-slate-100 to-emerald-100 text-sm font-semibold text-slate-500">
                Thumbnail placeholder
            </div>
            <p class="mt-5 text-2xl font-bold text-slate-900">{{ $course->price > 0 ? 'Rp '.number_format($course->price, 0, ',', '.') : 'Gratis' }}</p>
            <p class="mt-2 text-sm text-slate-500">Status: {{ $course->status->value }}</p>

            <div class="mt-5">
                @guest
                    <a href="{{ route('login') }}" class="block rounded-xl bg-indigo-600 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-700" wire:navigate>Login untuk lanjut</a>
                    <a href="{{ route('register') }}" class="mt-3 block rounded-xl border border-slate-300 px-4 py-3 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50" wire:navigate>Daftar akun</a>
                @else
                    @if (auth()->id() === $course->mentor_id)
                        <button type="button" disabled class="w-full rounded-xl bg-slate-200 px-4 py-3 text-sm font-semibold text-slate-500">Ini kelas Anda</button>
                    @else
                        <button type="button" disabled class="w-full rounded-xl bg-slate-200 px-4 py-3 text-sm font-semibold text-slate-500">Pembelian akan tersedia di tahap berikutnya</button>
                    @endif
                @endguest
            </div>
        </aside>
    </main>
</div>
