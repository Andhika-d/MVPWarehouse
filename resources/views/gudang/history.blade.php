<x-layout>
    <x-slot:title>Riwayat Permintaan — MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Riwayat Permintaan</x-slot:headerTitle>

    <div class="space-y-4">

        {{-- Search & Filter --}}
        <form method="GET" action="/gudang/history" data-auto-filter class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="relative flex-1 max-w-sm">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama barang..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all">
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <input type="month" name="month" value="{{ request('month') }}" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-corpblue-500">
                <select name="status" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-corpblue-500">
                    <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="Menunggu Review" {{ request('status') === 'Menunggu Review' ? 'selected' : '' }}>Menunggu Review</option>
                    <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Disetujui" {{ request('status') === 'Disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="Sebagian Diterima" {{ request('status') === 'Sebagian Diterima' ? 'selected' : '' }}>Sebagian Diterima</option>
                    <option value="Diterima Penuh" {{ request('status') === 'Diterima Penuh' ? 'selected' : '' }}>Diterima Penuh</option>
                    <option value="Ditutup Sebagian" {{ request('status') === 'Ditutup Sebagian' ? 'selected' : '' }}>Ditutup Sebagian</option>
                    <option value="Dibatalkan" {{ request('status') === 'Dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                    <option value="Ditolak" {{ request('status') === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
                @if(request()->hasAny(['search', 'status', 'month']))
                    <a href="/gudang/history" class="text-xs font-medium text-slate-500 hover:text-slate-700">Reset</a>
                @endif
                <a href="/gudang/history/export/preview{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="border border-slate-200 bg-white px-3 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">Preview Export</a>
            </div>
        </form>

        {{-- Desktop Table --}}
        <div class="hidden md:block bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-5">Tanggal</th>
                            <th class="py-3 px-5">Barang</th>
                            <th class="py-3 px-5">Jumlah</th>
                            <th class="py-3 px-5">Prioritas</th>
                            <th class="py-3 px-5">Status</th>
                            <th class="py-3 px-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($requests as $request)
                        <tr class="hover:bg-slate-50/70 transition-all">
                            <td class="py-3 px-5 text-slate-500 font-medium whitespace-nowrap">{{ $request->created_at->translatedFormat('d M Y, H:i') }}</td>
                            <td class="py-3 px-5 font-semibold text-slate-900">{{ $request->item?->name ?? $request->item_name ?? 'Barang' }}</td>
                            <td class="py-3 px-5">{{ $request->quantity }} <span class="text-xs text-slate-500">{{ $request->unit }}</span></td>
                            <td class="py-3 px-5">
                                <x-status-badge domain="priority" :status="$request->priority" />
                            </td>
                            <td class="py-3 px-5">
                                <x-status-badge domain="request" :status="$request->status" dot />
                            </td>
                            <td class="py-3 px-5 text-right">
                                <a href="/gudang/history/{{ $request->id }}" class="text-corpblue-500 hover:text-corpblue-700 font-medium text-xs bg-corpblue-50 hover:bg-corpblue-100 px-3 py-2 rounded-lg transition-all">Detail</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-slate-400">Belum ada riwayat permintaan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requests->hasPages())
            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
                {{ $requests->links() }}
            </div>
            @endif
        </div>

        {{-- Mobile Cards --}}
        <div class="md:hidden space-y-2">
            @forelse($requests as $request)
            <a href="/gudang/history/{{ $request->id }}" class="block bg-white rounded-xl border border-slate-200 shadow-sm p-4 hover:bg-slate-50 transition-all">
                <div class="flex items-start justify-between">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900 text-sm truncate">{{ $request->item?->name ?? $request->item_name ?? 'Barang' }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $request->quantity }} {{ $request->unit }} &middot; {{ $request->created_at->diffForHumans() }}</p>
                    </div>
                    <x-status-badge domain="request" :status="$request->status" class="ml-3 shrink-0" />
                </div>
                <div class="flex items-center gap-2 mt-2 pt-2 border-t border-slate-100">
                    <x-status-badge domain="priority" :status="$request->priority" />
                    <span class="text-xs text-slate-500">{{ $request->created_at->translatedFormat('d M Y') }}</span>
                </div>
            </a>
            @empty
            <div class="bg-white rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">
                Belum ada riwayat permintaan.
            </div>
            @endforelse
            @if($requests->hasPages())
            <div class="pt-2">{{ $requests->links() }}</div>
            @endif
        </div>

    </div>
</x-layout>
