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
                        <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
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
                                <span class="text-[10px] text-slate-400 block mt-0.5">{{ $request->created_at->translatedFormat('d M Y, H:i') }}</span>
                            </td>
                            <td class="py-4 px-6 font-semibold text-slate-900">{{ $request->item?->name ?? $request->item_name ?? 'Barang' }}</td>
                            <td class="py-4 px-6 text-slate-900">{{ $request->quantity }} <span class="text-xs text-slate-400 font-medium">{{ $request->unit }}</span></td>
                            <td class="py-4 px-6 text-xs text-slate-600">{{ $request->user?->name ?? 'Gudang' }}</td>
                            <td class="py-4 px-6">
                                @if($request->status === 'Diterima Penuh')
                                <span class="inline-flex items-center px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold">Diterima Penuh</span>
                                @elseif($request->status === 'Ditutup Sebagian')
                                <span class="inline-flex items-center px-2 py-0.5 bg-slate-100 text-slate-700 rounded-full text-xs font-bold">Ditutup Sebagian</span>
                                @elseif($request->status === 'Dibatalkan')
                                <span class="inline-flex items-center px-2 py-0.5 bg-stone-100 text-stone-700 rounded-full text-xs font-bold">Dibatalkan</span>
                                @elseif($request->status === 'Sebagian Diterima')
                                <span class="inline-flex items-center px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full text-xs font-bold">Sebagian Diterima</span>
                                @elseif($request->status === 'Disetujui')
                                <span class="inline-flex items-center px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold">Disetujui</span>
                                @elseif($request->status === 'Ditolak')
                                <span class="inline-flex items-center px-2 py-0.5 bg-red-100 text-red-900 rounded-full text-xs font-bold">Ditolak</span>
                                @elseif($request->status === 'Pending')
                                <span class="inline-flex items-center px-2 py-0.5 bg-amber-100 text-amber-800 rounded-full text-xs font-bold">Pending / Ditunda</span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 bg-blue-50 text-blue-600 rounded-full text-xs font-bold">{{ $request->status }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-xs {{ $request->status === 'Ditolak' ? 'text-red-900 font-semibold leading-relaxed max-w-xs' : 'text-slate-400 font-normal leading-relaxed' }}">
                                {{ $request->review_note ? '"' . $request->review_note . '"' : '-' }}
                            </td>
                            <td class="py-4 px-6 text-right">
                                @if($request->canClose())
                                <button onclick="openCloseModal(this)" data-id="{{ $request->id }}" data-url="/hr/requests/{{ $request->id }}/close" data-name="{{ $request->item?->name ?? $request->item_name ?? 'Barang' }}" data-remain="{{ $request->remainingQuantity() }}" data-unit="{{ $request->unit }}" class="px-3 py-1.5 border border-amber-300 text-amber-700 text-xs font-semibold rounded-lg hover:bg-amber-50 transition-all cursor-pointer whitespace-nowrap">Tutup Sisa</button>
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

    <x-slot:modals>
    {{-- MODAL TUTUP SISA --}}
    <div id="hrCloseModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="hrCloseModalTitle">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeHrCloseModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-sm p-6 z-[101]">
            <h3 id="hrCloseModalTitle" class="text-base font-bold text-slate-900 mb-1">Tutup Sisa Request</h3>
            <p class="text-xs text-slate-500 mb-4">Sisa yang belum diterima tidak akan diproses lebih lanjut. Aksi ini permanen.</p>
            <form id="hrCloseForm" method="POST" action="">
                @csrf
                <input type="hidden" name="stock_request_id" id="hrClose_request_id">
                <div class="flex flex-col gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-0.5">Barang</label>
                        <p id="hrClose_item_name" class="text-sm font-semibold text-slate-900"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-0.5">Sisa yang Ditutup</label>
                        <p id="hrClose_remain" class="text-sm font-bold text-amber-600"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-0.5">Alasan Penutupan <span class="text-red-500">*</span></label>
                        <textarea name="note" id="hrClose_note" rows="3" required maxlength="255" placeholder="Contoh: Kebutuhan sudah tidak diperlukan" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all"></textarea>
                        <p class="text-[11px] text-slate-400 mt-1">Wajib diisi dan tidak dapat diubah setelah request ditutup.</p>
                    </div>
                </div>
                <div class="flex gap-3 mt-5">
                    <button type="button" onclick="closeHrCloseModal()" class="flex-1 px-4 py-2.5 border border-slate-200 text-slate-600 text-sm font-semibold rounded-lg hover:bg-slate-50 transition-all">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-amber-500 text-white text-sm font-semibold rounded-lg hover:bg-amber-600 transition-all">Tutup Sisa</button>
                </div>
            </form>
        </div>
    </div>
    </x-slot:modals>

    <x-slot:scripts>
    <script>
        function openCloseModal(button) {
            document.getElementById('hrClose_request_id').value = button.getAttribute('data-id');
            document.getElementById('hrCloseForm').action = button.getAttribute('data-url');
            document.getElementById('hrClose_item_name').textContent = button.getAttribute('data-name');
            var remain = parseInt(button.getAttribute('data-remain'), 10);
            var unit = button.getAttribute('data-unit');
            document.getElementById('hrClose_remain').textContent = remain + ' ' + unit;
            document.getElementById('hrClose_note').value = '';
            openModal('hrCloseModal');
        }
        function closeHrCloseModal() {
            closeModal('hrCloseModal');
        }
    </script>
    </x-slot:scripts>
</x-layout>
