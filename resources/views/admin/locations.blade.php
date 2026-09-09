<x-layout :title="'Lokasi Rak — MVPWarehouse'" :headerTitle="'Tata Letak Rak'">
    <div class="space-y-6">

        {{-- Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-corpblue-50 flex items-center justify-center shrink-0">
                    <svg class="text-corpblue-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-900">{{ $totalLocations }}</p>
                    <p class="text-xs text-slate-500">Total Slot</p>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                    <svg class="text-emerald-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xl font-bold text-emerald-600">{{ $totalEmpty }}</p>
                    <p class="text-xs text-slate-500">Kosong</p>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                    <svg class="text-amber-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div>
                    <p class="text-xl font-bold text-amber-600">{{ $totalOccupied }}</p>
                    <p class="text-xs text-slate-500">Terisi</p>
                </div>
            </div>
        </div>

        {{-- Rack Tabs --}}
        <div class="flex gap-2 overflow-x-auto pb-1">
            @foreach($rackStats as $rs)
            <a href="{{ route('admin.locations.index', ['rack' => $rs['rack']] + request()->only(['status', 'search'])) }}"
               class="flex items-center gap-2 px-4 py-2.5 rounded-lg border text-sm font-medium whitespace-nowrap transition-all {{ $activeRack === $rs['rack'] ? 'bg-corpblue-50 border-corpblue-300 text-corpblue-700' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300' }}">
                <span class="font-bold">{{ $rs['prefix'] }}</span>
                <span class="text-xs {{ $activeRack === $rs['rack'] ? 'text-corpblue-500' : 'text-slate-400' }}">{{ $rs['occupied'] }}/{{ $rs['total'] }}</span>
            </a>
            @endforeach
        </div>

        {{-- Filter --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form method="GET" action="{{ route('admin.locations.index') }}" data-auto-filter class="flex flex-col sm:flex-row gap-3">
                <input type="hidden" name="rack" value="{{ $activeRack }}">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode, sub lokasi, atau nama barang..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                </div>
                <select name="status" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    <option value="all">Semua Status</option>
                    <option value="Terisi" {{ request('status') === 'Terisi' ? 'selected' : '' }}>Terisi</option>
                    <option value="Kosong" {{ request('status') === 'Kosong' ? 'selected' : '' }}>Kosong</option>
                </select>
                @if(request()->filled('search') || (request()->filled('status') && request('status') !== 'all'))
                <a href="{{ route('admin.locations.index', ['rack' => $activeRack]) }}" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-sm font-medium">Reset</a>
                @endif
            </form>
        </div>

        {{-- Location Grid --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Kode Tag</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Lokasi Rak</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Sub Lokasi</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Barang</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($locations as $loc)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md {{ $loc->isOccupied() ? 'bg-corpblue-50 text-corpblue-700' : 'bg-slate-100 text-slate-500' }} text-xs font-mono font-semibold whitespace-nowrap">{{ $loc->code }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-corpblue-50 text-corpblue-700 text-[11px] font-semibold">{{ $loc->rack }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($loc->sub_location)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-xs font-mono font-semibold">{{ $loc->sub_location }}</span>
                                @else
                                <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($loc->isOccupied())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">Terisi</span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-xs font-semibold">Kosong</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                @forelse($loc->items as $item)
                                <div class="flex items-center gap-1.5">
                                    <span>{{ $item->name }}</span>
                                </div>
                                @empty
                                <span class="text-slate-400 italic">—</span>
                                @endforelse
                            </td>
                            <td class="px-4 py-3 text-slate-500 text-xs">
                                @forelse($loc->items as $item)
                                <div>
                                    {{ $item->size ?: '' }} &middot; {{ $item->stock }} {{ $item->unit }}
                                    @if($loc->items->count() > 1)<span class="text-indigo-600 font-semibold ml-1">(ditumpuk)</span>@endif
                                </div>
                                @empty
                                Slot tersedia
                                @endforelse
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">Tidak ada data lokasi.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($locations->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $locations->links() }}
            </div>
            @endif
        </div>

    </div>
</x-layout>
