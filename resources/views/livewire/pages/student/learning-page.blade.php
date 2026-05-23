<?php

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\MaterialType;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Services\CourseReviewService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Course $course;
    public ?int $selectedMaterialId = null;
    public int $reviewRating = 5;
    public string $reviewComment = '';

    public function mount(Course $course): void
    {
        abort_unless($this->canAccessCourse($course), 403);

        $this->course = $course;

        $this->selectedMaterialId = $course->materials()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');

        $review = CourseReview::where('user_id', auth()->id())->where('course_id', $course->id)->first();

        if ($review) {
            $this->reviewRating = $review->rating;
            $this->reviewComment = $review->comment;
        }
    }

    public function selectMaterial(int $materialId): void
    {
        abort_unless(CourseMaterial::where('course_id', $this->course->id)->where('status', 'active')->whereKey($materialId)->exists(), 404);

        $this->selectedMaterialId = $materialId;
    }

    public function saveReview(CourseReviewService $reviewService): void
    {
        $validated = $this->validate([
            'reviewRating' => ['required', 'integer', 'min:1', 'max:5'],
            'reviewComment' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $review = CourseReview::where('user_id', auth()->id())->where('course_id', $this->course->id)->first();

        try {
            if ($review) {
                $reviewService->update(auth()->user(), $review, (int) $validated['reviewRating'], $validated['reviewComment']);
            } else {
                $reviewService->create(auth()->user(), $this->course->fresh(), (int) $validated['reviewRating'], $validated['reviewComment']);
            }
        } catch (ValidationException $exception) {
            $this->addError('review', collect($exception->errors())->flatten()->first() ?: 'Review gagal disimpan.');

            return;
        }

        session()->flash('review_status', 'Review berhasil disimpan.');
    }

    public function with(): array
    {
        $course = $this->course->fresh([
            'sections' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'sections.lessons' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'sections.lessons.materials' => fn ($query) => $query->where('status', 'active')->orderBy('sort_order')->orderBy('id'),
            'lessons' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'lessons.materials' => fn ($query) => $query->where('status', 'active')->orderBy('sort_order')->orderBy('id'),
        ]);

        return [
            'courseWithContent' => $course,
            'selectedMaterial' => $this->selectedMaterialId ? CourseMaterial::where('course_id', $course->id)->where('status', 'active')->find($this->selectedMaterialId) : null,
            'currentReview' => CourseReview::where('user_id', auth()->id())->where('course_id', $course->id)->first(),
        ];
    }

    private function canAccessCourse(Course $course): bool
    {
        if (! in_array($course->status, [CourseStatus::Published, CourseStatus::Archived], true)) {
            return false;
        }

        return Enrollment::where('user_id', auth()->id())
            ->where('course_id', $course->id)
            ->where('status', EnrollmentStatus::Active)
            ->exists();
    }
}; ?>

<div class="py-8">
    <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[320px_1fr] lg:px-8">
        <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-6">
            <a href="{{ route('student.courses') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700" wire:navigate>Kembali ke kelas saya</a>
            <h1 class="mt-3 text-xl font-bold text-slate-900">{{ $courseWithContent->title }}</h1>
            @if ($courseWithContent->status === CourseStatus::Archived)
                <span class="mt-3 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">Diarsipkan</span>
            @endif

            <div class="mt-6 space-y-5">
                @forelse ($courseWithContent->sections as $section)
                    <section>
                        <h2 class="text-sm font-bold text-slate-900">{{ $section->title }}</h2>
                        <div class="mt-3 space-y-3">
                            @forelse ($section->lessons as $lesson)
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="text-sm font-semibold text-slate-800">{{ $lesson->title }}</p>
                                    <div class="mt-2 space-y-2">
                                        @forelse ($lesson->materials as $material)
                                            <button type="button" wire:click="selectMaterial({{ $material->id }})" @class([
                                                'block w-full rounded-lg px-3 py-2 text-left text-sm',
                                                'bg-indigo-600 font-semibold text-white' => $selectedMaterial?->id === $material->id,
                                                'bg-white text-slate-700 hover:bg-indigo-50' => $selectedMaterial?->id !== $material->id,
                                            ])>
                                                {{ $material->title }}
                                            </button>
                                        @empty
                                            <p class="text-xs text-slate-500">Belum ada material aktif.</p>
                                        @endforelse
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">Belum ada lesson.</p>
                            @endforelse
                        </div>
                    </section>
                @empty
                    <p class="text-sm text-slate-500">Belum ada section.</p>
                @endforelse
            </div>
        </aside>

        <main class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @if ($selectedMaterial)
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">{{ strtoupper($selectedMaterial->type->value) }}</p>
                        <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ $selectedMaterial->title }}</h2>
                        @if ($selectedMaterial->description)
                            <p class="mt-2 text-sm text-slate-600">{{ $selectedMaterial->description }}</p>
                        @endif
                    </div>
                    <a href="{{ route('materials.view', $selectedMaterial) }}" target="_blank" class="rounded-xl border border-indigo-200 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Buka protected viewer</a>
                </div>

                <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-slate-950">
                    @if ($selectedMaterial->type === MaterialType::Pdf)
                        <iframe src="{{ route('materials.view', $selectedMaterial) }}" class="h-[640px] w-full bg-white" title="{{ $selectedMaterial->title }}"></iframe>
                    @else
                        <video src="{{ route('materials.view', $selectedMaterial) }}" controls controlslist="nodownload" class="max-h-[640px] w-full bg-black"></video>
                    @endif
                </div>

                <p class="mt-4 text-xs text-slate-500">Materi hanya untuk pembelajaran di platform. Route file dilindungi dan tidak memakai public storage URL.</p>
            @else
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
                    <h2 class="font-semibold text-slate-900">Belum ada material aktif</h2>
                    <p class="mt-2 text-sm text-slate-500">Kurikulum dapat dilihat di sidebar. Viewer akan muncul ketika material tersedia.</p>
                </div>
            @endif
        </main>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-start-2">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Review kelas</h2>
                    <p class="mt-1 text-sm text-slate-600">Bagikan pengalaman belajar Anda untuk membantu calon peserta lain.</p>
                </div>
                @if ($currentReview)
                    <span class="w-fit rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Sudah direview</span>
                @endif
            </div>

            @if (session('review_status'))
                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700">{{ session('review_status') }}</div>
            @endif

            @if ($currentReview)
                <div class="mt-5 rounded-xl bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">Review Anda saat ini: {{ $currentReview->rating }}/5</p>
                    <p class="mt-2 text-sm text-slate-600">{{ $currentReview->comment }}</p>
                    @if ($currentReview->edited_at)
                        <p class="mt-2 text-xs text-slate-500">Diedit {{ $currentReview->edited_at->format('d M Y H:i') }}</p>
                    @endif
                </div>
            @endif

            <form wire:submit="saveReview" class="mt-5 space-y-4">
                <div>
                    <x-input-label for="reviewRating" value="Rating" />
                    <select wire:model="reviewRating" id="reviewRating" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ([5, 4, 3, 2, 1] as $rating)
                            <option value="{{ $rating }}">{{ $rating }} / 5</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('reviewRating')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="reviewComment" value="Komentar" />
                    <textarea wire:model="reviewComment" id="reviewComment" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Minimal 10 karakter"></textarea>
                    <x-input-error :messages="$errors->get('reviewComment')" class="mt-2" />
                    <x-input-error :messages="$errors->get('review')" class="mt-2" />
                </div>
                <x-primary-button>{{ $currentReview ? 'Update review' : 'Kirim review' }}</x-primary-button>
            </form>
        </section>
    </div>
</div>
