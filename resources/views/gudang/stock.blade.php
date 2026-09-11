<x-layout :title="'Stok Barang — MVPWarehouse'" :headerTitle="'Monitoring Stok Barang'">
    <div class="space-y-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-corpblue-50 flex items-center justify-center shrink-0">
                    <svg class="text-corpblue-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                </div>
                <div><p class="text-xl font-bold text-slate-900">{{ number_format($summary['total_items']) }}</p><p class="text-xs text-slate-500">Total Lokasi</p></div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-corpblue-50 flex items-center justify-center shrink-0">
                    <svg class="text-corpblue-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div><p class="text-xl font-bold text-corpblue-700">{{ number_format($summary['total_stock']) }}</p><p class="text-xs text-slate-500">Total Stok</p></div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                    <svg class="text-amber-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77-.833.192-1.732 1.732-1.732z"/></svg>
                </div>
                <div><p class="text-xl font-bold text-amber-600">{{ number_format($summary['low_stock']) }}</p><p class="text-xs text-slate-500">Stok Menipis (1-5)</p></div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="text-red-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div><p class="text-xl font-bold text-red-600">{{ number_format($summary['out_of_stock']) }}</p><p class="text-xs text-slate-500">Barang Kosong (0)</p></div>
            </div>
        </div>

        <div class="flex gap-2 overflow-x-auto pb-1">
            <a href="{{ $basePath }}{{ $search || $status ? '?' . http_build_query(array_filter(['search' => $search, 'status' => $status])) : '' }}" class="flex items-center gap-2 px-4 py-2.5 rounded-lg border text-sm font-medium whitespace-nowrap transition-all {{ !$activeRack ? 'bg-corpblue-50 border-corpblue-300 text-corpblue-700' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300' }}">Semua</a>
            @foreach($rackData as $rack)
            <a href="{{ $basePath }}?rack={{ $rack['rack'] }}{{ $search || $status ? '&' . http_build_query(array_filter(['search' => $search, 'status' => $status])) : '' }}" class="flex items-center gap-2 px-4 py-2.5 rounded-lg border text-sm font-medium whitespace-nowrap transition-all {{ $activeRack === $rack['rack'] ? 'bg-corpblue-50 border-corpblue-300 text-corpblue-700' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300' }}"><span class="font-bold">{{ $rack['rack'] }}</span><span class="text-xs {{ $activeRack === $rack['rack'] ? 'text-corpblue-500' : 'text-slate-400' }}">{{ $rack['totalItems'] }}/{{ $rack['total'] }}</span></a>
            @endforeach
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form method="GET" action="{{ $basePath }}" data-auto-filter class="flex flex-col sm:flex-row gap-3">
                @if($activeRack)<input type="hidden" name="rack" value="{{ $activeRack }}">@endif
                <div class="flex-1"><input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari kode tag, nama barang, sub lokasi..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none"></div>
                <select name="status" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none cursor-pointer">
                    <option value="" {{ ($status ?? '') === '' ? 'selected' : '' }}>Semua Status</option>
                    <option value="occupied" {{ ($status ?? '') === 'occupied' ? 'selected' : '' }}>Terisi</option>
                    <option value="empty" {{ ($status ?? '') === 'empty' ? 'selected' : '' }}>Lokasi Kosong</option>
                    <option value="item_empty" {{ ($status ?? '') === 'item_empty' ? 'selected' : '' }}>Barang Kosong</option>
                </select>
                @if(($search ?? '') !== '' || ($status ?? '') !== '')
                <a href="{{ $activeRack ? $basePath.'?rack='.urlencode($activeRack) : $basePath }}" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-sm font-medium">Reset</a>
                @endif
            </form>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Kode Tag</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Lokasi Rak</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Sub Lokasi</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Nama Barang</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Ukuran</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Stok</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Satuan</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($locations as $location)
                            @if($location->items->isNotEmpty())
                            @foreach($location->items as $item)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-md bg-corpblue-50 text-corpblue-700 text-xs font-mono font-semibold whitespace-nowrap">{{ $location->code }}</span></td>
                                <td class="px-4 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-md bg-corpblue-50 text-corpblue-700 text-[11px] font-semibold">{{ $location->rack }}</span></td>
                                <td class="px-4 py-3 text-xs font-mono text-slate-600">{{ $location->sub_location ?? '—' }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $item->name }}@if($location->items->count() > 1)<span class="text-[11px] text-slate-500 ml-1">(+{{ $location->items->count() - 1 }} ditumpuk)</span>@endif</td>
                                <td class="px-4 py-3 text-slate-600">{{ $item->size ?? '—' }}</td>
                                <td class="px-4 py-3 text-center"><span class="font-semibold {{ $item->stock === 0 ? 'text-red-600' : ($item->stock <= 5 ? 'text-amber-600' : 'text-slate-900') }}">{{ $item->stock }}</span></td>
                                <td class="px-4 py-3 text-slate-600">{{ $item->unit }}</td>
                                <td class="px-4 py-3 text-center">
                                    <x-status-badge domain="location" status="Terisi" :label="$item->stock === 0 ? 'Terisi (Barang Kosong)' : 'Terisi'" dot />
                                </td>
                            </tr>
                            @endforeach
                            @else
                            <tr class="bg-slate-50/50">
                                <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-500 text-xs font-mono font-semibold whitespace-nowrap">{{ $location->code }}</span></td>
                                <td class="px-4 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 text-[11px] font-semibold">{{ $location->rack }}</span></td>
                                <td class="px-4 py-3 text-xs font-mono text-slate-500">{{ $location->sub_location ?? '—' }}</td>
                                <td class="px-4 py-3 font-medium text-slate-400 italic">Lokasi Kosong</td>
                                <td class="px-4 py-3 text-slate-400">—</td>
                                <td class="px-4 py-3 text-center text-slate-400">—</td>
                                <td class="px-4 py-3 text-slate-400">—</td>
                                <td class="px-4 py-3 text-center"><x-status-badge domain="location" status="Kosong" dot /></td>
                            </tr>
                            @endif
                        @empty
                        <tr><td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400">Tidak ada data lokasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($locations->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">{{ $locations->links() }}</div>
            @endif
        </div>
    </div>
</x-layout>
