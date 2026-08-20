<x-layout>
    <x-slot:title>Detail Permintaan - MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Detail Permintaan untuk Review</x-slot:headerTitle>

    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <a href="/hr/approval" class="p-2.5 border border-slate-200 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-50 transition-all min-w-[44px] min-h-[44px] inline-flex items-center justify-center" title="Kembali ke Approval">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </a>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 break-words">{{ $request->item?->display_name ?? $request->item_name ?? 'Barang' }}</h2>
                        <p class="text-sm text-slate-500">
                            #NOTA-{{ $request->created_at->format('Ymd') }} • Permintaan #{{ $request->id }} • Diajukan {{ $request->created_at->translatedFormat('d M Y, H:i') }}
                        </p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $request->status === 'Pending' ? 'bg-amber-100 text-amber-800' : 'bg-blue-50 text-blue-600' }}">
                    {{ $request->status }}
                </span>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pemohon</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $request->user?->name ?? 'Gudang' }}</p>
                    @if($request->user?->email)
                    <p class="text-xs text-slate-500">{{ $request->user->email }}</p>
                    @endif
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Tanggal Permintaan</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $request->created_at->translatedFormat('l, d F Y') }}</p>
                    <p class="text-xs text-slate-500">{{ $request->created_at->format('H:i') }} WIB</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Jumlah</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $request->quantity }} <span class="text-slate-500">{{ $request->unit }}</span></p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Stok Saat Ini</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $request->item?->stock ?? '-' }} <span class="text-slate-500">{{ $request->unit }}</span></p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Lokasi Rak</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $request->item?->rack_location ?? '-' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Prioritas</p>
                    <p class="mt-1">
                        <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $request->priority === 'Mendesak' ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-600' }}">{{ $request->priority }}</span>
                    </p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4 md:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Alasan</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $request->reason ?? '-' }}</p>
                </div>
            </div>

            @if($request->attachment_path)
            @php($ext = strtolower(pathinfo($request->attachment_path, PATHINFO_EXTENSION)))
            @php($imageExts = ['jpg', 'jpeg', 'png'])
            @php($attachmentUrl = Storage::disk('public')->url($request->attachment_path))
            <div class="mt-4 rounded-lg border border-slate-200 p-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Lampiran / Foto</p>
                    <a href="{{ $attachmentUrl }}" target="_blank" class="inline-flex items-center space-x-1.5 px-3 py-2 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 text-xs font-bold hover:bg-blue-600 hover:text-white transition-all min-h-[44px]">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span>Buka / Unduh ({{ strtoupper($ext) }})</span>
                    </a>
                </div>
                <div class="mt-3">
                    @if(in_array($ext, $imageExts, true))
                    <a href="{{ $attachmentUrl }}" target="_blank" title="Klik untuk melihat lebih besar">
                        <img src="{{ $attachmentUrl }}" alt="Lampiran {{ $request->item?->name ?? $request->item_name ?? 'Permintaan' }}" class="max-h-96 w-full md:w-auto max-w-full rounded-lg border border-slate-200 object-contain bg-slate-50">
                    </a>
                    @elseif($ext === 'pdf')
                    <embed src="{{ $attachmentUrl }}" type="application/pdf" class="w-full h-96 rounded-lg border border-slate-200 bg-slate-50" />
                    @else
                    <p class="text-xs text-slate-500">Dokumen {{ strtoupper($ext) }} dapat dibuka atau diunduh lewat tombol di atas.</p>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-slate-900">Timeline Status</h3>
            <div class="mt-6 space-y-4">
                @forelse($request->requestHistories->sortBy('created_at') as $history)
                    <div class="flex gap-3 rounded-lg border border-slate-100 p-4">
                        <div class="mt-1 h-2.5 w-2.5 rounded-full bg-blue-600"></div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $history->status }}</p>
                            <p class="text-sm text-slate-600">{{ $history->note }}</p>
                            <p class="mt-1 text-xs text-slate-400">Oleh {{ $history->user?->name ?? 'Sistem' }} • {{ $history->created_at->translatedFormat('d M Y, H:i') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada riwayat status.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layout>
