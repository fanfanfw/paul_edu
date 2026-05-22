# DESIGN.md — Technical & UI/UX Design Document
# Platform Digital Marketplace Kelas Skill Digital

## 1. Tujuan Dokumen

Dokumen ini menjelaskan desain teknis, arsitektur aplikasi, struktur database, flow transaksi, struktur kode, Docker setup, dan arahan UI/UX untuk implementasi platform marketplace kelas digital menggunakan Laravel 12.

Dokumen ini ditulis agar AI agent dapat mengimplementasikan aplikasi secara konsisten tanpa membuat asumsi sendiri.

---

## 2. Prinsip Desain Teknis

1. Gunakan modular monolith, bukan microservices.
2. Gunakan Laravel convention sebanyak mungkin.
3. Controller harus tipis.
4. Business logic utama ditempatkan pada Service/Action class.
5. Semua perubahan saldo harus melalui WalletService.
6. Semua pembelian kelas harus melalui CoursePurchaseService.
7. Gunakan database transaction untuk operasi keuangan.
8. Jangan hard delete data penting seperti course, user, order, wallet transaction.
9. Simpan snapshot harga dan komisi pada order.
10. File video/PDF harus private.
11. Gunakan policy/gate untuk akses materi.
12. Admin panel menggunakan Filament.
13. Frontend user/mentor menggunakan Blade + Livewire.
14. Deployment harus bisa berjalan dengan Docker Compose.

---

## 3. High-Level Architecture

```text
Browser
  |
  | HTTP/HTTPS
  v
Nginx Container
  |
  v
Laravel App Container (PHP-FPM)
  |
  |-- PostgreSQL Container
  |-- Redis Container optional
  |-- Local Storage Volume
  |-- Queue Worker Container
  |-- Scheduler Container
```

### 3.1 Application Areas

```text
Public Area
- Landing page
- Course catalog
- Course detail
- Mentor profile

Auth Area
- Login
- Register with role selection
- Forgot password

Student/User Area
- Dashboard
- My Courses
- Wallet
- Topup
- Transactions
- Learning page
- Review

Mentor Area
- Mentor dashboard
- Course management
- Material management
- Sales history
- Wallet history

Admin Area
- Filament panel
- User management
- Mentor management
- Course management
- Category management
- Order management
- Wallet transaction report
- Commission setting
- Review moderation
```

---

## 4. Recommended Laravel Packages

### 4.1 Required

```bash
composer require laravel/breeze --dev
php artisan breeze:install livewire
composer require filament/filament
composer require spatie/laravel-permission
```

Auth implementation note:

1. Use Breeze Livewire stack or the equivalent official Laravel Livewire starter kit.
2. Do not build a custom auth system unless the starter kit cannot be installed.
3. Email verification is not required for MVP.

### 4.2 Optional but Recommended

```bash
composer require doctrine/dbal
```

Optional package use cases:

1. `doctrine/dbal` may help with some schema changes.
2. Image processing package can be added later if thumbnails need resizing.

---

## 5. Suggested Folder Structure

```text
app/
  Actions/
    Auth/
    Course/
    Mentor/
    Wallet/
    Order/
    Review/

  Services/
    WalletService.php
    CoursePurchaseService.php
    CommissionService.php
    MaterialAccessService.php
    CourseStatusService.php

  Enums/
    UserStatus.php
    CourseStatus.php
    WalletTransactionType.php
    WalletTransactionDirection.php
    WalletTransactionStatus.php
    OrderStatus.php
    MaterialType.php
    EnrollmentStatus.php

  Models/
    User.php
    MentorProfile.php
    Course.php
    CourseCategory.php
    CourseSection.php
    CourseLesson.php
    CourseMaterial.php
    Wallet.php
    WalletTransaction.php
    Order.php
    OrderItem.php
    Enrollment.php
    CourseReview.php
    PlatformSetting.php

  Policies/
    CoursePolicy.php
    CourseMaterialPolicy.php
    MentorProfilePolicy.php
    WalletPolicy.php
    ReviewPolicy.php

  Livewire/
    Public/
      LandingPage.php
      CourseCatalog.php
      CourseDetail.php

    Student/
      Dashboard.php
      MyCourses.php
      WalletPage.php
      TopupForm.php
      TransactionHistory.php
      LearningPage.php
      ReviewForm.php

    Mentor/
      Dashboard.php
      CourseIndex.php
      CourseForm.php
      CourseMaterialManager.php
      SalesHistory.php
      WalletHistory.php

  Filament/
    Resources/
      UserResource/
      MentorProfileResource/
      CourseResource/
      CourseCategoryResource/
      OrderResource/
      WalletResource/
      WalletTransactionResource/
      PlatformSettingResource/
      CourseReviewResource/

database/
  migrations/
  seeders/
    DatabaseSeeder.php
    RolePermissionSeeder.php
    UserSeeder.php
    PlatformSettingSeeder.php
    PlatformWalletSeeder.php
    CourseCategorySeeder.php
    DemoCourseSeeder.php

routes/
  web.php
  auth.php
  console.php
```

---

## 6. Database Design

## 6.1 users

Purpose: menyimpan akun admin, mentor, dan user.

Columns:

```text
id bigserial primary key
name varchar(255)
email varchar(255) unique
email_verified_at timestamp nullable
password varchar(255)
status varchar(50) default 'active'
last_login_at timestamp nullable
remember_token varchar(100) nullable
created_at timestamp
updated_at timestamp
deleted_at timestamp nullable
```

Status values:

```text
active
suspended
deleted
```

Notes:

1. Gunakan SoftDeletes.
2. Role tidak disimpan langsung di users jika memakai Spatie.
3. Role dikelola oleh tabel bawaan Spatie.
4. `email_verified_at` boleh tetap ada sebagai kolom default Laravel, tetapi email verification tidak wajib untuk MVP.

---

## 6.2 mentor_profiles

Purpose: data tambahan untuk mentor.

Columns:

```text
id bigserial primary key
user_id foreignId references users(id)
display_name varchar(255)
slug varchar(255) unique
bio text nullable
expertise varchar(255) nullable
avatar_path varchar(500) nullable
status varchar(50) default 'active'
created_at timestamp
updated_at timestamp
deleted_at timestamp nullable
```

Rules:

1. Satu mentor user punya satu mentor profile.
2. Mentor profile dibuat otomatis saat register sebagai mentor.
3. Mentor profile soft delete jika mentor dihapus.

---

## 6.3 course_categories

Columns:

