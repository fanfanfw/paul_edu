<?php

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\CourseCategory;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
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

<div class="min-h-screen bg-slate-50">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-5 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="text-sm font-bold text-slate-900" wire:navigate>Skill Digital Platform</a>
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

    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Catalog</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">Jelajahi kelas digital</h1>
            <p class="mt-3 text-slate-600">Jelajahi kelas published, lihat rating peserta, lalu mulai belajar setelah enroll atau membeli kelas.</p>
        </div>

        <div class="mt-8 grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-3">
            <div>
                <label for="search" class="text-sm font-medium text-slate-700">Cari judul</label>
                <input wire:model.live.debounce.300ms="search" id="search" type="search" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Laravel, UI/UX...">
            </div>
            <div>
                <label for="category" class="text-sm font-medium text-slate-700">Kategori</label>
                <select wire:model.live="category" id="category" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="sort" class="text-sm font-medium text-slate-700">Urutkan</label>
                <select wire:model.live="sort" id="sort" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="latest">Terbaru</option>
                    <option value="price_low">Harga rendah</option>
                    <option value="price_high">Harga tinggi</option>
                </select>
            </div>
        </div>

        @if ($courses->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
                <p class="font-semibold text-slate-900">Belum ada kelas published.</p>
                <p class="mt-2 text-sm text-slate-500">Coba ubah filter atau seed demo courses.</p>
            </div>
        @else
            <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($courses as $course)
                    <a href="{{ route('courses.show', $course) }}" class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md" wire:navigate>
                        <div class="flex h-40 items-center justify-center bg-gradient-to-br from-indigo-100 via-slate-100 to-emerald-100 text-sm font-semibold text-slate-500">
                            {{ $course->thumbnail_path ? 'Thumbnail' : 'Thumbnail placeholder' }}
                        </div>
                        <div class="p-5">
                            <div class="flex items-center justify-between gap-3">
                                <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ $course->category?->name }}</span>
                                <span class="text-sm font-bold text-slate-900">{{ $course->price > 0 ? 'Rp '.number_format($course->price, 0, ',', '.') : 'Gratis' }}</span>
                            </div>
                            <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-indigo-700">{{ $course->title }}</h2>
                            <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ $course->short_description }}</p>
                            <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm">
                                <p class="font-medium text-slate-500">Mentor: {{ $course->mentor?->name }}</p>
                                @if ($course->published_reviews_count > 0)
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ number_format($course->published_reviews_avg_rating, 1) }}/5 · {{ $course->published_reviews_count }} review</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Belum ada rating</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </main>
</div>
