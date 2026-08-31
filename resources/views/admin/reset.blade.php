<x-layout :title="'Reset Data — MVPWarehouse'" :headerTitle="'Maintenance Data'">
    <div class="max-w-3xl mx-auto space-y-6">

        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 font-medium">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Kembali ke Dashboard
        </a>

        {{-- Warning --}}
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
            <div class="flex items-start gap-3">
                <svg class="text-amber-600 shrink-0 mt-0.5" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <h3 class="text-sm font-bold text-amber-800">Peringatan Penting</h3>
                    <p class="text-sm text-amber-700 mt-1">Setiap reset akan membuat backup otomatis sebelum penghapusan. Akun pengguna, pengaturan sistem, dan audit log tidak akan dihapus. Tindakan ini tidak dapat dibatalkan tanpa restore backup.</p>
                </div>
            </div>
        </div>

        {{-- 1. Master Barang --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Master Barang</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Hapus semua item dan reset seluruh lokasi. Cocok untuk import ulang data dari awal.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <p class="text-lg font-bold text-slate-900">{{ $stats['items'] }}</p>
                        <p class="text-[10px] text-slate-400">item</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-slate-900">{{ $stats['storage_locations'] }}</p>
                        <p class="text-[10px] text-slate-400">lokasi</p>
                    </div>
                </div>
            </div>
            <div class="px-5 py-3 bg-slate-50 flex items-center justify-between">
                <p class="text-xs text-slate-500">Menghapus: semua item &middot; status lokasi di-reset &middot; sub lokasi dihapus</p>
                <form method="POST" action="{{ route('admin.reset.master-items') }}" data-confirm="HAPUS SEMUA MASTER BARANG? Semua item akan dihapus dan lokasi di-reset. Backup akan dibuat otomatis." data-confirm-title="Reset Master Barang" data-confirm-tone="danger" data-confirm-button="Ya, Reset Sekarang">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-semibold transition-colors cursor-pointer">Reset Master Barang</button>
                </form>
            </div>
        </div>

        {{-- 2. Pergerakan Stok --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Pergerakan Stok</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Hapus seluruh histori pergerakan stok (IN, OUT, ADJUSTMENT).</p>
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold text-slate-900">{{ $stats['stock_movements'] }}</p>
                    <p class="text-[10px] text-slate-400">record</p>
                </div>
            </div>
            <div class="px-5 py-3 bg-slate-50 flex items-center justify-between">
                <p class="text-xs text-slate-500">Menghapus: seluruh histori pergerakan stok</p>
                <form method="POST" action="{{ route('admin.reset.stock-movements') }}" data-confirm="Hapus SELURUH histori pergerakan stok? Backup akan dibuat otomatis." data-confirm-title="Reset Pergerakan Stok" data-confirm-tone="danger" data-confirm-button="Ya, Reset Sekarang">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-semibold transition-colors cursor-pointer">Reset Pergerakan Stok</button>
                </form>
            </div>
        </div>

        {{-- 3. Request Barang --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Request Barang</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Hapus semua request barang dan histori approval.</p>
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold text-slate-900">{{ $stats['stock_requests'] }}</p>
                    <p class="text-[10px] text-slate-400">request</p>
                </div>
            </div>
            <div class="px-5 py-3 bg-slate-50 flex items-center justify-between">
                <p class="text-xs text-slate-500">Menghapus: semua request &middot; histori approval terkait</p>
                <form method="POST" action="{{ route('admin.reset.stock-requests') }}" data-confirm="Hapus SEMUA request barang dan histori approval? Backup akan dibuat otomatis." data-confirm-title="Reset Request Barang" data-confirm-tone="danger" data-confirm-button="Ya, Reset Sekarang">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-semibold transition-colors cursor-pointer">Reset Request Barang</button>
                </form>
            </div>
        </div>

        {{-- 4. Pengajuan Lokasi --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Pengajuan Lokasi</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Hapus semua pengajuan perubahan lokasi.</p>
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold text-slate-900">{{ $stats['location_change_requests'] }}</p>
                    <p class="text-[10px] text-slate-400">pengajuan</p>
                </div>
            </div>
            <div class="px-5 py-3 bg-slate-50 flex items-center justify-between">
                <p class="text-xs text-slate-500">Menghapus: semua pengajuan perubahan lokasi</p>
                <form method="POST" action="{{ route('admin.reset.location-changes') }}" data-confirm="Hapus SEMUA pengajuan lokasi? Backup akan dibuat otomatis." data-confirm-title="Reset Pengajuan Lokasi" data-confirm-tone="danger" data-confirm-button="Ya, Reset Sekarang">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-semibold transition-colors cursor-pointer">Reset Pengajuan Lokasi</button>
                </form>
            </div>
        </div>

        {{-- What is preserved --}}
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5">
            <div class="flex items-start gap-3">
                <svg class="text-emerald-600 shrink-0 mt-0.5" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <h3 class="text-sm font-bold text-emerald-800">Yang Tidak Dipengaruhi</h3>
                    <p class="text-sm text-emerald-700 mt-1">Akun pengguna, pengaturan sistem, backup, dan audit log tetap aman. Backup otomatis dibuat sebelum setiap reset.</p>
                </div>
            </div>
        </div>

    </div>
</x-layout>
