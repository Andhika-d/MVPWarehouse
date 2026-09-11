<x-layout>
    <x-slot:title>{{ $procurementNote->number }} - MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Detail Nota Pengadaan</x-slot:headerTitle>

    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div><a href="/hr/daftar-belanja" class="text-xs font-semibold text-corpblue-600">Kembali ke daftar</a><h1 class="mt-1 font-mono text-xl font-bold text-slate-900">{{ $procurementNote->number }}</h1><p class="text-xs text-slate-500">Dibuat {{ $procurementNote->created_at->translatedFormat('d F Y, H:i') }} oleh {{ $procurementNote->creator?->name ?? '-' }}</p></div>
            <x-status-badge domain="procurement" :status="$procurementNote->status" class="self-start" />
        </div>
        @if($procurementNote->isDraft())
            <form method="POST" action="{{ route('hr.procurement-notes.update', $procurementNote) }}" data-submit-once class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
                @csrf @method('PUT')
                <div class="border-b border-amber-200 bg-amber-50 px-5 py-4"><h2 class="text-sm font-bold text-amber-900">Edit Draft</h2><p class="mt-1 text-xs text-amber-700">Snapshot item akan dikunci setelah nota diterbitkan.</p></div>
                <div class="grid gap-4 p-5 sm:grid-cols-2"><div><label class="mb-1 block text-xs font-semibold">Nama Driver</label><input name="driver_name" value="{{ old('driver_name', $procurementNote->driver_name) }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></div><div><label class="mb-1 block text-xs font-semibold">Catatan</label><input name="notes" value="{{ old('notes', $procurementNote->notes) }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></div></div>
                <div class="max-h-80 overflow-y-auto border-y border-slate-200"><table class="w-full text-sm"><tbody class="divide-y divide-slate-100">@foreach($availableRequests as $stockRequest)<tr><td class="px-5 py-3"><input type="checkbox" name="request_ids[]" value="{{ $stockRequest->id }}" @checked(in_array($stockRequest->id, old('request_ids', $procurementNote->items->pluck('stock_request_id')->all())))></td><td class="px-5 py-3 font-semibold">{{ $stockRequest->item?->name ?? $stockRequest->item_name ?? 'Barang' }}</td><td class="px-5 py-3">{{ $stockRequest->quantity }} {{ $stockRequest->unit }}</td><td class="px-5 py-3 text-slate-500">{{ $stockRequest->user?->name ?? '-' }}</td></tr>@endforeach</tbody></table></div>
                <div class="p-5"><button class="btn btn--secondary">Simpan Draft</button></div>
            </form>
            <form method="POST" action="{{ route('hr.procurement-notes.issue', $procurementNote) }}" data-submit-once class="mt-3">@csrf<button type="submit" class="btn btn--primary">Terbitkan & Kunci Nota</button></form>
        @elseif($procurementNote->issued_at)
            <div class="flex flex-wrap gap-2 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <a href="{{ route('hr.procurement-notes.print', $procurementNote) }}" onclick="return guardNoteAction(this)" class="btn btn--primary">Cetak</a>
                <a href="{{ route('hr.procurement-notes.excel', $procurementNote) }}" onclick="return guardNoteAction(this)" class="btn btn--secondary">Excel</a>
                <button type="button" onclick="sendNoteToWhatsApp(this)" class="btn btn--success">WhatsApp</button>
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="grid gap-4 border-b border-slate-200 bg-slate-50/60 p-5 text-sm sm:grid-cols-3"><div><span class="block text-xs text-slate-500">Tanggal Nota</span><b>{{ $procurementNote->issued_at?->translatedFormat('d F Y, H:i') ?? 'Belum diterbitkan' }}</b></div><div><span class="block text-xs text-slate-500">Driver</span><b>{{ $procurementNote->driver_name ?: '-' }}</b></div><div><span class="block text-xs text-slate-500">Terakhir Dicetak</span><b>{{ $procurementNote->last_printed_at?->translatedFormat('d F Y, H:i') ?? '-' }}</b></div>@if($procurementNote->notes)<div class="sm:col-span-3"><span class="block text-xs text-slate-500">Catatan</span>{{ $procurementNote->notes }}</div>@endif</div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase text-slate-500">
                        <tr><th class="px-5 py-3">No</th><th class="px-5 py-3">Barang</th><th class="px-5 py-3">Jumlah</th><th class="px-5 py-3">Diterima</th><th class="px-5 py-3">Pemohon</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($procurementNote->items as $item)
                        <tr>
                            <td class="px-5 py-4">{{ $loop->iteration }}</td>
                            <td class="px-5 py-4"><b>{{ $item->item_name }}</b>@if($item->review_note)<span class="block text-xs text-corpblue-600">{{ $item->review_note }}</span>@endif</td>
                            <td class="px-5 py-4">{{ $item->quantity }} {{ $item->unit }}</td>
                            <td class="px-5 py-4">{{ $item->received_quantity }} {{ $item->unit }}</td>
                            <td class="px-5 py-4">{{ $item->requester_name ?: '-' }}</td>
                            <td class="px-5 py-4"><x-status-badge domain="receipt" :status="$item->receiptStatusLabel()" /></td>
                            <td class="px-5 py-4 text-right">
                                @if($procurementNote->issued_at && $procurementNote->status !== 'Dibatalkan' && $item->stockRequest?->canClose())
                                <x-hr-request-close-button :request="$item->stockRequest" class="whitespace-nowrap" />
                                @else
                                <span class="text-xs text-slate-300">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if(!in_array($procurementNote->status, ['Selesai', 'Dibatalkan']) && $procurementNote->items->sum('received_quantity') === 0)
            <form method="POST" action="{{ route('hr.procurement-notes.cancel', $procurementNote) }}" data-submit-once class="rounded-xl border border-red-200 bg-red-50 p-5">
                @csrf
                <label class="mb-1 block text-xs font-semibold text-red-800">Batalkan Dokumen Nota</label>
                <p class="mb-3 text-xs text-red-700">Request di dalam nota akan dikembalikan ke antrean pengadaan, bukan diakhiri sebagai request yang dibatalkan.</p>
                <div class="flex flex-col gap-2 sm:flex-row"><input required name="reason" maxlength="1000" placeholder="Alasan pembatalan nota" class="min-h-11 flex-1 rounded-lg border border-red-200 bg-white px-3 py-2 text-sm"><button type="submit" class="btn btn--danger">Batalkan Nota</button></div>
            </form>
        @endif
        @if($procurementNote->status === 'Dibatalkan')<div class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800"><b>Alasan pembatalan:</b> {{ $procurementNote->cancellation_reason }}</div>@endif
    </div>

    <x-slot:modals><x-hr-request-close-modal /></x-slot:modals>

    <x-slot:scripts><script>
        function guardNoteAction(element) {
            if (element.dataset.busy === 'true') return false;
            element.dataset.busy = 'true';
            element.setAttribute('aria-disabled', 'true');
            element.classList.add('pointer-events-none', 'opacity-60');
            window.setTimeout(() => {
                element.dataset.busy = 'false';
                element.removeAttribute('aria-disabled');
                element.classList.remove('pointer-events-none', 'opacity-60');
            }, 1500);
            return true;
        }

        function sendNoteToWhatsApp(button) {
            if (!guardNoteAction(button)) return;
            const items = @json($procurementNote->items->map(fn ($item) => ['name' => $item->item_name, 'quantity' => $item->quantity, 'unit' => $item->unit])->values());
            let text = `*MVPWAREHOUSE - NOTA PENGADAAN*\nNomor: {{ $procurementNote->number }}\nTanggal Nota: {{ $procurementNote->issued_at?->translatedFormat('d F Y') }}\nDriver: {{ $procurementNote->driver_name ?: '-' }}\n\n`;
            items.forEach((item, index) => text += `${index + 1}. ${item.name} - *${item.quantity} ${item.unit}*\n`);
            text += '\n_Silakan beli sesuai nota dan serahkan ke Gudang saat tiba._';
            window.open('https://wa.me/?text=' + encodeURIComponent(text), 'mvpwarehouse-procurement-whatsapp');
        }
    </script></x-slot:scripts>
</x-layout>