```text
id bigserial primary key
name varchar(255)
slug varchar(255) unique
description text nullable
icon varchar(255) nullable
sort_order integer default 0
is_active boolean default true
created_at timestamp
updated_at timestamp
```

---

## 6.4 courses

Columns:

```text
id bigserial primary key
mentor_id foreignId references users(id)
category_id foreignId references course_categories(id)
title varchar(255)
slug varchar(255) unique
short_description varchar(500) nullable
description text nullable
price bigint default 0
thumbnail_path varchar(500) nullable
status varchar(50) default 'draft'
published_at timestamp nullable
archived_at timestamp nullable
deleted_by_mentor_at timestamp nullable
hidden_by_admin_at timestamp nullable
created_at timestamp
updated_at timestamp
deleted_at timestamp nullable
```

Status values:

```text
draft
published
archived
deleted_by_mentor
hidden_by_admin
```

Rules:

1. `price >= 0`.
2. `mentor_id` harus user dengan role mentor.
3. Jangan hard delete.
4. Gunakan status untuk archive/delete behavior.
5. `deleted_at` hanya untuk admin-level soft delete jika diperlukan.
6. `hidden_by_admin` dipakai untuk moderasi/admin takedown, bukan approval flow.

Indexes:

```text
index mentor_id
index category_id
index status
index slug
```

---

## 6.5 course_sections

Columns:

```text
id bigserial primary key
course_id foreignId references courses(id)
title varchar(255)
description text nullable
sort_order integer default 0
created_at timestamp
updated_at timestamp
```

Rules:

1. Section dipakai untuk mengelompokkan lesson.
2. Jika ingin MVP sangat sederhana, section tetap dibuat agar scalable.

---

## 6.6 course_lessons

Columns:

```text
id bigserial primary key
course_id foreignId references courses(id)
section_id foreignId nullable references course_sections(id)
title varchar(255)
description text nullable
sort_order integer default 0
is_preview boolean default false
created_at timestamp
updated_at timestamp
```

Rules:

1. Lesson adalah unit belajar.
2. Lesson dapat memiliki satu atau lebih materials.
3. `is_preview` optional untuk future feature.

---

## 6.7 course_materials

Columns:

```text
id bigserial primary key
course_id foreignId references courses(id)
lesson_id foreignId nullable references course_lessons(id)
title varchar(255)
description text nullable
type varchar(50)
file_path varchar(500)
original_filename varchar(255) nullable
mime_type varchar(255) nullable
file_size bigint nullable
sort_order integer default 0
status varchar(50) default 'active'
created_at timestamp
updated_at timestamp
deleted_at timestamp nullable
```

Type values:

```text
video
pdf
```

Status values:

```text
active
hidden
deleted
```

Rules:

1. File disimpan di private storage.
2. File tidak boleh diakses langsung dari public URL.
3. Gunakan route/controller untuk stream/view file.
4. Gunakan policy untuk authorization.
5. Soft delete material jika dihapus.

---

## 6.8 wallets

Purpose: menyimpan saldo saat ini.

Columns:

```text
id bigserial primary key
owner_type varchar(100)
owner_id bigint nullable
balance bigint default 0
currency varchar(10) default 'IDR'
status varchar(50) default 'active'
created_at timestamp
updated_at timestamp
```

Owner examples:

```text
owner_type = user, owner_id = users.id
owner_type = platform, owner_id = 0
```

Rules:

1. Setiap akun manusia punya satu wallet dengan `owner_type = user` dan `owner_id = users.id`.
2. Mentor memakai wallet akun yang sama untuk topup, membeli kelas mentor lain, dan menerima pendapatan.
3. Platform hanya punya satu wallet.
4. Balance tidak boleh negatif.
5. Wallet update hanya melalui WalletService.

Indexes:

```text
unique owner_type + owner_id
index owner_type
index owner_id
```

Implementation note:

Untuk platform wallet, gunakan `owner_type = 'platform'` dan `owner_id = 0`. Jangan gunakan `owner_id = null` agar unique index PostgreSQL tetap sederhana dan deterministik.

---

## 6.9 wallet_transactions

Purpose: ledger semua perubahan saldo.

Columns:

```text
id bigserial primary key
wallet_id foreignId references wallets(id)
owner_type varchar(100)
owner_id bigint nullable
type varchar(100)
direction varchar(20)
amount bigint
balance_before bigint
balance_after bigint
status varchar(50) default 'success'
reference_type varchar(100) nullable
reference_id bigint nullable
description text nullable
metadata jsonb nullable
created_at timestamp
updated_at timestamp
```

Type values:

```text
topup
course_purchase
course_sale_income
platform_fee
adjustment
```

Direction values:

```text
credit
debit
```

Status values:

```text
pending
success
failed
cancelled
```

Rules:

1. Ledger immutable.
2. Jangan edit wallet transaction setelah dibuat kecuali sangat diperlukan oleh admin untuk correction.
3. Untuk MVP, semua transaksi topup/purchase langsung success.
4. Simpan balance_before dan balance_after.
5. Gunakan `metadata` untuk snapshot tambahan.

Indexes:

```text
index wallet_id
index owner_type + owner_id
index type
index direction
index reference_type + reference_id
index created_at
```

---

## 6.10 orders

Columns:

```text
id bigserial primary key
order_number varchar(100) unique
user_id foreignId references users(id)
course_id foreignId references courses(id)
mentor_id foreignId references users(id)
course_title_snapshot varchar(255)
course_price_snapshot bigint default 0
commission_rate_snapshot integer default 60
mentor_amount bigint default 0
platform_amount bigint default 0
total_amount bigint default 0
status varchar(50) default 'paid'
paid_at timestamp nullable
created_at timestamp
updated_at timestamp
```

Status values:

```text
paid
cancelled
```

Rules:

1. No refund status.
2. For MVP one order = one course.
3. Snapshot wajib disimpan.
4. Jangan hitung ulang amount dari current course price.
5. Jangan hitung ulang amount dari current commission setting.
6. Jika saldo kurang, order tidak dibuat.
7. Status `cancelled` hanya untuk pembatalan manual/system di masa depan, bukan untuk purchase yang gagal validasi.

Order number format:

```text
ORD-YYYYMMDD-{ULID short}
```

Rules:

1. `order_number` must be unique.
2. Generate inside the purchase transaction.
3. If a duplicate key occurs, retry generation instead of silently failing.

---

## 6.11 order_items

Although MVP only has one course per order, `order_items` is useful for future cart support.

Columns:

```text
id bigserial primary key
order_id foreignId references orders(id)
course_id foreignId references courses(id)
course_title_snapshot varchar(255)
price_snapshot bigint default 0
quantity integer default 1
subtotal bigint default 0
created_at timestamp
updated_at timestamp
```

