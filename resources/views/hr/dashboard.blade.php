<x-layout>
    <x-slot:title>Dashboard HRD - MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Dashboard Pantauan Permintaan</x-slot:headerTitle>

    <div class="flex flex-col gap-6">

        {{-- RINGKASAN STATUS BULAN INI --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 md:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Ringkasan Bulan {{ now()->translatedFormat('F Y') }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Total {{ $monthlyTotal }} permintaan masuk bulan ini.</p>
                </div>
                @if($pendingRequests > 0)
                <a href="/hr/approval" class="inline-flex items-center justify-center gap-2 bg-corpblue-500 text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-corpblue-600 transition-all shrink-0 shadow-sm">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Tinjau Sekarang
                </a>
                @endif
            </div>

            {{-- BAR STATUS DISTRIBUSI --}}
            @php
                $allStatuses = [
                    'Menunggu Review' => ['label' => 'Menunggu Review', 'color' => 'bg-corpblue-500'],
                    'Pending' => ['label' => 'Pending', 'color' => 'bg-amber-500'],
                    'Disetujui' => ['label' => 'Disetujui', 'color' => 'bg-corpblue-500'],
                    'Sebagian Diterima' => ['label' => 'Sebagian Diterima', 'color' => 'bg-indigo-500'],
                    'Diterima Penuh' => ['label' => 'Diterima Penuh', 'color' => 'bg-emerald-500'],
                    'Ditutup Sebagian' => ['label' => 'Ditutup Sebagian', 'color' => 'bg-slate-400'],
                    'Dibatalkan' => ['label' => 'Dibatalkan', 'color' => 'bg-stone-400'],
                    'Ditolak' => ['label' => 'Ditolak', 'color' => 'bg-red-500'],
                ];
                $totalForBar = max($monthlyTotal, 1);
            @endphp

            @if($monthlyTotal > 0)
            <div class="flex h-3 rounded-full overflow-hidden bg-slate-100">
                @foreach($allStatuses as $key => $s)
                    @php $count = $statusDistribution->get($key, 0); @endphp
                    @if($count > 0)
                    <div class="{{ $s['color'] }} transition-all" style="width: {{ ($count / $totalForBar) * 100 }}%" title="{{ $s['label'] }}: {{ $count }}"></div>
                    @endif
                @endforeach
            </div>
            <div class="flex flex-wrap gap-x-4 gap-y-1.5 mt-3">
                @foreach($allStatuses as $key => $s)
                    @php $count = $statusDistribution->get($key, 0); @endphp
                    @if($count > 0)
                    <div class="flex items-center gap-1.5 text-xs text-slate-600">
                        <span class="w-2.5 h-2.5 rounded-sm {{ $s['color'] }}"></span>
                        <span class="font-medium">{{ $s['label'] }}</span>
                        <span class="text-slate-400">{{ $count }}</span>
                    </div>
                    @endif
                @endforeach
            </div>
            @else
            <p class="text-xs text-slate-400">Belum ada data permintaan bulan ini.</p>
            @endif
        </div>

        {{-- 4 KARTU STATISTIK --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
            {{-- Pending --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 md:p-5 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <div class="p-2 bg-amber-50 text-amber-600 rounded-lg">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <a href="/hr/approval" class="text-xs font-semibold text-corpblue-600 hover:text-corpblue-700">Tinjau</a>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900">{{ $pendingRequests }}</p>
                    <p class="text-xs text-slate-500 font-medium">Menunggu Keputusan</p>
                </div>
            </div>

            {{-- Urgent --}}
            <div class="bg-white rounded-xl border border-slate-200 border-l-4 border-l-red-500 shadow-sm p-4 md:p-5 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <div class="p-2 bg-red-50 text-red-600 rounded-lg">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    @if($urgentRequests > 0)
                    <x-status-badge domain="priority" status="Mendesak" label="Urgent" />
                    @endif
                </div>
                <div>
                    <p class="text-2xl font-bold {{ $urgentRequests > 0 ? 'text-red-600' : 'text-slate-900' }}">{{ $urgentRequests }}</p>
                    <p class="text-xs text-slate-500 font-medium">Prioritas Mendesak</p>
                </div>
            </div>

            {{-- Disetujui / Belum Diterima --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 md:p-5 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <a href="/hr/daftar-belanja" class="text-xs font-semibold text-corpblue-600 hover:text-corpblue-700">Nota</a>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900">{{ $approvedRequests }}</p>
                    <p class="text-xs text-slate-500 font-medium">Disetujui (Siap Belanja)</p>
                </div>
            </div>

            {{-- Diterima --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 md:p-5 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                </div>
                <div>
                    <p class="text-2xl font-bold text-emerald-600">{{ $completedRequests }}</p>
                    <p class="text-xs text-slate-500 font-medium">Diterima Penuh</p>
                </div>
            </div>
        </div>

        {{-- ANTREAN PERMINTAAN MENDESAK --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                <div>
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        @if($actionableRequests->isNotEmpty())
                        <span class="inline-block w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                        @endif
                        <span>Antrean Permintaan</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $actionableRequests->count() }} permintaan menunggu keputusan Anda.</p>
                </div>
                @if($actionableRequests->isNotEmpty())
                <a href="/hr/approval" class="text-xs font-semibold text-blue-600 hover:text-blue-700 bg-white border border-slate-200 px-3 py-2 rounded-lg shadow-2xs transition-all text-center shrink-0">
                    Buka Meja Verifikasi Lengkap &rarr;
                </a>
                @endif
            </div>

            {{-- Desktop Table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/70 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="py-3.5 px-6">Barang</th>
                            <th class="py-3.5 px-6">Pemohon</th>
                            <th class="py-3.5 px-6">Jumlah</th>
                            <th class="py-3.5 px-6">Prioritas</th>
                            <th class="py-3.5 px-6">Alasan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($actionableRequests as $request)
                        <tr class="hover:bg-slate-50/50 transition-all">
                            <td class="py-4 px-6">
                                <span class="font-semibold text-slate-900">{{ $request->item?->name ?? $request->item_name ?? 'Barang' }}</span>
                                <span class="mt-0.5 block text-xs text-slate-500">Stok: {{ $request->item?->stock ?? '-' }} {{ $request->item?->unit ?? $request->unit }}</span>
                            </td>
                            <td class="py-4 px-6 text-sm font-medium text-slate-600">{{ $request->user?->name ?? '-' }}</td>
                            <td class="py-4 px-6">
                                <span class="font-bold text-slate-900">{{ $request->quantity }}</span>
                                <span class="text-xs text-slate-400">{{ $request->unit }}</span>
                            </td>
                            <td class="py-4 px-6">
                                @if($request->priority === 'Mendesak')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-50 text-red-600 rounded text-xs font-semibold">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"></path></svg>
                                    Mendesak
                                </span>
                                @else
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-xs font-semibold">Biasa</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-500 max-w-[200px] truncate">{{ $request->reason }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-10 px-6 text-center">
                                <svg class="mx-auto mb-3 text-slate-300" width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p class="text-sm font-semibold text-slate-500">Tidak ada permintaan aktif</p>
                                <p class="text-xs text-slate-400 mt-0.5">Semua permintaan sudah ditindaklanjuti.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards --}}
            <div class="md:hidden flex flex-col divide-y divide-slate-100">
                @forelse($actionableRequests as $request)
                <div class="p-4 flex flex-col gap-2">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-900 truncate">{{ $request->item?->name ?? $request->item_name ?? 'Barang' }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $request->user?->name ?? '-' }} &middot; Stok {{ $request->item?->stock ?? '-' }} {{ $request->item?->unit ?? $request->unit }}</p>
                        </div>
                        @if($request->priority === 'Mendesak')
                        <x-status-badge domain="priority" status="Mendesak" label="Urgent" class="shrink-0" />
                        @endif
                    </div>
                    <div class="flex items-center gap-3 text-xs text-slate-500">
                        <span class="font-semibold text-slate-700">{{ $request->quantity }} {{ $request->unit }}</span>
                        <span class="truncate">{{ $request->reason }}</span>
                    </div>
                    <a href="/hr/requests/{{ $request->id }}" class="mt-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-corpblue-50 text-corpblue-600 rounded-lg text-xs font-semibold border border-corpblue-100 hover:bg-corpblue-500 hover:text-white transition-all min-h-[40px]">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Review
                    </a>
                </div>
                @empty
                <div class="py-10 px-4 text-center">
                    <svg class="mx-auto mb-3 text-slate-300" width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-sm font-semibold text-slate-500">Tidak ada permintaan aktif</p>
                    <p class="text-xs text-slate-400 mt-0.5">Semua permintaan sudah ditindaklanjuti.</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>
</x-layout>
