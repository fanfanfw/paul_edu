<?php

namespace Tests\Feature\Marketplace;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Filament\Resources\CourseReviewResource\Pages\ListCourseReviews;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\CourseReviewService;
use Database\Seeders\CourseCategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class CourseReviewTest extends TestCase
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

        $this->mentor = User::factory()->create(['name' => 'Mentor Review']);
        $this->mentor->assignRole('mentor');

        $this->user = User::factory()->create(['name' => 'User Review']);
        $this->user->assignRole('user');

        $this->category = CourseCategory::firstOrFail();
    }

    public function test_enrolled_user_can_create_review(): void
    {
        $course = $this->courseFor($this->mentor);
        $this->enroll($this->user, $course);

        $review = app(CourseReviewService::class)->create($this->user, $course, 5, 'Kelas ini sangat membantu.');

        $this->assertSame(5, $review->rating);
        $this->assertTrue($review->is_published);
        $this->assertDatabaseHas('course_reviews', [
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'rating' => 5,
        ]);
    }

    public function test_non_enrolled_user_cannot_create_review(): void
    {
        $course = $this->courseFor($this->mentor);

        $this->expectException(ValidationException::class);

        app(CourseReviewService::class)->create($this->user, $course, 5, 'Kelas ini sangat membantu.');
    }

    public function test_user_cannot_review_same_course_twice(): void
    {
        $course = $this->courseFor($this->mentor);
        $this->enroll($this->user, $course);

        app(CourseReviewService::class)->create($this->user, $course, 5, 'Review pertama yang valid.');

        $this->expectException(ValidationException::class);

        app(CourseReviewService::class)->create($this->user, $course, 4, 'Review kedua tidak boleh.');
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        $course = $this->courseFor($this->mentor);
        $this->enroll($this->user, $course);

        $this->expectException(ValidationException::class);

        app(CourseReviewService::class)->create($this->user, $course, 6, 'Komentar valid minimal sepuluh.');
    }

    public function test_comment_min_and_max_validation(): void
    {
        $course = $this->courseFor($this->mentor);
        $this->enroll($this->user, $course);

        try {
            app(CourseReviewService::class)->create($this->user, $course, 5, 'pendek');
            $this->fail('Expected short comment validation failure.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('course_reviews', 0);
        }

        $this->expectException(ValidationException::class);

        app(CourseReviewService::class)->create($this->user, $course, 5, str_repeat('a', 2001));
    }

    public function test_user_can_edit_own_review_and_edited_at_is_set(): void
    {
        $course = $this->courseFor($this->mentor);
        $this->enroll($this->user, $course);
        $review = app(CourseReviewService::class)->create($this->user, $course, 4, 'Review awal yang valid.');

        app(CourseReviewService::class)->update($this->user, $review, 5, 'Review update yang lebih lengkap.');

        $review->refresh();

        $this->assertSame(5, $review->rating);
        $this->assertSame('Review update yang lebih lengkap.', $review->comment);
        $this->assertNotNull($review->edited_at);
    }

    public function test_user_cannot_edit_another_users_review(): void
    {
        $course = $this->courseFor($this->mentor);
        $this->enroll($this->user, $course);
        $review = app(CourseReviewService::class)->create($this->user, $course, 4, 'Review user pertama.');
        $otherUser = User::factory()->create();
        $otherUser->assignRole('user');
        $this->enroll($otherUser, $course);

        $this->expectException(ValidationException::class);

        app(CourseReviewService::class)->update($otherUser, $review, 5, 'Tidak boleh edit review orang.');
    }

    public function test_mentor_cannot_edit_buyer_review_for_own_course(): void
    {
        $course = $this->courseFor($this->mentor);
        $this->enroll($this->user, $course);
        $review = app(CourseReviewService::class)->create($this->user, $course, 4, 'Review buyer untuk mentor.');

        $this->expectException(ValidationException::class);

        app(CourseReviewService::class)->update($this->mentor, $review, 5, 'Mentor tidak boleh edit buyer review.');
    }

    public function test_mentor_can_review_another_mentor_course_if_enrolled(): void
    {
        $seller = User::factory()->create();
        $seller->assignRole('mentor');
        $course = $this->courseFor($seller);
        $this->enroll($this->mentor, $course);

        $review = app(CourseReviewService::class)->create($this->mentor, $course, 5, 'Mentor juga bisa review kelas mentor lain.');

        $this->assertSame($this->mentor->id, $review->user_id);
    }

    public function test_admin_cannot_create_normal_public_review(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $course = $this->courseFor($this->mentor);
        $this->enroll($admin, $course);

        $this->expectException(ValidationException::class);

        app(CourseReviewService::class)->create($admin, $course, 5, 'Admin tidak boleh review publik.');
    }

    public function test_admin_can_hide_and_publish_review(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $course = $this->courseFor($this->mentor);
        $this->enroll($this->user, $course);
        $review = app(CourseReviewService::class)->create($this->user, $course, 5, 'Review untuk moderation admin.');

        $this->actingAs($admin);

        Livewire::test(ListCourseReviews::class)
            ->callTableAction('hide', $review);

        $this->assertFalse($review->refresh()->is_published);

        Livewire::test(ListCourseReviews::class)
            ->callTableAction('publish', $review);

        $this->assertTrue($review->refresh()->is_published);
    }

    public function test_hidden_review_does_not_appear_on_course_detail(): void
    {
        $course = $this->courseFor($this->mentor);
        $this->enroll($this->user, $course);
        $review = app(CourseReviewService::class)->create($this->user, $course, 5, 'Review ini akan disembunyikan.');
        app(CourseReviewService::class)->hide($review);

        $this->get(route('courses.show', $course))
            ->assertOk()
            ->assertDontSee('Review ini akan disembunyikan.')
            ->assertSee('Belum ada review yang dipublikasikan.');
    }

    public function test_user_editing_hidden_review_keeps_it_hidden_until_admin_publishes(): void
    {
        $course = $this->courseFor($this->mentor);
        $this->enroll($this->user, $course);
        $review = app(CourseReviewService::class)->create($this->user, $course, 5, 'Review awal yang akan di-hide.');

        app(CourseReviewService::class)->hide($review);
        app(CourseReviewService::class)->update($this->user, $review->refresh(), 4, 'Review hidden diedit pemiliknya.');

        $review->refresh();

        $this->assertFalse($review->is_published);
        $this->assertNotNull($review->edited_at);

        $this->get(route('courses.show', $course))
            ->assertOk()
            ->assertDontSee('Review hidden diedit pemiliknya.')
            ->assertSee('Belum ada review yang dipublikasikan.');

        app(CourseReviewService::class)->publish($review);

        $this->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Review hidden diedit pemiliknya.')
            ->assertSee('Rating 4.0/5')
            ->assertSee('1 review');
    }

    public function test_course_detail_shows_average_and_count_from_published_reviews_only(): void
    {
        $course = $this->courseFor($this->mentor);
        $secondUser = User::factory()->create();
        $secondUser->assignRole('user');
        $hiddenUser = User::factory()->create();
        $hiddenUser->assignRole('user');
        $deletedUser = User::factory()->create();
        $deletedUser->assignRole('user');

        foreach ([$this->user, $secondUser, $hiddenUser, $deletedUser] as $user) {
            $this->enroll($user, $course);
        }

        app(CourseReviewService::class)->create($this->user, $course, 5, 'Review published pertama.');
        app(CourseReviewService::class)->create($secondUser, $course, 3, 'Review published kedua.');
        $hiddenReview = app(CourseReviewService::class)->create($hiddenUser, $course, 1, 'Review hidden tidak dihitung.');
        $deletedReview = app(CourseReviewService::class)->create($deletedUser, $course, 5, 'Review deleted tidak dihitung.');
        app(CourseReviewService::class)->hide($hiddenReview);
        $deletedReview->delete();

        $this->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Rating 4.0/5')
            ->assertSee('2 review')
            ->assertSee('Review published pertama.')
            ->assertSee('Review published kedua.')
            ->assertDontSee('Review hidden tidak dihitung.')
            ->assertDontSee('Review deleted tidak dihitung.');
    }

    public function test_review_form_appears_on_learning_page_for_enrolled_user(): void
    {
        $course = $this->courseFor($this->mentor);
        $this->enroll($this->user, $course);

        $this->actingAs($this->user)
            ->get(route('student.learn', $course))
            ->assertOk()
            ->assertSee('Review kelas')
            ->assertSee('Kirim review');
    }

    public function test_review_form_does_not_appear_for_non_enrolled_user(): void
    {
        $course = $this->courseFor($this->mentor);

        $this->actingAs($this->user)
            ->get(route('student.learn', $course))
            ->assertForbidden()
            ->assertDontSee('Review kelas');
    }

    public function test_learning_page_review_form_can_create_and_edit_review(): void
    {
        $course = $this->courseFor($this->mentor);
        $this->enroll($this->user, $course);

        $this->actingAs($this->user);

        Volt::test('pages.student.learning-page', ['course' => $course])
            ->set('reviewRating', 4)
            ->set('reviewComment', 'Review dibuat dari learning page.')
            ->call('saveReview')
            ->assertHasNoErrors();

        $review = CourseReview::firstOrFail();
        $this->assertSame(4, $review->rating);

        Volt::test('pages.student.learning-page', ['course' => $course])
            ->set('reviewRating', 5)
            ->set('reviewComment', 'Review diedit dari learning page.')
            ->call('saveReview')
            ->assertHasNoErrors();

        $review->refresh();
        $this->assertSame(5, $review->rating);
        $this->assertNotNull($review->edited_at);
        $this->assertDatabaseCount('course_reviews', 1);
    }

    private function courseFor(User $mentor, array $overrides = []): Course
    {
        return Course::create(array_merge([
            'mentor_id' => $mentor->id,
            'category_id' => $this->category->id,
            'title' => 'Review Course '.str()->random(6),
            'slug' => 'review-course-'.str()->random(8),
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
}