---

## 6.12 enrollments

Columns:

```text
id bigserial primary key
user_id foreignId references users(id)
course_id foreignId references courses(id)
order_id foreignId nullable references orders(id)
status varchar(50) default 'active'
enrolled_at timestamp
created_at timestamp
updated_at timestamp
```

Status values:

```text
active
inactive
revoked
```

Rules:

1. Unique user_id + course_id.
2. Created after order paid.
3. For free course, order total 0 still created.
4. Deleted course does not delete enrollment.

Indexes:

```text
unique user_id + course_id
index course_id
index order_id
```

---

## 6.13 course_reviews

Columns:

```text
id bigserial primary key
user_id foreignId references users(id)
course_id foreignId references courses(id)
rating integer
comment text
is_published boolean default true
edited_at timestamp nullable
created_at timestamp
updated_at timestamp
deleted_at timestamp nullable
```

Rules:

1. Rating between 1 and 5.
2. User must be enrolled.
3. Unique user_id + course_id.
4. Review langsung tampil.
5. Admin can hide review by setting `is_published = false`.

---

## 6.14 platform_settings

Columns:

```text
id bigserial primary key
key varchar(255) unique
value text
type varchar(50) default 'string'
description text nullable
created_at timestamp
updated_at timestamp
```

Default setting:

```text
key: mentor_commission_rate
value: 60
type: integer
```

Rules:

1. Commission must be 0-100.
2. Read through CommissionService.
3. Do not hardcode commission in purchase logic.

---

## 7. Model Relationships

### User

```php
User hasOne MentorProfile
User hasMany Course as mentor_id
User hasOne Wallet
User hasMany Order
User hasMany Enrollment
User hasMany CourseReview
```

### MentorProfile

```php
MentorProfile belongsTo User
```

### Course

```php
Course belongsTo User as mentor
Course belongsTo CourseCategory
Course hasMany CourseSection
Course hasMany CourseLesson
Course hasMany CourseMaterial
Course hasMany Enrollment
Course hasMany CourseReview
Course hasMany Order
```

### Wallet

```php
Wallet hasMany WalletTransaction
```

### Order

```php
Order belongsTo User
Order belongsTo Course
Order belongsTo User as mentor
Order hasMany OrderItem
Order hasOne Enrollment
```

### Enrollment

```php
Enrollment belongsTo User
Enrollment belongsTo Course
Enrollment belongsTo Order
```

### CourseReview

```php
CourseReview belongsTo User
CourseReview belongsTo Course
```

---

## 8. Enums

Use PHP enums or constant classes.

### CourseStatus

```php
DRAFT = 'draft'
PUBLISHED = 'published'
ARCHIVED = 'archived'
DELETED_BY_MENTOR = 'deleted_by_mentor'
HIDDEN_BY_ADMIN = 'hidden_by_admin'
```

### MaterialType

```php
VIDEO = 'video'
PDF = 'pdf'
```

### WalletTransactionType

```php
TOPUP = 'topup'
COURSE_PURCHASE = 'course_purchase'
COURSE_SALE_INCOME = 'course_sale_income'
PLATFORM_FEE = 'platform_fee'
ADJUSTMENT = 'adjustment'
```

### WalletTransactionDirection

```php
CREDIT = 'credit'
DEBIT = 'debit'
```

### WalletTransactionStatus

```php
PENDING = 'pending'
SUCCESS = 'success'
FAILED = 'failed'
CANCELLED = 'cancelled'
```

### OrderStatus

```php
PAID = 'paid'
CANCELLED = 'cancelled'
```

### UserStatus

```php
ACTIVE = 'active'
SUSPENDED = 'suspended'
DELETED = 'deleted'
```

---

## 9. Core Services

## 9.1 WalletService

Purpose: satu-satunya service yang boleh mengubah saldo.

Methods:

```php
credit(Wallet $wallet, int $amount, string $type, ?Model $reference = null, ?string $description = null, array $metadata = []): WalletTransaction

debit(Wallet $wallet, int $amount, string $type, ?Model $reference = null, ?string $description = null, array $metadata = []): WalletTransaction

getOrCreateUserWallet(User $user): Wallet

getOrCreatePlatformWallet(): Wallet
```

Rules:

1. Amount must be > 0 except special 0 transaction should be avoided.
2. Debit must check sufficient balance.
3. Use row lock when updating wallet balance.
4. Save balance_before and balance_after.
5. WalletService does not open DB transactions internally.
6. Caller is responsible for transaction boundaries.
7. Topup and purchase must wrap WalletService calls in DB transactions.

Pseudo-code:

```php
public function debit(Wallet $wallet, int $amount, string $type, ?Model $reference = null, ?string $description = null, array $metadata = [])
{
    if ($amount <= 0) {
        throw new InvalidArgumentException('Amount must be greater than zero.');
    }

    $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->first();

    if ($wallet->balance < $amount) {
        throw new InsufficientBalanceException();
    }

    $before = $wallet->balance;
    $after = $before - $amount;

    $wallet->update(['balance' => $after]);

    return WalletTransaction::create([
        'wallet_id' => $wallet->id,
        'owner_type' => $wallet->owner_type,
        'owner_id' => $wallet->owner_id,
        'type' => $type,
        'direction' => 'debit',
        'amount' => $amount,
        'balance_before' => $before,
        'balance_after' => $after,
        'status' => 'success',
        'reference_type' => $reference ? get_class($reference) : null,
        'reference_id' => $reference?->getKey(),
        'description' => $description,
        'metadata' => $metadata,
    ]);
}
```

Note: Karena WalletService tidak membuka transaction sendiri, controller/action untuk topup dan CoursePurchaseService wajib membuka `DB::transaction` sebelum memanggil `credit()` atau `debit()`.

---

## 9.2 CommissionService

Purpose: membaca dan memvalidasi komisi.

Methods:

```php
getCurrentMentorCommissionRate(): int
setMentorCommissionRate(int $rate): PlatformSetting
calculateSplit(int $price, int $commissionRate): array
```

Return `calculateSplit`:

```php
[
  'mentor_amount' => 60000,
  'platform_amount' => 40000,
]
```

Rules:

1. Rate between 0 and 100.
2. Default fallback 60 if setting missing.
3. Use integer math.
4. Use `floor` for mentor amount if needed.
5. Platform amount is remainder.

---

## 9.3 CoursePurchaseService

Purpose: menangani pembelian kelas.

Method:

