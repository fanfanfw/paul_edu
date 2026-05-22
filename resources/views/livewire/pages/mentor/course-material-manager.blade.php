<?php

use App\Enums\CourseStatus;
use App\Enums\MaterialType;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseMaterial;
use App\Models\CourseSection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public Course $course;

    public string $sectionTitle = '';
    public string $sectionDescription = '';
    public int $sectionSortOrder = 0;

    public ?int $lessonSectionId = null;
    public string $lessonTitle = '';
    public string $lessonDescription = '';
    public int $lessonSortOrder = 0;
    public bool $lessonIsPreview = false;

    public ?int $materialLessonId = null;
    public string $materialTitle = '';
    public string $materialDescription = '';
    public string $materialType = 'pdf';
    public int $materialSortOrder = 0;
    public $materialFile = null;

    public array $replacementFiles = [];

    public function mount(Course $course): void
    {
        abort_unless($course->mentor_id === auth()->id(), 403);

        $this->course = $course;
    }

    public function addSection(): void
    {
        $this->ensureCourseCanBeModified();

        $validated = $this->validate([
            'sectionTitle' => ['required', 'string', 'max:255'],
            'sectionDescription' => ['nullable', 'string'],
            'sectionSortOrder' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->course->sections()->create([
            'title' => $validated['sectionTitle'],
            'description' => $validated['sectionDescription'] ?: null,
            'sort_order' => $validated['sectionSortOrder'] ?? 0,
        ]);

        $this->reset('sectionTitle', 'sectionDescription', 'sectionSortOrder');
    }

    public function addLesson(): void
    {
        $this->ensureCourseCanBeModified();

        $validated = $this->validate([
            'lessonSectionId' => [
                'nullable',
                Rule::exists('course_sections', 'id')->where('course_id', $this->course->id),
            ],
            'lessonTitle' => ['required', 'string', 'max:255'],
            'lessonDescription' => ['nullable', 'string'],
            'lessonSortOrder' => ['nullable', 'integer', 'min:0'],
            'lessonIsPreview' => ['boolean'],
        ]);

        $this->course->lessons()->create([
            'section_id' => $validated['lessonSectionId'],
            'title' => $validated['lessonTitle'],
            'description' => $validated['lessonDescription'] ?: null,
            'sort_order' => $validated['lessonSortOrder'] ?? 0,
            'is_preview' => $validated['lessonIsPreview'],
        ]);

        $this->reset('lessonSectionId', 'lessonTitle', 'lessonDescription', 'lessonSortOrder', 'lessonIsPreview');
    }

    public function uploadMaterial(): void
    {
        $this->ensureCourseCanBeModified();

        $validated = $this->validate(array_merge($this->materialRules('materialFile'), [
            'materialLessonId' => [
                'required',
                Rule::exists('course_lessons', 'id')->where('course_id', $this->course->id),
            ],
            'materialTitle' => ['required', 'string', 'max:255'],
            'materialDescription' => ['nullable', 'string'],
            'materialType' => ['required', Rule::enum(MaterialType::class)],
            'materialSortOrder' => ['nullable', 'integer', 'min:0'],
        ]));

        /** @var TemporaryUploadedFile $file */
        $file = $validated['materialFile'];
        $path = $this->storeMaterialFile($file);

        $this->course->materials()->create([
            'lesson_id' => $validated['materialLessonId'],
            'title' => $validated['materialTitle'],
            'description' => $validated['materialDescription'] ?: null,
            'type' => $validated['materialType'],
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'sort_order' => $validated['materialSortOrder'] ?? 0,
            'status' => 'active',
        ]);

        $this->reset('materialLessonId', 'materialTitle', 'materialDescription', 'materialType', 'materialSortOrder', 'materialFile');
        $this->materialType = 'pdf';
    }

    public function replaceMaterialFile(int $materialId): void
    {
        $this->ensureCourseCanBeModified();

        $material = CourseMaterial::where('course_id', $this->course->id)->findOrFail($materialId);

        $validated = $this->validate($this->replacementRules($material));

        /** @var TemporaryUploadedFile $file */
        $file = $validated['replacementFiles'][$materialId];
        $oldPath = $material->file_path;
        $path = $this->storeMaterialFile($file);

        $material->update([
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        if ($oldPath && Storage::disk('course_materials')->exists($oldPath)) {
            Storage::disk('course_materials')->delete($oldPath);
        }

        unset($this->replacementFiles[$materialId]);
    }

    public function markMaterialDeleted(int $materialId): void
    {
        $this->ensureCourseCanBeModified();

        $material = CourseMaterial::where('course_id', $this->course->id)->findOrFail($materialId);
        $path = $material->file_path;

        $material->update(['status' => 'deleted']);
        $material->delete();

        if ($path && Storage::disk('course_materials')->exists($path)) {
            Storage::disk('course_materials')->delete($path);
        }
    }

    public function with(): array
    {
        return [
            'courseWithContent' => $this->course->fresh([
                'sections' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'sections.lessons' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'sections.lessons.materials' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'lessons' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'lessons.materials' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ]),
            'canModifyMaterials' => $this->canModifyMaterials(),
            'materialTypes' => MaterialType::cases(),
        ];
    }

    private function ensureCourseCanBeModified(): void
    {
        abort_if(! $this->canModifyMaterials(), 403);
    }

    private function canModifyMaterials(): bool
    {
        return $this->course->fresh()->status !== CourseStatus::DeletedByMentor;
    }

    private function materialRules(string $fileField): array
    {
        return match ($this->materialType) {
            MaterialType::Video->value => [$fileField => ['required', 'file', 'mimes:mp4,webm,mov', 'max:204800']],
            MaterialType::Pdf->value => [$fileField => ['required', 'file', 'mimes:pdf', 'max:20480']],
            default => [$fileField => ['required', 'file', 'mimes:pdf', 'max:20480']],
        };
    }

    private function replacementRules(CourseMaterial $material): array
    {
        $fileRules = match ($material->type) {
            MaterialType::Video => ['required', 'file', 'mimes:mp4,webm,mov', 'max:204800'],
            MaterialType::Pdf => ['required', 'file', 'mimes:pdf', 'max:20480'],
        };

        return [
            'replacementFiles.'.$material->id => $fileRules,
        ];
    }

    private function storeMaterialFile(UploadedFile $file): string
    {
        return $file->store('course-'.$this->course->id, 'course_materials');
    }
}; ?>

<div class="py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <a href="{{ route('mentor.courses.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700" wire:navigate>Kembali ke kelas</a>
                    <h1 class="mt-2 text-2xl font-bold text-slate-900">Materi: {{ $courseWithContent->title }}</h1>
                    <p class="mt-1 text-sm text-slate-600">Kelola section, lesson, dan file materi. File disimpan di private storage dan tidak punya public URL.</p>
                </div>
                <span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $courseWithContent->status->value }}</span>
            </div>

            @if (! $canModifyMaterials)
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    Kelas berstatus deleted_by_mentor. Materi ditampilkan read-only dan tidak bisa ditambah atau diganti.
                </div>
            @endif
        </div>

        @if ($canModifyMaterials)
            <div class="grid gap-6 lg:grid-cols-3">
                <form wire:submit="addSection" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-900">Tambah section</h2>
                    <div class="mt-4 space-y-4">
                        <div>
                            <x-input-label for="sectionTitle" value="Judul section" />
                            <x-text-input wire:model="sectionTitle" id="sectionTitle" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('sectionTitle')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="sectionDescription" value="Deskripsi" />
                            <textarea wire:model="sectionDescription" id="sectionDescription" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            <x-input-error :messages="$errors->get('sectionDescription')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="sectionSortOrder" value="Urutan" />
                            <x-text-input wire:model="sectionSortOrder" id="sectionSortOrder" type="number" min="0" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('sectionSortOrder')" class="mt-2" />
                        </div>
                        <x-primary-button>Tambah section</x-primary-button>
                    </div>
                </form>

                <form wire:submit="addLesson" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-900">Tambah lesson</h2>
                    <div class="mt-4 space-y-4">
                        <div>
                            <x-input-label for="lessonSectionId" value="Section" />
                            <select wire:model="lessonSectionId" id="lessonSectionId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Tanpa section</option>
                                @foreach ($courseWithContent->sections as $section)
                                    <option value="{{ $section->id }}">{{ $section->title }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('lessonSectionId')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="lessonTitle" value="Judul lesson" />
                            <x-text-input wire:model="lessonTitle" id="lessonTitle" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('lessonTitle')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="lessonDescription" value="Deskripsi" />
                            <textarea wire:model="lessonDescription" id="lessonDescription" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            <x-input-error :messages="$errors->get('lessonDescription')" class="mt-2" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="lessonSortOrder" value="Urutan" />
                                <x-text-input wire:model="lessonSortOrder" id="lessonSortOrder" type="number" min="0" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('lessonSortOrder')" class="mt-2" />
                            </div>
                            <label class="mt-6 flex items-center gap-2 text-sm text-slate-700">
                                <input wire:model="lessonIsPreview" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                Preview
                            </label>
                        </div>
                        <x-primary-button>Tambah lesson</x-primary-button>
                    </div>
                </form>

                <form wire:submit="uploadMaterial" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-900">Upload material</h2>
                    <div class="mt-4 space-y-4">
                        <div>
                            <x-input-label for="materialLessonId" value="Lesson" />
                            <select wire:model="materialLessonId" id="materialLessonId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Pilih lesson</option>
                                @foreach ($courseWithContent->lessons as $lesson)
                                    <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('materialLessonId')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="materialTitle" value="Judul material" />
                            <x-text-input wire:model="materialTitle" id="materialTitle" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('materialTitle')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="materialType" value="Tipe" />
                            <select wire:model="materialType" id="materialType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($materialTypes as $type)
                                    <option value="{{ $type->value }}">{{ strtoupper($type->value) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('materialType')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="materialFile" value="File" />
                            <input wire:model="materialFile" id="materialFile" type="file" class="mt-1 block w-full text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                            <p class="mt-1 text-xs text-slate-500">PDF max 20 MB. Video mp4/webm/mov max 200 MB.</p>
                            <x-input-error :messages="$errors->get('materialFile')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="materialDescription" value="Deskripsi" />
                            <textarea wire:model="materialDescription" id="materialDescription" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            <x-input-error :messages="$errors->get('materialDescription')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="materialSortOrder" value="Urutan" />
                            <x-text-input wire:model="materialSortOrder" id="materialSortOrder" type="number" min="0" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('materialSortOrder')" class="mt-2" />
                        </div>
                        <x-primary-button>Upload material</x-primary-button>
                    </div>
                </form>
            </div>
        @endif

        <div class="space-y-5">
            @forelse ($courseWithContent->sections as $section)
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Section {{ $section->sort_order }}</p>
                            <h2 class="text-xl font-bold text-slate-900">{{ $section->title }}</h2>
                            @if ($section->description)
                                <p class="mt-1 text-sm text-slate-600">{{ $section->description }}</p>
                            @endif
                        </div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $section->lessons->count() }} lesson</span>
                    </div>

                    <div class="mt-5 space-y-4">
                        @forelse ($section->lessons as $lesson)
                            @include('livewire.pages.mentor.partials.course-material-lesson-card', ['lesson' => $lesson, 'canModifyMaterials' => $canModifyMaterials])
                        @empty
                            <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Belum ada lesson di section ini.</p>
                        @endforelse
                    </div>
                </section>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
                    <h2 class="font-semibold text-slate-900">Belum ada section</h2>
                    <p class="mt-1 text-sm text-slate-500">Tambahkan section untuk mulai mengorganisasi lesson dan material.</p>
                </div>
            @endforelse

            @php
                $unsectionedLessons = $courseWithContent->lessons->whereNull('section_id');
            @endphp

            @if ($unsectionedLessons->isNotEmpty())
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-bold text-slate-900">Lesson tanpa section</h2>
                    <div class="mt-5 space-y-4">
                        @foreach ($unsectionedLessons as $lesson)
                            @include('livewire.pages.mentor.partials.course-material-lesson-card', ['lesson' => $lesson, 'canModifyMaterials' => $canModifyMaterials])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>
