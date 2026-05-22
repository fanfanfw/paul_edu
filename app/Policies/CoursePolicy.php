<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Course $course): bool
    {
        return $course->status === CourseStatus::Published;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('mentor');
    }

    public function update(User $user, Course $course): bool
    {
        return $user->hasRole('mentor') && $course->mentor_id === $user->id;
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }
}
