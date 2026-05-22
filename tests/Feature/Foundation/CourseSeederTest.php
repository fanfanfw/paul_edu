<?php

namespace Tests\Feature\Foundation;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\CourseCategory;
use Database\Seeders\CourseCategorySeeder;
use Database\Seeders\DemoCourseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_seeder_creates_required_categories_and_is_idempotent(): void
    {
        $this->seed(CourseCategorySeeder::class);
        $this->seed(CourseCategorySeeder::class);

        foreach ([
            'Web Development',
            'Digital Marketing',
            'UI/UX Design',
            'Data Analytics',
            'Business',
            'Productivity',
        ] as $category) {
            $this->assertDatabaseHas('course_categories', ['name' => $category]);
        }

        $this->assertSame(6, CourseCategory::count());
    }

    public function test_demo_course_seeder_creates_one_free_and_two_paid_published_courses(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(CourseCategorySeeder::class);
        $this->seed(DemoCourseSeeder::class);
        $this->seed(DemoCourseSeeder::class);

        $this->assertSame(3, Course::count());
        $this->assertSame(3, Course::where('status', CourseStatus::Published)->count());
        $this->assertSame(1, Course::where('price', 0)->count());
        $this->assertSame(2, Course::where('price', '>', 0)->count());
    }
}
