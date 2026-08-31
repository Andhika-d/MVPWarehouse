<x-layout>
    <x-slot:title>Stok Barang — MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Stok Barang</x-slot:headerTitle>

    @php
        $search = request('search');
        $activeRack = request('rack');
        $status = request('status');
    @endphp

    <div class="space-y-4">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between">
            @if($activeRack)
            <a href="{{ $basePath }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-700 transition-all">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                Kembali
            </a>
            @else
            <div></div>
            @endif

            <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-0.5">
                <button type="button" onclick="setLayout('grid')" data-btn="grid" class="layout-btn p-1.5 rounded-md text-slate-400 hover:text-slate-600 transition-all cursor-pointer" title="Grid">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"></path></svg>
                </button>
                <button type="button" onclick="setLayout('tiles')" data-btn="tiles" class="layout-btn p-1.5 rounded-md text-slate-400 hover:text-slate-600 transition-all cursor-pointer" title="Tiles">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                </button>
                <button type="button" onclick="setLayout('list')" data-btn="list" class="layout-btn p-1.5 rounded-md text-slate-400 hover:text-slate-600 transition-all cursor-pointer" title="List">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                </button>
                <button type="button" onclick="setLayout('content')" data-btn="content" class="layout-btn p-1.5 rounded-md text-slate-400 hover:text-slate-600 transition-all cursor-pointer" title="Content">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </button>
            </div>
        </div>

        @if($activeRack)
            {{-- ===== VIEW: Locations inside a rack ===== --}}

            {{-- Search + Filter --}}
            <form method="GET" action="{{ $basePath }}" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                <input type="hidden" name="rack" value="{{ $activeRack }}">
                <div class="relative flex-1 max-w-sm">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama barang, kode tag, sub lokasi..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all">
                </div>
                <select name="status" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 transition-all cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="occupied" {{ $status === 'occupied' ? 'selected' : '' }}>Terisi</option>
                    <option value="empty" {{ $status === 'empty' ? 'selected' : '' }}>Kosong</option>
                    <option value="item_empty" {{ $status === 'item_empty' ? 'selected' : '' }}>Barang Kosong</option>
                </select>
                <button type="submit" class="bg-corpblue-500 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-corpblue-600 transition-all">Cari</button>
            </form>

            {{-- Summary --}}
            <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
                <span>Menampilkan {{ $locations->firstItem() ?? 0 }}-{{ $locations->lastItem() ?? 0 }} dari {{ $locations->total() }} slot</span>
                <span class="flex items-center gap-3">
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> {{ $locations->getCollection()->filter(fn($l) => $l->isOccupied())->count() }} terisi</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-slate-400"></span> {{ $locations->getCollection()->filter(fn($l) => $l->isEmpty())->count() }} kosong</span>
                </span>
            </div>

            {{-- GRID --}}
            <div id="layout-grid-items" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($locations as $loc)
                @forelse($loc->items as $item)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition-all">
                    <div class="flex items-start justify-between mb-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-900">{{ $item->name }}</p>
                            @if($item->size)<p class="text-xs text-slate-500 mt-0.5">{{ $item->size }}</p>@endif
                            @if($loc->items->count() > 1)
                            <p class="text-[11px] font-semibold text-indigo-600 mt-0.5">{{ $loc->items->count() }} barang ditumpuk</p>
                            @endif
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 bg-corpblue-50 text-corpblue-600 rounded-lg text-xs font-bold whitespace-nowrap">{{ $loc->code }}</span>
                    </div>
                    <div class="space-y-1.5 text-sm mb-3">
                        <div class="flex justify-between"><span class="text-slate-500">Stok</span><span class="font-bold {{ $item->stock > 0 ? 'text-slate-900' : 'text-red-500' }}">{{ $item->stock }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Satuan</span><span class="font-semibold text-slate-700">{{ $item->unit }}</span></div>
                        @if($loc->sub_location)
                        <div class="flex justify-between"><span class="text-slate-500">Sub Lokasi</span><span class="font-mono text-xs text-indigo-600">{{ $loc->sub_location }}</span></div>
                        @endif
                    </div>
                    <div class="pt-3 border-t border-slate-100">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-semibold"><span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> Terisi</span>
                    </div>
                </div>
                @empty
                <div class="bg-white rounded-xl border border-dashed border-slate-200 shadow-sm p-5 hover:shadow-md transition-all">
                    <div class="flex items-start justify-between mb-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-400 italic">Lokasi Kosong</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 bg-slate-100 text-slate-400 rounded-lg text-xs font-bold whitespace-nowrap">{{ $loc->code }}</span>
                    </div>
                    <div class="space-y-1.5 text-sm mb-3">
                        <div class="flex justify-between"><span class="text-slate-500">Status</span><span class="text-slate-400">Slot tersedia</span></div>
                        @if($loc->sub_location)
                        <div class="flex justify-between"><span class="text-slate-500">Sub Lokasi</span><span class="font-mono text-xs text-indigo-600">{{ $loc->sub_location }}</span></div>
                        @endif
                    </div>
                    <div class="pt-3 border-t border-slate-100">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 text-slate-500 rounded-full text-xs font-semibold"><span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span> Kosong</span>
                    </div>
                </div>
                @endforelse
                @empty
                <div class="col-span-full bg-white rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">Tidak ada data ditemukan.</div>
                @endforelse
            </div>

            {{-- TILES --}}
            <div id="layout-tiles-items" class="hidden space-y-2">
                @forelse($locations as $loc)
                @forelse($loc->items as $item)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-3.5 flex items-center gap-4 hover:shadow-md transition-all">
                    <span class="inline-flex items-center px-2.5 py-1 bg-corpblue-50 text-corpblue-600 rounded-lg text-xs font-bold whitespace-nowrap shrink-0">{{ $loc->code }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-900 truncate">{{ $item->name }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ ($item->size ?: '—') }} · {{ $item->unit }}@if($loc->items->count() > 1) · <span class="text-indigo-600 font-semibold">ditumpuk</span>@endif</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-lg font-bold {{ $item->stock > 0 ? 'text-slate-900' : 'text-red-500' }}">{{ $item->stock }}</p>
                    </div>
                    <div class="shrink-0">
                        @if($item->stock > 0)<span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-[11px] font-semibold">Tersedia</span>
                        @else<span class="px-2 py-0.5 bg-red-50 text-red-700 rounded-full text-[11px] font-semibold">Habis</span>@endif
                    </div>
                </div>
                @empty
                <div class="bg-white rounded-xl border border-dashed border-slate-200 shadow-sm px-5 py-3.5 flex items-center gap-4 hover:shadow-md transition-all">
                    <span class="inline-flex items-center px-2.5 py-1 bg-slate-100 text-slate-400 rounded-lg text-xs font-bold whitespace-nowrap shrink-0">{{ $loc->code }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-400 italic truncate">Lokasi Kosong</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $loc->sub_location ?? '—' }}</p>
                    </div>
                    <div class="shrink-0">
                        <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full text-[11px] font-semibold">Kosong</span>
                    </div>
                </div>
                @endforelse
                @empty
                <div class="bg-white rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">Tidak ada data ditemukan.</div>
                @endforelse
            </div>

            {{-- LIST --}}
            <div id="layout-list-items" class="hidden bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                <table data-stock-item-results class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-5 whitespace-nowrap">Kode Tag</th>
                            <th class="py-3 px-5 whitespace-nowrap">Sub Lokasi</th>
                            <th class="py-3 px-5 whitespace-nowrap">Nama Barang</th>
                            <th class="py-3 px-5 whitespace-nowrap">Ukuran</th>
                            <th class="py-3 px-5 text-center whitespace-nowrap">Stok</th>
                            <th class="py-3 px-5 whitespace-nowrap">Satuan</th>
                            <th class="py-3 px-5 whitespace-nowrap">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($locations as $loc)
                        @forelse($loc->items as $item)
                        <tr class="hover:bg-slate-50/70 transition-all">
                            <td class="py-3 px-5"><span class="inline-flex items-center px-2 py-0.5 bg-corpblue-50 text-corpblue-600 rounded-lg text-xs font-bold font-mono whitespace-nowrap">{{ $loc->code }}</span></td>
                            <td class="py-3 px-5">
                                @if($loc->sub_location)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-xs font-mono font-semibold whitespace-nowrap">{{ $loc->sub_location }}</span>
                                @else
                                <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-5 font-semibold text-slate-900">{{ $item->name }}</td>
                            <td class="py-3 px-5 text-slate-500">{{ $item->size ?? '—' }}</td>
                            <td class="py-3 px-5 text-center">
                                <span class="text-base font-bold {{ $item->stock > 0 ? 'text-slate-900' : 'text-red-500' }}">{{ $item->stock }}</span>
                            </td>
                            <td class="py-3 px-5 text-slate-500">{{ $item->unit }}</td>
                            <td class="py-3 px-5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-semibold"><span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> Terisi</span>
                                @if($loc->items->count() > 1)
                                <span class="text-[11px] text-indigo-600 font-semibold ml-1">+{{ $loc->items->count() - 1 }} ditumpuk</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr class="hover:bg-slate-50/70 transition-all bg-slate-50/50">
                            <td class="py-3 px-5"><span class="inline-flex items-center px-2 py-0.5 bg-slate-100 text-slate-400 rounded-lg text-xs font-bold font-mono whitespace-nowrap">{{ $loc->code }}</span></td>
                            <td class="py-3 px-5">
                                @if($loc->sub_location)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-xs font-mono font-semibold whitespace-nowrap">{{ $loc->sub_location }}</span>
                                @else
                                <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-5 font-semibold text-slate-400 italic">—</td>
                            <td class="py-3 px-5 text-slate-500">—</td>
                            <td class="py-3 px-5 text-center"><span class="text-slate-400">0</span></td>
                            <td class="py-3 px-5 text-slate-500">—</td>
                            <td class="py-3 px-5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 text-slate-500 rounded-full text-xs font-semibold"><span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span> Kosong</span>
                            </td>
                        </tr>
                        @endforelse
                        @empty
                        <tr><td colspan="7" class="py-10 text-center text-slate-400">Tidak ada data ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            {{-- CONTENT --}}
            <div id="layout-content-items" class="space-y-2">
                @forelse($locations as $loc)
                @forelse($loc->items as $item)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-bold text-slate-900">{{ $item->name }}@if($loc->items->count() > 1)<span class="text-[11px] font-semibold text-indigo-600 ml-1">+{{ $loc->items->count() - 1 }} barang ditumpuk</span>@endif</p>
                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-[11px] font-semibold">Terisi</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div><span class="text-slate-500">Kode Tag</span><p class="font-semibold text-corpblue-600 mt-0.5 whitespace-nowrap">{{ $loc->code }}</p></div>
                        <div><span class="text-slate-500">Sub Lokasi</span><p class="font-semibold text-indigo-600 mt-0.5 font-mono text-[11px]">{{ $loc->sub_location ?? '—' }}</p></div>
                        <div><span class="text-slate-500">Ukuran</span><p class="font-semibold text-slate-900 mt-0.5">{{ $item->size ?: '—' }}</p></div>
                        <div><span class="text-slate-500">Stok</span><p class="font-bold {{ $item->stock > 0 ? 'text-slate-900' : 'text-red-500' }} mt-0.5 text-base">{{ $item->stock }}</p></div>
                        <div><span class="text-slate-500">Satuan</span><p class="font-semibold text-slate-900 mt-0.5">{{ $item->unit }}</p></div>
                    </div>
                </div>
                @empty
                <div class="bg-white rounded-xl border border-dashed border-slate-200 shadow-sm p-5 hover:shadow-md transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-bold text-slate-400 italic">Lokasi Kosong</p>
                        <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full text-[11px] font-semibold">Kosong</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div><span class="text-slate-500">Kode Tag</span><p class="font-semibold text-corpblue-600 mt-0.5 whitespace-nowrap">{{ $loc->code }}</p></div>
                        <div><span class="text-slate-500">Sub Lokasi</span><p class="font-semibold text-indigo-600 mt-0.5 font-mono text-[11px]">{{ $loc->sub_location ?? '—' }}</p></div>
                        <div class="col-span-2"><span class="text-slate-500">Status</span><p class="font-semibold text-slate-400 mt-0.5">Slot tersedia</p></div>
                    </div>
                </div>
                @endforelse
                @empty
                <div class="bg-white rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">Tidak ada data ditemukan.</div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if($locations->hasPages())
            <div class="mt-4">{{ $locations->withQueryString()->links() }}</div>
            @endif

        @else
            {{-- ===== VIEW: Rack cards ===== --}}

            {{-- GRID --}}
            <div id="layout-grid-racks" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($rackData as $rack)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:border-corpblue-200 transition-all">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-corpblue-50 rounded-xl flex items-center justify-center">
                            <span class="text-xl font-bold text-corpblue-600">{{ $rack['rack'] }}</span>
                        </div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rak {{ $rack['rack'] }}</span>
                    </div>
                    <div class="space-y-2 mb-4 text-sm">
                        <div class="flex justify-between"><span class="text-slate-500">Total Slot</span><span class="font-semibold text-slate-900">{{ $rack['total'] }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Terisi</span><span class="font-semibold text-slate-900">{{ $rack['totalItems'] }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Total Stok</span><span class="font-semibold {{ $rack['totalStock'] > 0 ? 'text-slate-900' : 'text-red-500' }}">{{ $rack['totalStock'] }}</span></div>
                    </div>
                    <a href="{{ $basePath }}?rack={{ $rack['rack'] }}" class="block w-full text-center px-4 py-2.5 bg-corpblue-500 text-white text-sm font-semibold rounded-lg hover:bg-corpblue-600 transition-all">Lihat Barang</a>
                </div>
                @endforeach
            </div>

            {{-- TILES --}}
            <div id="layout-tiles-racks" class="hidden space-y-2">
                @foreach($rackData as $rack)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-3.5 flex items-center gap-4 hover:shadow-md hover:border-corpblue-200 transition-all">
                    <div class="w-10 h-10 bg-corpblue-50 rounded-lg flex items-center justify-center shrink-0">
                        <span class="text-lg font-bold text-corpblue-600">{{ $rack['rack'] }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-900">Rak {{ $rack['rack'] }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $rack['total'] }} slot &middot; {{ $rack['totalItems'] }} terisi &middot; Stok: {{ $rack['totalStock'] }}</p>
                    </div>
                    <a href="{{ $basePath }}?rack={{ $rack['rack'] }}" class="shrink-0 px-4 py-2 bg-corpblue-500 text-white text-xs font-semibold rounded-lg hover:bg-corpblue-600 transition-all">Lihat Barang</a>
                </div>
                @endforeach
            </div>

            {{-- LIST --}}
            <div id="layout-list-racks" class="hidden bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-5">Rak</th>
                            <th class="py-3 px-5 text-center">Total Slot</th>
                            <th class="py-3 px-5 text-center">Terisi</th>
                            <th class="py-3 px-5 text-center">Total Stok</th>
                            <th class="py-3 px-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($rackData as $rack)
                        <tr class="hover:bg-slate-50/70 transition-all">
                            <td class="py-3 px-5"><span class="inline-flex items-center px-2.5 py-1 bg-corpblue-50 text-corpblue-600 rounded-lg text-sm font-bold whitespace-nowrap">{{ $rack['rack'] }}</span></td>
                            <td class="py-3 px-5 text-center font-semibold text-slate-900">{{ $rack['total'] }}</td>
                            <td class="py-3 px-5 text-center font-semibold text-slate-900">{{ $rack['totalItems'] }}</td>
                            <td class="py-3 px-5 text-center"><span class="font-bold {{ $rack['totalStock'] > 0 ? 'text-slate-900' : 'text-red-500' }}">{{ $rack['totalStock'] }}</span></td>
                            <td class="py-3 px-5 text-right"><a href="{{ $basePath }}?rack={{ $rack['rack'] }}" class="text-xs font-semibold text-corpblue-500 hover:underline">Lihat Barang &rarr;</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- CONTENT --}}
            <div id="layout-content-racks" class="space-y-2">
                @foreach($rackData as $rack)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:border-corpblue-200 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-corpblue-50 rounded-lg flex items-center justify-center">
                                <span class="text-lg font-bold text-corpblue-600">{{ $rack['rack'] }}</span>
                            </div>
                            <p class="text-sm font-bold text-slate-900">Rak {{ $rack['rack'] }}</p>
                        </div>
                        <a href="{{ $basePath }}?rack={{ $rack['rack'] }}" class="px-4 py-2 bg-corpblue-500 text-white text-xs font-semibold rounded-lg hover:bg-corpblue-600 transition-all">Lihat Barang</a>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div><span class="text-slate-500">Total Slot</span><p class="font-bold text-slate-900 mt-0.5 text-base">{{ $rack['total'] }}</p></div>
                        <div><span class="text-slate-500">Terisi</span><p class="font-bold text-slate-900 mt-0.5 text-base">{{ $rack['totalItems'] }}</p></div>
                        <div><span class="text-slate-500">Total Stok</span><p class="font-bold {{ $rack['totalStock'] > 0 ? 'text-slate-900' : 'text-red-500' }} mt-0.5 text-base">{{ $rack['totalStock'] }}</p></div>
                    </div>
                </div>
                @endforeach
            </div>

        @endif

    </div>

    <script>
        var currentLayout = localStorage.getItem('stockLayout') || 'list';

        function setLayout(layout) {
            currentLayout = layout;
            localStorage.setItem('stockLayout', layout);
            applyLayout();
        }

        function applyLayout() {
            var racks = ['grid-racks', 'tiles-racks', 'list-racks', 'content-racks'];
            var items = ['grid-items', 'tiles-items', 'list-items', 'content-items'];
            var all = racks.concat(items);

            all.forEach(function(id) {
                var el = document.getElementById('layout-' + id);
                if (el) el.classList.add('hidden');
            });

            var activeId = 'layout-' + currentLayout + '-' + (document.querySelector('[name="rack"]') ? 'items' : 'racks');
            var activeEl = document.getElementById(activeId);
            if (activeEl) activeEl.classList.remove('hidden');

            document.querySelectorAll('.layout-btn').forEach(function(btn) {
                btn.classList.remove('bg-white', 'text-slate-900', 'shadow-sm');
                btn.classList.add('text-slate-400');
            });
            var activeBtn = document.querySelector('[data-btn="' + currentLayout + '"]');
            if (activeBtn) {
                activeBtn.classList.add('bg-white', 'text-slate-900', 'shadow-sm');
                activeBtn.classList.remove('text-slate-400');
            }
        }

        applyLayout();
    </script>
</x-layout>
