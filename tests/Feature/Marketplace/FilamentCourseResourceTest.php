<?php

namespace Tests\Feature\Marketplace;

use App\Enums\CourseStatus;
use App\Filament\Resources\CourseResource\Pages\ListCourses;
use App\Models\Course;
use App\Models\User;
use App\Services\CourseStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentCourseResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_filament_course_and_category_resource_lists(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/courses')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/course-categories')
            ->assertOk();
    }

    public function test_admin_can_access_filament_course_view_resource(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $course = Course::firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/courses/'.$course->getKey())
            ->assertOk();
    }

    public function test_filament_course_edit_page_is_not_registered(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $course = Course::firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/courses/'.$course->getKey().'/edit')
            ->assertNotFound();
    }

    public function test_course_status_service_archive_sets_status_and_timestamp(): void
    {
        $this->seed();

        $course = Course::firstOrFail();

        app(CourseStatusService::class)->archive($course);

        $course->refresh();

        $this->assertSame(CourseStatus::Archived, $course->status);
        $this->assertNotNull($course->archived_at);
    }

    public function test_course_status_service_hide_by_admin_sets_status_and_timestamp(): void
    {
        $this->seed();

        $course = Course::firstOrFail();

        app(CourseStatusService::class)->hideByAdmin($course);

        $course->refresh();

        $this->assertSame(CourseStatus::HiddenByAdmin, $course->status);
        $this->assertNotNull($course->hidden_by_admin_at);
    }

    public function test_filament_archive_action_uses_status_service_behavior(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $course = Course::firstOrFail();

        $this->actingAs($admin);

        Livewire::test(ListCourses::class)
            ->callTableAction('archive', $course);

        $course->refresh();

        $this->assertSame(CourseStatus::Archived, $course->status);
        $this->assertNotNull($course->archived_at);
    }

    public function test_filament_hide_action_uses_status_service_behavior(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $course = Course::firstOrFail();

        $this->actingAs($admin);

        Livewire::test(ListCourses::class)
            ->callTableAction('hide', $course);

        $course->refresh();

        $this->assertSame(CourseStatus::HiddenByAdmin, $course->status);
        $this->assertNotNull($course->hidden_by_admin_at);
    }
}
