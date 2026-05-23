<?php

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\CourseCategory;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.public')] class extends Component
{
    public string $search = '';
    public string $category = '';
    public string $sort = 'latest';

    public function with(): array
    {
        $courses = Course::with(['mentor', 'category'])
            ->withAvg(['reviews as published_reviews_avg_rating' => fn ($query) => $query->where('is_published', true)], 'rating')
            ->withCount(['reviews as published_reviews_count' => fn ($query) => $query->where('is_published', true)])
            ->where('status', CourseStatus::Published)
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->category !== '', fn ($query) => $query->where('category_id', $this->category))
            ->when($this->sort === 'price_low', fn ($query) => $query->orderBy('price'))
            ->when($this->sort === 'price_high', fn ($query) => $query->orderByDesc('price'))
            ->when($this->sort === 'latest', fn ($query) => $query->latest('published_at')->latest())
            ->get();

        return [
            'courses' => $courses,
            'categories' => CourseCategory::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top_left,oklch(0.94_0.04_250),transparent_34rem),linear-gradient(180deg,oklch(0.99_0.006_90),oklch(0.96_0.01_250))]">
    <header class="border-b border-slate-200/70 bg-stone-50/85 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="group inline-flex items-center gap-3 text-sm font-extrabold tracking-tight text-slate-950" wire:navigate>
                <span class="grid size-9 place-items-center rounded-2xl bg-slate-950 text-sm font-black text-white shadow-sm transition group-hover:-rotate-3">S</span>
                <span>Skill Digital</span>
            </a>
            <div class="flex items-center gap-2 text-sm sm:gap-4">
                <a href="{{ url('/') }}" class="hidden font-semibold text-slate-600 transition hover:text-slate-950 sm:inline-flex" wire:navigate>Beranda</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="font-semibold text-slate-600 transition hover:text-slate-950" wire:navigate>Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="font-semibold text-slate-600 transition hover:text-slate-950" wire:navigate>Login</a>
                    <a href="{{ route('register') }}" class="rounded-full bg-slate-950 px-4 py-2 font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800" wire:navigate>Daftar</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <section class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-end">
            <div class="max-w-4xl">
                <p class="text-xs font-black uppercase tracking-[0.28em] text-indigo-700">Katalog kelas</p>
                <h1 class="mt-4 max-w-3xl text-4xl font-black tracking-tight text-slate-950 sm:text-5xl lg:text-6xl">Pilih skill digital yang siap dipraktikkan.</h1>
                <p class="mt-5 max-w-2xl text-base leading-8 text-slate-650 sm:text-lg">Cari kelas published, bandingkan mentor dan rating peserta, lalu lanjut enroll gratis atau beli kelas memakai wallet dummy MVP.</p>
            </div>
            <div class="rounded-[2rem] border border-slate-200 bg-white/80 p-5 shadow-[0_24px_70px_rgba(15,23,42,0.08)] backdrop-blur">
                <p class="text-sm font-bold text-slate-500">Tersedia sekarang</p>
                <div class="mt-3 flex items-end gap-3">
                    <span class="text-5xl font-black tracking-tight text-slate-950">{{ $courses->count() }}</span>
                    <span class="pb-2 text-sm font-semibold text-slate-500">kelas published</span>
                </div>
                <p class="mt-4 text-sm leading-6 text-slate-600">Filter di bawah akan memperbarui hasil otomatis tanpa reload penuh.</p>
            </div>
        </section>

        <div class="mt-10 grid gap-4 rounded-[1.75rem] border border-slate-200 bg-white/90 p-4 shadow-[0_20px_60px_rgba(15,23,42,0.08)] backdrop-blur md:grid-cols-[minmax(220px,1fr)_220px_200px]">
            <div>
                <label for="search" class="text-sm font-bold text-slate-800">Cari judul</label>
                <input wire:model.live.debounce.300ms="search" id="search" type="search" class="mt-2 block w-full rounded-2xl border-slate-200 bg-stone-50 px-4 py-3 text-sm shadow-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-indigo-500" placeholder="Laravel, UI/UX...">
            </div>
            <div>
                <label for="category" class="text-sm font-bold text-slate-800">Kategori</label>
                <select wire:model.live="category" id="category" class="mt-2 block w-full rounded-2xl border-slate-200 bg-stone-50 px-4 py-3 text-sm shadow-none transition focus:border-indigo-500 focus:bg-white focus:ring-indigo-500">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="sort" class="text-sm font-bold text-slate-800">Urutkan</label>
                <select wire:model.live="sort" id="sort" class="mt-2 block w-full rounded-2xl border-slate-200 bg-stone-50 px-4 py-3 text-sm shadow-none transition focus:border-indigo-500 focus:bg-white focus:ring-indigo-500">
                    <option value="latest">Terbaru</option>
                    <option value="price_low">Harga rendah</option>
                    <option value="price_high">Harga tinggi</option>
                </select>
            </div>
        </div>

        @if ($courses->isEmpty())
            <div class="mt-8 rounded-[2rem] border border-dashed border-slate-300 bg-white/85 p-10 text-center shadow-sm">
                <p class="text-lg font-black text-slate-950">Belum ada kelas yang cocok.</p>
                <p class="mt-2 text-sm text-slate-500">Coba ubah kata kunci, kategori, atau jalankan seed demo courses.</p>
            </div>
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($courses as $course)
                    <a href="{{ route('courses.show', $course) }}" class="group flex min-h-[420px] flex-col overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.07)] transition duration-200 hover:-translate-y-1 hover:border-slate-300 hover:shadow-[0_26px_70px_rgba(15,23,42,0.12)]" wire:navigate>
                        <div class="relative h-48 overflow-hidden bg-[linear-gradient(135deg,oklch(0.91_0.06_254),oklch(0.96_0.04_145))]">
                            <div class="absolute left-5 top-5 rounded-full bg-white/85 px-3 py-1 text-xs font-black text-slate-700 shadow-sm">{{ $course->category?->name }}</div>
                            <div class="absolute -bottom-10 -right-8 size-36 rounded-full bg-white/45"></div>
                            <div class="absolute bottom-5 left-5 right-5">
                                <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-500">Skill track</p>
                                <p class="mt-2 max-w-[16rem] text-2xl font-black leading-tight tracking-tight text-slate-950">{{ \Illuminate\Support\Str::limit($course->title, 46) }}</p>
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col p-5 sm:p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Mentor</p>
                                    <p class="mt-1 text-sm font-bold text-slate-700">{{ $course->mentor?->name }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-slate-950 px-3 py-1.5 text-xs font-black text-white">{{ $course->price > 0 ? 'Rp '.number_format($course->price, 0, ',', '.') : 'Gratis' }}</span>
                            </div>
                            <h2 class="mt-5 text-xl font-black leading-snug text-slate-950 transition group-hover:text-indigo-700">{{ $course->title }}</h2>
                            <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600">{{ $course->short_description }}</p>
                            <div class="mt-auto flex flex-wrap items-center justify-between gap-3 pt-6 text-sm">
                                @if ($course->published_reviews_count > 0)
                                    <span class="rounded-full bg-amber-100 px-3 py-1.5 text-xs font-black text-amber-800">{{ number_format($course->published_reviews_avg_rating, 1) }}/5 · {{ $course->published_reviews_count }} review</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-600">Belum ada rating</span>
                                @endif
                                <span class="font-black text-indigo-700 transition group-hover:translate-x-1">Lihat kelas</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </main>
</div>
