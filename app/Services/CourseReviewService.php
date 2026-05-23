<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CourseReviewService
{
    public function create(User $user, Course $course, int $rating, string $comment): CourseReview
    {
        $this->validateCanReview($user, $course, false);
        $this->validatePayload($rating, $comment);

        if (CourseReview::where('user_id', $user->id)->where('course_id', $course->id)->exists()) {
            throw ValidationException::withMessages(['review' => 'Anda sudah memberikan review untuk kelas ini.']);
        }

        return CourseReview::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'rating' => $rating,
            'comment' => $comment,
            'is_published' => true,
        ]);
    }

    public function update(User $user, CourseReview $review, int $rating, string $comment): CourseReview
    {
        if ($review->user_id !== $user->id) {
            throw ValidationException::withMessages(['review' => 'Anda tidak dapat mengedit review orang lain.']);
        }

        $this->validateCanReview($user, $review->course, true);
        $this->validatePayload($rating, $comment);

        $review->update([
            'rating' => $rating,
            'comment' => $comment,
            'edited_at' => now(),
        ]);

        return $review;
    }

    public function hide(CourseReview $review): CourseReview
    {
        $review->update(['is_published' => false]);

        return $review;
    }

    public function publish(CourseReview $review): CourseReview
    {
        $review->update(['is_published' => true]);

        return $review;
    }

    private function validateCanReview(User $user, Course $course, bool $allowArchived): void
    {
        if ($user->hasRole('admin') || ! $user->hasAnyRole(['user', 'mentor'])) {
            throw ValidationException::withMessages(['review' => 'Role ini tidak dapat membuat review publik.']);
        }

        $allowedStatuses = $allowArchived
            ? [CourseStatus::Published, CourseStatus::Archived]
            : [CourseStatus::Published];

        if (! in_array($course->status, $allowedStatuses, true)) {
            throw ValidationException::withMessages(['review' => 'Review tidak tersedia untuk status kelas ini.']);
        }

        $hasEnrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', EnrollmentStatus::Active)
            ->exists();

        if (! $hasEnrollment) {
            throw ValidationException::withMessages(['review' => 'Anda harus enroll aktif sebelum memberi review.']);
        }
    }

    private function validatePayload(int $rating, string $comment): void
    {
        if ($rating < 1 || $rating > 5) {
            throw ValidationException::withMessages(['rating' => 'Rating harus antara 1 sampai 5.']);
        }

        $length = mb_strlen($comment);

        if ($length < 10) {
            throw ValidationException::withMessages(['comment' => 'Komentar minimal 10 karakter.']);
        }

        if ($length > 2000) {
            throw ValidationException::withMessages(['comment' => 'Komentar maksimal 2000 karakter.']);
        }
    }
}
