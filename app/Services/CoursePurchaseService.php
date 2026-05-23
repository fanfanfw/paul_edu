<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserStatus;
use App\Enums\WalletTransactionType;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CoursePurchaseService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly CommissionService $commissionService,
        private readonly FreeEnrollmentService $freeEnrollmentService,
    ) {}

    public function purchase(User $buyer, Course $course): Order
    {
        $this->validatePurchase($buyer, $course);

        if ((int) $course->price === 0) {
            return $this->freeEnrollmentService->enroll($buyer, $course);
        }

        return DB::transaction(function () use ($buyer, $course): Order {
            $course = Course::with('mentor')->lockForUpdate()->findOrFail($course->id);

            $this->validatePurchase($buyer, $course);

            $price = (int) $course->price;
            $buyerWallet = Wallet::where('owner_type', 'user')->where('owner_id', $buyer->id)->lockForUpdate()->first();

            if (! $buyerWallet || $buyerWallet->balance < $price) {
                throw ValidationException::withMessages(['wallet' => 'Saldo tidak mencukupi.']);
            }

            $commissionRate = $this->commissionService->getCurrentMentorCommissionRate();
            $split = $this->commissionService->calculateSplit($price, $commissionRate);

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $buyer->id,
                'course_id' => $course->id,
                'mentor_id' => $course->mentor_id,
                'course_title_snapshot' => $course->title,
                'course_price_snapshot' => $price,
                'commission_rate_snapshot' => $commissionRate,
                'mentor_amount' => $split['mentor_amount'],
                'platform_amount' => $split['platform_amount'],
                'total_amount' => $price,
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'course_id' => $course->id,
                'course_title_snapshot' => $course->title,
                'price_snapshot' => $price,
                'quantity' => 1,
                'subtotal' => $price,
            ]);

            $metadata = [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'commission_rate_snapshot' => $commissionRate,
            ];

            $this->walletService->debit($buyerWallet, $price, WalletTransactionType::CoursePurchase, $order, 'Pembelian kelas '.$course->title, $metadata);

            if ($split['mentor_amount'] > 0) {
                $mentorWallet = $this->walletService->getOrCreateUserWallet($course->mentor);
                $this->walletService->credit($mentorWallet, $split['mentor_amount'], WalletTransactionType::CourseSaleIncome, $order, 'Pendapatan kelas '.$course->title, array_merge($metadata, [
                    'buyer_id' => $buyer->id,
                    'buyer_name' => $buyer->name,
                ]));
            }

            if ($split['platform_amount'] > 0) {
                $platformWallet = $this->walletService->getOrCreatePlatformWallet();
                $this->walletService->credit($platformWallet, $split['platform_amount'], WalletTransactionType::PlatformFee, $order, 'Platform fee dari kelas '.$course->title, array_merge($metadata, [
                    'buyer_id' => $buyer->id,
                    'mentor_id' => $course->mentor_id,
                ]));
            }

            Enrollment::create([
                'user_id' => $buyer->id,
                'course_id' => $course->id,
                'order_id' => $order->id,
                'status' => EnrollmentStatus::Active,
                'enrolled_at' => now(),
            ]);

            return $order;
        });
    }

    private function validatePurchase(User $buyer, Course $course): void
    {
        if ($buyer->status !== UserStatus::Active) {
            throw ValidationException::withMessages(['course' => 'Akun tidak aktif.']);
        }

        if ($buyer->hasRole('admin') || ! $buyer->hasAnyRole(['user', 'mentor'])) {
            throw ValidationException::withMessages(['course' => 'Role ini tidak dapat membeli atau enroll kelas.']);
        }

        if ($course->status !== CourseStatus::Published) {
            throw ValidationException::withMessages(['course' => 'Kelas belum tersedia untuk dibeli.']);
        }

        if ($course->mentor_id === $buyer->id) {
            throw ValidationException::withMessages(['course' => 'Anda tidak dapat membeli kelas milik sendiri.']);
        }

        if (Enrollment::where('user_id', $buyer->id)->where('course_id', $course->id)->where('status', EnrollmentStatus::Active)->exists()) {
            throw ValidationException::withMessages(['course' => 'Anda sudah memiliki kelas ini.']);
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
