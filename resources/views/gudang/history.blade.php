<x-layout>
    <x-slot:title>Riwayat Permintaan — MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Riwayat Permintaan Barang</x-slot:headerTitle>

    <div class="space-y-6">
        
        <!-- ================= QoL: SEARCH & FILTER ================= -->
        <form method="GET" action="/gudang/history" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="relative flex-1 max-w-md">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all" placeholder="Cari nama barang...">
            </div>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="/gudang/history/export/pdf{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 min-h-[44px] inline-flex items-center">PDF</a>
                <a href="/gudang/history/export/excel{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 min-h-[44px] inline-flex items-center">Excel</a>
                <select name="status" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 min-h-[44px] focus:outline-none focus:border-corpblue-500">
                    <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>Semua</option>
                    <option value="Menunggu Review" {{ request('status') === 'Menunggu Review' ? 'selected' : '' }}>Menunggu Review</option>
                    <option value="Disetujui" {{ request('status') === 'Disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="Ditolak" {{ request('status') === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
                <button type="submit" class="rounded-lg bg-corpblue-500 px-4 py-2 text-sm font-semibold text-white min-h-[44px]">Filter</button>
            </div>
        </form>

        <!-- ================= TABEL DATA BARANG ================= -->
                <!-- TABEL UTAMA (KRONOLOGIS RINGKAS) -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                            <th class="py-4 px-6">Tanggal Permintaan</th>
                            <th class="py-4 px-6">Nama Barang</th>
                            <th class="py-4 px-6">Jumlah</th>
                            <th class="py-4 px-6">Prioritas</th>
                            <th class="py-4 px-6">Status</th>
                            <th class="py-4 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($requests as $request)
                        <tr class="hover:bg-slate-50/70 transition-all">
                            <td class="py-4 px-6 text-slate-500 font-medium">{{ $request->created_at->translatedFormat('d M Y, H:i') }}</td>
                            <td class="py-4 px-6 font-semibold text-slate-900">{{ $request->item?->name ?? $request->item_name ?? 'Barang' }}</td>
                            <td class="py-4 px-6">{{ $request->quantity }} <span class="text-xs text-slate-500">{{ $request->unit }}</span></td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-0.5 {{ $request->priority === 'Mendesak' ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-600' }} rounded-full text-xs font-semibold">{{ $request->priority }}</span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 {{ $request->status === 'Disetujui' ? 'bg-emerald-50 text-emerald-700' : ($request->status === 'Ditolak' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }} rounded-full text-xs font-semibold">
                                    <span class="w-1.5 h-1.5 {{ $request->status === 'Disetujui' ? 'bg-emerald-500' : ($request->status === 'Ditolak' ? 'bg-red-500' : 'bg-amber-500') }} rounded-full"></span>
                                    <span>{{ $request->status }}</span>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <a href="/gudang/history/{{ $request->id }}" class="text-corpblue-500 hover:text-corpblue-700 font-medium text-xs bg-corpblue-50 hover:bg-corpblue-100 px-3 py-2 rounded-lg transition-all cursor-pointer inline-block min-h-[44px] leading-[36px]">Lihat Detail</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 px-6 text-center text-slate-500">Belum ada riwayat permintaan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- ================= KONTROL TABLE LIMIT SISI GUDANG ================= -->
            <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 bg-slate-50/30 text-xs text-slate-500 font-medium">
                <!-- Sisi Kiri: Dropdown Jumlah Baris -->
                <div class="flex items-center space-x-2">
                    <span>Tampilkan</span>
                    <select class="px-2 py-1 bg-white border border-slate-200 rounded-md focus:outline-none focus:border-corpblue-500 cursor-pointer text-slate-700 font-semibold">
                        <option value="10">10 Baris</option>
                        <option value="25">25 Baris</option>
                        <option value="50">50 Baris</option>
                    </select>
                    <span>dari total 12 riwayat pengajuan</span>
                </div>

                <!-- Sisi Kanan: Status Halaman & Tombol Navigasi -->
                <div class="flex items-center space-x-3">
                    <span>Halaman <b>1</b> dari <b>2</b></span>
                    <div class="inline-flex space-x-1">
                        <!-- Tombol Mundur (Disabled) -->
                        <button class="p-1.5 bg-slate-100 border border-slate-200 text-slate-400 rounded-lg cursor-not-allowed" disabled>
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </button>
                        <!-- Tombol Maju (Aktif karena total data ada 12, berasumsi limit halaman adalah 10) -->
                        <button class="p-1.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-lg cursor-pointer transition-colors">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>
                </div>
            </div>

        </div>

    </div>

</x-layout>