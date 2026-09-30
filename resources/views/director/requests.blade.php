<x-layout :title="__('Semua Request — THI2-WAREHOUSE')" :headerTitle="__('Semua Permintaan Barang')">
    <div class="space-y-6">

        {{-- Filters --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form id="requestFilters" method="GET" data-auto-filter class="flex flex-col sm:flex-row gap-3">
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="{{ __('Cari barang, alasan, peminta...') }}" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                <select name="status" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    <option value="all">{{ __('Semua Status') }}</option>
                    <option value="Menunggu Review" {{ ($status ?? '') === 'Menunggu Review' ? 'selected' : '' }}>{{ __('Menunggu Review') }}</option>
                    <option value="Pending" {{ ($status ?? '') === 'Pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                    <option value="Disetujui" {{ ($status ?? '') === 'Disetujui' ? 'selected' : '' }}>{{ __('Disetujui') }}</option>
                    <option value="Sebagian Diterima" {{ ($status ?? '') === 'Sebagian Diterima' ? 'selected' : '' }}>{{ __('Sebagian Diterima') }}</option>
                    <option value="Diterima Penuh" {{ ($status ?? '') === 'Diterima Penuh' ? 'selected' : '' }}>{{ __('Diterima Penuh') }}</option>
                    <option value="Ditutup Sebagian" {{ ($status ?? '') === 'Ditutup Sebagian' ? 'selected' : '' }}>{{ __('Ditutup Sebagian') }}</option>
                    <option value="Dibatalkan" {{ ($status ?? '') === 'Dibatalkan' ? 'selected' : '' }}>{{ __('Dibatalkan') }}</option>
                    <option value="Ditolak" {{ ($status ?? '') === 'Ditolak' ? 'selected' : '' }}>{{ __('Ditolak') }}</option>
                </select>
                <select name="priority" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    <option value="all">{{ __('Semua Prioritas') }}</option>
                    <option value="Mendesak" {{ ($priority ?? '') === 'Mendesak' ? 'selected' : '' }}>{{ __('Mendesak') }}</option>
                    <option value="Normal" {{ ($priority ?? '') === 'Normal' ? 'selected' : '' }}>{{ __('Normal') }}</option>
                </select>
                <x-period-filter-button :period="$period" />
                @if(request()->filled('search') || (request()->filled('status') && request('status') !== 'all') || (request()->filled('priority') && request('priority') !== 'all') || $period)
                <a href="/director/requests" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-sm font-medium">{{ __('Reset') }}</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[760px]">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('Barang') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('Peminta') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('Jumlah') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('Prioritas') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('Diterima') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('Tanggal') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($requests as $req)
                        <tr class="hover:bg-slate-50/50 cursor-pointer" onclick="window.location='{{ route('director.request-detail', $req) }}'">
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $req->item_name }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $req->user?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $req->quantity }} {{ $req->unit }}</td>
                            <td class="px-5 py-3">
                                <x-status-badge domain="priority" :status="$req->priority" />
                            </td>
                            <td class="px-5 py-3">
                                <x-status-badge domain="request" :status="$req->status" />
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $req->received_quantity }}/{{ $req->quantity }}</td>
                            <td class="px-5 py-3 text-slate-400 text-xs">{{ $req->created_at->translatedFormat('d M Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-slate-400">{{ __('Tidak ada request ditemukan.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requests->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">{{ $requests->links() }}</div>
            @endif
        </div>

    </div>
    <x-slot:modals><x-period-filter-modal :period="$period" form-id="requestFilters" /></x-slot:modals>
</x-layout>
