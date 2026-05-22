<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Skill Digital Platform') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 font-sans text-slate-900 antialiased">
        <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center justify-between py-6">
                <a href="{{ url('/') }}" class="text-sm font-bold">Skill Digital Platform</a>
                <div class="flex items-center gap-3 text-sm">
                    <a href="{{ route('courses.index') }}" class="font-medium text-slate-700 hover:text-indigo-600">Katalog</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="font-medium text-slate-700 hover:text-indigo-600">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="font-medium text-slate-700 hover:text-indigo-600">Login</a>
                        <a href="{{ route('register') }}" class="rounded-xl bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Daftar</a>
                    @endauth
                </div>
            </nav>

            <section class="grid flex-1 items-center gap-10 py-16 lg:grid-cols-[1.05fr_0.95fr]">
                <div>
                    <p class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-sm font-semibold text-indigo-700">Marketplace kelas skill digital</p>
                    <h1 class="mt-6 max-w-3xl text-4xl font-bold tracking-tight text-slate-950 md:text-6xl">Belajar skill digital dari mentor terbaik</h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">Fondasi marketplace sudah siap untuk katalog kelas. Pembelian, materi, dan review akan ditambahkan bertahap sesuai MVP.</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('courses.index') }}" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Lihat katalog</a>
                        <a href="{{ route('register') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Daftar sebagai mentor</a>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="rounded-2xl bg-gradient-to-br from-indigo-100 via-slate-100 to-emerald-100 p-6">
                        <p class="text-sm font-semibold text-indigo-700">Preview kelas</p>
                        <h2 class="mt-12 text-2xl font-bold text-slate-950">Laravel 12 dari Nol sampai Deploy</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Contoh kartu kelas untuk katalog publik.</p>
                    </div>
                    <div class="mt-5 grid grid-cols-3 gap-3 text-center text-sm">
                        <div class="rounded-xl bg-slate-50 p-3"><strong class="block text-slate-950">6</strong>Kategori</div>
                        <div class="rounded-xl bg-slate-50 p-3"><strong class="block text-slate-950">3</strong>Demo kelas</div>
                        <div class="rounded-xl bg-slate-50 p-3"><strong class="block text-slate-950">Livewire</strong>Stack</div>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
