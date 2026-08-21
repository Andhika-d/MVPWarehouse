<x-layout>
    <x-slot:title>Stok Barang — MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Stok Barang</x-slot:headerTitle>

    @php
        $search = request('search');
        $activeRack = request('rack');
    @endphp

    <div class="space-y-4">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between">
            @if($activeRack)
            <a href="/gudang/stock" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-700 transition-all">
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
            {{-- ===== VIEW: Items inside a rack ===== --}}

            {{-- Search --}}
            <form method="GET" action="/gudang/stock" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                <input type="hidden" name="rack" value="{{ $activeRack }}">
                <div class="relative flex-1 max-w-sm">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama barang..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all">
                </div>
                <button type="submit" class="bg-corpblue-500 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-corpblue-600 transition-all">Cari</button>
            </form>

            @php
                $filtered = $search ? $items->filter(fn($item) => str_contains(strtolower($item->name), strtolower($search))) : $items;
            @endphp

            {{-- GRID --}}
            <div id="layout-grid-items" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($filtered as $item)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition-all">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $item->name }}</p>
                            @if($item->size)<p class="text-xs text-slate-500 mt-0.5">{{ $item->size }}</p>@endif
                        </div>
                        <span class="inline-flex items-center justify-center w-8 h-8 bg-corpblue-50 text-corpblue-600 rounded-lg text-xs font-bold shrink-0">{{ $item->rack_location }}</span>
                    </div>
                    <div class="space-y-1.5 text-sm mb-3">
                        <div class="flex justify-between"><span class="text-slate-500">Stok</span><span class="font-bold {{ $item->stock > 0 ? 'text-slate-900' : 'text-red-500' }}">{{ $item->stock }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Satuan</span><span class="font-semibold text-slate-700">{{ $item->unit }}</span></div>
                    </div>
                    <div class="pt-3 border-t border-slate-100">
                        @if($item->stock > 0)<span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-semibold"><span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> Tersedia</span>
                        @else<span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-red-50 text-red-700 rounded-full text-xs font-semibold"><span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Habis</span>@endif
                    </div>
                </div>
                @empty
                <div class="col-span-full bg-white rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">Tidak ada barang ditemukan.</div>
                @endforelse
            </div>

            {{-- TILES --}}
            <div id="layout-tiles-items" class="hidden space-y-2">
                @forelse($filtered as $item)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-3.5 flex items-center gap-4 hover:shadow-md transition-all">
                    <span class="inline-flex items-center justify-center w-10 h-10 bg-corpblue-50 text-corpblue-600 rounded-lg text-sm font-bold shrink-0">{{ $item->rack_location }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-900 truncate">{{ $item->name }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $item->size ?: '—' }} &middot; {{ $item->unit }}</p>
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
                <div class="bg-white rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">Tidak ada barang ditemukan.</div>
                @endforelse
            </div>

            {{-- LIST --}}
            <div id="layout-list-items" class="hidden bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-5">Nama Barang</th>
                            <th class="py-3 px-5">Ukuran</th>
                            <th class="py-3 px-5">Rak</th>
                            <th class="py-3 px-5 text-center">Stok</th>
                            <th class="py-3 px-5">Satuan</th>
                            <th class="py-3 px-5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($filtered as $item)
                        <tr class="hover:bg-slate-50/70 transition-all">
                            <td class="py-3 px-5 font-semibold text-slate-900">{{ $item->name }}</td>
                            <td class="py-3 px-5 text-slate-500">{{ $item->size ?: '—' }}</td>
                            <td class="py-3 px-5"><span class="inline-flex items-center justify-center w-7 h-7 bg-corpblue-50 text-corpblue-600 rounded-lg text-xs font-bold">{{ $item->rack_location }}</span></td>
                            <td class="py-3 px-5 text-center"><span class="text-base font-bold {{ $item->stock > 0 ? 'text-slate-900' : 'text-red-500' }}">{{ $item->stock }}</span></td>
                            <td class="py-3 px-5 text-slate-500">{{ $item->unit }}</td>
                            <td class="py-3 px-5">
                                @if($item->stock > 0)<span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-semibold"><span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> Tersedia</span>
                                @else<span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-red-50 text-red-700 rounded-full text-xs font-semibold"><span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Habis</span>@endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="py-10 text-center text-slate-400">Tidak ada barang ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50 text-xs text-slate-500 font-medium">Total {{ $filtered->count() }} barang di Rak {{ $activeRack }}</div>
            </div>

            {{-- CONTENT --}}
            <div id="layout-content-items" class="space-y-2">
                @forelse($filtered as $item)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-bold text-slate-900">{{ $item->name }}</p>
                        @if($item->stock > 0)<span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-[11px] font-semibold">Tersedia</span>
                        @else<span class="px-2 py-0.5 bg-red-50 text-red-700 rounded-full text-[11px] font-semibold">Habis</span>@endif
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div><span class="text-slate-500">Ukuran</span><p class="font-semibold text-slate-900 mt-0.5">{{ $item->size ?: '—' }}</p></div>
                        <div><span class="text-slate-500">Rak</span><p class="font-semibold text-corpblue-600 mt-0.5">{{ $item->rack_location }}</p></div>
                        <div><span class="text-slate-500">Stok</span><p class="font-bold {{ $item->stock > 0 ? 'text-slate-900' : 'text-red-500' }} mt-0.5 text-base">{{ $item->stock }}</p></div>
                        <div><span class="text-slate-500">Satuan</span><p class="font-semibold text-slate-900 mt-0.5">{{ $item->unit }}</p></div>
                    </div>
                </div>
                @empty
                <div class="bg-white rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">Tidak ada barang ditemukan.</div>
                @endforelse
            </div>

            @if($filtered->isNotEmpty())
            <p class="text-xs text-slate-400 text-center">Total {{ $filtered->count() }} barang di Rak {{ $activeRack }}</p>
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
                        <div class="flex justify-between"><span class="text-slate-500">Jenis Barang</span><span class="font-semibold text-slate-900">{{ $rack['totalItems'] }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Total Stok</span><span class="font-semibold {{ $rack['totalStock'] > 0 ? 'text-slate-900' : 'text-red-500' }}">{{ $rack['totalStock'] }}</span></div>
                    </div>
                    <a href="/gudang/stock?rack={{ $rack['rack'] }}" class="block w-full text-center px-4 py-2.5 bg-corpblue-500 text-white text-sm font-semibold rounded-lg hover:bg-corpblue-600 transition-all">Lihat Barang</a>
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
                        <p class="text-xs text-slate-500 mt-0.5">{{ $rack['totalItems'] }} jenis &middot; Stok: {{ $rack['totalStock'] }}</p>
                    </div>
                    <a href="/gudang/stock?rack={{ $rack['rack'] }}" class="shrink-0 px-4 py-2 bg-corpblue-500 text-white text-xs font-semibold rounded-lg hover:bg-corpblue-600 transition-all">Lihat Barang</a>
                </div>
                @endforeach
            </div>

            {{-- LIST --}}
            <div id="layout-list-racks" class="hidden bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-5">Rak</th>
                            <th class="py-3 px-5 text-center">Jenis Barang</th>
                            <th class="py-3 px-5 text-center">Total Stok</th>
                            <th class="py-3 px-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($rackData as $rack)
                        <tr class="hover:bg-slate-50/70 transition-all">
                            <td class="py-3 px-5"><span class="inline-flex items-center justify-center w-8 h-8 bg-corpblue-50 text-corpblue-600 rounded-lg text-sm font-bold">{{ $rack['rack'] }}</span></td>
                            <td class="py-3 px-5 text-center font-semibold text-slate-900">{{ $rack['totalItems'] }}</td>
                            <td class="py-3 px-5 text-center"><span class="font-bold {{ $rack['totalStock'] > 0 ? 'text-slate-900' : 'text-red-500' }}">{{ $rack['totalStock'] }}</span></td>
                            <td class="py-3 px-5 text-right"><a href="/gudang/stock?rack={{ $rack['rack'] }}" class="text-xs font-semibold text-corpblue-500 hover:underline">Lihat Barang &rarr;</a></td>
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
                        <a href="/gudang/stock?rack={{ $rack['rack'] }}" class="px-4 py-2 bg-corpblue-500 text-white text-xs font-semibold rounded-lg hover:bg-corpblue-600 transition-all">Lihat Barang</a>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div><span class="text-slate-500">Jenis Barang</span><p class="font-bold text-slate-900 mt-0.5 text-base">{{ $rack['totalItems'] }}</p></div>
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
