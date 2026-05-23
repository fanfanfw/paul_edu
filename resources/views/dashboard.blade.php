<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($isAdmin)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-wide text-amber-600">Admin</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Monitoring platform via Filament</h3>
                    <p class="mt-2 max-w-2xl text-sm text-slate-600">Kelola kategori, pantau course, order, wallet, ledger, review, dan pengaturan komisi dari admin panel. Area buyer seperti wallet dan my courses sengaja tidak ditampilkan untuk admin.</p>
                    <a href="{{ url('/admin') }}" class="mt-5 inline-flex rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Buka Admin Panel</a>
                </div>
            @elseif ($isMentor)
                <div class="space-y-6">
                    <section class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                        <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-950 p-6 text-white shadow-sm sm:p-8">
                            <p class="text-sm font-black uppercase tracking-[0.22em] text-indigo-200">Mentor overview</p>
                            <h3 class="mt-4 max-w-2xl text-3xl font-black tracking-tight sm:text-4xl">Pantau performa kelas dan income Anda.</h3>
                            <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-300">Dashboard ini sekarang berisi data yang bisa dipakai mengambil keputusan: status kelas, penjualan, saldo, dan tren revenue 6 bulan terakhir.</p>

                            <div class="mt-8 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-3xl bg-white/10 p-4 ring-1 ring-white/10">
                                    <p class="text-sm text-slate-300">Total income</p>
                                    <p class="mt-2 text-2xl font-black">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
                                </div>
                                <div class="rounded-3xl bg-white/10 p-4 ring-1 ring-white/10">
                                    <p class="text-sm text-slate-300">Bulan ini</p>
                                    <p class="mt-2 text-2xl font-black">Rp {{ number_format($monthRevenue, 0, ',', '.') }}</p>
                                </div>
                                <div class="rounded-3xl bg-white/10 p-4 ring-1 ring-white/10">
                                    <p class="text-sm text-slate-300">Saldo wallet</p>
                                    <p class="mt-2 text-2xl font-black">Rp {{ number_format($walletBalance, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-bold text-slate-500">Status kelas</p>
                                    <p class="mt-2 text-4xl font-black text-slate-950">{{ $courseCount }}</p>
                                </div>
                                <a href="{{ route('mentor.courses.create') }}" class="rounded-full bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800" wire:navigate>Buat kelas</a>
                            </div>
                            <div class="mt-6 space-y-4">
                                @foreach ([['Published', $publishedCount, 'bg-emerald-500'], ['Draft', $draftCount, 'bg-amber-500'], ['Archived', $archivedCount, 'bg-slate-400']] as [$label, $count, $color])
                                    <div>
                                        <div class="flex justify-between text-sm font-semibold text-slate-700">
                                            <span>{{ $label }}</span>
                                            <span>{{ $count }}</span>
                                        </div>
                                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full {{ $color }}" style="width: {{ $courseCount > 0 ? max(8, round(($count / $courseCount) * 100)) : 0 }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-6 lg:grid-cols-[1fr_380px]">
                        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                            <div class="flex flex-wrap items-end justify-between gap-4">
                                <div>
                                    <p class="text-sm font-bold text-slate-500">Grafik income mentor</p>
                                    <h3 class="mt-1 text-2xl font-black text-slate-950">6 bulan terakhir</h3>
                                </div>
                                <p class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-bold text-indigo-700">{{ $totalSales }} order</p>
                            </div>
                            <div class="mt-8 flex h-64 items-end gap-3 sm:gap-5">
                                @foreach ($chartMonths as $month)
                                    <div class="flex flex-1 flex-col items-center gap-3">
                                        <div class="flex h-48 w-full items-end rounded-full bg-slate-100 p-1">
                                            <div class="w-full rounded-full bg-indigo-600" style="height: {{ $month['amount'] > 0 ? max(8, round(($month['amount'] / $maxChartAmount) * 100)) : 0 }}%"></div>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-xs font-black text-slate-700">{{ $month['label'] }}</p>
                                            <p class="mt-1 text-[11px] font-semibold text-slate-400">{{ $month['amount'] > 0 ? 'Rp '.number_format($month['amount'], 0, ',', '.') : '-' }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                                <h3 class="text-lg font-black text-slate-950">Kelas terlaris</h3>
                                <div class="mt-4 space-y-3">
                                    @forelse ($topCourses as $course)
                                        <div class="rounded-2xl bg-slate-50 p-4">
                                            <p class="font-bold text-slate-900">{{ $course['title'] }}</p>
                                            <p class="mt-1 text-sm text-slate-500">{{ $course['sales'] }} order · Rp {{ number_format($course['revenue'], 0, ',', '.') }}</p>
                                        </div>
                                    @empty
                                        <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">Belum ada penjualan. Publish kelas dan arahkan calon peserta ke katalog.</p>
                                    @endforelse
                                </div>
                            </div>

                            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                                <h3 class="text-lg font-black text-slate-950">Order terbaru</h3>
                                <div class="mt-4 space-y-3">
                                    @forelse ($recentOrders as $order)
                                        <div class="flex items-start justify-between gap-3 rounded-2xl bg-slate-50 p-4">
                                            <div>
                                                <p class="font-bold text-slate-900">{{ $order->course_title_snapshot }}</p>
                                                <p class="mt-1 text-sm text-slate-500">{{ $order->user?->name }} · {{ $order->paid_at?->format('d M Y') }}</p>
                                            </div>
                                            <p class="shrink-0 text-sm font-black text-emerald-700">+ Rp {{ number_format($order->mentor_amount, 0, ',', '.') }}</p>
                                        </div>
                                    @empty
                                        <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">Belum ada order masuk.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            @else
                <div class="space-y-6">
                    <section class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
                        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                            <p class="text-sm font-black uppercase tracking-[0.22em] text-indigo-700">Learning wallet</p>
                            <h3 class="mt-4 text-4xl font-black tracking-tight text-slate-950">Rp {{ number_format($walletBalance, 0, ',', '.') }}</h3>
                            <p class="mt-3 text-sm leading-6 text-slate-600">Saldo siap dipakai untuk membeli kelas berbayar. Topup masih dummy untuk kebutuhan MVP.</p>
                            <div class="mt-6 grid grid-cols-2 gap-3">
                                <div class="rounded-3xl bg-slate-50 p-4">
                                    <p class="text-sm font-semibold text-slate-500">Kelas saya aktif</p>
                                    <p class="mt-2 text-3xl font-black text-slate-950">{{ $activeEnrollmentCount }}</p>
                                </div>
                                <div class="rounded-3xl bg-slate-50 p-4">
                                    <p class="text-sm font-semibold text-slate-500">Total dibelanjakan</p>
                                    <p class="mt-2 text-3xl font-black text-slate-950">Rp {{ number_format($totalSpent, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-950 p-6 text-white shadow-sm sm:p-8">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-black uppercase tracking-[0.22em] text-indigo-200">Belajar berikutnya</p>
                                    <h3 class="mt-3 text-3xl font-black tracking-tight">Lanjutkan kelas terakhir Anda.</h3>
                                </div>
                                <p class="rounded-full bg-white/10 px-3 py-1 text-sm font-bold text-slate-200 ring-1 ring-white/10">{{ $orderCount }} order</p>
                            </div>
                            <div class="mt-6 grid gap-3">
                                @forelse ($activeEnrollments as $enrollment)
                                    <a href="{{ route('student.learn', $enrollment->course) }}" class="rounded-3xl bg-white/10 p-4 ring-1 ring-white/10 transition hover:bg-white/15" wire:navigate>
                                        <p class="text-xs font-black uppercase tracking-[0.18em] text-indigo-200">{{ $enrollment->course?->category?->name }}</p>
                                        <p class="mt-2 font-black text-white">{{ $enrollment->course?->title }}</p>
                                        <p class="mt-1 text-sm text-slate-300">Mentor: {{ $enrollment->course?->mentor?->name }}</p>
                                    </a>
                                @empty
                                    <div class="rounded-3xl bg-white/10 p-5 ring-1 ring-white/10">
                                        <p class="font-black text-white">Belum ada kelas aktif.</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-300">Mulai dari katalog untuk enroll kelas gratis atau membeli kelas berbayar.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-6 lg:grid-cols-[1fr_420px]">
                        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                            <div class="flex flex-wrap items-end justify-between gap-4">
                                <div>
                                    <p class="text-sm font-bold text-slate-500">Rekomendasi kelas</p>
                                    <h3 class="mt-1 text-2xl font-black text-slate-950">Temukan skill baru</h3>
                                </div>
                                <a href="{{ route('courses.index') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50" wire:navigate>Lihat katalog</a>
                            </div>
                            <div class="mt-6 grid gap-4 md:grid-cols-3">
                                @forelse ($recommendedCourses as $course)
                                    <a href="{{ route('courses.show', $course) }}" class="rounded-3xl border border-slate-200 bg-slate-50 p-4 transition hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-white" wire:navigate>
                                        <p class="text-xs font-black uppercase tracking-[0.18em] text-indigo-700">{{ $course->category?->name }}</p>
                                        <p class="mt-3 font-black leading-snug text-slate-950">{{ $course->title }}</p>
                                        <p class="mt-2 text-sm text-slate-500">{{ $course->price > 0 ? 'Rp '.number_format($course->price, 0, ',', '.') : 'Gratis' }}</p>
                                    </a>
                                @empty
                                    <p class="rounded-3xl bg-slate-50 p-5 text-sm text-slate-500 md:col-span-3">Belum ada rekomendasi baru saat ini.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                            <h3 class="text-2xl font-black text-slate-950">Transaksi terbaru</h3>
                            <div class="mt-5 space-y-3">
                                @forelse ($recentTransactions as $transaction)
                                    <div class="flex items-start justify-between gap-3 rounded-2xl bg-slate-50 p-4">
                                        <div>
                                            <p class="font-bold text-slate-900">{{ str_replace('_', ' ', $transaction->type->value) }}</p>
                                            <p class="mt-1 text-sm text-slate-500">{{ $transaction->created_at->format('d M Y H:i') }}</p>
                                        </div>
                                        <p class="shrink-0 text-sm font-black {{ $transaction->direction->value === 'credit' ? 'text-emerald-700' : 'text-rose-700' }}">
                                            {{ $transaction->direction->value === 'credit' ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                        </p>
                                    </div>
                                @empty
                                    <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">Belum ada transaksi.</p>
                                @endforelse
                            </div>
                        </div>
                    </section>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
