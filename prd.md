# PRD.md — Product Requirements Document
# Platform Digital Marketplace Kelas Skill Digital

## 1. Ringkasan Produk

Platform ini adalah aplikasi marketplace kelas digital berbasis Laravel 12. Platform memungkinkan mentor menjual kelas digital berupa video dan PDF, sedangkan user dapat melakukan topup saldo dummy, membeli kelas sekali bayar, mengakses materi kelas di dalam platform, dan memberikan review. Admin mengelola user, mentor, kursus, komisi, transaksi, saldo platform, dan konten administratif melalui Filament.

Produk ini dibuat sebagai MVP yang mudah dikembangkan, menggunakan dummy wallet tanpa payment gateway, tanpa rekening bank, tanpa payout, dan tanpa refund.

---

## 2. Tujuan Produk

### 2.1 Tujuan Utama

Membangun platform digital yang memungkinkan:

1. Mentor mendaftar dan menjual kelas digital.
2. User mendaftar, melakukan dummy topup, dan membeli kelas.
3. Sistem membagi hasil transaksi secara otomatis antara mentor dan platform/admin.
4. Admin dapat mengelola semua data melalui dashboard Filament.
5. Semua transaksi tercatat secara jelas melalui ledger wallet.
6. Materi kelas hanya dapat diakses di dalam platform oleh user yang sudah membeli/enroll.

### 2.2 Tujuan Teknis

1. Menggunakan Laravel 12.
2. Menggunakan Blade + Livewire untuk frontend.
3. Menggunakan Filament untuk admin panel.
4. Menggunakan PostgreSQL sebagai database.
5. Menggunakan Docker Compose agar deployment mudah.
6. Menggunakan local private storage untuk file video/PDF.
7. Menggunakan Laravel Queue untuk pekerjaan background.
8. Menggunakan Spatie Permission untuk role dan permission.
9. Membuat dokumentasi cukup detail agar AI agent dapat mengimplementasikan tanpa halusinasi.

---

## 3. Non-Goals / Di Luar Scope MVP

Fitur berikut tidak dibuat pada MVP:

1. Payment gateway asli.
2. Transfer bank otomatis.
3. Upload bukti transfer.
4. Payout/pencairan saldo mentor.
5. Refund.
6. Sertifikat.
7. Kuis.
8. Progress tracking detail per detik video.
9. Live class.
10. Chat mentor-user.
11. Subscription/membership bulanan.
12. Multi-currency.
13. Aplikasi mobile.
14. Streaming service eksternal.
15. Landing page editor dinamis.
16. Approval kelas oleh admin.
17. Versioning materi kelas.
18. Coupon/voucher.
19. Affiliate/referral.

---

## 4. Tech Stack Final

| Area | Pilihan |
|---|---|
| Framework | Laravel 12 |
| Frontend | Blade + Livewire |
| Auth | Laravel Breeze Livewire stack atau Laravel official starter kit berbasis Livewire |
| Admin Panel | Filament |
| Database | PostgreSQL |
| Role & Permission | Spatie Laravel Permission |
| Storage | Local private storage |
| Queue | Laravel Queue |
| Queue Driver MVP | database atau redis; rekomendasi awal database |
| Cache | database/redis optional |
| UI | Tailwind CSS |
| Deployment | Docker Compose |
| Web Server | Nginx |
| PHP Runtime | PHP-FPM |
| File Type Materi | Video dan PDF |

---

## 5. Role Pengguna

### 5.1 Admin

Admin adalah pengelola platform.

Kemampuan admin:

1. Login ke admin panel Filament.
2. Melihat dashboard statistik.
3. Mengelola user.
4. Mengelola mentor.
5. Soft delete atau suspend user/mentor.
6. Melihat semua kursus.
7. Mengelola kategori kursus.
8. Mengelola transaksi.
9. Mengelola wallet akun manusia dan platform secara read-only/reporting.
10. Mengubah setting komisi global mentor.
11. Melihat saldo platform.
12. Melihat history pendapatan platform.
13. Mengelola review, termasuk hide/unpublish review.
14. Melihat data topup dummy.
15. Melihat data order.
16. Melakukan monitoring materi kelas.

Admin tidak melakukan payout karena MVP tidak memiliki fitur pencairan.

### 5.2 Mentor

Mentor adalah penjual kelas.

Kemampuan mentor:

1. Register sebagai mentor dari halaman registrasi.
2. Login ke dashboard mentor.
3. Melengkapi profil mentor.
4. Membuat kelas.
5. Mengedit kelas.
6. Menentukan harga kelas.
7. Membuat kelas gratis atau berbayar.
8. Publish kelas secara bebas tanpa approval admin.
9. Upload materi video/PDF.
10. Menambah, mengedit, mengganti, dan menghapus materi.
11. Menghapus kelas secara soft/status.
12. Melihat daftar pembeli/peserta.
13. Melihat saldo mentor.
14. Melihat history pendapatan dari pembelian kelas.
15. Membeli kelas mentor lain sebagai user.

