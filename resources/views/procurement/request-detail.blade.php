<x-layout>
    <x-slot:title>Detail Riwayat — THI2-WAREHOUSE</x-slot:title>
    <x-slot:headerTitle>Detail Riwayat Request</x-slot:headerTitle>

    @php
        $itemName = $request->item?->name ?? $request->item_name ?? 'Barang';
        $sisa = max(0, $request->quantity - $request->received_quantity);
        $hrResponse = match ($request->status) {
            'Pending' => 'Ditunda',
            'Ditolak' => 'Ditolak',
            'Dibatalkan' => 'Dibatalkan',
            'Menunggu Review' => 'Belum Ditanggapi',
            default => 'Disetujui',
        };
    @endphp

    <div class="space-y-4">

        {{-- Back link --}}
        <a href="{{ route('procurement-notes.show', $procurementNote) . '#request-' . $request->id }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-700 transition-all">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Kembali ke {{ $procurementNote->number }}
        </a>

        {{-- Header card --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-lg font-bold text-slate-900">{{ $itemName }}</h2>
                    <x-status-badge domain="request" :status="$request->status" />
                </div>
                @if(auth()->user()->role === 'hr')
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    @if($request->isActionable())
                    <form method="POST" action="{{ url('/hr/requests/' . $request->id . '/approve') }}">
                        @csrf
                        <button type="submit" class="btn btn--success">Terima</button>
                    </form>
                    <button type="button" data-action="{{ url('/hr/requests/' . $request->id . '/reject') }}" data-name="{{ $itemName }}" onclick="openRejectModal(this)" class="btn btn--danger">Tolak</button>
                    <button type="button" data-action="{{ url('/hr/requests/' . $request->id . '/delay') }}" data-name="{{ $itemName }}" onclick="openDelayModal(this)" class="btn btn--warning">Tunda</button>
                    @elseif($request->canClose())
                    <x-hr-request-close-button :request="$request" class="whitespace-nowrap" />
                    @endif
                </div>
                @endif
            </div>
            <p class="text-sm text-slate-500 mt-1.5">{{ $procurementNote->number }} &middot; {{ $procurementNote->request_date->translatedFormat('d F Y') }} &middot; Permintaan #{{ $request->id }} &middot; {{ $request->created_at->translatedFormat('d M Y, H:i') }}</p>

            {{-- Info row --}}
            <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-xs text-slate-500">Diminta</p>
                    <p class="font-semibold text-slate-900 mt-0.5">{{ $request->quantity }} {{ $request->unit }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Diterima</p>
                    <p class="font-semibold text-slate-900 mt-0.5">{{ $request->received_quantity }} {{ $request->unit }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Sisa</p>
                    <p class="font-semibold text-slate-900 mt-0.5">{{ $sisa }} {{ $request->unit }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Pemohon</p>
                    <p class="font-semibold text-slate-900 mt-0.5">{{ $request->user?->name ?? 'Gudang' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Prioritas</p>
                    <p class="mt-0.5"><x-status-badge domain="priority" :status="$request->priority" /></p>
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
            </div>

            <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs text-slate-500">Reviewer</p>
                    <p class="font-semibold text-slate-900 mt-0.5">{{ $request->reviewedBy?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Tanggapan HR</p>
                    <p class="font-semibold text-slate-900 mt-0.5">{{ $hrResponse }}</p>
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
            @endif

            @if($request->review_note)
            <div class="mt-4 pt-4 border-t border-slate-100">
                <p class="text-xs text-slate-500">Catatan Review</p>
                <p class="text-sm text-slate-700 mt-0.5">{{ $request->review_note }}</p>
            </div>
            @endif

            @if($request->close_note)
            <div class="mt-4 pt-4 border-t border-slate-100">
                <p class="text-xs text-slate-500">Alasan Penutupan</p>
                <p class="text-sm text-slate-700 mt-0.5">{{ $request->close_note }}</p>
            </div>
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
            <h3 class="text-sm font-semibold text-slate-900 mb-4">Timeline Status</h3>
            <div class="space-y-3">
                @foreach($timeline as $event)
                @php
                    $eventLabel = match ($event['status']) {
                        'Pending' => 'Permintaan Ditunda',
                        'Disetujui' => 'Disetujui HR',
                        'Ditolak' => 'Ditolak HR',
                        'Sebagian Diterima' => 'Penerimaan Sebagian',
                        'Diterima Penuh' => 'Penerimaan Penuh',
                        'Ditutup Sebagian' => 'Sisa Request Ditutup',
                        'Dibatalkan' => 'Request Dibatalkan',
                        default => 'Permintaan Dibuat',
                    };
                    $eventDot = match ($event['status']) {
                        'Pending' => 'bg-amber-500',
                        'Sebagian Diterima' => 'bg-indigo-500',
                        'Diterima Penuh' => 'bg-emerald-500',
                        'Ditolak', 'Dibatalkan' => 'bg-red-500',
                        'Ditutup Sebagian' => 'bg-slate-500',
                        'Disetujui' => 'bg-blue-500',
                        default => 'bg-corpblue-500',
                    };
                @endphp
                <div class="flex gap-3">
                    <div class="flex flex-col items-center">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $eventDot }}"></span>
                        @if(!$loop->last)
                            <span class="w-px flex-1 bg-slate-200 my-1"></span>
                        @endif
                    </div>
                    <div class="pb-4">
                        <p class="text-sm font-medium text-slate-900">{{ $eventLabel }}</p>
                        @if($event['note'])
                            <p class="text-sm text-slate-600 mt-0.5 whitespace-pre-line">{{ $event['note'] }}</p>
                        @endif
                        <p class="mt-1 text-xs text-slate-500">{{ $event['user'] }} &middot; {{ \Illuminate\Support\Carbon::parse($event['time'])->translatedFormat('d M Y, H:i') }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    <x-slot:modals>
        <x-hr-request-close-modal />
        <x-hr-reject-modal />
        <x-hr-delay-modal />
    </x-slot:modals>
</x-layout>