<x-layout>
    <x-slot:title>Riwayat Pengadaan — THI2-WAREHOUSE</x-slot:title>
    <x-slot:headerTitle>Riwayat Pengadaan</x-slot:headerTitle>

    <div class="space-y-5">
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Total Nota</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($totalNotes) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Aktif</p>
                <p class="mt-1 text-2xl font-bold text-corpblue-700">{{ number_format($activeCount) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Selesai</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ number_format($completedCount) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Total Request</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($requestCount) }}</p>
            </div>
        </div>

        <form id="procurementFilters" method="GET" action="{{ route('procurement-notes.index') }}" data-auto-filter class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="grid gap-3 lg:grid-cols-[minmax(220px,1fr)_170px_220px_170px_auto]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor nota, barang, atau pemohon..." class="min-h-11 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-corpblue-500 focus:bg-white focus:outline-none">
                <select name="note_status" class="min-h-11 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                    <option value="all">Semua Status Nota</option>
                    <option value="Aktif" @selected(request('note_status') === 'Aktif')>Aktif</option>
                    <option value="Selesai" @selected(request('note_status') === 'Selesai')>Selesai</option>
                </select>
                <select name="request_status" class="min-h-11 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                    <option value="all">Semua Status Request</option>
                    @foreach(['Menunggu Review', 'Pending', 'Disetujui', 'Sebagian Diterima', 'Diterima Penuh', 'Ditutup Sebagian', 'Dibatalkan', 'Ditolak'] as $status)
                    <option value="{{ $status }}" @selected(request('request_status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                <select name="priority" class="min-h-11 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                    <option value="all">Semua Prioritas</option>
                    <option value="Biasa" @selected(request('priority') === 'Biasa')>Biasa</option>
                    <option value="Mendesak" @selected(request('priority') === 'Mendesak')>Mendesak</option>
                </select>
                <x-period-filter-button :period="$period" />
            </div>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3">
                <div class="flex gap-2">
                    <a href="{{ route('procurement-notes.print-period', request()->query()) }}" class="btn btn--secondary">Cetak</a>
                    <a href="{{ route('procurement-notes.excel-period', request()->query()) }}" class="btn btn--secondary">Excel</a>
                </div>
                @if(request()->filled('search') || $period || request('note_status', 'all') !== 'all' || request('request_status', 'all') !== 'all' || request('priority', 'all') !== 'all')
                <a href="{{ route('procurement-notes.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">Reset Filter</a>
                @endif
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Nomor Nota</th>
                            <th class="px-4 py-3">Tanggal</th>
                            @if($searchActive)
                            <th class="px-4 py-3">Barang Ditemukan<span class="block text-[9px] font-normal normal-case">Cocok dengan kata kunci</span></th>
                            @endif
                            <th class="px-4 py-3 text-center">Request</th>
                            <th class="px-4 py-3">Progres<span class="block text-[9px] font-normal normal-case">Menunggu / Proses / Selesai</span></th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($notes as $note)
                        @php
                            $counts = $note->statusCounts();
                            $completed = ($counts['Diterima Penuh'] ?? 0) + ($counts['Ditutup Sebagian'] ?? 0) + ($counts['Dibatalkan'] ?? 0) + ($counts['Ditolak'] ?? 0);
                        @endphp
                        <tr id="note-{{ $note->id }}" class="align-middle hover:bg-slate-50/60">
                            <td class="px-4 py-4"><a href="{{ route('procurement-notes.show', $note) }}" class="font-mono text-sm font-bold text-slate-900 hover:text-corpblue-700">{{ $note->number }}</a></td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $note->request_date->translatedFormat('d F Y') }}</td>
                            @if($searchActive)
                            @php
                                $matches = $matchingRequestsByNote[$note->id] ?? collect();
                                $numberMatched = str_contains(strtolower($note->number), strtolower($search));
                                $firstMatch = $matches->first();
                            @endphp
                            <td class="px-4 py-4">
                                @if($matches->isEmpty() && $numberMatched)
                                <span class="text-xs font-semibold text-corpblue-600">Nomor Nota cocok</span>
                                @else
                                <ul class="space-y-1.5">
                                    @foreach($matches->take(3) as $matched)
                                    <li class="text-xs">
                                        <a href="{{ route('procurement-notes.show', $note).'#request-'.$matched->id }}" class="font-semibold text-slate-900 hover:text-corpblue-700">{{ $matched->item?->name ?? $matched->item_name }}</a>
                                        <span class="mx-1 text-slate-300">·</span>
                                        <span class="text-slate-600">{{ $matched->quantity }} {{ $matched->unit }}</span>
                                        <span class="mx-1 text-slate-300">·</span>
                                        <span class="text-slate-600">{{ $matched->user?->name ?? '—' }}</span>
                                        <span class="mx-1 text-slate-300">·</span>
                                        <span class="text-slate-500">{{ $matched->status }}</span>
                                    </li>
                                    @endforeach
                                </ul>
                                @if($matches->count() > 3)
                                <p class="mt-1 text-[11px] text-slate-400">+{{ $matches->count() - 3 }} hasil lainnya</p>
                                @endif
                                @endif
                            </td>
                            @endif
                            <td class="px-4 py-4 text-center font-semibold text-slate-900">{{ $note->requests_count }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-center">
                                <span><b class="text-amber-600">{{ ($counts['Menunggu Review'] ?? 0) + ($counts['Pending'] ?? 0) }}</b> <span class="mx-1 text-slate-300">/</span><b class="text-blue-600">{{ ($counts['Disetujui'] ?? 0) + ($counts['Sebagian Diterima'] ?? 0) }}</b> <span class="mx-1 text-slate-300">/</span><b class="text-emerald-600">{{ $completed }}</b></span>
                            </td>
                            <td class="px-4 py-4"><x-status-badge domain="procurement" :status="$note->statusLabel()" dot /></td>
                            <td class="px-4 py-4 text-right"><a href="{{ route('procurement-notes.show', $note).($searchActive && $firstMatch ? '#request-'.$firstMatch->id : '') }}" class="text-xs font-semibold text-corpblue-600 hover:text-corpblue-800">Buka Nota &rarr;</a></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $searchActive ? 7 : 6 }}" class="px-4 py-12 text-center text-sm text-slate-500">Belum ada Nota yang sesuai dengan filter.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($notes->hasPages())
        <div>{{ $notes->links() }}</div>
        @endif
    </div>

    <x-slot:modals><x-period-filter-modal :period="$period" form-id="procurementFilters" /></x-slot:modals>
</x-layout>