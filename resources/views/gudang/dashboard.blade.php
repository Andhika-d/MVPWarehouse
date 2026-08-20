<x-layout>
    <x-slot:title>Dashboard Utama - CorpLogistics</x-slot:title>
    <x-slot:headerTitle>Ringkasan Aktivitas</x-slot:headerTitle>

    <div class="space-y-4 md:space-y-8">
        
        <!-- 1. BARIS KARTU INFORMASI (STATS CARDS) -->
        <!-- Sangat berguna untuk orang awam melihat status pengajuan mereka secara cepat -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 md:gap-6">
            
            <!-- Kartu 1: Total Request -->
            <div class="bg-white p-4 md:p-6 rounded-xl border border-slate-200 shadow-sm flex items-center space-x-3 md:space-x-4">
                <div class="p-2.5 md:p-3 bg-blue-50 text-blue-600 rounded-lg shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Total Pengajuan</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $totalRequests }}</p>
                </div>
            </div>

            <!-- Kartu 2: Menunggu Persetujuan -->
            <div class="bg-white p-4 md:p-6 rounded-xl border border-slate-200 shadow-sm flex items-center space-x-3 md:space-x-4">
                <div class="p-2.5 md:p-3 bg-amber-50 text-amber-600 rounded-lg shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Menunggu Persetujuan</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $pendingRequests }}</p>
                </div>
            </div>

            <!-- Kartu 3: Selesai / Disetujui -->
            <div class="bg-white p-4 md:p-6 rounded-xl border border-slate-200 shadow-sm flex items-center space-x-3 md:space-x-4">
                <div class="p-2.5 md:p-3 bg-emerald-50 text-emerald-600 rounded-lg shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Barang Disetujui</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $approvedRequests }}</p>
                </div>
            </div>

            <!-- Kartu 4: Ditolak -->
            <div class="bg-white p-4 md:p-6 rounded-xl border border-slate-200 shadow-sm flex items-center space-x-3 md:space-x-4">
                <div class="p-2.5 md:p-3 bg-red-50 text-red-600 rounded-lg shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Barang Ditolak</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $rejectedRequests }}</p>
                </div>
            </div>

        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-slate-900">Ringkasan Prioritas</h3>
            <p class="mt-2 text-sm text-slate-500">Anda memiliki {{ $urgentRequests }} permintaan prioritas mendesak.</p>
        </div>

        <!-- 2. BLOK SEAMLESS UNTUK PANDUAN / AKSI CEPAT -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl p-5 md:p-8 text-white shadow-md flex flex-col md:flex-row items-start md:items-center justify-between gap-4 md:gap-6">
            <div>
                <h2 class="text-xl font-bold mb-2">Butuh fasilitas atau barang baru untuk bekerja?</h2>
                <p class="text-blue-100 text-sm max-w-xl">Kamu bisa mengajukan permintaan barang operasional dengan mudah di sini.</p>
            </div>
            <a href="/gudang/request-barang" class="bg-white text-blue-600 hover:bg-blue-50 px-5 py-3 rounded-lg font-semibold text-sm transition-all shadow-sm shrink-0">
                Buat Request Sekarang &rarr;
            </a>
        </div>

        <!-- 3. PLACEHOLDER UNTUK AKTIVITAS TERBARU -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-slate-900">Aktivitas Terakhir Anda</h3>
                <a href="/gudang/history" class="text-sm font-medium text-blue-600 hover:underline">Lihat Semua History</a>
            </div>
            @if($requests->isEmpty())
                <p class="text-sm text-slate-400 py-8 text-center border border-dashed border-slate-100 rounded-lg">
                    Belum ada pengajuan terbaru.
                </p>
            @else
                <div class="space-y-3">
                    @foreach($requests as $request)
                        <div class="flex items-center justify-between rounded-lg border border-slate-100 p-3 bg-slate-50">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $request->item?->name ?? $request->item_name ?? 'Barang' }}</p>
                                <p class="text-sm text-slate-500">{{ $request->quantity }} {{ $request->unit }} • {{ $request->priority }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold shrink-0 {{ $request->status === 'Disetujui' ? 'bg-emerald-50 text-emerald-700' : ($request->status === 'Ditolak' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                                {{ $request->status }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</x-layout>