Batasan mentor:

1. Mentor tidak dapat membeli kelas miliknya sendiri.
2. Mentor tidak dapat mencairkan saldo.
3. Mentor tidak dapat melihat saldo platform.
4. Mentor tidak dapat mengubah komisi.
5. Mentor tidak dapat mengakses admin panel.
6. Mentor tidak dapat menghapus transaksi.
7. Mentor tidak dapat menghapus permanen histori pembelian.

### 5.3 User

User adalah pembeli kelas.

Kemampuan user:

1. Register sebagai user dari halaman registrasi.
2. Login ke dashboard user.
3. Melihat landing page.
4. Melihat katalog kelas.
5. Melihat detail kelas.
6. Melakukan dummy topup saldo.
7. Membeli kelas sekali bayar.
8. Enroll kelas gratis.
9. Mengakses kelas yang sudah dibeli.
10. Melihat video/PDF di dalam platform.
11. Melihat riwayat transaksi.
12. Melihat saldo.
13. Memberikan rating dan review untuk kelas yang sudah dibeli.
14. Melihat label jika kelas telah dihapus mentor.

Batasan user:

1. User tidak dapat mengakses materi kelas yang belum dibeli.
2. User tidak dapat download file secara direct/public.
3. User tidak dapat refund.
4. User tidak dapat mencairkan saldo.
5. User tidak dapat membeli kelas jika saldo kurang.
6. User tidak dapat membeli kelas yang statusnya tidak published.
7. User tidak dapat review kelas yang belum dibeli.
8. User tidak dapat review kelas yang sama lebih dari satu kali, kecuali sistem mengizinkan edit review.

---

## 6. User Stories

### 6.1 Public Visitor

#### US-PUB-001 — Melihat Landing Page

Sebagai visitor, saya ingin melihat landing page agar memahami nilai platform dan melihat kelas unggulan.

Acceptance Criteria:

1. Landing page dapat dibuka tanpa login.
2. Landing page menampilkan hero section.
3. Landing page menampilkan kategori/benefit.
4. Landing page menampilkan beberapa kelas terbaru/populer.
5. Landing page menampilkan CTA daftar sebagai user atau mentor.
6. Landing page bersifat hardcoded, bukan dikelola admin.

#### US-PUB-002 — Melihat Katalog Kelas

Sebagai visitor, saya ingin melihat daftar kelas agar dapat memilih kelas yang menarik.

Acceptance Criteria:

1. Katalog hanya menampilkan kelas dengan status `published`.
2. Kelas `archived`, `deleted_by_mentor`, dan `hidden_by_admin` tidak tampil di katalog.
3. Katalog menampilkan nama kelas, mentor, harga, rating, thumbnail, dan kategori.
4. Katalog dapat difilter berdasarkan kategori.
5. Katalog dapat dicari berdasarkan judul kelas.

#### US-PUB-003 — Melihat Detail Kelas

Sebagai visitor, saya ingin melihat detail kelas agar mengetahui isi dan harga kelas.

Acceptance Criteria:

1. Detail kelas menampilkan judul, deskripsi, mentor, harga, rating, total peserta, dan daftar materi.
2. Materi hanya ditampilkan sebagai daftar judul/preview, bukan file.
3. Tombol beli/enroll muncul.
4. Jika user belum login, tombol diarahkan ke login/register.
5. Jika kelas gratis, tombol bertuliskan "Enroll Gratis".
6. Jika kelas berbayar, tombol bertuliskan "Beli Kelas".

---

### 6.2 Authentication

#### US-AUTH-001 — Register sebagai User atau Mentor

Sebagai pengguna baru, saya ingin memilih role saat registrasi agar akun saya sesuai tujuan.

Acceptance Criteria:

1. Form register menampilkan pilihan role: `user` atau `mentor`.
2. Role `admin` tidak bisa dipilih dari registrasi publik.
3. Jika memilih `user`, sistem membuat akun user dan wallet akun tersebut.
4. Jika memilih `mentor`, sistem membuat akun mentor, mentor profile, dan wallet akun tersebut.
5. Akun admin hanya dibuat melalui seeder.
6. Email harus unik.
7. Password harus dikonfirmasi.
8. Setelah register, user diarahkan ke dashboard sesuai role.
9. Email verification tidak wajib untuk MVP agar demo dan seed user mudah digunakan.

#### US-AUTH-002 — Login

Sebagai user/mentor/admin, saya ingin login agar bisa mengakses fitur sesuai role.

Acceptance Criteria:

1. User bisa login dengan email dan password.
2. Sistem mengarahkan user ke dashboard user.
3. Sistem mengarahkan mentor ke dashboard mentor.
4. Sistem mengarahkan admin ke Filament admin panel atau admin dashboard.
5. Akun suspended/deleted tidak bisa login.

---

### 6.3 User Wallet

#### US-WALLET-001 — Dummy Topup

