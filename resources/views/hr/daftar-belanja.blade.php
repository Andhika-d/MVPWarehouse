<x-layout>
    <x-slot:title>Daftar Belanja Driver - MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Nota Belanja & Pengadaan Barang</x-slot:headerTitle>

    <div class="space-y-6">

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
        @endif

        {{-- Filter --}}
        <form method="GET" action="/hr/daftar-belanja" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center gap-3">
            <input type="month" name="month" value="{{ request('month') }}" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-corpblue-500">
            <button type="submit" class="bg-corpblue-500 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-corpblue-600 transition-all">Filter</button>
            @if(request()->has('month'))
                <a href="/hr/daftar-belanja" class="text-xs font-medium text-slate-500 hover:text-slate-700">Reset</a>
            @endif
        </form>

        <!-- ================= PANEL AKSI EKSPOR (NO-PRINT AREA) ================= -->
        <div class="no-print bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Aksi Distribusi Nota Belanja</h3>
                <p class="text-xs text-slate-500 mt-0.5">Kirim nota ini secara digital ke HP Driver atau cetak sebagai lembaran fisik serah terima.</p>
            </div>

            <div class="flex flex-col sm:flex-row flex-wrap gap-2 text-xs font-semibold">
                <button onclick="sendToWhatsApp()" class="inline-flex items-center px-4 py-2.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white border border-emerald-200 rounded-lg transition-all shadow-2xs cursor-pointer min-h-[44px]">
                    <span class="mr-1.5">Kirim ke Driver (WA)</span>
                </button>
                <button onclick="window.print()" class="inline-flex items-center px-4 py-2.5 bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white border border-blue-200 rounded-lg transition-all shadow-2xs cursor-pointer min-h-[44px]">
                    <span class="mr-1.5">Cetak PDF / Kertas</span>
                </button>
                <a href="/hr/daftar-belanja/export/excel{{ request()->has('month') ? '?month=' . request('month') : '' }}" class="inline-flex items-center px-4 py-2.5 bg-slate-50 text-slate-700 hover:bg-slate-700 hover:text-white border border-slate-200 rounded-lg transition-all shadow-2xs cursor-pointer min-h-[44px]">
                    <span class="mr-1.5">Unduh Excel</span>
                </a>
            </div>
        </div>

        <!-- ================= NOTA REKAPAN UTAMA (PRINTABLE AREA) ================= -->
        <div class="print-card bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            <div class="p-5 md:p-6 bg-slate-50/50 border-b border-slate-200 flex flex-col sm:flex-row justify-between items-start gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900 uppercase tracking-wide">Nota Pengadaan Barang Kantor</h2>
                    <p class="text-xs text-slate-500 mt-1">Tanggal Cetak: <span class="font-medium text-slate-700">{{ now()->translatedFormat('d F Y, H:i') }}</span></p>
                </div>
                <div class="text-right text-xs">
                    <p class="font-bold text-slate-900">MVP<span class="text-blue-600">Warehouse</span></p>
                    <p class="text-slate-400 mt-0.5">Sistem Pengadaan Barang</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                            <th class="py-4 px-6 w-12 text-center">No</th>
                            <th class="py-4 px-6">Nama Barang / Logistik</th>
                            <th class="py-4 px-6">Jumlah Disetujui</th>
                            <th class="py-4 px-6">Diterima</th>
                            <th class="py-4 px-6">Satuan</th>
                            <th class="py-4 px-6">Rak</th>
                            <th class="py-4 px-6 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                        @forelse($requests as $index => $request)
                        <tr class="hover:bg-slate-50/30 transition-colors">
                            <td class="py-4 px-6 text-center text-slate-400">{{ $index + 1 }}</td>
                            <td class="py-4 px-6">
                                <span class="font-bold text-slate-900 block text-sm">{{ $request->item?->name ?? $request->item_name ?? 'Barang' }}</span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">Stok Saat Ini Gudang: {{ $request->item?->stock ?? '-' }} {{ $request->item?->unit ?? $request->unit }}</span>
                                @if($request->review_note)
                                    <span class="text-[10px] text-blue-600 block mt-1">Catatan HR: {{ $request->review_note }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-base font-bold text-blue-600">{{ $request->quantity }}</td>
                            <td class="py-4 px-6 text-sm font-semibold text-slate-700">{{ $request->received_quantity }}</td>
                            <td class="py-4 px-6 font-semibold text-slate-500">{{ $request->unit }}</td>
                            <td class="py-4 px-6 text-xs text-slate-600 font-semibold">{{ $request->item?->rack_location ?? '-' }}</td>
                            <td class="py-4 px-6 text-center">
                                @if($request->status === 'Disetujui')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 text-blue-700 rounded-full text-[11px] font-semibold">
                                        <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span> Menunggu Penerimaan
                                    </span>
                                @elseif($request->status === 'Sebagian Diterima')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 text-amber-700 rounded-full text-[11px] font-semibold">
                                        <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span> {{ $request->received_quantity }}/{{ $request->quantity }} {{ $request->unit }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-full text-[11px] font-semibold">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> {{ $request->status }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-4 px-6 text-center text-slate-500">Belum ada permintaan yang disetujui.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($requests->hasPages())
            <div class="no-print px-5 py-3 border-t border-slate-100 bg-slate-50/50">
                {{ $requests->links() }}
            </div>
            @endif

            <div class="no-print p-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/30 text-xs text-slate-500">
                <span>Menampilkan {{ $requests->firstItem() ?? 0 }} - {{ $requests->lastItem() ?? 0 }} dari {{ $requests->total() }} item</span>
            </div>
        </div>


    </div>

    <script>
        @php
            $waItems = $requests->getCollection()->map(fn ($r) => [
                'name' => $r->item?->name ?? $r->item_name ?? 'Barang',
                'quantity' => $r->quantity,
                'unit' => $r->unit,
            ])->values();
        @endphp

        function sendToWhatsApp() {
            const items = @json($waItems);

            let text = `*MVPWAREHOUSE - NOTA BELANJA DRIVER*\nTanggal: {{ now()->translatedFormat('d F Y') }}\n\n`;

            items.forEach((item, i) => {
                text += `${i + 1}. ${item.name} - *${item.quantity} ${item.unit}*\n`;
            });

            text += '\n_Silakan beli sesuai jumlah di atas dan serahkan ke Gudang saat tiba._';

            navigator.clipboard.writeText(text).then(() => {
                alert('Format teks WhatsApp berhasil disalin ke clipboard!\n\nBuka WA Driver dan lakukan paste (Ctrl+V).');
            }).catch(() => {
                alert('Gagal menyalin ke clipboard. Silakan salin manual.');
            });
        }
    </script>
</x-layout>
