<x-layout>
    <x-slot:title>Dashboard HRD - MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Dashboard Pantauan Permintaan</x-slot:headerTitle>

    <div class="space-y-4 md:space-y-6">
        
        <!-- 1. BARIS ATAS: 3 KOTAK STATISTIK UTAMA -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 md:gap-6">
            <!-- Kartu 1: Perlu Tindakan Segera (Pending) -->
            <div class="bg-white p-4 md:p-6 rounded-xl border border-slate-200 shadow-sm flex items-center space-x-3 md:space-x-4">
                <div class="p-2.5 md:p-3 bg-amber-50 text-amber-600 rounded-lg shrink-0">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs md:text-sm font-medium text-slate-500 truncate">Perlu Tindakan (Pending)</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $pendingRequests }}</p>
                </div>
            </div>

            <!-- Kartu 2: Prioritas Mendesak (Urgent) -->
            <div class="bg-white p-4 md:p-6 rounded-xl border border-slate-200 shadow-sm flex items-center space-x-3 md:space-x-4 border-l-4 border-l-red-500">
                <div class="p-2.5 md:p-3 bg-red-50 text-red-600 rounded-lg shrink-0">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs md:text-sm font-medium text-slate-500 truncate">Urgensi Mendesak</p>
                    <p class="text-2xl font-bold text-red-600">{{ $urgentRequests }}</p>
                </div>
            </div>

            <!-- Kartu 3: Siap Belanja (Disetujui) -->
            <div class="bg-white p-4 md:p-6 rounded-xl border border-slate-200 shadow-sm flex items-center space-x-3 md:space-x-4">
                <div class="p-2.5 md:p-3 bg-emerald-50 text-emerald-600 rounded-lg shrink-0">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs md:text-sm font-medium text-slate-500 truncate">Siap Belanja (Disetujui)</p>
                    <p class="text-2xl font-bold text-emerald-600">{{ $approvedRequests }}</p>
                </div>
            </div>

            <!-- Kartu 4: Ditolak -->
            <div class="bg-white p-4 md:p-6 rounded-xl border border-slate-200 shadow-sm flex items-center space-x-3 md:space-x-4">
                <div class="p-2.5 md:p-3 bg-red-50 text-red-600 rounded-lg shrink-0">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs md:text-sm font-medium text-slate-500 truncate">Ditolak</p>
                    <p class="text-2xl font-bold text-red-600">{{ $rejectedRequests }}</p>
                </div>
            </div>
        </div>

        <x-feature-locked
            compact
            title="Dashboard Statistik Permintaan Bulanan"
            description="Analisis statistik, grafik tren, dan rekap permintaan per bulan akan tersedia pada rilis v1.1 — bersama minimum stock dan rekomendasi pembelian."
        />

        <!-- Komponen ini memadati ruang bawah layar monitor dengan informasi fungsional yang krusial -->
        <!-- 2. BARIS BAWAH: ANTREAN PENGAJUAN TERATAS (MENDESAK) WITH LIMIT -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- Header Mini-Tabel -->
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                <div>
                    <h3 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                        <span class="inline-block w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                        <span>Antrean Permintaan Paling Mendesak</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Daftar barang kritis dari Gudang yang membutuhkan keputusan persetujuan Anda segera.</p>
                </div>
                <a href="/hr/approval" class="text-xs font-semibold text-blue-600 hover:text-blue-700 bg-white border border-slate-200 px-3 py-2 rounded-lg shadow-2xs transition-all text-center shrink-0">
                    Buka Meja Verifikasi Lengkap &rarr;
                </a>
            </div>

            <!-- Konten Mini-Tabel -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50/70 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                            <th class="py-3.5 px-6">Barang</th>
                            <th class="py-3.5 px-6">Sisa Stok</th>
                            <th class="py-3.5 px-6">Jumlah Req</th>
                            <th class="py-3.5 px-6">Prioritas</th>
                            <th class="py-3.5 px-6">Alasan Ringkas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($requests as $request)
                        <tr class="hover:bg-slate-50/50 transition-all">
                            <td class="py-4 px-6 font-semibold text-slate-900">{{ $request->item?->name ?? $request->item_name ?? 'Barang' }}</td>
                            <td class="py-4 px-6 {{ $request->priority === 'Mendesak' ? 'text-red-600' : 'text-slate-600' }} font-semibold">{{ $request->item?->stock ?? '-' }} {{ $request->item?->unit ?? $request->unit }}</td>
                            <td class="py-4 px-6 font-medium">{{ $request->quantity }} <span class="text-xs text-slate-400">{{ $request->unit }}</span></td>
                            <td class="py-4 px-6"><span class="px-2 py-0.5 {{ $request->priority === 'Mendesak' ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-600' }} rounded text-xs font-semibold">{{ $request->priority }}</span></td>
                            <td class="py-4 px-6 text-xs text-slate-500 max-w-xs truncate">{{ $request->reason }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-4 px-6 text-center text-slate-500">Belum ada permintaan yang tersedia.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- ================= KOMPONEN TABLE LIMIT / PAGINATION BARU ================= -->
            <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 bg-slate-50/30 text-xs text-slate-500 font-medium">
                <!-- Sisi Kiri: Dropdown Pengatur Jumlah Baris -->
                <div class="flex items-center space-x-2">
                    <span>Tampilkan</span>
                    <select class="px-2 py-1 bg-white border border-slate-200 rounded-md focus:outline-none focus:border-blue-600 cursor-pointer text-slate-700 font-semibold">
                        <option value="10">10 Data</option>
                        <option value="25">25 Data</option>
                        <option value="50">50 Data</option>
                    </select>
                    <span>dari total 2 antrean mendesak</span>
                </div>

                <!-- Sisi Kanan: Status Halaman & Tombol Navigasi Fisik -->
                <div class="flex items-center space-x-3">
                    <span>Halaman <b>1</b> dari <b>1</b></span>
                    <div class="inline-flex space-x-1">
                        <!-- Tombol Mundur (Disabled karena masih halaman 1) -->
                        <button class="p-1.5 bg-slate-100 border border-slate-200 text-slate-400 rounded-lg cursor-not-allowed" disabled>
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </button>
                        <!-- Tombol Maju (Disabled karena data belum melebihi limit) -->
                        <button class="p-1.5 bg-slate-100 border border-slate-200 text-slate-400 rounded-lg cursor-not-allowed" disabled>
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
            <!-- ========================================================================= -->

        </div>
    </div>
</x-layout>
