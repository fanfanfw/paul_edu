<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Models\Course;

class CourseStatusService
{
    public function publish(Course $course): Course
    {
        $course->update([
            'status' => CourseStatus::Published,
            'published_at' => $course->published_at ?? now(),
        ]);

        return $course;
    }

    public function archive(Course $course): Course
    {
        $course->update([
            'status' => CourseStatus::Archived,
            'archived_at' => now(),
        ]);

        return $course;
    }

    public function markDeletedByMentor(Course $course): Course
    {
        $course->update([
            'status' => CourseStatus::DeletedByMentor,
            'deleted_by_mentor_at' => now(),
        ]);

        return $course;
    }

    public function hideByAdmin(Course $course): Course
    {
        $course->update([
            'status' => CourseStatus::HiddenByAdmin,
            'hidden_by_admin_at' => now(),
        ]);

        return $course;
    }
}
