<x-layout>
    <x-slot:title>Dashboard — MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Dashboard</x-slot:headerTitle>

    <div class="space-y-4">

        {{-- Stats --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-xs font-medium text-slate-500">Total Pengajuan</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $totalRequests }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-xs font-medium text-slate-500">Menunggu</p>
                <p class="text-2xl font-bold text-amber-600 mt-1">{{ $pendingRequests }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-xs font-medium text-slate-500">Menunggu Diterima</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">{{ $waitingReceipt }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-xs font-medium text-slate-500">Disetujui</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $approvedRequests }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-xs font-medium text-slate-500">Ditolak</p>
                <p class="text-2xl font-bold text-red-600 mt-1">{{ $rejectedRequests }}</p>
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="flex flex-wrap gap-3">
            <a href="/gudang/request-barang" class="bg-corpblue-500 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-corpblue-600 transition-all shadow-sm inline-flex items-center gap-2">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Buat Pengajuan
            </a>
            <a href="/gudang/penerimaan" class="bg-white text-slate-700 border border-slate-200 px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-slate-50 transition-all shadow-sm inline-flex items-center gap-2">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                Penerimaan Barang
            </a>
            <a href="/gudang/stock" class="bg-white text-slate-700 border border-slate-200 px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-slate-50 transition-all shadow-sm inline-flex items-center gap-2">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                Lihat Stok
            </a>
            <a href="/gudang/barang-keluar" class="bg-white text-slate-700 border border-slate-200 px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-slate-50 transition-all shadow-sm inline-flex items-center gap-2">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-4 0V4m0 0l-2 2m2-2l2 2"></path></svg>
                Barang Keluar
            </a>
            <a href="/gudang/history" class="bg-white text-slate-700 border border-slate-200 px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-slate-50 transition-all shadow-sm inline-flex items-center gap-2">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                History
            </a>
        </div>

        {{-- Urgent toast --}}
        @if($urgentRequests > 0)
        <div id="urgentToast" class="fixed top-5 right-5 z-50 w-full max-w-xs bg-white border border-red-200 rounded-xl shadow-lg p-4 flex items-start gap-3 transition-all duration-300 translate-x-0 opacity-100">
            <div class="w-9 h-9 bg-red-50 rounded-lg flex items-center justify-center shrink-0">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-red-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slate-900">Permintaan Mendesak</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ $urgentRequests }} permintaan aktif sedang diproses.</p>
                <a href="/gudang/history" class="inline-block mt-2 text-xs font-medium text-corpblue-500 hover:underline">Lihat History</a>
            </div>
            <button type="button" onclick="closeUrgentToast()" class="shrink-0 w-6 h-6 flex items-center justify-center rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all cursor-pointer">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <script>
            function closeUrgentToast() {
                var t = document.getElementById('urgentToast');
                if (t) { t.style.opacity = '0'; t.style.transform = 'translateX(100%)'; setTimeout(function(){ t.remove(); }, 300); }
            }
            setTimeout(closeUrgentToast, 8000);
        </script>
        @endif

        {{-- Recent activity --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Aktivitas Terbaru</h3>
                @if($requests->isNotEmpty())
                    <a href="/gudang/history" class="text-xs font-medium text-corpblue-500 hover:underline">Lihat Semua</a>
                @endif
            </div>

            @if($requests->isEmpty())
                <p class="text-sm text-slate-400 py-10 text-center">Belum ada pengajuan.</p>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($requests as $request)
                    <div class="px-5 py-3 flex items-center justify-between hover:bg-slate-50/50 transition-all">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-900 truncate">{{ $request->item?->name ?? $request->item_name ?? 'Barang' }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $request->quantity }} {{ $request->unit }} &middot; {{ $request->created_at->diffForHumans() }}</p>
                        </div>
                        <x-status-badge domain="request" :status="$request->status" class="ml-3 shrink-0" />
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</x-layout>
