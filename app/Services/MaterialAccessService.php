<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\UserStatus;
use App\Models\CourseMaterial;
use App\Models\Enrollment;
use App\Models\User;

class MaterialAccessService
{
    public function canAccessMaterial(User $user, CourseMaterial $material): bool
    {
        if ($user->status !== UserStatus::Active) {
            return false;
        }

        if ($material->status !== 'active') {
            return false;
        }

        $course = $material->course;

        if (! $course) {
            return false;
        }

        if (! in_array($course->status, [CourseStatus::Published, CourseStatus::Archived], true)) {
            return false;
        }

        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', EnrollmentStatus::Active)
            ->exists();
    }
}
