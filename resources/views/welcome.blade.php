<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Skill Digital Platform') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[radial-gradient(circle_at_18%_8%,oklch(0.91_0.07_250),transparent_34rem),radial-gradient(circle_at_90%_18%,oklch(0.94_0.07_145),transparent_28rem),linear-gradient(180deg,oklch(0.99_0.006_90),oklch(0.96_0.012_250))] font-sans text-slate-950 antialiased">
        <main class="overflow-hidden">
            <section class="mx-auto flex min-h-screen max-w-7xl flex-col px-4 sm:px-6 lg:px-8">
                <nav class="flex items-center justify-between py-5">
                    <a href="{{ url('/') }}" class="group inline-flex items-center gap-3 text-sm font-extrabold tracking-tight">
                        <span class="grid size-9 place-items-center rounded-2xl bg-slate-950 text-sm font-black text-white shadow-sm transition group-hover:-rotate-3">S</span>
                        <span>Skill Digital</span>
                    </a>
                    <div class="flex items-center gap-2 text-sm sm:gap-4">
                        <a href="{{ route('courses.index') }}" class="font-semibold text-slate-600 transition hover:text-slate-950">Katalog</a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="font-semibold text-slate-600 transition hover:text-slate-950">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="font-semibold text-slate-600 transition hover:text-slate-950">Login</a>
                            <a href="{{ route('register') }}" class="rounded-full bg-slate-950 px-4 py-2 font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800">Daftar</a>
                        @endauth
                    </div>
                </nav>

                <div class="grid flex-1 items-center gap-12 py-12 lg:grid-cols-[1.02fr_0.98fr] lg:py-16">
                    <div class="relative">
                        <p class="inline-flex rounded-full border border-indigo-200 bg-white/70 px-4 py-2 text-sm font-black text-indigo-700 shadow-sm backdrop-blur">Marketplace kelas skill digital</p>
                        <h1 class="mt-6 max-w-5xl text-5xl font-black leading-[0.92] tracking-tight text-slate-950 sm:text-7xl lg:text-8xl">Belajar skill digital yang langsung terasa gunanya.</h1>
                        <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-650">Pilih kelas, lihat mentor, cek harga dan rating, lalu mulai belajar tanpa proses yang bertele-tele. Cocok untuk pemula yang ingin naik level, pekerja yang ingin upgrade skill, dan mentor yang ingin menjual kelas digital dengan rapi.</p>
                        <div class="mt-9 flex flex-wrap gap-3">
                            <a href="{{ route('courses.index') }}" class="rounded-full bg-slate-950 px-6 py-3 text-sm font-black text-white shadow-[0_16px_40px_rgba(15,23,42,0.22)] transition hover:-translate-y-0.5 hover:bg-slate-800">Mulai jelajahi kelas</a>
                            <a href="{{ route('register') }}" class="rounded-full border border-slate-300 bg-white/80 px-6 py-3 text-sm font-black text-slate-800 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-400 hover:bg-white">Gabung sebagai mentor</a>
                        </div>
                        <dl class="mt-10 grid max-w-2xl gap-3 text-sm sm:grid-cols-3">
                            <div class="rounded-3xl bg-white/75 p-4 shadow-sm ring-1 ring-slate-200/70 backdrop-blur"><dt class="font-semibold text-slate-500">Kategori aktif</dt><dd class="mt-2 text-2xl font-black">6</dd></div>
                            <div class="rounded-3xl bg-white/75 p-4 shadow-sm ring-1 ring-slate-200/70 backdrop-blur"><dt class="font-semibold text-slate-500">Kelas demo</dt><dd class="mt-2 text-2xl font-black">3</dd></div>
                            <div class="rounded-3xl bg-white/75 p-4 shadow-sm ring-1 ring-slate-200/70 backdrop-blur"><dt class="font-semibold text-slate-500">Akses belajar</dt><dd class="mt-2 text-2xl font-black">Instan</dd></div>
                        </dl>
                    </div>

                    <div class="relative lg:pl-6">
                        <div class="absolute -right-12 -top-10 hidden size-44 rounded-full bg-emerald-200/70 blur-3xl lg:block"></div>
                        <div class="absolute -bottom-12 -left-10 hidden size-40 rounded-full bg-indigo-200/70 blur-3xl lg:block"></div>
                        <div class="relative rotate-1 rounded-[2.25rem] border border-slate-200 bg-white/85 p-4 shadow-[0_35px_100px_rgba(15,23,42,0.16)] backdrop-blur sm:p-6">
                            <div class="rounded-[1.75rem] bg-slate-950 p-5 text-white">
                                <div class="flex items-center justify-between gap-4">
                                    <p class="rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.18em] text-indigo-100 ring-1 ring-white/10">Kelas pilihan</p>
                                    <p class="rounded-full bg-emerald-300 px-3 py-1 text-xs font-black text-slate-950">Siap mulai</p>
                                </div>
                                <h2 class="mt-20 max-w-sm text-4xl font-black leading-tight tracking-tight">Bangun portofolio digital pertama Anda</h2>
                                <p class="mt-4 max-w-md text-sm leading-6 text-slate-300">Belajar dari fondasi, praktik membuat proyek, lalu simpan materi kelas untuk diakses kembali kapan saja.</p>
                            </div>
                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-3xl border border-slate-200 bg-stone-50 p-4">
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Untuk peserta</p>
                                    <p class="mt-2 font-black text-slate-950">Kelas tersusun, harga jelas, akses materi aman.</p>
                                </div>
                                <div class="rounded-3xl border border-slate-200 bg-stone-50 p-4">
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Untuk mentor</p>
                                    <p class="mt-2 font-black text-slate-950">Kelola kelas, materi, penjualan, dan income.</p>
                                </div>
                            </div>
                            <div class="mt-4 rounded-3xl bg-indigo-50 p-5">
                                <p class="text-sm font-black text-indigo-800">Alur singkat</p>
                                <div class="mt-4 grid gap-3 text-sm text-slate-700 sm:grid-cols-3">
                                    <p><span class="font-black text-slate-950">01.</span> Cari kelas</p>
                                    <p><span class="font-black text-slate-950">02.</span> Enroll atau beli</p>
                                    <p><span class="font-black text-slate-950">03.</span> Mulai belajar</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-20">
                <div class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-end">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.24em] text-indigo-700">Kenapa pakai Skill Digital</p>
                        <h2 class="mt-4 max-w-2xl text-4xl font-black tracking-tight text-slate-950 sm:text-5xl">Bukan sekadar katalog. Ini ruang belajar yang dibuat untuk jalan terus.</h2>
                    </div>
                    <p class="max-w-3xl text-lg leading-8 text-slate-650">Banyak orang berhenti belajar bukan karena tidak mampu, tapi karena alurnya membingungkan: kelas sulit dicari, akses materi tersebar, dan progres tidak terasa. Di sini, pengalaman dibuat lebih ringkas supaya peserta bisa fokus ke praktik, sedangkan mentor bisa fokus mengajar.</p>
                </div>

                <div class="mt-10 grid gap-5 md:grid-cols-3">
                    <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-5xl font-black text-indigo-700">01</p>
                        <h3 class="mt-8 text-xl font-black text-slate-950">Kelas mudah dibandingkan</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Peserta bisa melihat kategori, mentor, harga, rating, dan ringkasan kelas sebelum memutuskan.</p>
                    </article>
                    <article class="rounded-[2rem] border border-slate-200 bg-slate-950 p-6 text-white shadow-sm">
                        <p class="text-5xl font-black text-emerald-300">02</p>
                        <h3 class="mt-8 text-xl font-black">Materi tetap dalam satu tempat</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-300">Setelah enroll atau membeli kelas, peserta dapat membuka materi dari area belajar yang terlindungi.</p>
                    </article>
                    <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-5xl font-black text-indigo-700">03</p>
                        <h3 class="mt-8 text-xl font-black text-slate-950">Mentor punya ruang operasional</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Mentor dapat membuat kelas, menyusun materi, memantau penjualan, dan melihat income dari satu dashboard.</p>
                    </article>
                </div>
            </section>

            <section class="bg-slate-950 py-14 text-white sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid gap-10 lg:grid-cols-[1fr_1fr] lg:items-center">
                        <div>
                            <p class="text-sm font-black uppercase tracking-[0.24em] text-emerald-300">Untuk peserta</p>
                            <h2 class="mt-4 text-4xl font-black tracking-tight sm:text-5xl">Belajar tanpa merasa tersesat.</h2>
                            <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-300">Mulai dari kelas gratis, naik ke kelas berbayar saat sudah siap, dan gunakan wallet untuk transaksi dummy di MVP ini. Semua dibuat agar proses belajar terasa ringan, jelas, dan bisa dilanjutkan kapan saja.</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-3xl bg-white/10 p-5 ring-1 ring-white/10">
                                <h3 class="font-black">Mulai dari pemula</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Pilih kelas fondasi sebelum masuk topik yang lebih spesifik.</p>
                            </div>
                            <div class="rounded-3xl bg-white/10 p-5 ring-1 ring-white/10">
                                <h3 class="font-black">Belajar sesuai kebutuhan</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Gunakan filter kategori dan pencarian untuk menemukan kelas paling relevan.</p>
                            </div>
                            <div class="rounded-3xl bg-white/10 p-5 ring-1 ring-white/10">
                                <h3 class="font-black">Riwayat rapi</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Transaksi wallet dan kelas yang diikuti tersimpan di dashboard peserta.</p>
                            </div>
                            <div class="rounded-3xl bg-white/10 p-5 ring-1 ring-white/10">
                                <h3 class="font-black">Akses materi privat</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Materi kelas hanya dibuka untuk peserta yang sudah memiliki akses aktif.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
                <div class="grid gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:items-center">
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.08)] sm:p-8">
                        <p class="text-sm font-black uppercase tracking-[0.22em] text-indigo-700">Area mentor</p>
                        <h2 class="mt-4 text-4xl font-black tracking-tight text-slate-950">Jual kelas dengan halaman yang lebih terstruktur.</h2>
                        <p class="mt-5 text-base leading-8 text-slate-650">Mentor tidak perlu menyebar materi dan catatan penjualan di banyak tempat. Buat kelas, publish saat siap, unggah materi, lalu pantau order dan pendapatan dari dashboard.</p>
                        <a href="{{ route('register') }}" class="mt-7 inline-flex rounded-full bg-slate-950 px-6 py-3 text-sm font-black text-white transition hover:-translate-y-0.5 hover:bg-slate-800">Mulai sebagai mentor</a>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Kelas</p>
                            <h3 class="mt-3 text-xl font-black text-slate-950">Atur status publish</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Simpan draft, publish kelas, atau arsipkan saat materi perlu diperbarui.</p>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Materi</p>
                            <h3 class="mt-3 text-xl font-black text-slate-950">Susun section dan lesson</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Buat struktur belajar yang mudah diikuti peserta dari awal sampai selesai.</p>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Sales</p>
                            <h3 class="mt-3 text-xl font-black text-slate-950">Lihat order masuk</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Pantau kelas mana yang mulai diminati dan siapa peserta yang membeli.</p>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Wallet</p>
                            <h3 class="mt-3 text-xl font-black text-slate-950">Pantau income</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Pendapatan kelas tercatat jelas agar mentor bisa mengevaluasi performa.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8 lg:pb-24">
                <div class="rounded-[2.5rem] bg-[linear-gradient(135deg,oklch(0.91_0.06_254),oklch(0.97_0.05_145))] p-6 shadow-[0_28px_80px_rgba(15,23,42,0.12)] sm:p-10 lg:p-12">
                    <div class="grid gap-10 lg:grid-cols-[1fr_0.9fr] lg:items-center">
                        <div>
                            <p class="text-sm font-black uppercase tracking-[0.24em] text-indigo-800">Mulai hari ini</p>
                            <h2 class="mt-4 max-w-3xl text-4xl font-black tracking-tight text-slate-950 sm:text-6xl">Cari satu kelas yang membuat minggu ini lebih produktif.</h2>
                            <p class="mt-5 max-w-2xl text-base leading-8 text-slate-700">Tidak perlu menunggu waktu sempurna. Mulai dari kelas gratis, cek kelas berbayar yang relevan, atau daftar sebagai mentor kalau Anda sudah siap membagikan keahlian.</p>
                        </div>
                        <div class="rounded-[2rem] bg-white/80 p-5 shadow-sm ring-1 ring-white/60 backdrop-blur">
                            <div class="grid gap-3">
                                <a href="{{ route('courses.index') }}" class="flex items-center justify-between rounded-3xl bg-slate-950 px-5 py-4 text-sm font-black text-white transition hover:-translate-y-0.5 hover:bg-slate-800">
                                    Buka katalog kelas
                                    <span aria-hidden="true">→</span>
                                </a>
                                <a href="{{ route('register') }}" class="flex items-center justify-between rounded-3xl border border-slate-300 bg-white px-5 py-4 text-sm font-black text-slate-800 transition hover:-translate-y-0.5 hover:border-slate-400">
                                    Buat akun gratis
                                    <span aria-hidden="true">→</span>
                                </a>
                            </div>
                            <p class="mt-5 text-sm leading-6 text-slate-600">Akun yang sama bisa digunakan untuk belajar sebagai peserta atau mengelola kelas sebagai mentor sesuai role yang dipilih saat daftar.</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