```php
purchase(User $buyer, Course $course): Order
```

Steps for paid course:

1. Validate buyer active.
2. Validate course status published.
3. Validate buyer is not course mentor.
4. Validate buyer has no enrollment.
5. Get user wallet.
6. Check balance.
7. Get commission rate.
8. Snapshot course price.
9. Calculate mentor/platform amount.
10. Begin DB transaction.
11. Create order.
12. Create order item.
13. Debit buyer wallet.
14. Credit mentor account wallet.
15. Credit platform wallet.
16. Create enrollment.
17. Commit.
18. Return order.

Steps for free course:

1. Validate same rules.
2. Begin DB transaction.
3. Create order total 0.
4. Create order item subtotal 0.
5. Create enrollment.
6. No wallet transaction.
7. Commit.

Must be atomic.

Pseudo-code:

```php
public function purchase(User $buyer, Course $course): Order
{
    $this->validatePurchase($buyer, $course);

    return DB::transaction(function () use ($buyer, $course) {
        $price = (int) $course->price;
        $commissionRate = $this->commissionService->getCurrentMentorCommissionRate();
        $split = $this->commissionService->calculateSplit($price, $commissionRate);

        $order = Order::create([
            'order_number' => $this->generateOrderNumber(), // Must be unique and retry on duplicate key.
            'user_id' => $buyer->id,
            'course_id' => $course->id,
            'mentor_id' => $course->mentor_id,
            'course_title_snapshot' => $course->title,
            'course_price_snapshot' => $price,
            'commission_rate_snapshot' => $commissionRate,
            'mentor_amount' => $split['mentor_amount'],
            'platform_amount' => $split['platform_amount'],
            'total_amount' => $price,
            'status' => 'paid',
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

        if ($price > 0) {
            $buyerWallet = $this->walletService->getOrCreateUserWallet($buyer);
            $mentorWallet = $this->walletService->getOrCreateUserWallet($course->mentor);
            $platformWallet = $this->walletService->getOrCreatePlatformWallet();

            $this->walletService->debit($buyerWallet, $price, 'course_purchase', $order, 'Pembelian kelas '.$course->title, [
                'course_id' => $course->id,
                'course_title' => $course->title,
            ]);

            if ($split['mentor_amount'] > 0) {
                $this->walletService->credit($mentorWallet, $split['mentor_amount'], 'course_sale_income', $order, 'Pendapatan kelas '.$course->title, [
                    'buyer_id' => $buyer->id,
                    'buyer_name' => $buyer->name,
                    'course_id' => $course->id,
                    'commission_rate_snapshot' => $commissionRate,
                ]);
            }

            if ($split['platform_amount'] > 0) {
                $this->walletService->credit($platformWallet, $split['platform_amount'], 'platform_fee', $order, 'Platform fee dari kelas '.$course->title, [
                    'buyer_id' => $buyer->id,
                    'mentor_id' => $course->mentor_id,
                    'course_id' => $course->id,
                    'commission_rate_snapshot' => $commissionRate,
                ]);
            }
        }

        Enrollment::create([
            'user_id' => $buyer->id,
            'course_id' => $course->id,
            'order_id' => $order->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        return $order;
    });
}
```

---

## 9.4 MaterialAccessService

Purpose: menentukan apakah user boleh melihat materi.

Method:

```php
canAccessMaterial(User $user, CourseMaterial $material): bool
```

Rules:

Return false if:

1. User not authenticated.
2. Course status is `deleted_by_mentor`.
3. Course status is `hidden_by_admin`.
4. User has no active enrollment.
5. User suspended/deleted.
6. Material status not active.

Return true if:

1. User has active enrollment.
2. Course status is `published` or `archived`.
3. Material active.

---

## 9.5 CourseStatusService

Purpose: mengelola status course.

Methods:

```php
publish(Course $course): Course
archive(Course $course): Course
markDeletedByMentor(Course $course): Course
hideByAdmin(Course $course): Course
restoreFromDeletedByMentor(Course $course): Course optional
```

Rules:

1. Publish sets status `published` and `published_at`.
2. Archive sets status `archived` and `archived_at`.
3. Delete by mentor sets status `deleted_by_mentor` and `deleted_by_mentor_at`.
4. Do not hard delete course.
5. Hide by admin sets status `hidden_by_admin` and `hidden_by_admin_at`.

---

## 10. Routes Design

### 10.1 Public Routes

```php
Route::get('/', LandingPage::class)->name('home');
Route::get('/courses', CourseCatalog::class)->name('courses.index');
Route::get('/courses/{course:slug}', CourseDetail::class)->name('courses.show');
Route::get('/mentors/{mentorProfile:slug}', MentorProfilePage::class)->name('mentors.show');
```

### 10.2 Authenticated User Routes

```php
Route::middleware(['auth', 'active.user'])->group(function () {
    Route::get('/dashboard', StudentDashboard::class)->name('dashboard');
    Route::get('/my-courses', MyCourses::class)->name('student.courses');
    Route::get('/wallet', WalletPage::class)->name('student.wallet');
    Route::get('/transactions', TransactionHistory::class)->name('student.transactions');
    Route::get('/learn/{course:slug}', LearningPage::class)->name('student.learn');
    Route::post('/courses/{course}/purchase', [CoursePurchaseController::class, 'store'])->name('courses.purchase');
    Route::get('/materials/{material}/view', [CourseMaterialViewerController::class, 'show'])->name('materials.view');
});
```

### 10.3 Mentor Routes

```php
Route::middleware(['auth', 'role:mentor', 'active.user'])->prefix('mentor')->name('mentor.')->group(function () {
    Route::get('/dashboard', MentorDashboard::class)->name('dashboard');
    Route::get('/courses', MentorCourseIndex::class)->name('courses.index');
    Route::get('/courses/create', MentorCourseForm::class)->name('courses.create');
    Route::get('/courses/{course}/edit', MentorCourseForm::class)->name('courses.edit');
    Route::get('/courses/{course}/materials', CourseMaterialManager::class)->name('courses.materials');
    Route::get('/sales', SalesHistory::class)->name('sales');
    Route::get('/wallet', MentorWalletHistory::class)->name('wallet');
});
```

### 10.4 Admin Routes

Filament handles admin routes, default path:

```text
/admin
```

---

## 11. Middleware

### 11.1 active.user

Purpose: prevent suspended/deleted user login access.

Logic:

```php
if (auth()->user()->status !== 'active') {
    auth()->logout();
    abort(403, 'Akun Anda tidak aktif.');
}
```

### 11.2 role middleware

Use Spatie role middleware.

