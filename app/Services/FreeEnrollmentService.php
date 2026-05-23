<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FreeEnrollmentService
{
    public function enroll(User $user, Course $course): Order
    {
        $this->validateEnrollment($user, $course);

        return DB::transaction(function () use ($user, $course): Order {
            $course = Course::query()->lockForUpdate()->findOrFail($course->id);

            $this->validateEnrollment($user, $course);

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'course_id' => $course->id,
                'mentor_id' => $course->mentor_id,
                'course_title_snapshot' => $course->title,
                'course_price_snapshot' => 0,
                'commission_rate_snapshot' => 60,
                'mentor_amount' => 0,
                'platform_amount' => 0,
                'total_amount' => 0,
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'course_id' => $course->id,
                'course_title_snapshot' => $course->title,
                'price_snapshot' => 0,
                'quantity' => 1,
                'subtotal' => 0,
            ]);

            Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'order_id' => $order->id,
                'status' => EnrollmentStatus::Active,
                'enrolled_at' => now(),
            ]);

            return $order;
        });
    }

    private function validateEnrollment(User $user, Course $course): void
    {
        if ($user->status !== UserStatus::Active) {
            throw ValidationException::withMessages(['course' => 'Akun tidak aktif.']);
        }

        if ($user->hasRole('admin') || ! $user->hasAnyRole(['user', 'mentor'])) {
            throw ValidationException::withMessages(['course' => 'Role ini tidak dapat enroll kelas.']);
        }

        if ($course->status !== CourseStatus::Published) {
            throw ValidationException::withMessages(['course' => 'Kelas belum tersedia untuk enrollment.']);
        }

        if ((int) $course->price !== 0) {
            throw ValidationException::withMessages(['course' => 'Enrollment gratis hanya tersedia untuk kelas gratis.']);
        }

        if ($course->mentor_id === $user->id) {
            throw ValidationException::withMessages(['course' => 'Anda tidak dapat enroll kelas milik sendiri.']);
        }

        if (Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->exists()) {
            throw ValidationException::withMessages(['course' => 'Anda sudah enroll kelas ini.']);
        }
    }

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::substr((string) Str::ulid(), 0, 10));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}
