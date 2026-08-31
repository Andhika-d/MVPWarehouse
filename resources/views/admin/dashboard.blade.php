<x-layout :title="'Dashboard Admin — MVPWarehouse'" :headerTitle="'Dashboard Admin'">
    <div class="space-y-6">

        {{-- Stats Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-lg bg-corpblue-50 flex items-center justify-center shrink-0">
                    <svg class="text-corpblue-500" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900">{{ $totalItems }}</p>
                    <p class="text-xs text-slate-500 font-medium">Total Barang</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                    <svg class="text-emerald-500" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900">{{ $occupiedLocations }} / {{ $totalLocations }}</p>
                    <p class="text-xs text-slate-500 font-medium">Slot Terisi</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                    <svg class="text-amber-500" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900">{{ $pendingLocationChanges }}</p>
                    <p class="text-xs text-slate-500 font-medium">Pengajuan Lokasi</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0">
                    <svg class="text-indigo-500" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900">{{ $totalUsers }}</p>
                    <p class="text-xs text-slate-500 font-medium">Pengguna</p>
                </div>
            </div>
        </div>

        {{-- Developer Mode --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Developer Mode</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Membuka seluruh pembatasan role untuk testing</p>
                </div>
                <form method="POST" action="{{ route('admin.settings.dev-mode.toggle') }}">
                    @csrf
                    <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors cursor-pointer {{ setting('dev_mode') ? 'bg-red-500' : 'bg-slate-300' }}">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ setting('dev_mode') ? 'translate-x-6' : 'translate-x-1' }}"></span>
                    </button>
                </form>
            </div>
            @if(setting('dev_mode'))
                <div class="mt-3 p-2 bg-red-50 rounded-lg border border-red-200">
                    <p class="text-xs text-red-600 font-medium">Developer Mode aktif. Seluruh pembatasan role dibuka.</p>
                </div>
            @endif
        </div>

        {{-- Quick Links --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('admin.items.index') }}" class="bg-white rounded-xl border border-slate-200 p-5 hover:border-corpblue-300 hover:shadow-sm transition-all group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-corpblue-50 flex items-center justify-center group-hover:bg-corpblue-100 transition-colors">
                        <svg class="text-corpblue-500" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Master Barang</p>
                        <p class="text-xs text-slate-500">Kelola data barang</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.import.index') }}" class="bg-white rounded-xl border border-slate-200 p-5 hover:border-corpblue-300 hover:shadow-sm transition-all group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center group-hover:bg-emerald-100 transition-colors">
                        <svg class="text-emerald-500" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Import Barang</p>
                        <p class="text-xs text-slate-500">Import dari Excel</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.locations.index') }}" class="bg-white rounded-xl border border-slate-200 p-5 hover:border-corpblue-300 hover:shadow-sm transition-all group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center group-hover:bg-amber-100 transition-colors">
                        <svg class="text-amber-500" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Lokasi Rak</p>
                        <p class="text-xs text-slate-500">Tata letak gudang</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.users.index') }}" class="bg-white rounded-xl border border-slate-200 p-5 hover:border-corpblue-300 hover:shadow-sm transition-all group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center group-hover:bg-indigo-100 transition-colors">
                        <svg class="text-indigo-500" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Pengguna</p>
                        <p class="text-xs text-slate-500">Kelola akun</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- Audit Log --}}
        <div class="bg-white rounded-xl border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Audit Log Terbaru</h3>
                <a href="{{ route('admin.audit.index') }}" class="text-xs text-corpblue-600 hover:text-corpblue-700 font-semibold">Lihat Semua</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($auditLogs as $log)
                <div class="px-5 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center shrink-0">
                        <svg class="text-slate-400" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-slate-700 truncate">{{ $log->details ?? $log->action }}</p>
                        <p class="text-xs text-slate-400">{{ $log->user?->name ?? 'System' }} &middot; {{ $log->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 text-center text-sm text-slate-400">Belum ada audit log.</div>
                @endforelse
            </div>
        </div>

    </div>
</x-layout>
