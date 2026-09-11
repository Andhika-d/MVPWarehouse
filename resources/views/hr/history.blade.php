<x-layout>
    <x-slot:title>History HRD - MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Arsip Historis Pengadaan Barang</x-slot:headerTitle>

    <div class="space-y-6">

        <!-- ================= SEARCH & FILTER ================= -->
        <form method="GET" action="/hr/history" data-auto-filter class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="relative flex-1 max-w-md">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-blue-600 focus:bg-white transition-all" placeholder="Cari nota, barang, atau pemohon...">
            </div>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <input type="date" name="date" value="{{ request('date') }}" aria-label="Tanggal nota" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 min-h-[44px]">
                <select name="status" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 min-h-[44px]">
                    <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>Semua Rekap</option>
                    <option value="Disetujui" {{ request('status') === 'Disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="Sebagian Diterima" {{ request('status') === 'Sebagian Diterima' ? 'selected' : '' }}>Sebagian Diterima</option>
                    <option value="Diterima Penuh" {{ request('status') === 'Diterima Penuh' ? 'selected' : '' }}>Diterima Penuh</option>
                    <option value="Ditutup Sebagian" {{ request('status') === 'Ditutup Sebagian' ? 'selected' : '' }}>Ditutup Sebagian</option>
                    <option value="Dibatalkan" {{ request('status') === 'Dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                    <option value="Ditolak" {{ request('status') === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                    <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Menunggu Review" {{ request('status') === 'Menunggu Review' ? 'selected' : '' }}>Menunggu Review</option>
                </select>
                <a href="/hr/history/export/preview{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 min-h-[44px] inline-flex items-center">Preview Export</a>
                @if(request()->filled('search') || request()->filled('date') || (request()->filled('status') && request('status') !== 'all'))
                    <a href="/hr/history" class="px-2 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800">Reset</a>
                @endif
            </div>
        </form>

        <!-- ================= TABEL ARSIP AUDIT HRD ================= -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="py-4 px-6">Nota & Tanggal</th>
                            <th class="py-4 px-6">Nama Barang</th>
                            <th class="py-4 px-6">Jumlah</th>
                            <th class="py-4 px-6">Pemohon</th>
                            <th class="py-4 px-6">Status Keputusan</th>
                            <th class="py-4 px-6">Catatan / Alasan Tolak</th>
                            <th class="py-4 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                        @forelse($requests as $request)
                        <tr class="hover:bg-slate-50/40 transition-colors {{ $request->status === 'Ditolak' ? 'bg-red-50/10' : '' }}">
                            <td class="py-4 px-6">
                                <span class="text-slate-900 font-bold block">#NOTA-{{ $request->created_at->format('Ymd') }}</span>
                                <span class="mt-0.5 block text-xs text-slate-500">{{ $request->created_at->translatedFormat('d M Y, H:i') }}</span>
                            </td>
                            <td class="py-4 px-6 font-semibold text-slate-900">{{ $request->item?->name ?? $request->item_name ?? 'Barang' }}</td>
                            <td class="py-4 px-6 text-slate-900">{{ $request->quantity }} <span class="text-xs font-medium text-slate-500">{{ $request->unit }}</span></td>
                            <td class="py-4 px-6 text-xs text-slate-600">{{ $request->user?->name ?? 'Gudang' }}</td>
                            <td class="py-4 px-6">
                                <x-status-badge domain="request" :status="$request->status" />
                            </td>
                            <td class="py-4 px-6 text-xs {{ $request->status === 'Ditolak' ? 'text-red-900 font-semibold leading-relaxed max-w-xs' : 'text-slate-500 font-normal leading-relaxed' }}">
                                {{ $request->review_note ? '"' . $request->review_note . '"' : '-' }}
                            </td>
                            <td class="py-4 px-6 text-right">
                                @if($request->canClose() && ! $request->procurementNote?->isDraft())
                                <x-hr-request-close-button :request="$request" class="whitespace-nowrap" />
                                @else
                                <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-8 px-6 text-center text-slate-500">Belum ada riwayat pengadaan barang.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 bg-slate-50/30 text-xs text-slate-500 font-medium">
                @if($requests->hasPages())
                <span>Menampilkan {{ $requests->firstItem() ?? 0 }} - {{ $requests->lastItem() ?? 0 }} dari {{ $requests->total() }} rekap</span>
                {{ $requests->links() }}
                @else
                <span>Menampilkan {{ $requests->count() }} rekap dari arsip audit dokumen</span>
                @endif
            </div>
        </div>
    </div>

    <x-slot:modals><x-hr-request-close-modal /></x-slot:modals>
</x-layout>