Examples:

```php
role:mentor
role:admin
```

---

## 12. Policies

## 12.1 CoursePolicy

Methods:

```php
viewAny(User $user)
view(?User $user, Course $course)
create(User $user)
update(User $user, Course $course)
delete(User $user, Course $course)
publish(User $user, Course $course)
```

Rules:

1. Admin can view all.
2. Mentor can update/delete own courses.
3. User/guest can view published courses.
4. Deleted courses cannot be viewed publicly.
5. Archived courses cannot be viewed publicly but can be viewed by enrolled users on learning page.
6. Hidden by admin courses cannot be viewed publicly and cannot be purchased.

## 12.2 CourseMaterialPolicy

Methods:

```php
view(User $user, CourseMaterial $material)
update(User $user, CourseMaterial $material)
delete(User $user, CourseMaterial $material)
```

Rules:

1. Admin can view/manage.
2. Mentor can manage own course materials.
3. Enrolled user can view active material if course not deleted_by_mentor.
4. Non-enrolled user cannot view.

## 12.3 ReviewPolicy

Rules:

1. User can create review only if enrolled.
2. User can update own review.
3. Admin can hide/unpublish.
4. Mentor cannot edit user reviews.

---

## 13. File Storage Design

## 13.1 Disk Configuration

Use local private disk.

In `config/filesystems.php`:

```php
'course_materials' => [
    'driver' => 'local',
    'root' => storage_path('app/private/course-materials'),
    'throw' => false,
],
```

For thumbnails, public disk is allowed:

```text
storage/app/public/course-thumbnails
```

For course materials, private disk only.

## 13.2 Upload Rules

Video:

```text
allowed mime: mp4, webm, mov optional
max size MVP: 200 MB
```

PDF:

```text
allowed mime: application/pdf
max size MVP: 20 MB
```

Thumbnail:

```text
allowed mime: jpg, jpeg, png, webp
max size: 5 MB
```

## 13.3 File Access

Never expose material file path directly.

Use:

```text
GET /materials/{material}/view
```

Controller:

1. Authenticate user.
2. Load material and course.
3. Authorize via CourseMaterialPolicy.
4. Check file exists.
5. Return stream/response.
6. Set correct content type.
7. For PDF, allow inline display.
8. For video, support streaming if possible.
9. Always read files through `Storage::disk('course_materials')`, not the default disk.
10. File protection prevents unauthorized public/direct access; it is not DRM and cannot fully prevent screen recording or browser-level copying.

---

## 14. Transaction Flow

## 14.1 Dummy Topup Flow

```text
User opens wallet page
User inputs amount
System validates min/max
System creates/gets user wallet
System credits wallet
System creates wallet transaction
System shows success message
```

No external payment.

## 14.2 Paid Course Purchase Flow

```text
User clicks Buy Course
System validates login
System validates course published
System validates not already enrolled
System validates not own course
System validates balance enough
System reads commission setting
System snapshots price and commission
System calculates split
System creates order
System debits user wallet
System credits mentor account wallet
System credits platform wallet
System creates enrollment
System redirects to learning page
```

## 14.3 Free Course Enrollment Flow

```text
User clicks Enroll Gratis
System validates login
System validates course published
System validates not already enrolled
System creates order with total 0
System creates order item with subtotal 0
System creates enrollment
System redirects to learning page
```

## 14.4 Delete Course by Mentor Flow

```text
Mentor clicks delete course
System confirms action
System validates owner
System sets status deleted_by_mentor
System sets deleted_by_mentor_at
Course disappears from public catalog
Course card remains in user's My Courses as disabled
Learning access blocked
```

---

## 15. Filament Admin Design

## 15.1 Admin Panel Path

```text
/admin
```

## 15.2 Resources

### UserResource

Fields:

1. name
2. email
3. roles
4. status
5. created_at

Actions:

1. View.
2. Edit.
3. Suspend.
4. Activate.
5. Soft delete.

Filters:

1. role
2. status
3. created date

### MentorProfileResource

Fields:

1. user
2. display_name
3. expertise
4. status
5. total_courses
6. total_sales optional

Actions:

1. View.
2. Edit.
3. Suspend linked user.
4. Soft delete.

### CourseResource

Fields:

1. title
2. mentor
3. category
4. price
5. status
6. rating
7. total_enrollments
8. created_at

Actions:

1. View.
2. Edit status.
3. Archive.
4. Mark hidden by admin.

Filters:

1. status
2. category
3. mentor
4. price free/paid

### CourseCategoryResource

Fields:

1. name
2. slug
3. icon
4. sort_order
5. is_active

### OrderResource

Fields:

1. order_number
2. buyer
3. course
4. mentor
5. total_amount
6. commission_rate_snapshot
7. mentor_amount
8. platform_amount
9. status
10. paid_at

Read-only. Admin must not edit/delete orders from Filament for MVP.

### WalletResource

Fields:

1. owner_type
2. owner_id
3. owner display name
4. balance
5. currency
6. status

Read-only. Admin must not edit balances directly from Filament.

### WalletTransactionResource

Fields:

1. wallet
2. owner
3. type
4. direction
5. amount
6. balance_before
7. balance_after
8. reference
9. created_at

Read-only and immutable. Admin must not edit/delete ledger rows from Filament.

### PlatformSettingResource

Fields:

1. key
2. value
3. type
4. description

Rules:

1. Only expose `mentor_commission_rate` for MVP.
2. Validate value 0-100.

### CourseReviewResource

Fields:

1. user
2. course
3. rating
4. comment
5. is_published
6. created_at

Actions:

1. Publish.
2. Hide.
3. Delete optional soft delete.

---

## 16. Seeder Design

## 16.1 RolePermissionSeeder

Create roles:

```php
Role::firstOrCreate(['name' => 'admin']);
Role::firstOrCreate(['name' => 'mentor']);
Role::firstOrCreate(['name' => 'user']);
```

Create permissions:

```text
access_admin_panel
manage_users
manage_mentors
manage_courses
manage_categories
manage_orders
manage_wallets
manage_settings
manage_reviews
create_course
edit_own_course
delete_own_course
upload_material
buy_course
topup_wallet
review_course
```

Assign:

Admin gets all permissions.

Mentor gets:

```text
create_course
edit_own_course
delete_own_course
upload_material
buy_course
topup_wallet
review_course
```

User gets:

```text
buy_course
topup_wallet
review_course
```

## 16.2 UserSeeder

Create:

```text
admin@example.com / password
mentor@example.com / password
user@example.com / password
```

Rules:

