<?php

namespace App\Http\Controllers;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('admin');
        $isMentor = $user->hasRole('mentor');
        $wallet = $isAdmin ? null : $user->wallet()->first();

        return view('dashboard', array_merge([
            'isAdmin' => $isAdmin,
            'isMentor' => $isMentor,
            'walletBalance' => $wallet?->balance ?? 0,
            'recentTransactions' => $wallet ? $wallet->transactions()->latest()->limit(4)->get() : collect(),
        ], match (true) {
            $isAdmin => [],
            $isMentor => $this->mentorData($user->id),
            default => $this->studentData($user->id),
        }));
    }

    private function mentorData(int $mentorId): array
    {
        $courseQuery = Course::where('mentor_id', $mentorId);
        $courseCount = (clone $courseQuery)->count();
        $chartMonths = $this->mentorRevenueMonths($mentorId);

        return [
            'courseCount' => $courseCount,
            'publishedCount' => (clone $courseQuery)->where('status', CourseStatus::Published)->count(),
            'draftCount' => (clone $courseQuery)->where('status', CourseStatus::Draft)->count(),
            'archivedCount' => (clone $courseQuery)->where('status', CourseStatus::Archived)->count(),
            'totalSales' => Order::where('mentor_id', $mentorId)->count(),
            'totalRevenue' => Order::where('mentor_id', $mentorId)->sum('mentor_amount'),
            'monthRevenue' => Order::where('mentor_id', $mentorId)
                ->where('paid_at', '>=', now()->startOfMonth())
                ->sum('mentor_amount'),
            'recentOrders' => Order::with('user')
                ->where('mentor_id', $mentorId)
                ->latest('paid_at')
                ->latest()
                ->limit(4)
                ->get(),
            'chartMonths' => $chartMonths,
            'maxChartAmount' => max((int) $chartMonths->max('amount'), 1),
            'topCourses' => Order::where('mentor_id', $mentorId)
                ->get(['course_title_snapshot', 'mentor_amount'])
                ->groupBy('course_title_snapshot')
                ->map(fn (Collection $orders, string $title): array => [
                    'title' => $title,
                    'sales' => $orders->count(),
                    'revenue' => $orders->sum('mentor_amount'),
                ])
                ->sortByDesc('revenue')
                ->take(3)
                ->values(),
        ];
    }

    private function studentData(int $userId): array
    {
        return [
            'activeEnrollmentCount' => Enrollment::where('user_id', $userId)
                ->where('status', EnrollmentStatus::Active)
                ->count(),
            'activeEnrollments' => Enrollment::with(['course.mentor', 'course.category'])
                ->where('user_id', $userId)
                ->where('status', EnrollmentStatus::Active)
                ->latest('enrolled_at')
                ->limit(3)
                ->get(),
            'totalSpent' => Order::where('user_id', $userId)->sum('total_amount'),
            'orderCount' => Order::where('user_id', $userId)->count(),
            'recommendedCourses' => Course::with(['mentor', 'category'])
                ->where('status', CourseStatus::Published)
                ->whereDoesntHave('enrollments', fn ($query) => $query
                    ->where('user_id', $userId)
                    ->where('status', EnrollmentStatus::Active))
                ->latest('published_at')
                ->latest()
                ->limit(3)
                ->get(),
        ];
    }

    private function mentorRevenueMonths(int $mentorId): Collection
    {
        $orders = Order::where('mentor_id', $mentorId)
            ->where('paid_at', '>=', now()->subMonths(5)->startOfMonth())
            ->get(['mentor_amount', 'paid_at']);

        return collect(range(5, 0))->map(function (int $monthsBack) use ($orders): array {
            $date = now()->subMonths($monthsBack);

            return [
                'label' => $date->format('M'),
                'amount' => $orders
                    ->filter(fn (Order $order): bool => $order->paid_at?->format('Y-m') === $date->format('Y-m'))
                    ->sum('mentor_amount'),
            ];
        });
    }
}
