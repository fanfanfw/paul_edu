<?php

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\CoursePurchaseService;
use App\Services\FreeEnrollmentService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public Course $course;

    public function mount(Course $course): void
    {
        abort_unless($course->status === CourseStatus::Published, 404);

        $this->course = $course->load([
            'mentor',
            'category',
            'sections' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'sections.lessons' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'sections.lessons.materials' => fn ($query) => $query->where('status', 'active')->orderBy('sort_order')->orderBy('id'),
            'publishedReviews.user',
        ]);
    }

    public function enrollFree(FreeEnrollmentService $enrollmentService): void
    {
        abort_unless(auth()->check(), 403);

        $enrollmentService->enroll(auth()->user(), $this->course->fresh());

        $this->redirect(route('student.learn', $this->course), navigate: true);
    }

    public function purchase(CoursePurchaseService $purchaseService): void
    {
        abort_unless(auth()->check(), 403);

        try {
            $purchaseService->purchase(auth()->user(), $this->course->fresh());
        } catch (ValidationException $exception) {
            $this->addError('purchase', collect($exception->errors())->flatten()->first() ?: 'Pembelian gagal.');

            return;
        }

        $this->redirect(route('student.learn', $this->course), navigate: true);
    }

    public function with(): array
    {
        $enrollment = auth()->check()
            ? Enrollment::where('user_id', auth()->id())
                ->where('course_id', $this->course->id)
                ->where('status', EnrollmentStatus::Active)
                ->first()
            : null;

        $publishedReviews = $this->course->publishedReviews;

        return [
            'isEnrolled' => $enrollment !== null,
            'reviewCount' => $publishedReviews->count(),
            'averageRating' => $publishedReviews->count() > 0 ? round($publishedReviews->avg('rating'), 1) : null,
        ];
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
            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm">
                @if ($averageRating)
                    <span class="rounded-full bg-amber-50 px-3 py-1 font-semibold text-amber-700">Rating {{ number_format($averageRating, 1) }}/5</span>
                    <span class="text-slate-500">{{ $reviewCount }} review</span>
                @else
                    <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600">Belum ada rating</span>
                @endif
            </div>
            <p class="mt-6 text-base leading-7 text-slate-700">{{ $course->description ?: $course->short_description }}</p>

            <div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5">
                <h2 class="font-semibold text-slate-900">Materi kelas</h2>
                @if ($course->sections->isEmpty())
                    <p class="mt-2 text-sm text-slate-600">Kurikulum belum tersedia.</p>
                @else
                    <div class="mt-4 space-y-4">
                        @foreach ($course->sections as $section)
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <h3 class="font-semibold text-slate-900">{{ $section->title }}</h3>
                                <div class="mt-3 space-y-3">
                                    @forelse ($section->lessons as $lesson)
                                        <div>
                                            <p class="text-sm font-medium text-slate-800">{{ $lesson->title }}</p>
                                            @if ($lesson->materials->isNotEmpty())
                                                <ul class="mt-2 space-y-1 text-sm text-slate-600">
                                                    @foreach ($lesson->materials as $material)
                                                        <li>{{ $material->title }} · {{ strtoupper($material->type->value) }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    @empty
                                        <p class="text-sm text-slate-500">Belum ada lesson.</p>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-semibold text-slate-900">Review peserta</h2>
                @if ($course->publishedReviews->isEmpty())
                    <p class="mt-2 text-sm text-slate-600">Belum ada review yang dipublikasikan.</p>
                @else
                    <div class="mt-4 space-y-4">
                        @foreach ($course->publishedReviews as $review)
                            <article class="rounded-xl bg-slate-50 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <p class="font-semibold text-slate-900">{{ $review->user?->name }}</p>
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ $review->rating }}/5</span>
                                </div>
                                <p class="mt-2 text-sm text-slate-600">{{ $review->comment }}</p>
                            </article>
                        @endforeach
                    </div>
                @endif
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
                    <a href="{{ route('login') }}" class="block rounded-xl bg-indigo-600 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-700" wire:navigate>{{ $course->price > 0 ? 'Login untuk lanjut' : 'Login untuk enroll gratis' }}</a>
                    <a href="{{ route('register') }}" class="mt-3 block rounded-xl border border-slate-300 px-4 py-3 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50" wire:navigate>Daftar akun</a>
                @else
                    @if (auth()->user()->hasRole('admin'))
                        <button type="button" disabled class="w-full rounded-xl bg-slate-200 px-4 py-3 text-sm font-semibold text-slate-500">Admin tidak dapat membeli kelas</button>
                    @elseif (auth()->id() === $course->mentor_id)
                        <button type="button" disabled class="w-full rounded-xl bg-slate-200 px-4 py-3 text-sm font-semibold text-slate-500">Ini kelas Anda</button>
                    @elseif ($isEnrolled)
                        <a href="{{ route('student.learn', $course) }}" class="block rounded-xl bg-indigo-600 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-700" wire:navigate>Lanjut Belajar</a>
                    @elseif ((int) $course->price === 0)
                        <button type="button" wire:click="enrollFree" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-700">Enroll Gratis</button>
                    @else
                        <button type="button" wire:click="purchase" class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Beli Kelas</button>
                        <x-input-error :messages="$errors->get('purchase')" class="mt-3" />
                    @endif
                @endguest
            </div>
        </aside>
    </main>
</div>