1. Admin role assigned.
2. Mentor role assigned and mentor profile created.
3. User role assigned.
4. Wallet created for mentor and user.

## 16.3 PlatformSettingSeeder

Create:

```php
PlatformSetting::updateOrCreate(
    ['key' => 'mentor_commission_rate'],
    [
        'value' => '60',
        'type' => 'integer',
        'description' => 'Default mentor commission percentage',
    ]
);
```

## 16.4 PlatformWalletSeeder

Create:

```php
Wallet::firstOrCreate(
    ['owner_type' => 'platform', 'owner_id' => 0],
    ['balance' => 0, 'currency' => 'IDR', 'status' => 'active']
);
```

## 16.5 CourseCategorySeeder

Create categories:

```text
Web Development
Digital Marketing
UI/UX Design
Data Analytics
Business
Productivity
```

## 16.6 DemoCourseSeeder

Create:

1. Free course: "Dasar HTML & CSS untuk Pemula"
2. Paid course: "Laravel 12 dari Nol sampai Deploy"
3. Paid course: "UI/UX Modern dengan Figma"

Rules:

1. Owned by mentor demo.
2. Include sections and lessons.
3. Include dummy PDF material.
4. Include dummy video material path or placeholder file.
5. Published status.

---

## 17. Docker Design

## 17.1 Services

```text
app
nginx
postgres
redis
queue
scheduler
```

### app

Purpose:

1. PHP-FPM runtime.
2. Runs Laravel app.

### nginx

Purpose:

1. Web server.
2. Serves public directory.
3. Passes PHP to app container.

### postgres

Purpose:

1. Main database.

### redis

Purpose:

1. Optional cache/queue.
2. Can be included even if queue starts with database.

### queue

Command:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

### scheduler

Command:

```bash
php artisan schedule:work
```

---

## 17.2 docker-compose.yml Outline

```yaml
services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: skill_app
    volumes:
      - .:/var/www/html
      - storage_data:/var/www/html/storage
    depends_on:
      - postgres
      - redis

  nginx:
    image: nginx:alpine
    container_name: skill_nginx
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

  postgres:
    image: postgres:16-alpine
    container_name: skill_postgres
    environment:
      POSTGRES_DB: skill_platform
      POSTGRES_USER: skill_user
      POSTGRES_PASSWORD: secret
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:alpine
    container_name: skill_redis
    ports:
      - "6379:6379"

  queue:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: skill_queue
    command: php artisan queue:work --sleep=3 --tries=3 --timeout=90
    volumes:
      - .:/var/www/html
      - storage_data:/var/www/html/storage
    depends_on:
      - app
      - postgres
      - redis

  scheduler:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: skill_scheduler
    command: php artisan schedule:work
    volumes:
      - .:/var/www/html
      - storage_data:/var/www/html/storage
    depends_on:
      - app
      - postgres
      - redis

volumes:
  postgres_data:
  storage_data:
```

Implementation note:

For development, bind mount `.:/var/www/html` is useful. For production, build image with source copied into image.

---

## 17.3 Dockerfile Outline

```dockerfile
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

USER www-data
```

For local development, composer install may be run manually instead of during build.

---

## 17.4 Nginx Config Outline

```nginx
server {
    listen 80;
    index index.php index.html;
    server_name localhost;
    root /var/www/html/public;

    client_max_body_size 250M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

---

## 17.5 .env Example

```env
APP_NAME="Skill Digital Platform"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=skill_platform
DB_USERNAME=skill_user
DB_PASSWORD=secret

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database

FILESYSTEM_DISK=local

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Note:

`FILESYSTEM_DISK=local` is acceptable for the default disk. Course materials must still use the explicit `course_materials` private disk in code.

---

## 18. UI/UX Design Direction — Impeccable Style

The UI must feel modern, polished, friendly, and credible. The visual direction should be similar to a premium edtech marketplace, not a plain admin CRUD application.

### 18.1 Design Keywords

```text
modern
clean
premium
educational
friendly
trustworthy
spacious
focused
mobile-first
conversion-oriented
```

### 18.2 Visual Personality

The interface should communicate:

1. Learning is easy to start.
2. Mentors look professional and trustworthy.
3. Courses feel valuable.
4. Wallet and transactions feel transparent.
5. Admin dashboard feels controlled and structured.

### 18.3 Color Direction

Use Tailwind design tokens.

Recommended palette:

```text
Primary: Indigo / Blue
Accent: Emerald or Violet
Neutral: Slate / Zinc
Success: Emerald
Warning: Amber
Danger: Rose
Background: White / Slate-50
Dark Text: Slate-900
Muted Text: Slate-500
```

Example Tailwind usage:

```text
primary: indigo-600
primary hover: indigo-700
accent: emerald-500
background: slate-50
card: white
border: slate-200
```

Avoid overly saturated rainbow colors.

### 18.4 Typography

Recommended:

1. Use system font or Inter if configured.
2. Headings bold and clear.
3. Body text readable.
4. Use consistent scale.

Suggested scale:

```text
Hero title: text-4xl md:text-6xl font-bold
Page title: text-2xl md:text-3xl font-bold
Section title: text-xl md:text-2xl font-semibold
Card title: text-base md:text-lg font-semibold
Body: text-sm md:text-base
Small/muted: text-xs md:text-sm
```

### 18.5 Spacing

Use generous spacing:

```text
Page container: max-w-7xl mx-auto px-4 sm:px-6 lg:px-8
Section spacing: py-16 md:py-24
Card padding: p-5 or p-6
Grid gap: gap-6 or gap-8
```

### 18.6 Border Radius and Shadow

```text
Cards: rounded-2xl
Buttons: rounded-xl
Input: rounded-xl
Modal: rounded-2xl
Shadow: shadow-sm hover:shadow-md
```

### 18.7 Component Style

Buttons:

1. Primary button: filled indigo.
2. Secondary button: white with border.
3. Danger button: rose.
4. Disabled state must be clear.

Cards:

1. White background.
2. Rounded corners.
3. Subtle border.
4. Soft shadow.
5. Hover lift for course cards.

Badges:

1. Free: emerald.
2. Paid: indigo.
3. Draft: slate.
4. Published: emerald.
5. Archived: amber.
6. Deleted by mentor: rose.

Forms:

1. Large enough input height.
2. Clear label.
3. Error message below input.
4. Helper text for upload limits.
5. Drag-and-drop upload area optional.

---

## 19. Page-Level UI Design

## 19.1 Landing Page

Sections:

1. Navbar.
2. Hero.
3. Featured categories.
4. Featured courses.
5. How it works.
6. Mentor CTA.
7. Testimonials/static trust section.
8. Footer.

