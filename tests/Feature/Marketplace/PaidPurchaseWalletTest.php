<?php

namespace Tests\Feature\Marketplace;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionType;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\WalletResource\Pages\ListWallets;
use App\Filament\Resources\WalletTransactionResource\Pages\ListWalletTransactions;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\CommissionService;
use App\Services\CoursePurchaseService;
use App\Services\WalletService;
use Database\Seeders\CourseCategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use RuntimeException;
use Tests\TestCase;

class PaidPurchaseWalletTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;
    private User $mentor;
    private CourseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CourseCategorySeeder::class);

        $this->buyer = User::factory()->create(['name' => 'Buyer User']);
        $this->buyer->assignRole('user');

        $this->mentor = User::factory()->create(['name' => 'Mentor Seller']);
        $this->mentor->assignRole('mentor');

        $this->category = CourseCategory::firstOrFail();
    }

    public function test_user_can_dummy_topup_and_balance_increases(): void
    {
        $this->actingAs($this->buyer);

        Volt::test('pages.student.wallet-page')
            ->set('amount', 100000)
            ->call('topup')
            ->assertHasNoErrors();

        $wallet = Wallet::where('owner_type', 'user')->where('owner_id', $this->buyer->id)->firstOrFail();

        $this->assertSame(100000, $wallet->balance);
    }

    public function test_topup_creates_wallet_transaction_with_before_and_after_balance(): void
    {
        $wallet = Wallet::create([
            'owner_type' => 'user',
            'owner_id' => $this->buyer->id,
            'balance' => 50000,
            'currency' => 'IDR',
            'status' => 'active',
        ]);

        $this->actingAs($this->buyer);

        Volt::test('pages.student.wallet-page')
            ->set('amount', 75000)
            ->call('topup');

        $transaction = WalletTransaction::firstOrFail();

        $this->assertSame($wallet->id, $transaction->wallet_id);
        $this->assertSame(WalletTransactionType::Topup, $transaction->type);
        $this->assertSame(WalletTransactionDirection::Credit, $transaction->direction);
        $this->assertSame(75000, $transaction->amount);
        $this->assertSame(50000, $transaction->balance_before);
        $this->assertSame(125000, $transaction->balance_after);
    }

    public function test_invalid_topup_amount_rejected(): void
    {
        $this->actingAs($this->buyer);

        Volt::test('pages.student.wallet-page')
            ->set('amount', 9999)
            ->call('topup')
            ->assertHasErrors(['amount']);

        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_user_can_buy_paid_published_course_with_enough_balance(): void
    {
        app(CommissionService::class)->setMentorCommissionRate(60);
        $course = $this->courseFor($this->mentor, ['price' => 100000]);
        $this->walletFor($this->buyer, 150000);

        $order = app(CoursePurchaseService::class)->purchase($this->buyer, $course);

        $this->assertSame(100000, $order->total_amount);
        $this->assertSame(60000, $order->mentor_amount);
        $this->assertSame(40000, $order->platform_amount);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $this->buyer->id,
            'course_id' => $course->id,
            'status' => EnrollmentStatus::Active->value,
        ]);
    }

    public function test_paid_purchase_creates_order_order_item_and_enrollment(): void
    {
        $course = $this->courseFor($this->mentor, ['price' => 120000]);
        $this->walletFor($this->buyer, 200000);

        $order = app(CoursePurchaseService::class)->purchase($this->buyer, $course);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'course_id' => $course->id,
            'user_id' => $this->buyer->id,
            'total_amount' => 120000,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'course_id' => $course->id,
            'price_snapshot' => 120000,
            'subtotal' => 120000,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'order_id' => $order->id,
            'course_id' => $course->id,
            'user_id' => $this->buyer->id,
        ]);
    }

    public function test_paid_purchase_updates_buyer_mentor_and_platform_wallets_and_ledgers(): void
    {
        app(CommissionService::class)->setMentorCommissionRate(60);
        $course = $this->courseFor($this->mentor, ['price' => 100000]);
        $buyerWallet = $this->walletFor($this->buyer, 150000);

        $order = app(CoursePurchaseService::class)->purchase($this->buyer, $course);

        $mentorWallet = Wallet::where('owner_type', 'user')->where('owner_id', $this->mentor->id)->firstOrFail();
        $platformWallet = Wallet::where('owner_type', 'platform')->where('owner_id', 0)->firstOrFail();

        $this->assertSame(50000, $buyerWallet->refresh()->balance);
        $this->assertSame(60000, $mentorWallet->balance);
        $this->assertSame(40000, $platformWallet->balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $buyerWallet->id,
            'type' => WalletTransactionType::CoursePurchase->value,
            'direction' => WalletTransactionDirection::Debit->value,
            'amount' => 100000,
            'balance_before' => 150000,
            'balance_after' => 50000,
            'reference_type' => $order->getMorphClass(),
            'reference_id' => $order->id,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $mentorWallet->id,
            'type' => WalletTransactionType::CourseSaleIncome->value,
            'direction' => WalletTransactionDirection::Credit->value,
            'amount' => 60000,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $platformWallet->id,
            'type' => WalletTransactionType::PlatformFee->value,
            'direction' => WalletTransactionDirection::Credit->value,
            'amount' => 40000,
        ]);
    }

    public function test_paid_purchase_stores_price_and_commission_snapshots(): void
    {
        app(CommissionService::class)->setMentorCommissionRate(75);
        $course = $this->courseFor($this->mentor, ['price' => 200000]);
        $this->walletFor($this->buyer, 250000);

        $order = app(CoursePurchaseService::class)->purchase($this->buyer, $course);

        $this->assertSame(200000, $order->course_price_snapshot);
        $this->assertSame(75, $order->commission_rate_snapshot);
        $this->assertSame(150000, $order->mentor_amount);
        $this->assertSame(50000, $order->platform_amount);
    }

    public function test_old_order_snapshots_remain_unchanged_after_course_price_and_commission_changes(): void
    {
        app(CommissionService::class)->setMentorCommissionRate(60);
        $course = $this->courseFor($this->mentor, ['price' => 100000]);
        $this->walletFor($this->buyer, 300000);

        $oldOrder = app(CoursePurchaseService::class)->purchase($this->buyer, $course);

        $secondBuyer = User::factory()->create();
        $secondBuyer->assignRole('user');
        $this->walletFor($secondBuyer, 300000);

        $course->update(['price' => 150000]);
        app(CommissionService::class)->setMentorCommissionRate(80);

        $newOrder = app(CoursePurchaseService::class)->purchase($secondBuyer, $course->fresh());

        $this->assertSame(100000, $oldOrder->refresh()->course_price_snapshot);
        $this->assertSame(60, $oldOrder->commission_rate_snapshot);
        $this->assertSame(150000, $newOrder->course_price_snapshot);
        $this->assertSame(80, $newOrder->commission_rate_snapshot);
    }

    public function test_user_cannot_buy_paid_course_with_insufficient_balance_and_no_partial_writes(): void
    {
        $course = $this->courseFor($this->mentor, ['price' => 100000]);
        $this->walletFor($this->buyer, 50000);

        try {
            app(CoursePurchaseService::class)->purchase($this->buyer, $course);
            $this->fail('Expected validation exception.');
        } catch (ValidationException $exception) {
            $this->assertSame('Saldo tidak mencukupi.', collect($exception->errors())->flatten()->first());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_user_cannot_buy_same_course_twice(): void
    {
        $course = $this->courseFor($this->mentor, ['price' => 50000]);
        $this->walletFor($this->buyer, 200000);

        app(CoursePurchaseService::class)->purchase($this->buyer, $course);

        $this->expectException(ValidationException::class);
        app(CoursePurchaseService::class)->purchase($this->buyer, $course);
    }

    public function test_mentor_cannot_buy_own_course(): void
    {
        $course = $this->courseFor($this->mentor, ['price' => 50000]);
        $this->walletFor($this->mentor, 100000);

        $this->expectException(ValidationException::class);
        app(CoursePurchaseService::class)->purchase($this->mentor, $course);
    }

    public function test_user_cannot_buy_unpublished_archived_deleted_or_hidden_course(): void
    {
        $this->walletFor($this->buyer, 500000);

        foreach ([CourseStatus::Draft, CourseStatus::Archived, CourseStatus::DeletedByMentor, CourseStatus::HiddenByAdmin] as $status) {
            try {
                app(CoursePurchaseService::class)->purchase($this->buyer, $this->courseFor($this->mentor, ['status' => $status, 'price' => 50000]));
                $this->fail('Expected validation exception for '.$status->value);
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_user_can_see_only_own_wallet_transactions(): void
    {
        $walletService = app(WalletService::class);
        $otherUser = User::factory()->create();
        $otherUser->assignRole('user');

        DB::transaction(function () use ($walletService, $otherUser): void {
            $walletService->credit($walletService->getOrCreateUserWallet($this->buyer), 50000, WalletTransactionType::Topup, null, 'Own topup');
            $walletService->credit($walletService->getOrCreateUserWallet($otherUser), 50000, WalletTransactionType::Topup, null, 'Other topup');
        });

        $this->actingAs($this->buyer)
            ->get(route('transactions'))
            ->assertOk()
            ->assertSee('Own topup')
            ->assertDontSee('Other topup');
    }

    public function test_mentor_wallet_income_appears_after_sale(): void
    {
        $course = $this->courseFor($this->mentor, ['price' => 100000]);
        $this->walletFor($this->buyer, 150000);

        app(CoursePurchaseService::class)->purchase($this->buyer, $course);

        $this->actingAs($this->mentor)
            ->get(route('mentor.wallet'))
            ->assertOk()
            ->assertSee('Pendapatan kelas')
            ->assertSee('60.000');
    }

    public function test_platform_wallet_receives_platform_fee(): void
    {
        $course = $this->courseFor($this->mentor, ['price' => 100000]);
        $this->walletFor($this->buyer, 150000);

        app(CoursePurchaseService::class)->purchase($this->buyer, $course);

        $platformWallet = Wallet::where('owner_type', 'platform')->where('owner_id', 0)->firstOrFail();

        $this->assertSame(40000, $platformWallet->balance);
    }

    public function test_commission_split_calculation_works_for_edge_rates(): void
    {
        $service = app(CommissionService::class);

        $this->assertSame(['mentor_amount' => 0, 'platform_amount' => 100000], $service->calculateSplit(100000, 0));
        $this->assertSame(['mentor_amount' => 60000, 'platform_amount' => 40000], $service->calculateSplit(100000, 60));
        $this->assertSame(['mentor_amount' => 100000, 'platform_amount' => 0], $service->calculateSplit(100000, 100));

        $this->expectException(InvalidArgumentException::class);
        $service->calculateSplit(100000, 101);
    }

    public function test_wallet_service_prevents_negative_balance(): void
    {
        $wallet = $this->walletFor($this->buyer, 10000);

        $this->expectException(RuntimeException::class);

        DB::transaction(fn () => app(WalletService::class)->debit($wallet, 20000, WalletTransactionType::CoursePurchase));
    }

    public function test_course_detail_paid_cta_purchases_and_redirects_to_learning_page(): void
    {
        $course = $this->courseFor($this->mentor, ['price' => 100000]);
        $this->walletFor($this->buyer, 150000);

        $this->actingAs($this->buyer);

        Volt::test('pages.public.course-detail', ['course' => $course])
            ->call('purchase')
            ->assertRedirect(route('student.learn', $course));

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->buyer->id,
            'course_id' => $course->id,
            'total_amount' => 100000,
        ]);
    }

    public function test_course_detail_paid_cta_shows_error_for_insufficient_balance(): void
    {
        $course = $this->courseFor($this->mentor, ['price' => 100000]);
        $this->walletFor($this->buyer, 10000);

        $this->actingAs($this->buyer);
        Volt::test('pages.public.course-detail', ['course' => $course])
            ->call('purchase')
            ->assertHasErrors(['purchase']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_filament_order_wallet_and_transaction_resources_are_admin_accessible_and_read_only(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $course = $this->courseFor($this->mentor, ['price' => 100000]);
        $this->walletFor($this->buyer, 150000);
        $order = app(CoursePurchaseService::class)->purchase($this->buyer, $course);
        $wallet = Wallet::where('owner_type', 'user')->where('owner_id', $this->buyer->id)->firstOrFail();
        $transaction = WalletTransaction::firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/orders')
            ->assertOk();
        $this->actingAs($admin)
            ->get('/admin/wallets')
            ->assertOk();
        $this->actingAs($admin)
            ->get('/admin/wallet-transactions')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/orders/'.$order->id.'/edit')
            ->assertNotFound();
        $this->actingAs($admin)
            ->get('/admin/wallets/'.$wallet->id.'/edit')
            ->assertNotFound();
        $this->actingAs($admin)
            ->get('/admin/wallet-transactions/'.$transaction->id.'/edit')
            ->assertNotFound();

        Livewire::actingAs($admin)
            ->test(ListOrders::class)
            ->assertTableActionExists('view')
            ->assertTableActionDoesNotExist('edit')
            ->assertTableActionDoesNotExist('delete');
        Livewire::actingAs($admin)
            ->test(ListWallets::class)
            ->assertTableActionExists('view')
            ->assertTableActionDoesNotExist('edit')
            ->assertTableActionDoesNotExist('delete');
        Livewire::actingAs($admin)
            ->test(ListWalletTransactions::class)
            ->assertTableActionExists('view')
            ->assertTableActionDoesNotExist('edit')
            ->assertTableActionDoesNotExist('delete');
    }

    private function courseFor(User $mentor, array $overrides = []): Course
    {
        return Course::create(array_merge([
            'mentor_id' => $mentor->id,
            'category_id' => $this->category->id,
            'title' => 'Paid Course '.str()->random(6),
            'slug' => 'paid-course-'.str()->random(8),
            'short_description' => 'Short description',
            'description' => 'Long description',
            'price' => 100000,
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ], $overrides));
    }

    private function walletFor(User $user, int $balance): Wallet
    {
        return Wallet::updateOrCreate(
            ['owner_type' => 'user', 'owner_id' => $user->id],
            ['balance' => $balance, 'currency' => 'IDR', 'status' => 'active']
        );
    }
}
