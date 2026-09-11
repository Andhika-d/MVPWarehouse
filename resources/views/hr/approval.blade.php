<x-layout>
    <x-slot:title>Verifikasi & Approval - MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Meja Verifikasi Permintaan Barang</x-slot:headerTitle>

    <div class="space-y-6">

        <!-- ================= 1. SEARCH BAR & QUICK FILTER TAB ================= -->
        <form method="GET" action="/hr/approval" data-auto-filter class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="relative flex-1 max-w-md">
                <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-4 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-blue-600 focus:bg-white transition-all" placeholder="Cari nama barang...">
            </div>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="/hr/approval/export/preview{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 min-h-[44px] inline-flex items-center">Preview Export</a>
                <input type="date" name="date" value="{{ request('date') }}" aria-label="Tanggal nota" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 min-h-[44px]">
                <select name="status" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 min-h-[44px]">
                    <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>Semua</option>
                    <option value="Menunggu Review" {{ request('status') === 'Menunggu Review' ? 'selected' : '' }}>Menunggu Review</option>
                </select>
                @if(request()->filled('search') || request()->filled('date') || (request()->filled('status') && request('status') !== 'all'))
                <a href="/hr/approval" class="px-2 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800">Reset</a>
                @endif
            </div>
        </form>

        <!-- ================= 2. KARTU NOTA (DIGABUNG PER TANGGAL) ================= -->
        @forelse($notas as $date => $notaItems)
        @php
            $first = $notaItems->first();
        @endphp
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden transition-all duration-300">

            <!-- HEADER NOTA + AKSI BULK -->
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div class="flex items-center space-x-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-corpblue-500 text-xs font-bold text-white">
                        {{ \Carbon\Carbon::parse($date)->format('d') }}
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-slate-900 text-sm">#NOTA-{{ str_replace('-', '', $date) }}</span>
                            <span class="status-badge status-badge--info">{{ $notaItems->count() }} item</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }} • Pemohon: {{ $first->user?->name ?? 'Gudang' }}
                        </p>
                    </div>
                </div>

                <!-- AKSI BULK SELURUH NOTA -->
                <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
                    <form action="/hr/nota/{{ $date }}/approve-all" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white border border-emerald-200 rounded-lg transition-all cursor-pointer min-h-[44px]">
                            ✓ Terima Semua
                        </button>
                    </form>
                    <button type="button" data-action="/hr/nota/{{ $date }}/reject-all" data-label="Seluruh item nota {{ \Carbon\Carbon::parse($date)->format('d F Y') }}" onclick="openRejectModal(this)" class="px-3 py-2 bg-red-50 text-red-700 hover:bg-red-600 hover:text-white border border-red-200 rounded-lg transition-all cursor-pointer min-h-[44px]">
                        Tolak Semua
                    </button>
                    <button type="button" data-action="/hr/nota/{{ $date }}/delay-all" data-label="Seluruh item nota {{ \Carbon\Carbon::parse($date)->format('d F Y') }}" onclick="openDelayModal(this)" class="px-3 py-2 bg-amber-50 text-amber-700 hover:bg-amber-600 hover:text-white border border-amber-200 rounded-lg transition-all cursor-pointer min-h-[44px]">
                        Tunda Semua
                    </button>
                </div>
            </div>

            <!-- TABEL ITEM DALAM NOTA -->
            <div class="overflow-x-auto bg-white">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-6">Barang</th>
                            <th class="py-3 px-6">Kode Tag</th>
                            <th class="py-3 px-6">Jumlah</th>
                            <th class="py-3 px-6">Alasan</th>
                            <th class="py-3 px-6 text-center">Status</th>
                            <th class="py-3 px-6 text-center">Aksi Individual</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                        @foreach($notaItems as $request)
                        <tr class="hover:bg-slate-50/30 transition-colors duration-200">
                            <td class="py-4 px-6 font-bold text-slate-900 text-sm">{{ $request->item?->name ?? $request->item_name ?? 'Barang' }} @if($request->attachment_path)<span title="Ada lampiran/foto" class="ml-1">📎</span>@endif</td>
                            <td class="py-4 px-6"><span class="text-slate-600 font-bold block font-mono text-xs whitespace-nowrap">{{ $request->item?->storageLocation?->code ?? '-' }}</span></td>
                            <td class="py-4 px-6 text-sm font-bold text-slate-900">{{ $request->quantity }} <span class="text-xs font-medium text-slate-500">{{ $request->unit }}</span></td>
                            <td class="py-4 px-6 text-slate-500 max-w-xs leading-relaxed">{{ $request->reason ?? '-' }}</td>
                            <td class="py-4 px-6 text-center">
                                <x-status-badge domain="request" :status="$request->status" />
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <a href="/hr/requests/{{ $request->id }}" title="Lihat detail & lampiran" class="action-link action-link--neutral">Detail</a>
                                    <form action="/hr/requests/{{ $request->id }}/approve" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" title="Terima item ini" class="action-link action-link--success">Terima</button>
                                    </form>
                                    <button type="button" data-action="/hr/requests/{{ $request->id }}/reject" data-name="{{ $request->item?->name ?? $request->item_name ?? 'Barang' }}" onclick="openRejectModal(this)" title="Tolak item ini" class="action-link action-link--danger">Tolak</button>
                                    <button type="button" data-action="/hr/requests/{{ $request->id }}/delay" data-name="{{ $request->item?->name ?? $request->item_name ?? 'Barang' }}" onclick="openDelayModal(this)" title="Tunda item ini" class="action-link action-link--warning">Tunda</button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center text-slate-500">
            Tidak ada permintaan yang menunggu review.
        </div>
        @endforelse

        <!-- ================= 3. RINGKASAN ANTREAN ================= -->
        <div class="p-4 bg-white rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500 font-medium">
            <span>Menampilkan {{ $requests->firstItem() ?? 0 }} - {{ $requests->lastItem() ?? 0 }} dari {{ $requests->total() }} permintaan</span>
            {{ $requests->links() }}
        </div>

    </div>

    <x-slot:modals>
    {{-- MODAL TOLAK --}}
    <div id="rejectModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="rejectModalTitle">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeRejectModal()"></div>
        <div class="relative bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md flex flex-col overflow-hidden max-h-[90vh] z-[101]">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
                <div>
                    <h3 id="rejectModalTitle" class="text-sm font-bold text-slate-900">Konfirmasi Penolakan</h3>
                    <p id="rejectModalTarget" class="text-xs text-red-600 mt-0.5 font-semibold"></p>
                </div>
                <button onclick="closeRejectModal()" type="button" class="text-slate-400 hover:text-slate-600 p-2 text-2xl font-light leading-none cursor-pointer transition-colors" aria-label="Tutup">&times;</button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="p-5 space-y-4 bg-white flex-1 overflow-y-auto">
                    <div>
                        <label for="rejectReasonText" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Alasan Penolakan (Wajib Diisi)</label>
                        <textarea id="rejectReasonText" name="note" rows="3" required class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-red-600 focus:bg-white transition-all text-slate-900 resize-none" placeholder="Tuliskan alasan penolakan secara jelas agar dibaca oleh staf Gudang..."></textarea>
                    </div>
                </div>
                <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end space-x-2 shrink-0">
                    <button onclick="closeRejectModal()" type="button" class="px-4 py-2.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-all cursor-pointer shadow-2xs min-h-[44px]">Batal</button>
                    <button type="submit" class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-all cursor-pointer min-h-[44px]">Tolak</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL TUNDA --}}
    <div id="delayModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="delayModalTitle">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeDelayModal()"></div>
        <div class="relative bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md flex flex-col overflow-hidden max-h-[90vh] z-[101]">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
                <div>
                    <h3 id="delayModalTitle" class="text-sm font-bold text-slate-900">Konfirmasi Penundaan</h3>
                    <p id="delayModalTarget" class="text-xs text-amber-600 mt-0.5 font-semibold"></p>
                </div>
                <button onclick="closeDelayModal()" type="button" class="text-slate-400 hover:text-slate-600 p-2 text-2xl font-light leading-none cursor-pointer transition-colors" aria-label="Tutup">&times;</button>
            </div>
            <form id="delayForm" method="POST">
                @csrf
                <div class="p-5 space-y-4 bg-white flex-1 overflow-y-auto">
                    <div>
                        <label for="delayReasonText" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Catatan Penundaan <span class="text-slate-400 font-normal normal-case">(opsional)</span></label>
                        <textarea id="delayReasonText" name="note" rows="3" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-amber-500 focus:bg-white transition-all text-slate-900 resize-none" placeholder="Contoh: Menunggu anggaran bulan depan..."></textarea>
                    </div>
                </div>
                <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end space-x-2 shrink-0">
                    <button onclick="closeDelayModal()" type="button" class="px-4 py-2.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-all cursor-pointer shadow-2xs min-h-[44px]">Batal</button>
                    <button type="submit" class="px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-xs font-semibold text-white rounded-lg shadow-sm transition-all cursor-pointer min-h-[44px]">Tunda</button>
                </div>
            </form>
        </div>
    </div>
    </x-slot:modals>

    <x-slot:scripts>
    <script>
        function modalLabel(button) {
            var label = button.getAttribute('data-label');
            if (label) return label;
            return button.getAttribute('data-name') || 'Barang';
        }

        function openRejectModal(button) {
            document.getElementById('rejectForm').action = button.getAttribute('data-action');
            document.getElementById('rejectModalTarget').innerText = 'Mencoret: ' + modalLabel(button);
            document.getElementById('rejectReasonText').value = '';
            openModal('rejectModal');
        }
        function closeRejectModal() {
            closeModal('rejectModal');
            document.getElementById('rejectReasonText').value = '';
        }

        function openDelayModal(button) {
            document.getElementById('delayForm').action = button.getAttribute('data-action');
            document.getElementById('delayModalTarget').innerText = 'Menunda: ' + modalLabel(button);
            document.getElementById('delayReasonText').value = '';
            openModal('delayModal');
        }
        function closeDelayModal() {
            closeModal('delayModal');
            document.getElementById('delayReasonText').value = '';
        }
    </script>
    </x-slot:scripts>

</x-layout>