Hero design:

```text
Left:
- Badge: "Marketplace kelas skill digital"
- Headline: "Belajar skill digital dari mentor terbaik"
- Subheadline
- CTA primary: Mulai Belajar
- CTA secondary: Jadi Mentor

Right:
- Course preview card mockup
- Floating stat cards:
  - 100+ kelas
  - Mentor terpercaya
  - Belajar fleksibel
```

Visual style:

1. Gradient background subtle.
2. Modern card mockup.
3. Rounded large shapes.
4. Clean CTA.

---

## 19.2 Course Catalog Page

Layout:

```text
Header:
- Title
- Subtitle
- Search input

Content:
- Sidebar/category filter on desktop
- Horizontal filter chips on mobile
- Course grid
```

Course card:

1. Thumbnail.
2. Category badge.
3. Title.
4. Short description.
5. Mentor name/avatar.
6. Rating.
7. Price.
8. CTA.

Card states:

1. Free badge.
2. Paid price.
3. Hover shadow.
4. Empty state if no course.

---

## 19.3 Course Detail Page

Layout:

```text
Left/main:
- Course title
- Description
- Curriculum
- Reviews

Right/sidebar sticky:
- Thumbnail/video preview
- Price
- Buy/enroll button
- Course stats
- Mentor mini profile
```

If user already enrolled:

Button: `Lanjut Belajar`

If mentor owns course:

Button disabled: `Ini kelas Anda`

If course deleted:

Show unavailable state.

---

## 19.4 Student Dashboard

Sections:

1. Welcome header.
2. Wallet balance card.
3. Continue learning.
4. My courses.
5. Recent transactions.

Cards:

1. Use stat cards.
2. Use progress placeholder optional.
3. Use empty state if no courses.

---

## 19.5 My Courses Page

Course card states:

1. Active course: CTA `Lanjut Belajar`.
2. Archived course: still accessible, badge `Diarsipkan`.
3. Deleted by mentor: disabled, badge `Kelas ini dihapus oleh mentor`.

Deleted card design:

```text
Muted background
Rose badge
Disabled button
Message: "Materi kelas ini tidak lagi tersedia karena dihapus oleh mentor."
```

---

## 19.6 Learning Page

Layout:

```text
Desktop:
- Left sidebar curriculum
- Main content viewer

Mobile:
- Collapsible curriculum
- Main content viewer
```

Video viewer:

1. Native HTML5 video.
2. Controls enabled.
3. No download attribute where possible.
4. Display warning: "Materi hanya untuk pembelajaran di platform."

PDF viewer:

1. iframe or embedded viewer.
2. Route protected.
3. No direct public link.

---

## 19.7 Wallet Page

Sections:

1. Balance card.
2. Dummy topup form.
3. Transaction history.

Topup form:

1. Amount input.
2. Quick amount buttons:
   - Rp50.000
   - Rp100.000
   - Rp250.000
   - Rp500.000
3. Confirm button.
4. Success alert after topup.

Important label:

```text
"Topup ini masih dummy untuk kebutuhan MVP. Tidak ada transaksi bank/payment gateway."
```

---

## 19.8 Mentor Dashboard

Sections:

1. Greeting.
2. Balance card.
3. Total sales.
4. Total courses.
5. Total students.
6. Recent purchases.
7. CTA create course.

Course management table/card:

1. Thumbnail.
2. Title.
3. Status.
4. Price.
5. Students.
6. Revenue.
7. Actions.

---

## 19.9 Mentor Course Form

Fields:

1. Title.
2. Category.
3. Short description.
4. Full description.
5. Price.
6. Thumbnail.
7. Status draft/published.

UX:

1. Show slug preview.
2. Price can be 0.
3. Explain: "Perubahan harga hanya berlaku untuk pembelian berikutnya."
4. Save draft button.
5. Publish button.

---

## 19.10 Mentor Material Manager

Layout:

1. Course header.
2. Section list.
3. Lesson list.
4. Material upload form.

Upload UI:

1. Type selector: Video/PDF.
2. Title.
3. File input.
4. Helper text max size.
5. Sort order.
6. Current file info.
7. Replace file action.
8. Delete material action.

---

## 19.11 Admin Dashboard

Use Filament widgets:

1. StatsOverviewWidget:
   - Total Users
   - Total Mentors
   - Total Courses
   - Platform Balance

2. Charts optional:
   - Orders per day.
   - Revenue per day.

3. Tables:
   - Latest orders.
   - Latest topups.
   - Top courses.

---

## 20. Livewire Component Design

### 20.1 CourseCatalog

State:

```php
public string $search = '';
public ?int $categoryId = null;
public string $sort = 'latest';
```

Methods:

```php
updatedSearch()
setCategory($id)
setSort($sort)
```

Query:

1. Course where status published.
2. Search title.
3. Filter category.
4. Sort.

### 20.2 CourseDetail

State:

```php
public Course $course;
public bool $isEnrolled;
public bool $canPurchase;
```

Actions:

```php
purchase()
enrollFree()
```

Use CoursePurchaseService.

### 20.3 TopupForm

State:

```php
public int $amount;
```

Validation:

```php
amount required integer min:10000 max:10000000
```

Action:

```php
topup()
```

Calls WalletService credit.

### 20.4 LearningPage

State:

```php
public Course $course;
public ?CourseLesson $selectedLesson;
public ?CourseMaterial $selectedMaterial;
```

Rules:

1. Validate enrollment on mount.
2. Validate course not deleted_by_mentor.
3. List active materials only.
4. Material route handles actual file authorization.

### 20.5 MentorCourseForm

State:

Course fields.

Rules:

1. Title required.
2. Price numeric min 0.
3. Category required.
4. Thumbnail optional image.

Actions:

```php
saveDraft()
publish()
```

### 20.6 CourseMaterialManager

State:

1. Course.
2. Sections.
3. Lessons.
4. Upload form.

Actions:

1. Add section.
2. Add lesson.
3. Upload material.
4. Replace material.
5. Delete material.
6. Reorder optional.

---

## 21. Validation Rules

### Register

```text
name required string max:255
email required email unique:users,email
password required confirmed min:8
role required in:user,mentor
```

### Course

```text
title required string max:255
category_id required exists:course_categories,id
short_description nullable string max:500
description nullable string
price required integer min:0
thumbnail nullable image max:5120
status required in:draft,published
```

### Material

```text
title required string max:255
type required in:video,pdf
file required
if video: mimes mp4,webm,mov max 204800
if pdf: mimes pdf max 20480
```