Sebagai user, saya ingin topup saldo dummy agar dapat membeli kelas.

Acceptance Criteria:

1. User dapat membuka halaman wallet/topup.
2. User memasukkan nominal topup.
3. Minimum topup adalah Rp10.000.
4. Maximum topup adalah Rp10.000.000.
5. Setelah user klik konfirmasi, topup langsung sukses.
6. Saldo user bertambah sesuai nominal.
7. Sistem membuat wallet transaction bertipe `topup`.
8. Status transaksi adalah `success`.
9. Tidak ada rekening, bukti transfer, atau payment gateway.
10. Topup hanya dummy/internal.

#### US-WALLET-002 — Melihat Riwayat Wallet

Sebagai user, saya ingin melihat riwayat saldo agar transparan.

Acceptance Criteria:

1. User melihat saldo saat ini.
2. User melihat daftar transaksi wallet.
3. Riwayat menampilkan tipe, nominal, arah credit/debit, tanggal, dan deskripsi.
4. User hanya melihat transaksi miliknya sendiri.

---

### 6.4 Course Purchase

#### US-PURCHASE-001 — Membeli Kelas Berbayar

Sebagai user, saya ingin membeli kelas dengan saldo agar dapat mengakses materi.

Acceptance Criteria:

1. User harus login.
2. Kelas harus berstatus `published`.
3. User tidak boleh sudah enroll kelas yang sama.
4. Saldo user harus cukup.
5. Sistem mengurangi saldo user sebesar harga kelas.
6. Sistem menghitung komisi mentor dari setting aktif.
7. Sistem menyimpan `commission_rate_snapshot`.
8. Sistem menyimpan `course_price_snapshot`.
9. Sistem membuat order.
10. Sistem membuat order item.
11. Sistem membuat enrollment.
12. Sistem menambah saldo mentor sesuai `mentor_amount`.
13. Sistem menambah saldo platform sesuai `platform_amount`.
14. Sistem membuat wallet transaction untuk user, mentor, dan platform.
15. Transaksi lama tidak berubah jika harga/komisi berubah setelah pembelian.
16. User diarahkan ke halaman belajar setelah berhasil.

#### US-PURCHASE-002 — Enroll Kelas Gratis

Sebagai user, saya ingin enroll kelas gratis tanpa saldo agar dapat belajar.

Acceptance Criteria:

1. Harga kelas adalah 0.
2. User tidak perlu saldo.
3. Sistem tetap membuat order dengan total 0.
4. Sistem membuat enrollment.
5. Wallet tidak berubah.
6. `mentor_amount` adalah 0.
7. `platform_amount` adalah 0.
8. User dapat mengakses materi.

#### US-PURCHASE-003 — Mencegah Pembelian Kelas Sendiri

Sebagai mentor, saya tidak boleh membeli kelas saya sendiri agar transaksi tidak manipulatif.

Acceptance Criteria:

1. Jika mentor membuka kelas miliknya sendiri, tombol beli tidak aktif.
2. Sistem menolak purchase request ke kelas milik sendiri.
3. Error message: "Anda tidak dapat membeli kelas milik sendiri."

---

### 6.5 Mentor Course Management

#### US-MENTOR-001 — Membuat Kelas

Sebagai mentor, saya ingin membuat kelas agar dapat menjual materi.

Acceptance Criteria:

1. Mentor dapat membuat kelas dari dashboard mentor.
2. Field minimum: judul, slug, kategori, deskripsi singkat, deskripsi lengkap, harga, thumbnail.
3. Harga boleh 0.
4. Harga tidak boleh negatif.
5. Mentor dapat memilih status draft/published.
6. Mentor bebas publish tanpa approval admin.
7. Course owner adalah mentor yang membuat kelas.

#### US-MENTOR-002 — Mengedit Kelas

Sebagai mentor, saya ingin mengedit kelas agar dapat memperbarui informasi.

Acceptance Criteria:

1. Mentor hanya dapat mengedit kelas miliknya sendiri.
2. Mentor dapat mengubah judul, deskripsi, thumbnail, kategori, dan harga.
3. Perubahan harga hanya berlaku untuk transaksi berikutnya.
4. Transaksi lama tetap memakai `course_price_snapshot`.
5. Mentor tidak dapat mengedit kelas milik mentor lain.

#### US-MENTOR-003 — Upload Materi

Sebagai mentor, saya ingin upload video/PDF agar user bisa belajar.

Acceptance Criteria:

1. Mentor dapat menambah section.
2. Mentor dapat menambah lesson.
3. Mentor dapat upload materi bertipe video atau PDF.
4. File disimpan di local private storage.
5. File tidak dapat diakses public secara langsung.
6. Mentor dapat edit judul/deskripsi materi.
7. Mentor dapat mengganti file materi.
8. Mentor dapat menghapus materi.
9. Perubahan materi langsung berlaku ke user yang sudah enroll.
10. MVP tidak memakai versioning materi.

#### US-MENTOR-004 — Menghapus Kelas

