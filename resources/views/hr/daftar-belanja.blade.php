<x-layout>
    <x-slot:title>Nota Pengadaan - MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Nota Pengadaan Barang</x-slot:headerTitle>

    <div class="space-y-6">
        <form method="GET" action="/hr/daftar-belanja" data-auto-filter class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center">
            <div>
                <label for="procurementDate" class="mb-1 block text-xs font-semibold text-slate-500">Tanggal</label>
                <input id="procurementDate" type="date" name="date" value="{{ request('date') }}" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-corpblue-500 focus:outline-none">
            </div>
            @if(request()->has('date'))
                <a href="/hr/daftar-belanja" class="text-xs font-medium text-slate-500 hover:text-slate-700">Reset</a>
            @endif
        </form>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50/60 px-5 py-4">
                <h2 class="text-sm font-bold text-slate-900">Antrean Request Disetujui</h2>
                <p class="mt-1 text-xs text-slate-500">Pilih satu atau beberapa request untuk membuat draft nota. Request yang dipilih tidak akan muncul di nota lain.</p>
            </div>

            @if($requests->isEmpty())
                <div class="p-10 text-center text-sm text-slate-500">Tidak ada request yang menunggu pembuatan nota.</div>
            @else
                <form method="POST" action="{{ route('hr.procurement-notes.store') }}" data-submit-once>
                    @csrf
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-slate-200 bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                                <tr><th class="px-5 py-3"><input type="checkbox" data-check-all aria-label="Pilih semua"></th><th class="px-5 py-3">Barang</th><th class="px-5 py-3">Jumlah</th><th class="px-5 py-3">Pemohon</th><th class="px-5 py-3">Tanggal Approval</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($requests as $stockRequest)
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="px-5 py-4"><input type="checkbox" name="request_ids[]" value="{{ $stockRequest->id }}" data-request-check @checked(in_array($stockRequest->id, old('request_ids', [])))></td>
                                        <td class="px-5 py-4"><span class="block font-semibold text-slate-900">{{ $stockRequest->item?->name ?? $stockRequest->item_name ?? 'Barang' }}</span>@if($stockRequest->review_note)<span class="mt-1 block text-xs text-blue-600">{{ $stockRequest->review_note }}</span>@endif</td>
                                        <td class="whitespace-nowrap px-5 py-4 font-semibold text-blue-700">{{ $stockRequest->quantity }} {{ $stockRequest->unit }}</td>
                                        <td class="px-5 py-4 text-slate-600">{{ $stockRequest->user?->name ?? '-' }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-slate-500">{{ ($stockRequest->approved_at ?? $stockRequest->created_at)->translatedFormat('d M Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="grid gap-3 border-t border-slate-200 bg-slate-50/50 p-5 sm:grid-cols-2">
                        <div><label class="mb-1 block text-xs font-semibold text-slate-600">Nama Driver (opsional)</label><input name="driver_name" value="{{ old('driver_name') }}" maxlength="255" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"></div>
                        <div><label class="mb-1 block text-xs font-semibold text-slate-600">Catatan Nota (opsional)</label><input name="notes" value="{{ old('notes') }}" maxlength="2000" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"></div>
                        <div class="sm:col-span-2"><button class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Buat Draft Nota</button></div>
                    </div>
                </form>
            @endif
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4"><h2 class="text-sm font-bold text-slate-900">Riwayat Nota Permanen</h2></div>
            @if($notes->isEmpty())
                <div class="p-10 text-center text-sm text-slate-500">Belum ada nota pengadaan.</div>
            @else
                <div class="overflow-x-auto"><table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3">Nomor</th><th class="px-5 py-3">Tanggal Nota</th><th class="px-5 py-3">Item</th><th class="px-5 py-3">Driver</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($notes as $note)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-4 font-mono text-xs font-bold text-slate-900">{{ $note->number }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ ($note->issued_at ?? $note->created_at)->translatedFormat('d M Y H:i') }}</td>
                                <td class="px-5 py-4">{{ $note->items_count }} item</td>
                                <td class="px-5 py-4 text-slate-600">{{ $note->driver_name ?: '-' }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $note->status }}</span></td>
                                <td class="px-5 py-4 text-right"><a href="{{ route('hr.procurement-notes.show', $note) }}" class="font-semibold text-blue-600 hover:text-blue-800">Buka</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
                @if($notes->hasPages())<div class="border-t border-slate-100 px-5 py-3">{{ $notes->links() }}</div>@endif
            @endif
        </section>
    </div>

    <x-slot:scripts><script>
        const checkAll = document.querySelector('[data-check-all]');
        checkAll?.addEventListener('change', () => document.querySelectorAll('[data-request-check]').forEach(input => input.checked = checkAll.checked));
    </script></x-slot:scripts>
</x-layout>
