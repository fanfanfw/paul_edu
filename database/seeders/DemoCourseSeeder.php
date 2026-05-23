<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Enums\MaterialType;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseLesson;
use App\Models\CourseMaterial;
use App\Models\CourseSection;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoCourseSeeder extends Seeder
{
    /**
     * Seed published demo courses with lightweight section and lesson metadata.
     */
    public function run(): void
    {
        $mentor = User::where('email', 'mentor@example.com')->first();

        if (! $mentor) {
            return;
        }

        $courses = [
            [
                'category' => 'Web Development',
                'title' => 'Dasar HTML & CSS untuk Pemula',
                'short_description' => 'Mulai membuat halaman web modern dengan fondasi HTML dan CSS.',
                'description' => 'Kelas pengantar untuk memahami struktur HTML, styling CSS, dan praktik layout dasar.',
                'price' => 0,
            ],
            [
                'category' => 'Web Development',
                'title' => 'Laravel 12 dari Nol sampai Deploy',
                'short_description' => 'Bangun aplikasi Laravel modern dari setup awal sampai siap deploy.',
                'description' => 'Kelas Laravel praktis untuk memahami routing, model, migration, Blade, Livewire, dan deployment dasar.',
                'price' => 250000,
            ],
            [
                'category' => 'UI/UX Design',
                'title' => 'UI/UX Modern dengan Figma',
                'short_description' => 'Pelajari workflow desain interface yang rapi dan siap dikembangkan.',
                'description' => 'Kelas UI/UX yang membahas struktur layout, komponen, design system ringan, dan handoff dengan Figma.',
                'price' => 180000,
            ],
        ];

        foreach ($courses as $course) {
            $category = CourseCategory::where('name', $course['category'])->first();

            if (! $category) {
                continue;
            }

            $demoCourse = Course::updateOrCreate(
                ['slug' => Str::slug($course['title'])],
                [
                    'mentor_id' => $mentor->id,
                    'category_id' => $category->id,
                    'title' => $course['title'],
                    'short_description' => $course['short_description'],
                    'description' => $course['description'],
                    'price' => $course['price'],
                    'status' => CourseStatus::Published,
                    'published_at' => now(),
                ]
            );

            $section = CourseSection::updateOrCreate(
                ['course_id' => $demoCourse->id, 'title' => 'Mulai belajar'],
                [
                    'description' => 'Section pembuka untuk memahami alur kelas.',
                    'sort_order' => 0,
                ]
            );

            $lesson = CourseLesson::updateOrCreate(
                ['course_id' => $demoCourse->id, 'title' => 'Pengenalan kelas'],
                [
                    'section_id' => $section->id,
                    'description' => 'Lesson metadata demo tanpa file besar.',
                    'sort_order' => 0,
                    'is_preview' => true,
                ]
            );

            if ((int) $demoCourse->price === 0) {
                $path = 'demo/course-'.$demoCourse->id.'/intro.pdf';

                Storage::disk('course_materials')->put($path, "%PDF-1.4\n% Tiny demo PDF placeholder\n");

                CourseMaterial::updateOrCreate(
                    ['course_id' => $demoCourse->id, 'lesson_id' => $lesson->id, 'title' => 'Panduan demo PDF'],
                    [
                        'description' => 'File PDF kecil untuk demo protected material route.',
                        'type' => MaterialType::Pdf,
                        'file_path' => $path,
                        'original_filename' => 'intro.pdf',
                        'mime_type' => 'application/pdf',
                        'file_size' => Storage::disk('course_materials')->size($path),
                        'sort_order' => 0,
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}
