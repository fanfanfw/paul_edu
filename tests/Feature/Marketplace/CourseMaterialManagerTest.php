<?php

namespace Tests\Feature\Marketplace;

use App\Enums\CourseStatus;
use App\Enums\MaterialType;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseLesson;
use App\Models\CourseMaterial;
use App\Models\CourseSection;
use App\Models\User;
use Database\Seeders\CourseCategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class CourseMaterialManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $mentor;
    private CourseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CourseCategorySeeder::class);

        $this->mentor = User::factory()->create();
        $this->mentor->assignRole('mentor');
        $this->category = CourseCategory::firstOrFail();
    }

    public function test_mentor_can_access_own_course_material_manager(): void
    {
        $course = $this->courseFor($this->mentor, ['title' => 'Own Material Course']);

        $this->actingAs($this->mentor)
            ->get(route('mentor.courses.materials', $course))
            ->assertOk()
            ->assertSee('Own Material Course');
    }

    public function test_mentor_cannot_access_another_mentor_course_material_manager(): void
    {
        $otherMentor = User::factory()->create();
        $otherMentor->assignRole('mentor');
        $course = $this->courseFor($otherMentor);

        $this->actingAs($this->mentor)
            ->get(route('mentor.courses.materials', $course))
            ->assertForbidden();
    }

    public function test_non_mentor_cannot_access_material_manager(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        $course = $this->courseFor($this->mentor);

        $this->actingAs($user)
            ->get(route('mentor.courses.materials', $course))
            ->assertForbidden();
    }

    public function test_mentor_can_create_section_for_own_course(): void
    {
        $course = $this->courseFor($this->mentor);

        $this->actingAs($this->mentor);

        Volt::test('pages.mentor.course-material-manager', ['course' => $course])
            ->set('sectionTitle', 'Getting Started')
            ->set('sectionDescription', 'Intro section')
            ->set('sectionSortOrder', 2)
            ->call('addSection');

        $this->assertDatabaseHas('course_sections', [
            'course_id' => $course->id,
            'title' => 'Getting Started',
            'description' => 'Intro section',
            'sort_order' => 2,
        ]);
    }

    public function test_mentor_can_create_lesson_for_own_course(): void
    {
        $course = $this->courseFor($this->mentor);
        $section = $this->sectionFor($course);

        $this->actingAs($this->mentor);
        Volt::test('pages.mentor.course-material-manager', ['course' => $course])
            ->set('lessonSectionId', $section->id)
            ->set('lessonTitle', 'Intro Lesson')
            ->set('lessonDescription', 'Lesson description')
            ->set('lessonSortOrder', 3)
            ->set('lessonIsPreview', true)
            ->call('addLesson');

        $this->assertDatabaseHas('course_lessons', [
            'course_id' => $course->id,
            'section_id' => $section->id,
            'title' => 'Intro Lesson',
            'description' => 'Lesson description',
            'sort_order' => 3,
            'is_preview' => true,
        ]);
    }

    public function test_lesson_cannot_be_created_with_section_from_another_course(): void
    {
        $course = $this->courseFor($this->mentor);
        $otherCourse = $this->courseFor($this->mentor, ['title' => 'Other Course']);
        $otherSection = $this->sectionFor($otherCourse);

        $this->actingAs($this->mentor);
        Volt::test('pages.mentor.course-material-manager', ['course' => $course])
            ->set('lessonSectionId', $otherSection->id)
            ->set('lessonTitle', 'Invalid Lesson')
            ->call('addLesson')
            ->assertHasErrors(['lessonSectionId']);

        $this->assertDatabaseMissing('course_lessons', [
            'course_id' => $course->id,
            'title' => 'Invalid Lesson',
        ]);
    }

    public function test_mentor_can_upload_pdf_material_to_private_course_materials_disk(): void
    {
        Storage::fake('course_materials');
        Storage::fake('public');

        $course = $this->courseFor($this->mentor);
        $lesson = $this->lessonFor($course);
        $file = UploadedFile::fake()->create('guide.pdf', 12, 'application/pdf');

        $this->actingAs($this->mentor);
        Volt::test('pages.mentor.course-material-manager', ['course' => $course])
            ->set('materialLessonId', $lesson->id)
            ->set('materialTitle', 'PDF Guide')
            ->set('materialType', MaterialType::Pdf->value)
            ->set('materialFile', $file)
            ->call('uploadMaterial');

        $material = CourseMaterial::firstOrFail();

        $this->assertSame(MaterialType::Pdf, $material->type);
        Storage::disk('course_materials')->assertExists($material->file_path);
        Storage::disk('public')->assertMissing($material->file_path);
        $this->assertStringStartsWith('course-'.$course->id.'/', $material->file_path);
        $this->assertStringNotContainsString('http://', $material->file_path);
        $this->assertStringNotContainsString('https://', $material->file_path);
    }

    public function test_mentor_can_upload_video_material_to_private_course_materials_disk(): void
    {
        Storage::fake('course_materials');

        $course = $this->courseFor($this->mentor);
        $lesson = $this->lessonFor($course);
        $file = UploadedFile::fake()->create('intro.mp4', 256, 'video/mp4');

        $this->actingAs($this->mentor);
        Volt::test('pages.mentor.course-material-manager', ['course' => $course])
            ->set('materialLessonId', $lesson->id)
            ->set('materialTitle', 'Intro Video')
            ->set('materialType', MaterialType::Video->value)
            ->set('materialFile', $file)
            ->call('uploadMaterial');

        $material = CourseMaterial::firstOrFail();

        $this->assertSame(MaterialType::Video, $material->type);
        Storage::disk('course_materials')->assertExists($material->file_path);
    }

    public function test_invalid_material_type_or_file_validation_fails(): void
    {
        Storage::fake('course_materials');

        $course = $this->courseFor($this->mentor);
        $lesson = $this->lessonFor($course);

        $this->actingAs($this->mentor);
        Volt::test('pages.mentor.course-material-manager', ['course' => $course])
            ->set('materialLessonId', $lesson->id)
            ->set('materialTitle', 'Invalid Type')
            ->set('materialType', 'audio')
            ->set('materialFile', UploadedFile::fake()->create('guide.pdf', 12, 'application/pdf'))
            ->call('uploadMaterial')
            ->assertHasErrors(['materialType']);

        Volt::test('pages.mentor.course-material-manager', ['course' => $course])
            ->set('materialLessonId', $lesson->id)
            ->set('materialTitle', 'Invalid File')
            ->set('materialType', MaterialType::Pdf->value)
            ->set('materialFile', UploadedFile::fake()->create('notes.txt', 1, 'text/plain'))
            ->call('uploadMaterial')
            ->assertHasErrors(['materialFile']);

        $this->assertDatabaseCount('course_materials', 0);
    }

    public function test_material_stores_original_filename_mime_type_file_size_and_private_path(): void
    {
        Storage::fake('course_materials');

        $course = $this->courseFor($this->mentor);
        $lesson = $this->lessonFor($course);
        $file = UploadedFile::fake()->create('mentor-guide.pdf', 18, 'application/pdf');

        $this->actingAs($this->mentor);
        Volt::test('pages.mentor.course-material-manager', ['course' => $course])
            ->set('materialLessonId', $lesson->id)
            ->set('materialTitle', 'Metadata Material')
            ->set('materialType', MaterialType::Pdf->value)
            ->set('materialFile', $file)
            ->call('uploadMaterial');

        $material = CourseMaterial::firstOrFail();

        $this->assertSame('mentor-guide.pdf', $material->original_filename);
        $this->assertSame('application/pdf', $material->mime_type);
        $this->assertSame($file->getSize(), $material->file_size);
        $this->assertNotEmpty($material->file_path);
        Storage::disk('course_materials')->assertExists($material->file_path);
    }

    public function test_mentor_can_replace_material_file(): void
    {
        Storage::fake('course_materials');

        $course = $this->courseFor($this->mentor);
        $lesson = $this->lessonFor($course);
        $material = $this->materialFor($course, $lesson, 'materials/old.pdf');
        Storage::disk('course_materials')->put('materials/old.pdf', 'old file');
        $replacement = UploadedFile::fake()->create('replacement.pdf', 20, 'application/pdf');

        $this->actingAs($this->mentor);
        Volt::test('pages.mentor.course-material-manager', ['course' => $course])
            ->set('replacementFiles.'.$material->id, $replacement)
            ->call('replaceMaterialFile', $material->id);

        $material->refresh();

        $this->assertSame('replacement.pdf', $material->original_filename);
        Storage::disk('course_materials')->assertExists($material->file_path);
        Storage::disk('course_materials')->assertMissing('materials/old.pdf');
    }

    public function test_mentor_can_mark_material_as_deleted_without_hard_deleting_course(): void
    {
        Storage::fake('course_materials');

        $course = $this->courseFor($this->mentor);
        $lesson = $this->lessonFor($course);
        $material = $this->materialFor($course, $lesson, 'materials/delete-me.pdf');
        Storage::disk('course_materials')->put('materials/delete-me.pdf', 'old file');

        $this->actingAs($this->mentor);
        Volt::test('pages.mentor.course-material-manager', ['course' => $course])
            ->call('markMaterialDeleted', $material->id);

        $this->assertNotSoftDeleted($course);
        $this->assertSoftDeleted('course_materials', ['id' => $material->id]);
        $this->assertSame('deleted', CourseMaterial::withTrashed()->findOrFail($material->id)->status);
        Storage::disk('course_materials')->assertMissing('materials/delete-me.pdf');
    }

    public function test_deleted_course_blocks_new_material_upload_and_replacement(): void
    {
        Storage::fake('course_materials');

        $course = $this->courseFor($this->mentor, ['status' => CourseStatus::DeletedByMentor]);
        $lesson = $this->lessonFor($course);
        $material = $this->materialFor($course, $lesson, 'materials/existing.pdf');

        $this->actingAs($this->mentor);
        Volt::test('pages.mentor.course-material-manager', ['course' => $course])
            ->set('materialLessonId', $lesson->id)
            ->set('materialTitle', 'Blocked Upload')
            ->set('materialType', MaterialType::Pdf->value)
            ->set('materialFile', UploadedFile::fake()->create('blocked.pdf', 12, 'application/pdf'))
            ->call('uploadMaterial')
            ->assertForbidden();

        Volt::test('pages.mentor.course-material-manager', ['course' => $course])
            ->set('replacementFiles.'.$material->id, UploadedFile::fake()->create('blocked-replace.pdf', 12, 'application/pdf'))
            ->call('replaceMaterialFile', $material->id)
            ->assertForbidden();

        Volt::test('pages.mentor.course-material-manager', ['course' => $course])
            ->call('markMaterialDeleted', $material->id)
            ->assertForbidden();

        $this->assertDatabaseMissing('course_materials', [
            'course_id' => $course->id,
            'title' => 'Blocked Upload',
        ]);
    }

    private function courseFor(User $mentor, array $overrides = []): Course
    {
        return Course::create(array_merge([
            'mentor_id' => $mentor->id,
            'category_id' => $this->category->id,
            'title' => 'Mentor Material Course',
            'slug' => 'mentor-material-course-'.str()->random(8),
            'short_description' => 'Short description',
            'description' => 'Long description',
            'price' => 50000,
            'status' => CourseStatus::Draft,
        ], $overrides));
    }

    private function sectionFor(Course $course): CourseSection
    {
        return CourseSection::create([
            'course_id' => $course->id,
            'title' => 'Section '.$course->id,
            'sort_order' => 0,
        ]);
    }

    private function lessonFor(Course $course): CourseLesson
    {
        $section = $this->sectionFor($course);

        return CourseLesson::create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'title' => 'Lesson '.$course->id,
            'sort_order' => 0,
        ]);
    }

    private function materialFor(Course $course, CourseLesson $lesson, string $path): CourseMaterial
    {
        return CourseMaterial::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'title' => 'Existing Material',
            'type' => MaterialType::Pdf,
            'file_path' => $path,
            'original_filename' => basename($path),
            'mime_type' => 'application/pdf',
            'file_size' => 8,
            'status' => 'active',
        ]);
    }
}