Sebagai mentor, saya ingin menghapus kelas jika kelas tidak lagi tersedia.

Acceptance Criteria:

1. Mentor hanya dapat menghapus kelas miliknya sendiri.
2. Sistem tidak melakukan hard delete.
3. Status kelas berubah menjadi `deleted_by_mentor`.
4. Kelas hilang dari katalog publik.
5. Kelas hilang dari halaman detail publik.
6. Di halaman My Courses user, kelas tetap muncul sebagai disabled.
7. User melihat label "Kelas ini dihapus oleh mentor".
8. User tidak dapat membuka materi kelas tersebut.
9. Riwayat transaksi tetap ada.
10. Laporan admin tetap menampilkan transaksi historis.

#### US-MENTOR-005 — Melihat Pendapatan

Sebagai mentor, saya ingin melihat saldo dan histori pembelian agar mengetahui penghasilan.

Acceptance Criteria:

1. Mentor dapat melihat saldo wallet.
2. Mentor dapat melihat daftar transaksi credit dari pembelian kelas.
3. Riwayat menampilkan nama/username pembeli, nama kelas, nominal kelas, komisi snapshot, mentor amount, tanggal.
4. Mentor tidak dapat mencairkan saldo.
5. Tidak ada tombol payout.

---

### 6.6 Learning Access

#### US-LEARN-001 — Mengakses Kelas yang Dibeli

Sebagai user, saya ingin membuka materi kelas yang sudah dibeli agar dapat belajar.

Acceptance Criteria:

1. User harus login.
2. User harus memiliki enrollment aktif.
3. Course tidak boleh berstatus `deleted_by_mentor`.
4. Sistem menampilkan daftar section dan lesson.
5. User dapat melihat video di halaman platform.
6. User dapat melihat PDF di halaman platform.
7. File tidak diberikan sebagai direct public URL.
8. Jika user tidak punya akses, sistem menampilkan 403.

#### US-LEARN-002 — Kelas Dihapus Mentor

Sebagai user, saya ingin mengetahui jika kelas yang saya beli dihapus mentor.

Acceptance Criteria:

1. Kelas tetap muncul di My Courses.
2. Card kelas disabled.
3. Label tampil: "Kelas ini dihapus oleh mentor".
4. User tidak bisa membuka materi.
5. Riwayat pembelian tetap ada.

---

### 6.7 Reviews

#### US-REVIEW-001 — Memberikan Review

Sebagai user, saya ingin memberi review agar membantu user lain.

Acceptance Criteria:

1. User harus login.
2. User harus sudah enroll kelas.
3. User hanya dapat memberi satu review per kelas.
4. Rating wajib 1 sampai 5.
5. Komentar opsional atau wajib sesuai implementasi; rekomendasi wajib minimal 10 karakter.
6. Review langsung tampil.
7. Admin dapat hide/unpublish review.
8. User dapat edit review miliknya sendiri.

#### US-REVIEW-002 — Melihat Review

Sebagai visitor/user, saya ingin melihat review agar bisa menilai kualitas kelas.

Acceptance Criteria:

1. Detail kelas menampilkan rating rata-rata.
2. Detail kelas menampilkan jumlah review.
3. Detail kelas menampilkan review yang `is_published = true`.
4. Review hidden oleh admin tidak tampil.

---

### 6.8 Admin Management

#### US-ADMIN-001 — Dashboard Admin

Sebagai admin, saya ingin melihat statistik utama platform.

Acceptance Criteria:

Dashboard menampilkan:

1. Total user.
2. Total mentor.
3. Total kelas published.
4. Total order.
5. Total dummy topup.
6. Total pendapatan platform.
7. Saldo platform.
8. Transaksi terbaru.
9. Kelas terbaru.
10. Mentor dengan penjualan terbanyak.

#### US-ADMIN-002 — User Management

Sebagai admin, saya ingin mengelola user dan mentor.

Acceptance Criteria:

1. Admin dapat melihat daftar user.
2. Admin dapat melihat role user.
3. Admin dapat melihat status user.
4. Admin dapat suspend user.
5. Admin dapat soft delete user.
6. Admin dapat menghapus/suspend mentor.
7. User/mentor yang dihapus tidak bisa login.
8. Histori transaksi user/mentor tidak terhapus.

#### US-ADMIN-003 — Course Management

Sebagai admin, saya ingin memonitor semua kelas.

Acceptance Criteria:

1. Admin dapat melihat semua kelas.
2. Admin dapat filter berdasarkan status.
3. Admin dapat melihat owner/mentor kelas.
4. Admin dapat mengubah status kelas jika diperlukan.
5. Admin dapat melihat daftar materi.
6. Admin dapat melihat transaksi terkait kelas.

#### US-ADMIN-004 — Commission Setting

Sebagai admin, saya ingin mengubah komisi mentor.

Acceptance Criteria:

1. Default komisi mentor adalah 60%.
2. Admin dapat mengubah komisi global.
3. Komisi harus berada antara 0 sampai 100.
4. Perubahan hanya berlaku untuk transaksi berikutnya.
5. Transaksi lama tidak boleh berubah.
6. Setiap order menyimpan `commission_rate_snapshot`.
7. Platform amount dihitung dari `100 - commission_rate_snapshot`.

#### US-ADMIN-005 — Platform Wallet

Sebagai admin, saya ingin melihat saldo platform.

Acceptance Criteria:

1. Sistem memiliki satu wallet platform.
2. Platform wallet bertambah dari platform fee setiap pembelian berbayar.
3. Tidak ada pencairan saldo platform.
4. Admin dapat melihat history credit platform.
5. Admin dapat melihat referensi order dari setiap transaksi platform.

---

## 7. Business Rules

### 7.1 Course Pricing

1. Harga kelas boleh 0.
2. Harga kelas tidak boleh negatif.
3. Kelas harga 0 dianggap gratis.
4. Kelas harga lebih dari 0 dianggap berbayar.
5. Harga kelas saat pembelian harus disimpan sebagai snapshot.
6. Perubahan harga tidak mengubah transaksi lama.

### 7.2 Commission

1. Default mentor commission adalah 60%.
2. Admin dapat mengubah komisi global.
3. Commission rate disimpan sebagai persen.
4. Saat purchase, sistem mengambil nilai komisi aktif.
5. Nilai komisi aktif disalin ke order sebagai `commission_rate_snapshot`.
6. `mentor_amount = floor(course_price_snapshot * commission_rate_snapshot / 100)`.
7. `platform_amount = course_price_snapshot - mentor_amount`.
8. Untuk kelas gratis, semua amount = 0.
9. Perubahan komisi tidak mengubah transaksi lama.

### 7.3 Wallet

1. Wallet menyimpan saldo saat ini.
2. Wallet transaction menyimpan semua perubahan saldo.
3. Saldo tidak boleh negatif.
4. Semua perubahan saldo harus melalui service.
5. Jangan update saldo langsung dari controller.
6. Gunakan database transaction untuk purchase.
7. Purchase harus atomic.
8. Jika salah satu proses gagal, semua rollback.
9. Setiap akun manusia, baik user maupun mentor, memakai satu wallet dengan `owner_type = user` dan `owner_id = users.id`.
10. Mentor yang membeli kelas mentor lain menggunakan wallet akun yang sama.
11. Pendapatan mentor juga masuk ke wallet akun yang sama.
12. Platform wallet memakai `owner_type = platform` dan `owner_id = 0`.

### 7.4 Dummy Topup

1. Topup hanya untuk user.
2. Mentor sebagai role mentor yang juga bertindak sebagai user boleh melakukan topup jika diperlukan untuk membeli kelas mentor lain.
3. Topup langsung success.
4. Tidak ada rekening.
5. Tidak ada bukti transfer.
6. Tidak ada payment gateway.
7. Minimum topup Rp10.000.
8. Maximum topup Rp10.000.000.

### 7.5 Order

1. Satu order dapat berisi satu course untuk MVP.
2. Sistem dapat dibuat fleksibel agar nanti mendukung cart/multiple item.
3. Order status untuk MVP: `paid`, `cancelled`.
4. Karena tidak ada payment gateway, order berbayar langsung `paid` jika saldo cukup.
5. Tidak ada status refund.
6. Tidak ada refund flow.
7. Untuk saldo kurang, order tidak dibuat sama sekali; status `cancelled` hanya dipakai jika admin/system membutuhkan pembatalan manual di masa depan.

### 7.6 Enrollment

1. Enrollment dibuat setelah order paid.
2. User tidak boleh enroll dua kali di kelas yang sama.
3. Enrollment tetap tercatat walaupun kelas dihapus mentor.
4. Jika course `deleted_by_mentor`, user tidak bisa mengakses materi.
5. Jika course `archived`, user lama tetap bisa mengakses materi.

### 7.7 Course Deletion

1. Mentor delete course berarti status menjadi `deleted_by_mentor`.
2. Tidak hard delete.
3. Course tidak tampil di katalog.
4. Course tidak bisa dibeli.
5. User lama melihat label "Kelas ini dihapus oleh mentor".
6. User lama tidak bisa membuka materi.
7. Admin tetap bisa melihat data course dan transaksi.

### 7.8 Course Archive

1. Course `archived` tidak tampil di katalog.
2. Course `archived` tidak bisa dibeli.
3. User yang sudah enroll tetap bisa belajar.
4. Archive berbeda dari delete.

### 7.9 Course Hidden by Admin

1. Course `hidden_by_admin` tidak tampil di katalog.
2. Course `hidden_by_admin` tidak bisa dibeli.
3. User yang sudah enroll tidak bisa membuka materi selama course masih hidden.
4. Status ini dipakai untuk moderasi/admin takedown, bukan approval flow sebelum publish.
5. Admin dapat mengembalikan status course ke `draft`, `published`, atau `archived` jika diperlukan.

