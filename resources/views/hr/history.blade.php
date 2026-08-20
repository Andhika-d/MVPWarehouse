<x-layout>
    <x-slot:title>History HRD - MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Arsip Historis Pengadaan Barang</x-slot:headerTitle>

    <div class="space-y-6">

        <!-- ================= SEARCH & FILTER ================= -->
        <form method="GET" action="/hr/history" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="relative flex-1 max-w-md">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-blue-600 focus:bg-white transition-all" placeholder="Cari nota, barang, atau pemohon...">
            </div>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="/hr/history/export/pdf{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 min-h-[44px] inline-flex items-center">PDF</a>
                <a href="/hr/history/export/excel{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 min-h-[44px] inline-flex items-center">Excel</a>
                <select name="status" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 min-h-[44px]">
                    <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>Semua Rekap</option>
                    <option value="Disetujui" {{ request('status') === 'Disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="Selesai Dibelanjakan" {{ request('status') === 'Selesai Dibelanjakan' ? 'selected' : '' }}>Selesai Belanja</option>
                    <option value="Ditolak" {{ request('status') === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                    <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Menunggu Review" {{ request('status') === 'Menunggu Review' ? 'selected' : '' }}>Menunggu Review</option>
                </select>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white min-h-[44px]">Filter</button>
            </div>
        </form>

        <!-- ================= TABEL ARSIP AUDIT HRD ================= -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                            <th class="py-4 px-6">Nota & Tanggal</th>
                            <th class="py-4 px-6">Nama Barang</th>
                            <th class="py-4 px-6">Jumlah</th>
                            <th class="py-4 px-6">Pemohon</th>
                            <th class="py-4 px-6">Status Keputusan</th>
                            <th class="py-4 px-6">Catatan / Alasan Tolak</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                        @forelse($requests as $request)
                        <tr class="hover:bg-slate-50/40 transition-colors {{ $request->status === 'Ditolak' ? 'bg-red-50/10' : '' }}">
                            <td class="py-4 px-6">
                                <span class="text-slate-900 font-bold block">#NOTA-{{ $request->created_at->format('Ymd') }}</span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">{{ $request->created_at->translatedFormat('d M Y, H:i') }}</span>
                            </td>
                            <td class="py-4 px-6 font-semibold text-slate-900">{{ $request->item?->name ?? $request->item_name ?? 'Barang' }}</td>
                            <td class="py-4 px-6 text-slate-900">{{ $request->quantity }} <span class="text-xs text-slate-400 font-medium">{{ $request->unit }}</span></td>
                            <td class="py-4 px-6 text-xs text-slate-600">{{ $request->user?->name ?? 'Gudang' }}</td>
                            <td class="py-4 px-6">
                                @if($request->status === 'Selesai Dibelanjakan')
                                <span class="inline-flex items-center px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold">🟢 Selesai Dibelanjakan</span>
                                @elseif($request->status === 'Disetujui')
                                <span class="inline-flex items-center px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold">🟢 Disetujui</span>
                                @elseif($request->status === 'Ditolak')
                                <span class="inline-flex items-center px-2 py-0.5 bg-red-100 text-red-900 rounded-full text-xs font-bold">🔴 Ditolak</span>
                                @elseif($request->status === 'Pending')
                                <span class="inline-flex items-center px-2 py-0.5 bg-amber-100 text-amber-800 rounded-full text-xs font-bold">🟡 Pending / Ditunda</span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 bg-blue-50 text-blue-600 rounded-full text-xs font-bold">🔵 {{ $request->status }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-xs {{ $request->status === 'Ditolak' ? 'text-red-900 font-semibold leading-relaxed max-w-xs' : 'text-slate-400 font-normal leading-relaxed' }}">
                                {{ $request->review_note ? '"' . $request->review_note . '"' : '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 px-6 text-center text-slate-500">Belum ada riwayat pengadaan barang.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 bg-slate-50/30 text-xs text-slate-500 font-medium">
                <span>Menampilkan {{ $requests->count() }} rekap dari arsip audit dokumen</span>
            </div>
        </div>
    </div>
</x-layout>
