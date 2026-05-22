<?php

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Services\CourseStatusService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?Course $course = null;
    public string $title = '';
    public ?int $category_id = null;
    public string $short_description = '';
    public string $description = '';
    public int $price = 0;
    public string $thumbnail_path = '';
    public string $status = 'draft';

    public function mount(?Course $course = null): void
    {
        if ($course?->exists) {
            $this->authorize('update', $course);

            abort_unless($course->mentor_id === auth()->id(), 403);

            $this->course = $course;
            $this->title = $course->title;
            $this->category_id = $course->category_id;
            $this->short_description = (string) $course->short_description;
            $this->description = (string) $course->description;
            $this->price = (int) $course->price;
            $this->thumbnail_path = (string) $course->thumbnail_path;
            $this->status = $course->status->value;

            return;
        }

        $this->authorize('create', Course::class);
    }

    public function save(CourseStatusService $statusService): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', Rule::exists('course_categories', 'id')->where('is_active', true)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'thumbnail_path' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in([CourseStatus::Draft->value, CourseStatus::Published->value])],
        ]);

        $attributes = [
            'mentor_id' => auth()->id(),
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'slug' => $this->uniqueSlug($validated['title'], $this->course?->id),
            'short_description' => $validated['short_description'] ?: null,
            'description' => $validated['description'] ?: null,
            'price' => $validated['price'],
            'thumbnail_path' => $validated['thumbnail_path'] ?: null,
            'status' => CourseStatus::Draft,
        ];

        if ($this->course) {
            $this->authorize('update', $this->course);
            $this->course->update($attributes);
            $course = $this->course->refresh();
        } else {
            $course = Course::create($attributes);
            $this->course = $course;
        }

        if ($validated['status'] === CourseStatus::Published->value) {
            $statusService->publish($course);
        }

        $this->redirect(route('mentor.courses.index'), navigate: true);
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title) ?: 'course';
        $slug = $baseSlug;
        $suffix = 2;

        while (Course::where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public function with(): array
    {
        return [
            'categories' => CourseCategory::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="py-10">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">{{ $course ? 'Edit kelas' : 'Buat kelas' }}</h1>
            <p class="mt-1 text-sm text-slate-600">Publish bebas tanpa approval admin. Materi dan pembelian akan ditambahkan di tahap berikutnya.</p>

            <form wire:submit="save" class="mt-6 space-y-5">
                <div>
                    <x-input-label for="title" value="Judul" />
                    <x-text-input wire:model="title" id="title" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="category_id" value="Kategori" />
                    <select wire:model="category_id" id="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        <option value="">Pilih kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="short_description" value="Deskripsi singkat" />
                    <textarea wire:model="short_description" id="short_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    <x-input-error :messages="$errors->get('short_description')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" value="Deskripsi lengkap" />
                    <textarea wire:model="description" id="description" rows="6" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="price" value="Harga" />
                        <x-text-input wire:model="price" id="price" type="number" min="0" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('price')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="status" value="Status" />
                        <select wire:model="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="thumbnail_path" value="Thumbnail path opsional" />
                    <x-text-input wire:model="thumbnail_path" id="thumbnail_path" class="mt-1 block w-full" placeholder="course-thumbnails/example.jpg" />
                    <p class="mt-1 text-xs text-slate-500">Upload thumbnail sebenarnya belum dibuat pada tahap ini.</p>
                    <x-input-error :messages="$errors->get('thumbnail_path')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('mentor.courses.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" wire:navigate>Batal</a>
                    <x-primary-button>Simpan</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