### 7.10 Reviews

1. Review hanya untuk user yang sudah enroll.
2. Review langsung tampil.
3. Admin dapat hide review.
4. Rating 1-5.
5. Satu user satu review per course.

---

## 8. Functional Requirements

### 8.1 Landing Page

Halaman:

- `/`

Komponen:

1. Navbar.
2. Hero section.
3. CTA daftar sebagai user.
4. CTA daftar sebagai mentor.
5. Section benefit.
6. Section kategori.
7. Section kelas terbaru.
8. Section cara kerja.
9. Section untuk mentor.
10. Section testimonial dummy/static.
11. Footer.

Sifat:

1. Hardcoded.
2. Tidak diedit melalui admin.
3. Responsive.
4. Menggunakan Tailwind.
5. Visual modern dan clean.

### 8.2 Course Catalog

Halaman:

- `/courses`

Fitur:

1. List course published.
2. Search by title.
3. Filter category.
4. Sort latest/popular/rating/price.
5. Card course.
6. Empty state.

### 8.3 Course Detail

Halaman:

- `/courses/{slug}`

Fitur:

1. Detail course.
2. Mentor profile summary.
3. Curriculum preview.
4. Review.
5. CTA beli/enroll.
6. Related courses.

### 8.4 User Dashboard

Halaman:

- `/dashboard`
- `/my-courses`
- `/wallet`
- `/transactions`

Fitur:

1. Ringkasan saldo.
2. Kelas yang dimiliki.
3. Riwayat transaksi.
4. Topup dummy.
5. Continue learning.

### 8.5 Mentor Dashboard

Halaman:

- `/mentor/dashboard`
- `/mentor/courses`
- `/mentor/courses/create`
- `/mentor/courses/{course}/edit`
- `/mentor/courses/{course}/materials`
- `/mentor/sales`
- `/mentor/wallet`

Fitur:

1. Ringkasan saldo mentor.
2. Total kelas.
3. Total peserta.
4. Total penjualan.
5. CRUD kelas.
6. CRUD materi.
7. Riwayat pembeli.
8. Riwayat pendapatan.

### 8.6 Admin Panel

Base path:

- `/admin`

Menggunakan Filament.

Resources minimal:

1. UserResource.
2. MentorProfileResource.
3. CourseResource.
4. CourseCategoryResource.
5. CourseSectionResource optional.
6. CourseLessonResource optional.
7. CourseMaterialResource optional.
8. OrderResource.
9. WalletResource.
10. WalletTransactionResource.
11. PlatformSettingResource.
12. ReviewResource.

---

## 9. Permission Matrix

| Feature | Admin | Mentor | User | Guest |
|---|---:|---:|---:|---:|
| View landing page | Yes | Yes | Yes | Yes |
| View course catalog | Yes | Yes | Yes | Yes |
| View course detail | Yes | Yes | Yes | Yes |
| Register as user | No | No | No | Yes |
| Register as mentor | No | No | No | Yes |
| Access admin panel | Yes | No | No | No |
| Manage users | Yes | No | No | No |
| Manage commission | Yes | No | No | No |
| Create course | No | Yes | No | No |
| Publish own course | No | Yes | No | No |
| Edit own course | No | Yes | No | No |
| Delete own course | No | Yes | No | No |
| Upload material | No | Yes | No | No |
| Topup dummy | No | Yes* | Yes | No |
| Buy course | No | Yes* | Yes | No |
| Buy own course | No | No | N/A | No |
| Access purchased material | No | Yes* | Yes | No |
| Review purchased course | No | Yes* | Yes | No |
| View platform wallet | Yes | No | No | No |
| View own wallet | No | Yes | Yes | No |

`Yes*` berarti mentor dapat bertindak sebagai pembeli untuk kelas mentor lain.

---

## 10. Data Requirements

### 10.1 User

Data:

1. name
2. email
3. password
4. status
5. last_login_at optional
6. deleted_at optional

### 10.2 Mentor Profile

Data:

1. user_id
2. display_name
3. slug/username
4. bio
5. expertise
6. avatar_path
7. status

### 10.3 Course

Data:

1. mentor_id
2. category_id
3. title
4. slug
5. short_description
6. description
7. price
8. thumbnail_path
9. status
10. published_at
11. deleted_by_mentor_at optional

Status values:

1. `draft`
2. `published`
3. `archived`
4. `deleted_by_mentor`
5. `hidden_by_admin`

### 10.4 Material

Data:

1. lesson_id/course_id
2. title
3. type video/pdf
4. file_path
5. mime_type
6. file_size
7. sort_order
8. is_preview optional
9. status

### 10.5 Wallet

Data:

1. owner_type
2. owner_id
3. balance
4. currency
5. status

### 10.6 Wallet Transaction

Data:

1. wallet_id
2. owner_type
3. owner_id
4. type
5. direction
6. amount
7. balance_before
8. balance_after
9. status
10. reference_type
11. reference_id
12. description
13. metadata jsonb

