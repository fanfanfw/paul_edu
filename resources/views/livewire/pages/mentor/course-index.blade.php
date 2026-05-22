<?php

use App\Models\Course;
use App\Services\CourseStatusService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function markDeleted(int $courseId, CourseStatusService $statusService): void
    {
        $course = Course::where('mentor_id', auth()->id())->findOrFail($courseId);

        $this->authorize('delete', $course);

        $statusService->markDeletedByMentor($course);
    }

    public function with(): array
    {
        return [
            'courses' => Course::with('category')
                ->where('mentor_id', auth()->id())
                ->latest()
                ->get(),
        ];
    }
}; ?>

<div class="py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Kelas saya</h1>
                <p class="mt-1 text-sm text-slate-600">Kelola draft dan kelas published milik Anda.</p>
            </div>
            <a href="{{ route('mentor.courses.create') }}" class="inline-flex rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" wire:navigate>Buat kelas</a>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($courses->isEmpty())
                <div class="p-8 text-center">
                    <p class="text-sm text-slate-500">Belum ada kelas.</p>
                    <a href="{{ route('mentor.courses.create') }}" class="mt-4 inline-flex rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" wire:navigate>Buat kelas pertama</a>
                </div>
            @else
                <div class="divide-y divide-slate-200">
                    @foreach ($courses as $course)
                        <div class="grid gap-4 p-5 lg:grid-cols-[1fr_auto] lg:items-center">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="font-semibold text-slate-900">{{ $course->title }}</h2>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $course->status->value }}</span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">{{ $course->category?->name }} · {{ $course->price > 0 ? 'Rp '.number_format($course->price, 0, ',', '.') : 'Gratis' }}</p>
                                <p class="mt-2 text-sm text-slate-600">{{ $course->short_description ?: 'Belum ada deskripsi singkat.' }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('mentor.courses.edit', $course) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" wire:navigate>Edit</a>
                                @if ($course->status->value !== 'deleted_by_mentor')
                                    <button type="button" wire:click="markDeleted({{ $course->id }})" wire:confirm="Tandai kelas ini sebagai dihapus?" class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">Hapus</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
