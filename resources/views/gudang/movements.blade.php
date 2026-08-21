<x-layout>
    <x-slot:title>Riwayat Movement — MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Riwayat Movement</x-slot:headerTitle>

    <div class="space-y-4">

        {{-- Filter --}}
        <form method="GET" action="/gudang/movements" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2 flex-wrap">
                <select name="type" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-corpblue-500">
                    <option value="">Semua Tipe</option>
                    <option value="IN" {{ request('type') === 'IN' ? 'selected' : '' }}>IN (Masuk)</option>
                    <option value="OUT" {{ request('type') === 'OUT' ? 'selected' : '' }}>OUT (Keluar)</option>
                    <option value="ADJUSTMENT" {{ request('type') === 'ADJUSTMENT' ? 'selected' : '' }}>Adjustment</option>
                </select>
                <select name="item_id" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-corpblue-500">
                    <option value="">Semua Barang</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>{{ $item->display_name }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-corpblue-500" title="Dari tanggal">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-corpblue-500" title="Sampai tanggal">
                <button type="submit" class="bg-corpblue-500 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-corpblue-600 transition-all">Filter</button>
                @if(request()->hasAny(['type', 'item_id', 'date_from', 'date_to']))
                    <a href="/gudang/movements" class="text-xs font-medium text-slate-500 hover:text-slate-700">Reset</a>
                @endif
            </div>
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
                            <th class="py-3 px-5">Saldo</th>
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
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-[11px] font-bold">IN</span>
                                @elseif($m->type === 'OUT')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-50 text-red-700 rounded-full text-[11px] font-bold">OUT</span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 text-amber-700 rounded-full text-[11px] font-bold">ADJ</span>
                                @endif
                            </td>
                            <td class="py-3 px-5 text-center">
                                <span class="font-bold {{ $m->type === 'IN' ? 'text-emerald-600' : ($m->type === 'OUT' ? 'text-red-600' : 'text-amber-600') }}">
                                    {{ $m->type === 'OUT' ? '-' : '+' }}{{ $m->quantity }}
                                </span>
                                <span class="text-xs text-slate-400">{{ $m->unit }}</span>
                            </td>
                            <td class="py-3 px-5 font-semibold text-slate-900">{{ $m->balance_after }}</td>
                            <td class="py-3 px-5 text-xs text-slate-500 max-w-[200px] truncate">{{ $m->reason }}</td>
                            <td class="py-3 px-5 text-xs text-slate-500">{{ $m->user?->name ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-400 text-sm">Belum ada riwayat movement.</td>
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
                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-[11px] font-bold">IN</span>
                    @elseif($m->type === 'OUT')
                        <span class="px-2 py-0.5 bg-red-50 text-red-700 rounded-full text-[11px] font-bold">OUT</span>
                    @else
                        <span class="px-2 py-0.5 bg-amber-50 text-amber-700 rounded-full text-[11px] font-bold">ADJ</span>
                    @endif
                </div>
                <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100 text-xs">
                    <div class="flex items-center gap-3">
                        <span class="font-bold {{ $m->type === 'IN' ? 'text-emerald-600' : ($m->type === 'OUT' ? 'text-red-600' : 'text-amber-600') }} text-base">
                            {{ $m->type === 'OUT' ? '-' : '+' }}{{ $m->quantity }} {{ $m->unit }}
                        </span>
                    </div>
                    <span class="text-slate-500">Saldo: <strong class="text-slate-900">{{ $m->balance_after }}</strong></span>
                </div>
                @if($m->reason)
                <p class="text-[11px] text-slate-400 mt-2">{{ $m->reason }} &middot; {{ $m->user?->name ?? '—' }}</p>
                @endif
            </div>
            @empty
            <div class="bg-white rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">Belum ada riwayat movement.</div>
            @endforelse
            @if($movements->hasPages())
            <div class="pt-2">{{ $movements->links() }}</div>
            @endif
        </div>

    </div>
</x-layout>