### 10.7 Order

Data:

1. user_id
2. course_id
3. mentor_id
4. order_number
5. course_price_snapshot
6. commission_rate_snapshot
7. mentor_amount
8. platform_amount
9. total_amount
10. status
11. paid_at

### 10.8 Enrollment

Data:

1. user_id
2. course_id
3. order_id
4. status
5. enrolled_at

### 10.9 Review

Data:

1. user_id
2. course_id
3. rating
4. comment
5. is_published
6. edited_at optional

### 10.10 Platform Setting

Data:

1. key
2. value
3. type
4. description

---

## 11. Seeder Requirements

Seeder wajib membuat:

### 11.1 Roles

1. admin
2. mentor
3. user

### 11.2 Permissions

Minimal permissions:

1. access_admin_panel
2. manage_users
3. manage_mentors
4. manage_courses
5. manage_categories
6. manage_orders
7. manage_wallets
8. manage_settings
9. manage_reviews
10. create_course
11. edit_own_course
12. delete_own_course
13. upload_material
14. buy_course
15. topup_wallet
16. review_course

### 11.3 Demo Users

Admin:

- name: Admin
- email: admin@example.com
- password: password
- role: admin

Mentor:

- name: Mentor Demo
- email: mentor@example.com
- password: password
- role: mentor

User:

- name: User Demo
- email: user@example.com
- password: password
- role: user

### 11.4 Platform Wallet

Satu wallet platform wajib dibuat.

Owner convention:

- owner_type: `platform`
- owner_id: 0
- balance: 0
- currency: IDR

### 11.5 Platform Setting

Default:

- key: `mentor_commission_rate`
- value: `60`
- type: `integer`
- description: `Default mentor commission percentage`

### 11.6 Categories

Contoh kategori:

1. Web Development
2. Digital Marketing
3. UI/UX Design
4. Data Analytics
5. Business
6. Productivity

### 11.7 Demo Courses

Minimal:

1. Satu kelas gratis.
2. Dua kelas berbayar.
3. Minimal satu kelas punya video dummy.
4. Minimal satu kelas punya PDF dummy.
5. Semua kelas dimiliki mentor demo.

### 11.8 Demo Wallet Balance

Rekomendasi:

1. User demo wallet: Rp500.000.
2. Mentor demo wallet: Rp0 pada wallet akun mentor.
3. Platform wallet: Rp0.

---

## 12. Phase Implementation Plan

### Phase 1 — Foundation

Scope:

1. Setup Laravel 12.
2. Setup Docker Compose.
3. Setup PostgreSQL.
4. Setup Laravel Livewire starter kit.
5. Setup Filament.
6. Setup Spatie Permission.
7. Setup Tailwind.
8. Setup Queue.
9. Setup Storage.
10. Seeder role, permission, demo users.
11. Basic layout public/user/mentor.

Acceptance Criteria:

1. App bisa dijalankan dengan Docker Compose.
2. PostgreSQL terkoneksi.
3. User bisa register/login.
4. User bisa memilih role user/mentor saat register.
5. Admin bisa login ke Filament.
6. Seeder membuat admin, mentor, user.
7. Role terpasang benar.
8. Queue worker bisa berjalan.

### Phase 2 — Course Marketplace Core

Scope:

1. Course category.
2. Mentor profile.
3. CRUD course mentor.
4. Course status.
5. Upload thumbnail.
6. Course catalog.
7. Course detail.
8. Free enrollment.
9. Basic admin resources untuk course/category.

Acceptance Criteria:

1. Mentor bisa membuat kelas.
2. Mentor bisa publish kelas.
3. Visitor bisa melihat katalog.
4. User bisa enroll kelas gratis.
5. Course gratis membuat order 0 dan enrollment.
6. Course deleted tidak tampil di katalog.

### Phase 3 — Dummy Wallet & Transaction

Scope:

1. Wallet model.
2. Wallet transaction model.
3. Dummy topup.
4. Course purchase berbayar.
5. Commission service.
6. Platform wallet.
7. Mentor income masuk ke wallet akun mentor.
8. Buyer wallet debit.
9. Order and order item/enrollment.
10. Transaction ledger.

Acceptance Criteria:

1. User bisa topup dummy.
2. Saldo user bertambah.
3. User bisa membeli kelas jika saldo cukup.
4. Saldo user berkurang.
5. Saldo mentor bertambah.
6. Saldo platform bertambah.
7. Commission snapshot tersimpan.
8. Price snapshot tersimpan.
9. Semua proses purchase atomic.

### Phase 4 — Learning Access & File Protection

Scope:

1. Course learning page.
2. Private file route.
3. Video viewer.
4. PDF viewer.
5. Authorization policy.
6. Deleted course behavior.
7. Archived course behavior.

Acceptance Criteria:

