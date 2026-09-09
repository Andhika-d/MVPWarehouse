<x-layout :title="'Perubahan Stok — MVPWarehouse'" :headerTitle="'Riwayat Perubahan Stok'">
    <div class="space-y-6">

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form method="GET" data-auto-filter class="flex flex-col sm:flex-row gap-3">
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari barang atau keterangan..." class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                <select name="type" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    <option value="all">Semua Tipe</option>
                    <option value="IN" {{ ($type ?? '') === 'IN' ? 'selected' : '' }}>Barang Masuk</option>
                    <option value="OUT" {{ ($type ?? '') === 'OUT' ? 'selected' : '' }}>Barang Keluar</option>
                    <option value="ADJUSTMENT" {{ ($type ?? '') === 'ADJUSTMENT' ? 'selected' : '' }}>Penyesuaian</option>
                </select>
                @if(request()->filled('search') || (request()->filled('type') && request('type') !== 'all'))
                <a href="/director/movements" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-sm font-medium">Reset</a>
                @endif
            </form>
        </div>

        <div class="bg-white rounded-xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Waktu</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Barang</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Tipe</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Jumlah</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Stok</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Oleh</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($movements as $mov)
                        <tr>
                            <td class="px-5 py-3 text-slate-400 text-xs whitespace-nowrap">{{ ($mov->occurred_at ?? $mov->created_at)->format('d M Y, H:i') }}</td>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $mov->item?->name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                @if($mov->type === 'IN')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">Masuk</span>
                                @elseif($mov->type === 'OUT')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-xs font-semibold">Keluar</span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold">Penyesuaian</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $mov->quantity }} {{ $mov->unit }}</td>
                            <td class="px-5 py-3 text-slate-600 font-medium">
                                <span>{{ $mov->balance_after ?? '—' }} {{ $mov->unit }}</span>
                                @if($mov->balance_before !== null)
                                <span class="relative inline-flex ml-1 align-middle">
                                    <button type="button" data-balance-tooltip aria-label="Lihat detail saldo" aria-expanded="false" class="inline-flex items-center justify-center w-4 h-4 rounded-full border border-slate-300 text-[10px] text-slate-500 hover:border-corpblue-400 hover:text-corpblue-600 cursor-help">i</button>
                                    <span class="balance-tooltip-content hidden">
                                        Stok sebelum: <strong>{{ $mov->balance_before }} {{ $mov->unit }}</strong><br>
                                        Perubahan: <strong>{{ $mov->type === 'OUT' ? '-' : '+' }}{{ $mov->quantity }} {{ $mov->unit }}</strong><br>
                                        Stok setelah: <strong>{{ $mov->balance_after }} {{ $mov->unit }}</strong>
                                    </span>
                                </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $mov->user?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-500 text-xs max-w-[200px] truncate">{{ $mov->reason }}{{ $mov->note ? " ({$mov->note})" : '' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-slate-400">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($movements->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">{{ $movements->links() }}</div>
            @endif
        </div>

    </div>
</x-layout>
