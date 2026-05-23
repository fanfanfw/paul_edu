<?php

namespace Tests\Feature\Marketplace;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\MaterialType;
use App\Enums\OrderStatus;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseLesson;
use App\Models\CourseMaterial;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FreeEnrollmentService;
use Database\Seeders\CourseCategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;
use Tests\TestCase;

class EnrollmentLearningAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $mentor;
    private User $user;
    private CourseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CourseCategorySeeder::class);

        $this->mentor = User::factory()->create();
        $this->mentor->assignRole('mentor');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        $this->category = CourseCategory::firstOrFail();
    }

    public function test_free_course_enrollment_creates_order_order_item_and_enrollment(): void
    {
        $course = $this->courseFor($this->mentor, ['price' => 0, 'status' => CourseStatus::Published]);

        $order = app(FreeEnrollmentService::class)->enroll($this->user, $course);

        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertMatchesRegularExpression('/^ORD-\d{8}-[0-9A-Z]{10}$/', $order->order_number);
        $this->assertSame(0, $order->total_amount);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'course_id' => $course->id,
            'price_snapshot' => 0,
            'quantity' => 1,
            'subtotal' => 0,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'order_id' => $order->id,
            'status' => EnrollmentStatus::Active->value,
        ]);
    }

    public function test_free_course_enrollment_does_not_create_wallet_transaction_or_change_wallet_balance(): void
    {
        $course = $this->courseFor($this->mentor, ['price' => 0, 'status' => CourseStatus::Published]);
        $wallet = Wallet::create([
            'owner_type' => 'user',
            'owner_id' => $this->user->id,
            'balance' => 123456,
            'currency' => 'IDR',
            'status' => 'active',
        ]);

        app(FreeEnrollmentService::class)->enroll($this->user, $course);

        $this->assertSame(123456, $wallet->refresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_user_cannot_enroll_non_free_course_through_free_enrollment_service(): void
    {
        $course = $this->courseFor($this->mentor, ['price' => 100000, 'status' => CourseStatus::Published]);

        $this->expectException(ValidationException::class);

        try {
            app(FreeEnrollmentService::class)->enroll($this->user, $course);
        } finally {
            $this->assertDatabaseCount('orders', 0);
            $this->assertDatabaseCount('enrollments', 0);
        }
    }

    public function test_user_cannot_enroll_unpublished_course(): void
    {
        $course = $this->courseFor($this->mentor, ['price' => 0, 'status' => CourseStatus::Draft]);

        $this->expectException(ValidationException::class);

        try {
            app(FreeEnrollmentService::class)->enroll($this->user, $course);
        } finally {
            $this->assertDatabaseCount('orders', 0);
            $this->assertDatabaseCount('enrollments', 0);
        }
    }

    public function test_user_cannot_enroll_same_course_twice(): void
    {
        $course = $this->courseFor($this->mentor, ['price' => 0, 'status' => CourseStatus::Published]);

        app(FreeEnrollmentService::class)->enroll($this->user, $course);

        $this->expectException(ValidationException::class);

        try {
            app(FreeEnrollmentService::class)->enroll($this->user, $course);
        } finally {
            $this->assertDatabaseCount('orders', 1);
            $this->assertDatabaseCount('enrollments', 1);
        }
    }

    public function test_mentor_cannot_enroll_own_free_course(): void
    {
        $course = $this->courseFor($this->mentor, ['price' => 0, 'status' => CourseStatus::Published]);

        $this->expectException(ValidationException::class);

        try {
            app(FreeEnrollmentService::class)->enroll($this->mentor, $course);
        } finally {
            $this->assertDatabaseCount('orders', 0);
            $this->assertDatabaseCount('enrollments', 0);
        }
    }

    public function test_failed_enrollment_creates_no_order_or_enrollment(): void
    {
        $course = $this->courseFor($this->mentor, ['price' => 0, 'status' => CourseStatus::HiddenByAdmin]);

        try {
            app(FreeEnrollmentService::class)->enroll($this->user, $course);
        } catch (ValidationException) {
            // Expected; assertion below verifies no partial writes.
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_course_detail_free_cta_enrolls_and_redirects_to_learning_page(): void
    {
        $course = $this->courseFor($this->mentor, ['price' => 0, 'status' => CourseStatus::Published]);

        $this->actingAs($this->user);

        Volt::test('pages.public.course-detail', ['course' => $course])
            ->call('enrollFree')
            ->assertRedirect(route('student.learn', $course));

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $this->user->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_my_courses_shows_enrolled_course_only(): void
    {
        $enrolledCourse = $this->courseFor($this->mentor, ['title' => 'Enrolled Course', 'status' => CourseStatus::Published]);
        $otherCourse = $this->courseFor($this->mentor, ['title' => 'Not Enrolled Course', 'status' => CourseStatus::Published]);
        $this->enroll($this->user, $enrolledCourse);

        $this->actingAs($this->user)
            ->get(route('student.courses'))
            ->assertOk()
            ->assertSee('Enrolled Course')
            ->assertDontSee('Not Enrolled Course');
    }

    public function test_my_courses_shows_deleted_by_mentor_course_as_disabled_unavailable(): void
    {
        $course = $this->courseFor($this->mentor, ['title' => 'Deleted Course', 'status' => CourseStatus::DeletedByMentor]);
        $this->enroll($this->user, $course);

        $this->actingAs($this->user)
            ->get(route('student.courses'))
            ->assertOk()
            ->assertSee('Deleted Course')
            ->assertSee('Kelas ini dihapus oleh mentor')
            ->assertSee('Materi kelas ini tidak lagi tersedia karena dihapus oleh mentor.');
    }

    public function test_enrolled_user_can_access_learning_page_for_published_course(): void
    {
        $course = $this->courseFor($this->mentor, ['status' => CourseStatus::Published]);
        $this->enroll($this->user, $course);

        $this->actingAs($this->user)
            ->get(route('student.learn', $course))
            ->assertOk()
            ->assertSee($course->title);
    }

    public function test_enrolled_user_can_access_learning_page_for_archived_course(): void
    {
        $course = $this->courseFor($this->mentor, ['status' => CourseStatus::Archived]);
        $this->enroll($this->user, $course);

        $this->actingAs($this->user)
            ->get(route('student.learn', $course))
            ->assertOk()
            ->assertSee('Diarsipkan');
    }

    public function test_enrolled_user_cannot_access_learning_page_for_deleted_by_mentor_course(): void
    {
        $course = $this->courseFor($this->mentor, ['status' => CourseStatus::DeletedByMentor]);
        $this->enroll($this->user, $course);

        $this->actingAs($this->user)
            ->get(route('student.learn', $course))
            ->assertForbidden();
    }

    public function test_enrolled_user_cannot_access_learning_page_for_hidden_by_admin_course(): void
    {
        $course = $this->courseFor($this->mentor, ['status' => CourseStatus::HiddenByAdmin]);
        $this->enroll($this->user, $course);

        $this->actingAs($this->user)
            ->get(route('student.learn', $course))
            ->assertForbidden();
    }

    public function test_non_enrolled_user_cannot_access_learning_page(): void
    {
        $course = $this->courseFor($this->mentor, ['status' => CourseStatus::Published]);

        $this->actingAs($this->user)
            ->get(route('student.learn', $course))
            ->assertForbidden();
    }

    public function test_enrolled_user_can_access_active_material_via_protected_route(): void
    {
        Storage::fake('course_materials');

        $course = $this->courseFor($this->mentor, ['status' => CourseStatus::Published]);
        $material = $this->materialFor($course, 'materials/guide.pdf', 'pdf body');
        $this->enroll($this->user, $course);

        $this->actingAs($this->user)
            ->get(route('materials.view', $material))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertSee('pdf body');
    }

    public function test_non_enrolled_user_cannot_access_material_route(): void
    {
        Storage::fake('course_materials');

        $course = $this->courseFor($this->mentor, ['status' => CourseStatus::Published]);
        $material = $this->materialFor($course, 'materials/guide.pdf', 'pdf body');

        $this->actingAs($this->user)
            ->get(route('materials.view', $material))
            ->assertForbidden();
    }

    public function test_deleted_or_hidden_course_blocks_material_route(): void
    {
        Storage::fake('course_materials');

        $deletedCourse = $this->courseFor($this->mentor, ['status' => CourseStatus::DeletedByMentor]);
        $deletedMaterial = $this->materialFor($deletedCourse, 'materials/deleted.pdf', 'deleted body');
        $this->enroll($this->user, $deletedCourse);

        $hiddenCourse = $this->courseFor($this->mentor, ['status' => CourseStatus::HiddenByAdmin]);
        $hiddenMaterial = $this->materialFor($hiddenCourse, 'materials/hidden.pdf', 'hidden body');
        $this->enroll($this->user, $hiddenCourse);

        $this->actingAs($this->user)
            ->get(route('materials.view', $deletedMaterial))
            ->assertForbidden();

        $this->actingAs($this->user)
            ->get(route('materials.view', $hiddenMaterial))
            ->assertForbidden();
    }

    public function test_missing_private_file_returns_safe_404_and_does_not_expose_file_path(): void
    {
        Storage::fake('course_materials');
        Log::spy();

        $course = $this->courseFor($this->mentor, ['status' => CourseStatus::Published]);
        $material = $this->materialFor($course, 'private/missing-file.pdf', null);
        $this->enroll($this->user, $course);

        $this->actingAs($this->user)
            ->get(route('materials.view', $material))
            ->assertNotFound()
            ->assertDontSee('private/missing-file.pdf');

        Log::shouldHaveReceived('warning')->once();
    }

    public function test_material_route_reads_from_course_materials_disk(): void
    {
        Storage::fake('course_materials');
        Storage::fake('public');

        $course = $this->courseFor($this->mentor, ['status' => CourseStatus::Published]);
        $material = $this->materialFor($course, 'same-path/guide.pdf', null);
        Storage::disk('course_materials')->put('same-path/guide.pdf', 'private disk body');
        Storage::disk('public')->put('same-path/guide.pdf', 'public disk body');
        $this->enroll($this->user, $course);

        $this->actingAs($this->user)
            ->get(route('materials.view', $material))
            ->assertOk()
            ->assertSee('private disk body')
            ->assertDontSee('public disk body');
    }

    private function courseFor(User $mentor, array $overrides = []): Course
    {
        return Course::create(array_merge([
            'mentor_id' => $mentor->id,
            'category_id' => $this->category->id,
            'title' => 'Learning Course '.str()->random(6),
            'slug' => 'learning-course-'.str()->random(8),
            'short_description' => 'Short description',
            'description' => 'Long description',
            'price' => 0,
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ], $overrides));
    }

    private function enroll(User $user, Course $course): Enrollment
    {
        return Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);
    }

    private function materialFor(Course $course, string $path, ?string $contents): CourseMaterial
    {
        $section = CourseSection::create([
            'course_id' => $course->id,
            'title' => 'Section',
            'sort_order' => 0,
        ]);

        $lesson = CourseLesson::create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'title' => 'Lesson',
            'sort_order' => 0,
        ]);

        if ($contents !== null) {
            Storage::disk('course_materials')->put($path, $contents);
        }

        return CourseMaterial::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'title' => 'Protected PDF',
            'type' => MaterialType::Pdf,
            'file_path' => $path,
            'original_filename' => basename($path),
            'mime_type' => 'application/pdf',
            'file_size' => $contents === null ? 0 : strlen($contents),
            'status' => 'active',
        ]);
    }
}