1. User yang sudah enroll bisa mengakses materi.
2. User yang belum enroll mendapat 403.
3. File tidak public direct.
4. Course deleted menampilkan label di My Courses.
5. Course deleted tidak bisa dibuka.
6. Course archived tetap bisa dibuka oleh user lama.

### Phase 5 — Review, Admin Controls, Reports

Scope:

1. Review/rating.
2. Average rating.
3. Admin hide review.
4. User management.
5. Mentor management.
6. Commission setting.
7. Transaction reports.
8. Platform wallet dashboard.
9. Mentor sales report.

Acceptance Criteria:

1. User bisa review kelas yang dibeli.
2. Review langsung tampil.
3. Admin bisa hide review.
4. Admin bisa mengubah komisi.
5. Komisi baru hanya berlaku untuk transaksi baru.
6. Admin bisa melihat seluruh transaksi.
7. Mentor bisa melihat history pembeli.

### Phase 6 — Polish, Testing, Documentation

Scope:

1. UI polish modern.
2. Responsive mobile-first.
3. Empty states.
4. Loading states.
5. Error states.
6. Seeder final.
7. Feature tests.
8. Policy tests.
9. Docker deployment notes.
10. README implementation guide.

Acceptance Criteria:

1. Semua halaman utama responsive.
2. Semua role diuji.
3. Semua business rules diuji.
4. AI agent dapat mengikuti dokumentasi tanpa asumsi tambahan.
5. App siap deploy via Docker Compose.

---

## 13. Edge Cases

### 13.1 Saldo Kurang

Jika saldo user kurang:

1. Purchase ditolak.
2. Tidak ada order dibuat.
3. Tidak ada enrollment.
4. Tidak ada wallet transaction.
5. Message: "Saldo tidak mencukupi."

### 13.2 User Membeli Kelas yang Sudah Dimiliki

Jika user sudah enroll:

1. Tombol berubah menjadi "Lanjut Belajar".
2. Purchase request ditolak.
3. Tidak ada transaksi baru.

### 13.3 Kelas Dihapus Setelah Dibeli

Jika kelas status `deleted_by_mentor`:

1. Kelas muncul di My Courses sebagai disabled.
2. Label tampil.
3. Materi tidak bisa dibuka.
4. Transaksi tetap ada.

### 13.4 Kelas Diarsipkan Setelah Dibeli

Jika kelas status `archived`:

1. Kelas tidak tampil di katalog.
2. User lama tetap bisa belajar.
3. User baru tidak bisa membeli.

### 13.5 Komisi Berubah Setelah Transaksi

Jika admin mengubah komisi:

1. Order lama tetap menampilkan komisi lama.
2. Order baru memakai komisi baru.
3. Saldo lama tidak dihitung ulang.

### 13.6 Harga Berubah Setelah Transaksi

Jika mentor mengubah harga:

1. Order lama tetap memakai harga lama.
2. Order baru memakai harga baru.
3. Laporan revenue tidak berubah untuk transaksi lama.

### 13.7 Mentor Dihapus

Jika admin soft delete mentor:

1. Mentor tidak bisa login.
2. Course milik mentor dapat diubah ke status `hidden_by_admin` agar tidak tampil publik dan tidak bisa dibeli.
3. Histori transaksi tetap ada.
4. Wallet tetap ada untuk audit.

### 13.8 File Materi Hilang dari Storage

Jika file tidak ditemukan:

1. Sistem menampilkan error ramah.
2. Tidak menampilkan path internal.
3. Error dicatat di log.
4. Admin/mentor dapat mengganti file.

---

## 14. Success Metrics

MVP dianggap berhasil jika:

1. User dapat register, topup dummy, dan membeli kelas.
2. Mentor dapat register, membuat kelas, dan menerima saldo dummy.
3. Admin dapat melihat laporan transaksi dan saldo platform.
4. Komisi dinamis berjalan dengan snapshot.
5. Materi video/PDF tidak bisa diakses tanpa enrollment.
6. Semua role berjalan sesuai permission.
7. App dapat dijalankan melalui Docker Compose.
8. Seed data lengkap tersedia.
9. AI agent dapat mengimplementasikan dengan minim klarifikasi tambahan.

---

## 15. Glossary

| Istilah | Definisi |
|---|---|
| User | Pembeli kelas |
| Mentor | Penjual kelas |
| Admin | Pengelola platform |
| Course | Kelas digital |
| Lesson | Unit materi dalam kelas |
| Material | File video/PDF |
| Enrollment | Hak akses user ke kelas |
| Wallet | Saldo internal/dummy |
| Ledger | Catatan transaksi wallet |
| Dummy Topup | Topup saldo langsung sukses tanpa payment gateway |
| Platform Wallet | Wallet milik platform/admin |
| Commission Snapshot | Salinan persentase komisi saat transaksi |
| Price Snapshot | Salinan harga kelas saat transaksi |
| Deleted by Mentor | Status kelas dihapus mentor, tanpa hard delete |
| Archived | Status kelas disembunyikan dari katalog tapi user lama tetap bisa akses |
