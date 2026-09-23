<x-layout>
    <x-slot:title>{{ $procurementNote->number }} — THI2-WAREHOUSE</x-slot:title>
    <x-slot:headerTitle>Detail Nota</x-slot:headerTitle>

    @php
        $counts = $procurementNote->statusCounts();
        $waiting = ($counts['Menunggu Review'] ?? 0) + ($counts['Pending'] ?? 0);
        $process = ($counts['Disetujui'] ?? 0) + ($counts['Sebagian Diterima'] ?? 0);
        $terminal = ($counts['Diterima Penuh'] ?? 0) + ($counts['Ditutup Sebagian'] ?? 0) + ($counts['Dibatalkan'] ?? 0) + ($counts['Ditolak'] ?? 0);
    @endphp
    <div class="space-y-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <a href="{{ route('procurement-notes.index') }}" class="text-xs font-semibold text-corpblue-600 hover:text-corpblue-800">&larr; Riwayat Pengadaan</a>
            <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="font-mono text-xl font-bold text-slate-900">{{ $procurementNote->number }}</h1>
                    <x-status-badge domain="procurement" :status="$procurementNote->statusLabel()" dot />
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('procurement-notes.print', $procurementNote) }}" class="btn btn--secondary">Cetak Nota</a>
                    <a href="{{ route('procurement-notes.excel', $procurementNote) }}" class="btn btn--secondary">Excel</a>
                </div>
            </div>
            <p class="mt-2 text-sm text-slate-500">Rekap otomatis request tanggal {{ $procurementNote->request_date->translatedFormat('d F Y') }}</p>
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Total</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $procurementNote->requests->count() }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Menunggu</p>
                <p class="mt-1 text-2xl font-bold text-amber-600">{{ $waiting }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Proses</p>
                <p class="mt-1 text-2xl font-bold text-blue-600">{{ $process }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Terminal</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $terminal }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">Barang</th>
                            <th class="px-4 py-3">Pemohon</th>
                            <th class="px-4 py-3">Jumlah<span class="block text-[9px] font-normal normal-case">Diminta / Diterima / Sisa</span></th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Catatan</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($procurementNote->requests as $stockRequest)
                        @php
                            $closedQuantity = in_array($stockRequest->status, \App\Models\StockRequest::CLOSED_STATUSES, true) ? max(0, $stockRequest->quantity - $stockRequest->received_quantity) : null;
                            $activeRemaining = $stockRequest->canReceive() ? max(0, $stockRequest->quantity - $stockRequest->received_quantity) : null;
                        @endphp
                        <tr id="request-{{ $stockRequest->id }}" class="align-top hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-4 text-xs text-slate-500"><b class="block font-semibold text-slate-700">#{{ $stockRequest->id }}</b>{{ $stockRequest->created_at->translatedFormat('H:i') }}</td>
                            <td class="px-4 py-4"><b class="font-semibold text-slate-900">{{ $stockRequest->item?->name ?? $stockRequest->item_name ?? 'Barang' }}</b><span class="mt-1 block text-xs text-slate-500">{{ $stockRequest->priority }}</span></td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $stockRequest->user?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-xs">
                                <span class="block"><span class="text-slate-400">Diminta</span> <b class="text-slate-900">{{ $stockRequest->quantity }} {{ $stockRequest->unit }}</b></span>
                                <span class="block"><span class="text-slate-400">Diterima</span> <b class="text-emerald-700">{{ $stockRequest->received_quantity }} {{ $stockRequest->unit }}</b></span>
                                <span class="block"><span class="text-slate-400">Sisa</span> <b class="text-slate-900">{{ $activeRemaining ?? $closedQuantity ?? '—' }}</b></span>
                            </td>
                            <td class="px-4 py-4"><x-status-badge domain="request" :status="$stockRequest->status" /></td>
                            <td class="max-w-xs px-4 py-4 text-xs text-slate-500">{{ $stockRequest->close_note ?? $stockRequest->review_note ?? '—' }}</td>
                            <td class="px-4 py-4 text-right"><a href="{{ route('procurement-notes.requests.show', [$procurementNote, $stockRequest]) }}" class="action-link">Detail Riwayat</a>@if(auth()->user()->role === 'hr' && $stockRequest->canClose())<x-hr-request-close-button :request="$stockRequest" class="whitespace-nowrap" />@endif</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-slot:modals><x-hr-request-close-modal /></x-slot:modals>
</x-layout>