### Topup

```text
amount required integer min:10000 max:10000000
```

### Review

```text
rating required integer min:1 max:5
comment required string min:10 max:2000
```

### Commission Setting

```text
value required integer min:0 max:100
```

---

## 22. Security Requirements

1. Use Laravel CSRF protection.
2. Use auth middleware for protected pages.
3. Use role middleware for admin/mentor pages.
4. Use policy for course/material access.
5. Never expose private file path.
6. Validate all uploads.
7. Limit file size.
8. Do not trust client role input beyond allowed values.
9. Admin role cannot be registered publicly.
10. Use soft delete for important business data.
11. Use DB transactions for purchase.
12. Use row lock for wallet update.
13. Hide internal error messages from user.
14. Log unexpected storage/file errors.
15. Prevent mentor from buying own course.
16. Prevent duplicate enrollment.
17. Prevent negative wallet balance.
18. Prevent direct URL access to course material.

---

## 23. Testing Plan

## 23.1 Feature Tests

### Auth

1. User can register as user.
2. User can register as mentor.
3. Public cannot register as admin.
4. Suspended user cannot access dashboard.

### Course

1. Mentor can create course.
2. Mentor can publish course.
3. Mentor can edit own course.
4. Mentor cannot edit other mentor course.
5. Published course appears in catalog.
6. Deleted course does not appear in catalog.

### Wallet

1. User can topup dummy.
2. Topup increases balance.
3. Topup creates wallet transaction.
4. Invalid topup amount rejected.

### Purchase

1. User can buy paid course with enough balance.
2. User cannot buy paid course with insufficient balance.
3. User cannot buy same course twice.
4. Mentor cannot buy own course.
5. Free course creates order and enrollment.
6. Paid course debits user wallet.
7. Paid course credits mentor account wallet.
8. Paid course credits platform wallet.
9. Purchase stores price snapshot.
10. Purchase stores commission snapshot.

### Commission

1. Default commission is 60.
2. Admin can update commission.
3. Old orders keep old commission.
4. New orders use new commission.

### Learning Access

1. Enrolled user can access material.
2. Non-enrolled user cannot access material.
3. Deleted course blocks material access.
4. Archived course still allows enrolled user access.

### Review

1. Enrolled user can review.
2. Non-enrolled user cannot review.
3. User cannot review same course twice.
4. Admin can hide review.

---

## 23.2 Policy Tests

1. Course update only owner mentor/admin.
2. Material view only enrolled user/admin/owner mentor.
3. Material update only owner mentor/admin.
4. Review update only owner user.
5. Admin can manage all.

---

## 23.3 Unit Tests

1. CommissionService split calculation.
2. WalletService credit.
3. WalletService debit.
4. WalletService insufficient balance.
5. CoursePurchaseService paid purchase.
6. CoursePurchaseService free enrollment.
7. MaterialAccessService.

---

## 24. Implementation Commands — Suggested Flow

```bash
composer create-project laravel/laravel .

composer require laravel/breeze --dev
php artisan breeze:install livewire
composer require filament/filament
composer require spatie/laravel-permission

php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

php artisan make:model MentorProfile -m
php artisan make:model CourseCategory -m
php artisan make:model Course -m
php artisan make:model CourseSection -m
php artisan make:model CourseLesson -m
php artisan make:model CourseMaterial -m
php artisan make:model Wallet -m
php artisan make:model WalletTransaction -m
php artisan make:model Order -m
php artisan make:model OrderItem -m
php artisan make:model Enrollment -m
php artisan make:model CourseReview -m
php artisan make:model PlatformSetting -m

php artisan make:seeder RolePermissionSeeder
php artisan make:seeder UserSeeder
php artisan make:seeder PlatformSettingSeeder
php artisan make:seeder PlatformWalletSeeder
php artisan make:seeder CourseCategorySeeder
php artisan make:seeder DemoCourseSeeder
```

Important:

1. Run `composer create-project laravel/laravel .` only when the repository root is empty.
2. Do not create a nested `skill-platform` directory inside this repo.
3. If files already exist in the root, initialize Laravel in a temporary directory and copy the generated Laravel files into the root carefully.

Queue database:

```bash
php artisan queue:table
php artisan migrate
```

Session/cache tables if using database:

```bash
php artisan session:table
php artisan cache:table
php artisan migrate
```

Storage:

```bash
php artisan storage:link
```

Note:

`storage:link` is only for public files like thumbnails. Course materials remain private.

---

## 25. AI Agent Guardrails

AI agent must follow these rules:

1. Do not add payment gateway.
2. Do not add payout.
3. Do not add refund.
4. Do not make landing page dynamic.
5. Do not use Inertia/React/Vue.
6. Do not expose course material in public storage.
7. Do not hard delete course on mentor delete action.
8. Do not recalculate old orders after commission/price changes.
9. Do not update wallet balance directly in controllers.
10. Do not allow public registration as admin.
11. Do not allow mentor to buy own course.
12. Do not allow access to materials without enrollment.
13. Do not remove transaction history when user/mentor/course is deleted.
14. Do not create payout UI.
15. Do not create bank account fields for MVP.
16. Use Blade + Livewire for user/mentor UI.
17. Use Filament for admin UI.
18. Use PostgreSQL-specific compatible migrations.
19. Use Docker Compose for local/deployment flow.
20. Keep UI modern, polished, and mobile-first.

---

## 26. Definition of Done

A phase is done when:

1. Feature works for the correct role.
2. Unauthorized role cannot access it.
3. Validation is implemented.
4. Empty/loading/error states exist for Livewire pages.
5. Database records are correct.
6. Wallet ledger is correct if feature touches balance.
7. Tests are added for critical rules.
8. UI is responsive.
9. Seeder/demo data still works.
10. Docker environment can run the app.

---

## 27. Final MVP Checklist

1. Docker Compose works.
2. Laravel app boots.
3. PostgreSQL connected.
4. Admin/mentor/user seeders work.
5. Register role selection works.
6. Filament admin works.
7. Mentor can create/publish course.
8. User can topup dummy.
9. User can buy paid course.
10. User can enroll free course.
11. Mentor receives dummy income.
12. Platform receives dummy income.
13. Commission snapshot works.
14. Price snapshot works.
15. User can access purchased material.
16. Unauthorized material access blocked.
17. User can review course.
18. Admin can hide review.
19. Mentor can delete course.
20. Deleted course label appears in My Courses.
21. Deleted course material access is blocked.
22. UI polished according to Impeccable Style.
23. Documentation complete.
