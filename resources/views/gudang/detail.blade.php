<x-layout>
    <x-slot:title>Detail Riwayat Permintaan — MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Detail Timeline Permintaan</x-slot:headerTitle>

    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 break-words">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 break-words">{{ $request->item?->name ?? $request->item_name ?? 'Barang' }}</h2>
                    <p class="text-sm text-slate-500">Diajukan oleh {{ $request->user?->name ?? 'Gudang' }} • {{ $request->created_at->translatedFormat('d M Y, H:i') }}</p>
                </div>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $request->status === 'Disetujui' ? 'bg-emerald-50 text-emerald-700' : ($request->status === 'Ditolak' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                    {{ $request->status }}
                </span>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Jumlah</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $request->quantity }} {{ $request->unit }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Prioritas</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $request->priority }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Lokasi Rak</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $request->item?->rack_location ?? '-' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Review Note</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $request->review_note ?? 'Belum ada catatan review' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4 md:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Alasan</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $request->reason ?? '-' }}</p>
                </div>
                @if($request->attachment_path)
                <div class="rounded-lg border border-slate-200 p-4 md:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Lampiran</p>
                    <a href="{{ Storage::disk('public')->url($request->attachment_path) }}" target="_blank" class="mt-1 inline-flex text-sm font-semibold text-corpblue-500 hover:text-corpblue-700">
                        Lihat / Unduh lampiran
                    </a>
                </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-slate-900">Timeline Status</h3>
            <div class="mt-6 space-y-4">
                @foreach($request->requestHistories->sortBy('created_at') as $history)
                    <div class="flex gap-3 rounded-lg border border-slate-100 p-4">
                        <div class="mt-1 h-2.5 w-2.5 rounded-full {{ $history->status === 'Disetujui' ? 'bg-emerald-500' : ($history->status === 'Ditolak' ? 'bg-red-500' : 'bg-corpblue-500') }}"></div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $history->status }}</p>
                            <p class="text-sm text-slate-600">{{ $history->note }}</p>
                            <p class="mt-1 text-xs text-slate-400">Oleh {{ $history->user?->name ?? 'Sistem' }} • {{ $history->created_at->translatedFormat('d M Y, H:i') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layout>
