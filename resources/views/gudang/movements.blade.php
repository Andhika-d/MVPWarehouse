<x-layout>
    <x-slot:title>Riwayat Perubahan Stok — MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Riwayat Perubahan Stok</x-slot:headerTitle>

    <div class="space-y-4">

        {{-- Filter --}}
        <form method="GET" action="/gudang/movements" data-auto-filter class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2 flex-wrap">
                <select name="type" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-corpblue-500">
                    <option value="">Semua Tipe</option>
                    <option value="IN" {{ request('type') === 'IN' ? 'selected' : '' }}>Barang Masuk</option>
                    <option value="OUT" {{ request('type') === 'OUT' ? 'selected' : '' }}>Barang Keluar</option>
                    <option value="ADJUSTMENT" {{ request('type') === 'ADJUSTMENT' ? 'selected' : '' }}>Penyesuaian Stok</option>
                </select>
                <select name="item_id" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-corpblue-500">
                    <option value="">Semua Barang</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>{{ $item->display_name }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-corpblue-500" title="Dari tanggal">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-corpblue-500" title="Sampai tanggal">
                @if(request()->hasAny(['type', 'item_id', 'date_from', 'date_to']))
                    <a href="/gudang/movements" class="text-xs font-medium text-slate-500 hover:text-slate-700">Reset</a>
                @endif
            </div>
            <button type="button" onclick="openExportModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-semibold transition-all cursor-pointer whitespace-nowrap">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export
            </button>
        </form>

        {{-- Desktop Table --}}
        <div class="hidden md:block bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-5">Tanggal & Waktu</th>
                            <th class="py-3 px-5">Barang</th>
                            <th class="py-3 px-5 text-center">Tipe</th>
                            <th class="py-3 px-5 text-center">Jumlah</th>
                            <th class="py-3 px-5">Stok</th>
                            <th class="py-3 px-5">Keterangan</th>
                            <th class="py-3 px-5">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($movements as $m)
                        <tr class="hover:bg-slate-50/50 transition-all">
                            <td class="py-3 px-5 text-xs text-slate-500">{{ $m->occurred_at->translatedFormat('d M Y, H:i') }}</td>
                            <td class="py-3 px-5 font-semibold text-slate-900">{{ $m->item?->name ?? '—' }}</td>
                            <td class="py-3 px-5 text-center">
                                @if($m->type === 'IN')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-[11px] font-bold">Masuk</span>
                                @elseif($m->type === 'OUT')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-50 text-red-700 rounded-full text-[11px] font-bold">Keluar</span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 text-amber-700 rounded-full text-[11px] font-bold">Penyesuaian</span>
                                @endif
                            </td>
                            <td class="py-3 px-5 text-center">
                                <span class="font-bold {{ $m->type === 'IN' ? 'text-emerald-600' : ($m->type === 'OUT' ? 'text-red-600' : 'text-amber-600') }}">
                                    {{ $m->type === 'OUT' ? '-' : '+' }}{{ $m->quantity }}
                                </span>
                                <span class="text-xs text-slate-400">{{ $m->unit }}</span>
                            </td>
                            <td class="py-3 px-5 font-semibold text-slate-900">
                                <span>{{ $m->balance_after }} {{ $m->unit }}</span>
                                @if($m->balance_before !== null)
                        <span class="relative inline-flex ml-1 align-middle">
                            <button type="button" data-balance-tooltip aria-label="Lihat detail saldo" aria-expanded="false" class="inline-flex items-center justify-center w-4 h-4 rounded-full border border-slate-300 text-[10px] text-slate-500 hover:border-corpblue-400 hover:text-corpblue-600 cursor-help">i</button>
                            <span class="balance-tooltip-content hidden">
                                        Stok sebelum: <strong>{{ $m->balance_before }} {{ $m->unit }}</strong><br>
                                        Perubahan: <strong>{{ $m->type === 'OUT' ? '-' : '+' }}{{ $m->quantity }} {{ $m->unit }}</strong><br>
                                        Stok setelah: <strong>{{ $m->balance_after }} {{ $m->unit }}</strong>
                                    </span>
                                </span>
                                @endif
                            </td>
                            <td class="py-3 px-5 text-xs text-slate-500 max-w-[200px] truncate">{{ $m->reason }}</td>
                            <td class="py-3 px-5 text-xs text-slate-500">{{ $m->user?->name ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-400 text-sm">Belum ada riwayat perubahan stok.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
                {{ $movements->links() }}
            </div>
        </div>

        {{-- Mobile Cards --}}
        <div class="md:hidden space-y-2">
            @forelse($movements as $m)
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold text-slate-900 text-sm">{{ $m->item?->name ?? '—' }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $m->occurred_at->translatedFormat('d M Y, H:i') }}</p>
                    </div>
                    @if($m->type === 'IN')
                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-[11px] font-bold">Masuk</span>
                    @elseif($m->type === 'OUT')
                        <span class="px-2 py-0.5 bg-red-50 text-red-700 rounded-full text-[11px] font-bold">Keluar</span>
                    @else
                        <span class="px-2 py-0.5 bg-amber-50 text-amber-700 rounded-full text-[11px] font-bold">Penyesuaian</span>
                    @endif
                </div>
                <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100 text-xs">
                    <div class="flex items-center gap-3">
                        <span class="font-bold {{ $m->type === 'IN' ? 'text-emerald-600' : ($m->type === 'OUT' ? 'text-red-600' : 'text-amber-600') }} text-base">
                            {{ $m->type === 'OUT' ? '-' : '+' }}{{ $m->quantity }} {{ $m->unit }}
                        </span>
                    </div>
                    <span class="text-slate-500">Saldo: <strong class="text-slate-900">{{ $m->balance_after }} {{ $m->unit }}</strong>
                        @if($m->balance_before !== null)
                        <span class="relative inline-flex ml-1 align-middle">
                            <button type="button" data-balance-tooltip aria-label="Lihat detail saldo" aria-expanded="false" class="inline-flex items-center justify-center w-4 h-4 rounded-full border border-slate-300 text-[10px] text-slate-500 hover:border-corpblue-400 hover:text-corpblue-600 cursor-help">i</button>
                            <span class="balance-tooltip-content hidden">
                                Stok sebelum: <strong>{{ $m->balance_before }} {{ $m->unit }}</strong><br>
                                Perubahan: <strong>{{ $m->type === 'OUT' ? '-' : '+' }}{{ $m->quantity }} {{ $m->unit }}</strong><br>
                                Stok setelah: <strong>{{ $m->balance_after }} {{ $m->unit }}</strong>
                            </span>
                        </span>
                        @endif
                    </span>
                </div>
                @if($m->reason)
                <p class="text-[11px] text-slate-400 mt-2">{{ $m->reason }} &middot; {{ $m->user?->name ?? '—' }}</p>
                @endif
            </div>
            @empty
            <div class="bg-white rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">Belum ada riwayat perubahan stok.</div>
            @endforelse
            @if($movements->hasPages())
            <div class="pt-2">{{ $movements->links() }}</div>
            @endif
        </div>

    </div>

    <script>
        function openExportModal() {
            openModal('exportModal');
        }

        function closeExportModal() {
            closeModal('exportModal');
        }

        function setExportType(type) {
            document.getElementById('exportTypeInput').value = type;
        }
    </script>

    <x-slot:modals>
        <div id="exportModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="exportModalTitle">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeExportModal()"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden anim-modal-in z-[101]" onclick="event.stopPropagation()">
                <div class="px-6 py-5 flex items-start gap-4">
                    <div class="w-11 h-11 rounded-xl bg-corpblue-100 flex items-center justify-center shrink-0">
                        <svg class="text-corpblue-600" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 id="exportModalTitle" class="text-base font-bold text-slate-900">Export Perubahan Stok</h3>
                        <p class="text-sm text-slate-500 mt-1">Pilih data yang ingin di-export ke Excel.</p>
                    </div>
                </div>
                <div class="px-6 pb-5">
                    <form id="exportForm" method="GET" action="/gudang/movements/export/preview" class="space-y-2">
                        <input type="hidden" name="item_id" value="{{ request('item_id', '') }}">
                        <input type="hidden" name="date_from" value="{{ request('date_from', '') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to', '') }}">
                        <input type="hidden" name="export_type" id="exportTypeInput" value="">

                        <button type="submit" onclick="setExportType('in')" class="w-full text-left px-4 py-3 rounded-xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50 transition-all group">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
                                    <svg class="text-emerald-600" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0-16l-4 4m4-4l4 4"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900 group-hover:text-emerald-700">Barang Masuk</p>
                                    <p class="text-xs text-slate-400">Hanya data penerimaan barang</p>
                                </div>
                            </div>
                        </button>

                        <button type="submit" onclick="setExportType('out')" class="w-full text-left px-4 py-3 rounded-xl border border-slate-200 hover:border-red-300 hover:bg-red-50 transition-all group">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                                    <svg class="text-red-600" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20V4m0 16l-4-4m4 4l4-4"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900 group-hover:text-red-700">Barang Keluar</p>
                                    <p class="text-xs text-slate-400">Hanya data pengeluaran barang</p>
                                </div>
                            </div>
                        </button>

                        <button type="submit" onclick="setExportType('in_out')" class="w-full text-left px-4 py-3 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50 transition-all group">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                                    <svg class="text-indigo-600" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900 group-hover:text-indigo-700">Barang Masuk & Keluar</p>
                                    <p class="text-xs text-slate-400">Gabungan data masuk dan keluar</p>
                                </div>
                            </div>
                        </button>

                    <button type="submit" onclick="setExportType('adjustment')" class="w-full text-left px-4 py-3 rounded-xl border border-slate-200 hover:border-amber-300 hover:bg-amber-50 transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                                <svg class="text-amber-600" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 group-hover:text-amber-700">Penyesuaian Stok</p>
                                <p class="text-xs text-slate-400">Hanya data penyesuaian / stok awal</p>
                            </div>
                        </div>
                    </button>

                    <button type="submit" onclick="setExportType('all')" class="w-full text-left px-4 py-3 rounded-xl border border-slate-200 hover:border-corpblue-300 hover:bg-corpblue-50 transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-corpblue-100 flex items-center justify-center shrink-0">
                                <svg class="text-corpblue-600" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 group-hover:text-corpblue-700">Semua Perubahan Stok</p>
                                <p class="text-xs text-slate-400">Stok awal, masuk, keluar, & penyesuaian</p>
                            </div>
                        </div>
                    </button>
                    </form>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end">
                    <button type="button" onclick="closeExportModal()" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-200 rounded-xl font-medium transition-colors cursor-pointer">Batal</button>
                </div>
            </div>
        </div>
    </x-slot:modals>

</x-layout>
