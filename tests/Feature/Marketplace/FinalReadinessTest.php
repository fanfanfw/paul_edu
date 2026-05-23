<?php

namespace Tests\Feature\Marketplace;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Filament\Resources\PlatformSettingResource\Pages\EditPlatformSetting;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CoursePurchaseService;
use Database\Seeders\CourseCategorySeeder;
use Database\Seeders\PlatformSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinalReadinessTest extends TestCase
{
    use RefreshDatabase;

    private CourseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CourseCategorySeeder::class);
        $this->seed(PlatformSettingSeeder::class);

        $this->category = CourseCategory::firstOrFail();
    }

    public function test_admin_navigation_and_dashboard_do_not_show_buyer_links_and_show_admin_link(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Admin Panel')
            ->assertSee('Monitoring platform via Filament')
            ->assertDontSee('Kelas Saya')
            ->assertDontSee('Wallet')
            ->assertDontSee('Transaksi');
    }

    public function test_user_dashboard_shows_user_quick_links(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Katalog')
            ->assertSee('Kelas saya')
            ->assertSee('Wallet')
            ->assertSee('Transaksi')
            ->assertDontSee('Mentor Dashboard')
            ->assertDontSee('Kelas Mentor');
    }

    public function test_mentor_dashboard_and_navigation_show_mentor_links(): void
    {
        $mentor = User::factory()->create();
        $mentor->assignRole('mentor');

        $this->actingAs($mentor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Katalog')
            ->assertSee('Kelas saya')
            ->assertSee('Wallet')
            ->assertSee('Transaksi')
            ->assertSee('Mentor Dashboard')
            ->assertSee('Kelas Mentor')
            ->assertSee('Mentor Wallet')
            ->assertSee('Sales');
    }

    public function test_platform_setting_resource_validates_commission_rate(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $setting = PlatformSetting::where('key', 'mentor_commission_rate')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/platform-settings')
            ->assertOk();

        Livewire::actingAs($admin)
            ->test(EditPlatformSetting::class, ['record' => $setting->id])
            ->fillForm(['value' => 101])
            ->call('save')
            ->assertHasFormErrors(['value']);

        Livewire::actingAs($admin)
            ->test(EditPlatformSetting::class, ['record' => $setting->id])
            ->fillForm(['value' => 80])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('80', $setting->refresh()->value);
    }

    public function test_updated_commission_setting_affects_new_purchase_but_not_existing_order_snapshot(): void
    {
        $mentor = User::factory()->create();
        $mentor->assignRole('mentor');
        $firstBuyer = User::factory()->create();
        $firstBuyer->assignRole('user');
        $secondBuyer = User::factory()->create();
        $secondBuyer->assignRole('user');
        $course = $this->courseFor($mentor, ['price' => 100000]);
        $this->walletFor($firstBuyer, 200000);
        $this->walletFor($secondBuyer, 200000);

        $oldOrder = app(CoursePurchaseService::class)->purchase($firstBuyer, $course);

        PlatformSetting::where('key', 'mentor_commission_rate')->update(['value' => '80']);

        $newOrder = app(CoursePurchaseService::class)->purchase($secondBuyer, $course->fresh());

        $this->assertSame(60, $oldOrder->refresh()->commission_rate_snapshot);
        $this->assertSame(80, $newOrder->commission_rate_snapshot);
        $this->assertSame(80000, $newOrder->mentor_amount);
        $this->assertSame(20000, $newOrder->platform_amount);
    }

    public function test_catalog_displays_rating_summary_from_published_reviews_only(): void
    {
        $mentor = User::factory()->create();
        $mentor->assignRole('mentor');
        $course = $this->courseFor($mentor, ['title' => 'Rated Catalog Course']);
        $firstUser = User::factory()->create(['name' => 'Published One']);
        $firstUser->assignRole('user');
        $secondUser = User::factory()->create(['name' => 'Published Two']);
        $secondUser->assignRole('user');
        $hiddenUser = User::factory()->create(['name' => 'Hidden User']);
        $hiddenUser->assignRole('user');

        CourseReview::create(['user_id' => $firstUser->id, 'course_id' => $course->id, 'rating' => 5, 'comment' => 'Published review one.']);
        CourseReview::create(['user_id' => $secondUser->id, 'course_id' => $course->id, 'rating' => 3, 'comment' => 'Published review two.']);
        CourseReview::create(['user_id' => $hiddenUser->id, 'course_id' => $course->id, 'rating' => 1, 'comment' => 'Hidden review ignored.', 'is_published' => false]);

        $this->get(route('courses.index'))
            ->assertOk()
            ->assertSee('Rated Catalog Course')
            ->assertSee('4.0/5 · 2 review')
            ->assertDontSee('1.0/5');
    }

    public function test_my_courses_filters_inactive_and_revoked_enrollments_but_shows_active_deleted_course(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        $mentor = User::factory()->create();
        $mentor->assignRole('mentor');
        $activeDeletedCourse = $this->courseFor($mentor, ['title' => 'Active Deleted Course', 'status' => CourseStatus::DeletedByMentor]);
        $inactiveCourse = $this->courseFor($mentor, ['title' => 'Inactive Enrollment Course']);
        $revokedCourse = $this->courseFor($mentor, ['title' => 'Revoked Enrollment Course']);

        $this->enroll($user, $activeDeletedCourse, EnrollmentStatus::Active);
        $this->enroll($user, $inactiveCourse, EnrollmentStatus::Inactive);
        $this->enroll($user, $revokedCourse, EnrollmentStatus::Revoked);

        $this->actingAs($user)
            ->get(route('student.courses'))
            ->assertOk()
            ->assertSee('Active Deleted Course')
            ->assertSee('Kelas ini dihapus oleh mentor')
            ->assertDontSee('Inactive Enrollment Course')
            ->assertDontSee('Revoked Enrollment Course');
    }

    private function courseFor(User $mentor, array $overrides = []): Course
    {
        return Course::create(array_merge([
            'mentor_id' => $mentor->id,
            'category_id' => $this->category->id,
            'title' => 'Readiness Course '.str()->random(6),
            'slug' => 'readiness-course-'.str()->random(8),
            'short_description' => 'Short description',
            'description' => 'Long description',
            'price' => 100000,
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ], $overrides));
    }

    private function walletFor(User $user, int $balance): Wallet
    {
        return Wallet::create([
            'owner_type' => 'user',
            'owner_id' => $user->id,
            'balance' => $balance,
            'currency' => 'IDR',
            'status' => 'active',
        ]);
    }

    private function enroll(User $user, Course $course, EnrollmentStatus $status): Enrollment
    {
        return Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => $status,
            'enrolled_at' => now(),
        ]);
    }
}
