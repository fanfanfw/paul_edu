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
    <body class="bg-[radial-gradient(circle_at_18%_12%,oklch(0.92_0.06_250),transparent_30rem),linear-gradient(180deg,oklch(0.99_0.006_90),oklch(0.96_0.012_250))] font-sans text-slate-950 antialiased">
        <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-4 sm:px-6 lg:px-8">
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

            <section class="grid flex-1 items-center gap-10 py-12 sm:py-16 lg:grid-cols-[1.05fr_0.95fr] lg:py-20">
                <div class="relative">
                    <p class="inline-flex rounded-full border border-indigo-200 bg-white/70 px-4 py-2 text-sm font-black text-indigo-700 shadow-sm backdrop-blur">Marketplace kelas skill digital</p>
                    <h1 class="mt-6 max-w-4xl text-5xl font-black leading-[0.95] tracking-tight text-slate-950 sm:text-6xl lg:text-7xl">Belajar skill digital tanpa alur yang ribet.</h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-650">Temukan kelas dari mentor, enroll kelas gratis, beli kelas berbayar dengan wallet dummy, lalu akses materi private dalam satu platform Laravel + Livewire.</p>
                    <div class="mt-9 flex flex-wrap gap-3">
                        <a href="{{ route('courses.index') }}" class="rounded-full bg-slate-950 px-6 py-3 text-sm font-black text-white shadow-[0_16px_40px_rgba(15,23,42,0.22)] transition hover:-translate-y-0.5 hover:bg-slate-800">Lihat katalog</a>
                        <a href="{{ route('register') }}" class="rounded-full border border-slate-300 bg-white/80 px-6 py-3 text-sm font-black text-slate-800 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-400 hover:bg-white">Daftar sebagai mentor</a>
                    </div>
                    <dl class="mt-10 grid max-w-xl grid-cols-3 gap-3 text-sm">
                        <div class="rounded-3xl bg-white/75 p-4 shadow-sm ring-1 ring-slate-200/70 backdrop-blur"><dt class="font-semibold text-slate-500">Kategori</dt><dd class="mt-2 text-2xl font-black">6</dd></div>
                        <div class="rounded-3xl bg-white/75 p-4 shadow-sm ring-1 ring-slate-200/70 backdrop-blur"><dt class="font-semibold text-slate-500">Demo kelas</dt><dd class="mt-2 text-2xl font-black">3</dd></div>
                        <div class="rounded-3xl bg-white/75 p-4 shadow-sm ring-1 ring-slate-200/70 backdrop-blur"><dt class="font-semibold text-slate-500">Stack</dt><dd class="mt-2 text-2xl font-black">Livewire</dd></div>
                    </dl>
                </div>

                <div class="relative">
                    <div class="absolute -right-8 -top-8 hidden size-36 rounded-full bg-indigo-200/60 blur-3xl lg:block"></div>
                    <div class="relative rounded-[2rem] border border-slate-200 bg-white/85 p-4 shadow-[0_30px_90px_rgba(15,23,42,0.14)] backdrop-blur sm:p-6">
                        <div class="overflow-hidden rounded-[1.5rem] bg-[linear-gradient(135deg,oklch(0.91_0.06_254),oklch(0.96_0.04_145))] p-6">
                            <div class="flex items-center justify-between gap-4">
                                <p class="rounded-full bg-white/85 px-3 py-1 text-xs font-black uppercase tracking-[0.18em] text-slate-600">Preview kelas</p>
                                <p class="rounded-full bg-slate-950 px-3 py-1 text-xs font-black text-white">Rp 250.000</p>
                            </div>
                            <h2 class="mt-24 max-w-sm text-3xl font-black leading-tight tracking-tight text-slate-950">Laravel 12 dari Nol sampai Deploy</h2>
                            <p class="mt-3 max-w-sm text-sm leading-6 text-slate-700">Routing, model, migration, Blade, Livewire, dan deployment dasar.</p>
                        </div>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-3xl border border-slate-200 bg-stone-50 p-4">
                                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Mentor</p>
                                <p class="mt-2 font-black text-slate-950">Mentor Demo</p>
                            </div>
                            <div class="rounded-3xl border border-slate-200 bg-stone-50 p-4">
                                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Akses</p>
                                <p class="mt-2 font-black text-slate-950">Materi private</p>
                            </div>
                        </div>
                        <a href="{{ route('courses.index') }}" class="mt-4 flex items-center justify-between rounded-3xl bg-slate-950 px-5 py-4 text-sm font-black text-white transition hover:-translate-y-0.5 hover:bg-slate-800">
                            Jelajahi semua kelas
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
