<?php

namespace Tests\Feature\Marketplace;

use App\Enums\CourseStatus;
use App\Enums\MaterialType;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseLesson;
use App\Models\CourseMaterial;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CoursePurchaseService;
use App\Services\FreeEnrollmentService;
use Database\Seeders\CourseCategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BuyerAuthorizationAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $mentor;
    private User $user;
    private CourseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CourseCategorySeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->mentor = User::factory()->create();
        $this->mentor->assignRole('mentor');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        $this->category = CourseCategory::firstOrFail();
    }

    public function test_admin_cannot_access_buyer_student_routes(): void
    {
        Storage::fake('course_materials');

        $course = $this->courseFor($this->mentor, ['status' => CourseStatus::Published]);
        $material = $this->materialFor($course);

        $this->actingAs($this->admin)->get(route('wallet'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('transactions'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('student.courses'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('student.learn', $course))->assertForbidden();
        $this->actingAs($this->admin)->get(route('materials.view', $material))->assertForbidden();
    }

    public function test_admin_cannot_free_enroll_course_via_service(): void
    {
        $course = $this->courseFor($this->mentor, ['price' => 0, 'status' => CourseStatus::Published]);

        $this->expectException(ValidationException::class);

        try {
            app(FreeEnrollmentService::class)->enroll($this->admin, $course);
        } finally {
            $this->assertDatabaseCount('orders', 0);
            $this->assertDatabaseCount('enrollments', 0);
        }
    }

    public function test_admin_cannot_paid_purchase_course_via_service(): void
    {
        $course = $this->courseFor($this->mentor, ['price' => 100000, 'status' => CourseStatus::Published]);
        Wallet::create([
            'owner_type' => 'user',
            'owner_id' => $this->admin->id,
            'balance' => 500000,
            'currency' => 'IDR',
            'status' => 'active',
        ]);

        $this->expectException(ValidationException::class);

        try {
            app(CoursePurchaseService::class)->purchase($this->admin, $course);
        } finally {
            $this->assertDatabaseCount('orders', 0);
            $this->assertDatabaseCount('enrollments', 0);
            $this->assertDatabaseCount('wallet_transactions', 0);
        }
    }

    public function test_admin_course_detail_does_not_show_active_buy_or_enroll_cta(): void
    {
        $freeCourse = $this->courseFor($this->mentor, ['price' => 0, 'status' => CourseStatus::Published]);
        $paidCourse = $this->courseFor($this->mentor, ['price' => 100000, 'status' => CourseStatus::Published]);

        $this->actingAs($this->admin)
            ->get(route('courses.show', $freeCourse))
            ->assertOk()
            ->assertSee('Admin tidak dapat membeli kelas')
            ->assertDontSee('Enroll Gratis');

        $this->actingAs($this->admin)
            ->get(route('courses.show', $paidCourse))
            ->assertOk()
            ->assertSee('Admin tidak dapat membeli kelas')
            ->assertDontSee('Beli Kelas');
    }

    public function test_mentor_can_still_purchase_course_from_another_mentor(): void
    {
        $seller = User::factory()->create();
        $seller->assignRole('mentor');
        $course = $this->courseFor($seller, ['price' => 100000, 'status' => CourseStatus::Published]);
        Wallet::create([
            'owner_type' => 'user',
            'owner_id' => $this->mentor->id,
            'balance' => 150000,
            'currency' => 'IDR',
            'status' => 'active',
        ]);

        app(CoursePurchaseService::class)->purchase($this->mentor, $course);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $this->mentor->id,
            'course_id' => $course->id,
        ]);
    }

    private function courseFor(User $mentor, array $overrides = []): Course
    {
        return Course::create(array_merge([
            'mentor_id' => $mentor->id,
            'category_id' => $this->category->id,
            'title' => 'Audit Course '.str()->random(6),
            'slug' => 'audit-course-'.str()->random(8),
            'short_description' => 'Short description',
            'description' => 'Long description',
            'price' => 0,
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ], $overrides));
    }

    private function materialFor(Course $course): CourseMaterial
    {
        $section = CourseSection::create([
            'course_id' => $course->id,
            'title' => 'Section',
        ]);

        $lesson = CourseLesson::create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'title' => 'Lesson',
        ]);

        Storage::disk('course_materials')->put('audit/material.pdf', 'pdf body');

        return CourseMaterial::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'title' => 'Audit Material',
            'type' => MaterialType::Pdf,
            'file_path' => 'audit/material.pdf',
            'original_filename' => 'material.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 8,
            'status' => 'active',
        ]);
    }
}
