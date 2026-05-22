<?php

namespace Tests\Feature\Marketplace;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\User;
use Database\Seeders\CourseCategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCourseTest extends TestCase
{
    use RefreshDatabase;

    private User $mentor;
    private CourseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CourseCategorySeeder::class);

        $this->mentor = User::factory()->create(['name' => 'Mentor Public']);
        $this->mentor->assignRole('mentor');
        $this->category = CourseCategory::firstOrFail();
    }

    public function test_published_course_appears_in_catalog(): void
    {
        $this->course('Visible Published Course', CourseStatus::Published);

        $this->get('/courses')
            ->assertOk()
            ->assertSee('Visible Published Course');
    }

    public function test_non_published_courses_do_not_appear_in_catalog(): void
    {
        $this->course('Visible Published Course', CourseStatus::Published);
        $this->course('Draft Hidden Course', CourseStatus::Draft);
        $this->course('Archived Hidden Course', CourseStatus::Archived);
        $this->course('Deleted Hidden Course', CourseStatus::DeletedByMentor);
        $this->course('Admin Hidden Course', CourseStatus::HiddenByAdmin);

        $this->get('/courses')
            ->assertOk()
            ->assertSee('Visible Published Course')
            ->assertDontSee('Draft Hidden Course')
            ->assertDontSee('Archived Hidden Course')
            ->assertDontSee('Deleted Hidden Course')
            ->assertDontSee('Admin Hidden Course');
    }

    public function test_public_can_view_published_course_detail(): void
    {
        $course = $this->course('Published Detail Course', CourseStatus::Published);

        $this->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Published Detail Course')
            ->assertSee('Mentor Public');
    }

    public function test_public_cannot_view_non_published_course_detail(): void
    {
        $course = $this->course('Draft Detail Course', CourseStatus::Draft);

        $this->get(route('courses.show', $course))
            ->assertNotFound();
    }

    private function course(string $title, CourseStatus $status): Course
    {
        return Course::create([
            'mentor_id' => $this->mentor->id,
            'category_id' => $this->category->id,
            'title' => $title,
            'slug' => str($title)->slug().'-'.str()->random(6),
            'short_description' => 'Short description for '.$title,
            'description' => 'Long description for '.$title,
            'price' => 100000,
            'status' => $status,
            'published_at' => $status === CourseStatus::Published ? now() : null,
        ]);
    }
}
