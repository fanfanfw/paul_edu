<?php

namespace Tests\Feature\Marketplace;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\User;
use Database\Seeders\CourseCategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class CourseManagementTest extends TestCase
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

    public function test_mentor_can_create_course(): void
    {
        $this->actingAs($this->mentor);

        Volt::test('pages.mentor.course-form')
            ->set('title', 'Course From Mentor')
            ->set('category_id', $this->category->id)
            ->set('short_description', 'Short course description')
            ->set('description', 'Long course description')
            ->set('price', 100000)
            ->set('status', CourseStatus::Draft->value)
            ->call('save')
            ->assertRedirect(route('mentor.courses.index'));

        $this->assertDatabaseHas('courses', [
            'mentor_id' => $this->mentor->id,
            'title' => 'Course From Mentor',
            'slug' => 'course-from-mentor',
            'status' => CourseStatus::Draft->value,
            'price' => 100000,
        ]);
    }

    public function test_mentor_can_publish_course(): void
    {
        $this->actingAs($this->mentor);

        Volt::test('pages.mentor.course-form')
            ->set('title', 'Published From Mentor')
            ->set('category_id', $this->category->id)
            ->set('price', 0)
            ->set('status', CourseStatus::Published->value)
            ->call('save');

        $course = Course::where('slug', 'published-from-mentor')->firstOrFail();

        $this->assertSame(CourseStatus::Published, $course->status);
        $this->assertNotNull($course->published_at);
    }

    public function test_mentor_can_edit_own_course(): void
    {
        $course = $this->courseFor($this->mentor, ['title' => 'Old Title']);

        $this->actingAs($this->mentor);

        Volt::test('pages.mentor.course-form', ['course' => $course])
            ->set('title', 'Updated Title')
            ->set('category_id', $this->category->id)
            ->set('price', 120000)
            ->set('status', CourseStatus::Draft->value)
            ->call('save');

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Updated Title',
            'price' => 120000,
        ]);
    }

    public function test_mentor_cannot_edit_other_mentor_course(): void
    {
        $otherMentor = User::factory()->create();
        $otherMentor->assignRole('mentor');
        $course = $this->courseFor($otherMentor);

        $this->actingAs($this->mentor)
            ->get(route('mentor.courses.edit', $course))
            ->assertForbidden();
    }

    public function test_mentor_delete_marks_own_course_as_deleted_by_mentor_without_hard_delete(): void
    {
        $course = $this->courseFor($this->mentor, ['status' => CourseStatus::Published]);

        $this->actingAs($this->mentor);

        Volt::test('pages.mentor.course-index')
            ->call('markDeleted', $course->id);

        $course->refresh();

        $this->assertSame(CourseStatus::DeletedByMentor, $course->status);
        $this->assertNotNull($course->deleted_by_mentor_at);
        $this->assertNotSoftDeleted($course);
    }

    public function test_course_price_cannot_be_negative(): void
    {
        $this->actingAs($this->mentor);

        Volt::test('pages.mentor.course-form')
            ->set('title', 'Negative Course')
            ->set('category_id', $this->category->id)
            ->set('price', -1)
            ->set('status', CourseStatus::Draft->value)
            ->call('save')
            ->assertHasErrors(['price']);
    }

    public function test_user_non_mentor_cannot_access_mentor_course_management_routes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user)
            ->get(route('mentor.courses.index'))
            ->assertForbidden();
    }

    private function courseFor(User $mentor, array $overrides = []): Course
    {
        return Course::create(array_merge([
            'mentor_id' => $mentor->id,
            'category_id' => $this->category->id,
            'title' => 'Mentor Course',
            'slug' => 'mentor-course-'.str()->random(8),
            'short_description' => 'Short description',
            'description' => 'Long description',
            'price' => 50000,
            'status' => CourseStatus::Draft,
        ], $overrides));
    }
}
