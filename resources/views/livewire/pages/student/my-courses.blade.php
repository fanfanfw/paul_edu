<?php

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function with(): array
    {
        return [
            'enrollments' => Enrollment::with(['course.mentor', 'course.category'])
                ->where('user_id', auth()->id())
                ->where('status', EnrollmentStatus::Active)
                ->latest('enrolled_at')
                ->get(),
        ];
    }

    public function canContinue(CourseStatus $status): bool
    {
        return in_array($status, [CourseStatus::Published, CourseStatus::Archived], true);
    }
}; ?>

<div class="py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Learning</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">Kelas saya</h1>
            <p class="mt-3 text-slate-600">Daftar kelas yang sudah Anda enroll.</p>
        </div>

        @if ($enrollments->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
                <p class="font-semibold text-slate-900">Belum ada kelas.</p>
                <a href="{{ route('courses.index') }}" class="mt-4 inline-flex rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" wire:navigate>Jelajahi katalog</a>
            </div>
        @else
            <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($enrollments as $enrollment)
                    @php
                        $course = $enrollment->course;
                        $canContinue = $course && $this->canContinue($course->status);
                    @endphp

                    <article @class([
                        'rounded-2xl border p-5 shadow-sm',
                        'border-slate-200 bg-white' => $canContinue,
                        'border-rose-200 bg-rose-50' => $course?->status === CourseStatus::DeletedByMentor,
                        'border-amber-200 bg-amber-50' => $course?->status === CourseStatus::HiddenByAdmin,
                    ])>
                        <div class="flex items-start justify-between gap-3">
                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ $course?->category?->name }}</span>
                            @if ($course?->status === CourseStatus::Archived)
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">Diarsipkan</span>
                            @elseif ($course?->status === CourseStatus::DeletedByMentor)
                                <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">Kelas ini dihapus oleh mentor</span>
                            @elseif ($course?->status === CourseStatus::HiddenByAdmin)
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Tidak tersedia</span>
                            @endif
                        </div>

                        <h2 class="mt-4 text-lg font-bold text-slate-900">{{ $course?->title }}</h2>
                        <p class="mt-2 text-sm text-slate-600">Mentor: {{ $course?->mentor?->name }}</p>

                        @if ($course?->status === CourseStatus::DeletedByMentor)
                            <p class="mt-4 text-sm text-rose-700">Materi kelas ini tidak lagi tersedia karena dihapus oleh mentor.</p>
                            <button type="button" disabled class="mt-5 w-full rounded-xl bg-rose-100 px-4 py-3 text-sm font-semibold text-rose-500">Tidak tersedia</button>
                        @elseif ($course?->status === CourseStatus::HiddenByAdmin)
                            <p class="mt-4 text-sm text-amber-700">Kelas ini sementara tidak tersedia.</p>
                            <button type="button" disabled class="mt-5 w-full rounded-xl bg-amber-100 px-4 py-3 text-sm font-semibold text-amber-600">Tidak tersedia</button>
                        @else
                            <a href="{{ route('student.learn', $course) }}" class="mt-5 block rounded-xl bg-indigo-600 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-700" wire:navigate>Lanjut Belajar</a>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>
