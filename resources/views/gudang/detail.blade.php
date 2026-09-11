<x-layout>
    <x-slot:title>Detail Permintaan — MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Detail Permintaan</x-slot:headerTitle>

    <div class="space-y-4">

        {{-- Back link --}}
        <a href="/gudang/history" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-700 transition-all">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Kembali ke History
        </a>

        {{-- Header card --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">{{ $request->item?->name ?? $request->item_name ?? 'Barang' }}</h2>
                    <p class="text-sm text-slate-500 mt-0.5">{{ $request->user?->name ?? 'Gudang' }} &middot; {{ $request->created_at->translatedFormat('d M Y, H:i') }}</p>
                </div>
                <x-status-badge domain="request" :status="$request->status" class="shrink-0" />
            </div>

            {{-- Info row --}}
            <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-xs text-slate-500">Jumlah</p>
                    <p class="font-semibold text-slate-900 mt-0.5">{{ $request->quantity }} {{ $request->unit }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Prioritas</p>
                    <p class="font-semibold text-slate-900 mt-0.5">{{ $request->priority }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Kode Tag</p>
                    <p class="font-semibold text-slate-900 mt-0.5 font-mono text-xs whitespace-nowrap">{{ $request->item?->storageLocation?->code ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Lokasi Rak</p>
                    <p class="font-semibold text-slate-900 mt-0.5">{{ $request->item?->storageLocation?->rack ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Sub Lokasi</p>
                    <p class="font-semibold text-slate-900 mt-0.5 font-mono text-xs">{{ $request->item?->storageLocation?->sub_location ?? '—' }}</p>
                </div>
                <div class>
                    <p class="text-xs text-slate-500">Catatan Review</p>
                    <p class="font-semibold text-slate-900 mt-0.5">{{ $request->review_note ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Diterima</p>
                    <p class="font-semibold text-slate-900 mt-0.5">{{ $request->received_quantity }} {{ $request->unit }}</p>
                </div>
            </div>

            @if($request->isClosed() && $request->closed_at)
            <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs text-slate-500">Ditutup oleh</p>
                    <p class="font-semibold text-slate-900 mt-0.5">{{ $request->closedBy?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Waktu Penutupan</p>
                    <p class="font-semibold text-slate-900 mt-0.5">{{ $request->closed_at->translatedFormat('d M Y, H:i') }}</p>
                </div>
            </div>
            @if($request->close_note)
            <div class="mt-4 pt-4 border-t border-slate-100">
                <p class="text-xs text-slate-500">Alasan Penutupan</p>
                <p class="text-sm text-slate-700 mt-0.5">{{ $request->close_note }}</p>
            </div>
            @endif
            @endif

            @if($request->reason)
            <div class="mt-4 pt-4 border-t border-slate-100">
                <p class="text-xs text-slate-500">Alasan</p>
                <p class="text-sm text-slate-700 mt-0.5">{{ $request->reason }}</p>
            </div>
            @endif

            @if($request->attachment_path)
            <div class="mt-4 pt-4 border-t border-slate-100">
                <a href="{{ Storage::disk('public')->url($request->attachment_path) }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm font-medium text-corpblue-500 hover:text-corpblue-700">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Lihat Lampiran
                </a>
            </div>
            @endif
        </div>

        {{-- Timeline --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">Timeline</h3>
            <div class="space-y-3">
                @foreach($request->requestHistories->sortBy('created_at') as $history)
                @php
                    $historyDot = match ($history->status) {
                        'Pending' => 'bg-amber-500',
                        'Sebagian Diterima' => 'bg-indigo-500',
                        'Diterima Penuh' => 'bg-emerald-500',
                        'Ditolak' => 'bg-red-500',
                        'Ditutup Sebagian', 'Dibatalkan' => 'bg-slate-500',
                        default => 'bg-corpblue-500',
                    };
                @endphp
                <div class="flex gap-3">
                    <div class="flex flex-col items-center">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $historyDot }}"></span>
                        @if(!$loop->last)
                            <span class="w-px flex-1 bg-slate-200 my-1"></span>
                        @endif
                    </div>
                    <div class="pb-4">
                        <p class="text-sm font-medium text-slate-900">{{ $history->status }}</p>
                        @if($history->note)
                            <p class="text-sm text-slate-600 mt-0.5">{{ $history->note }}</p>
                        @endif
                        <p class="mt-1 text-xs text-slate-500">{{ $history->user?->name ?? 'Sistem' }} &middot; {{ $history->created_at->translatedFormat('d M Y, H:i') }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</x-layout>
