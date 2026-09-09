<x-layout :title="'Semua Request — MVPWarehouse'" :headerTitle="'Semua Permintaan Barang'">
    <div class="space-y-6">

        {{-- Filters --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form method="GET" data-auto-filter class="flex flex-col sm:flex-row gap-3">
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari barang, alasan, peminta..." class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                <select name="status" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    <option value="all">Semua Status</option>
                    <option value="Menunggu Review" {{ ($status ?? '') === 'Menunggu Review' ? 'selected' : '' }}>Menunggu Review</option>
                    <option value="Pending" {{ ($status ?? '') === 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Disetujui" {{ ($status ?? '') === 'Disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="Sebagian Diterima" {{ ($status ?? '') === 'Sebagian Diterima' ? 'selected' : '' }}>Sebagian Diterima</option>
                    <option value="Diterima Penuh" {{ ($status ?? '') === 'Diterima Penuh' ? 'selected' : '' }}>Diterima Penuh</option>
                    <option value="Ditutup Sebagian" {{ ($status ?? '') === 'Ditutup Sebagian' ? 'selected' : '' }}>Ditutup Sebagian</option>
                    <option value="Dibatalkan" {{ ($status ?? '') === 'Dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                    <option value="Ditolak" {{ ($status ?? '') === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
                <select name="priority" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    <option value="all">Semua Prioritas</option>
                    <option value="Mendesak" {{ ($priority ?? '') === 'Mendesak' ? 'selected' : '' }}>Mendesak</option>
                    <option value="Normal" {{ ($priority ?? '') === 'Normal' ? 'selected' : '' }}>Normal</option>
                </select>
                @if(request()->filled('search') || (request()->filled('status') && request('status') !== 'all') || (request()->filled('priority') && request('priority') !== 'all'))
                <a href="/director/requests" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-sm font-medium">Reset</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Barang</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Peminta</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Jumlah</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Prioritas</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Diterima</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($requests as $req)
                        <tr class="hover:bg-slate-50/50 cursor-pointer" onclick="window.location='{{ route('director.request-detail', $req) }}'">
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $req->item_name }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $req->user?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $req->quantity }} {{ $req->unit }}</td>
                            <td class="px-5 py-3">
                                @if($req->priority === 'Mendesak')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-xs font-semibold">Mendesak</span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold">Normal</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if($req->status === 'Menunggu Review' || $req->status === 'Pending')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold">{{ $req->status }}</span>
                                @elseif($req->status === 'Disetujui')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-corpblue-50 text-corpblue-700 text-xs font-semibold">{{ $req->status }}</span>
                                @elseif($req->status === 'Sebagian Diterima')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold">{{ $req->status }}</span>
                                @elseif($req->status === 'Diterima Penuh')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">{{ $req->status }}</span>
                                @elseif($req->status === 'Ditolak')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-xs font-semibold">{{ $req->status }}</span>
                                @elseif($req->status === 'Ditutup Sebagian')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">{{ $req->status }}</span>
                                @elseif($req->status === 'Dibatalkan')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-stone-100 text-stone-700 text-xs font-semibold">{{ $req->status }}</span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold">{{ $req->status }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $req->received_quantity }}/{{ $req->quantity }}</td>
                            <td class="px-5 py-3 text-slate-400 text-xs">{{ $req->created_at->format('d M Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-slate-400">Tidak ada request ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requests->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">{{ $requests->links() }}</div>
            @endif
        </div>

    </div>
</x-layout>
