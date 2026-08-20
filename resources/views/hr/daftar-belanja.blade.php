<x-layout>
    <x-slot:title>Daftar Belanja Driver - MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Nota Belanja & Pengadaan Barang</x-slot:headerTitle>

    <div class="space-y-6">

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
        @endif

        <!-- ================= PANEL AKSI EKSPOR (NO-PRINT AREA) ================= -->
        <div class="no-print bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Aksi Distribusi Nota Belanja</h3>
                <p class="text-xs text-slate-500 mt-0.5">Kirim nota ini secara digital ke HP Driver atau cetak sebagai lembaran fisik serah terima.</p>
            </div>

            <div class="flex flex-col sm:flex-row flex-wrap gap-2 text-xs font-semibold">
                <button onclick="sendToWhatsApp()" class="inline-flex items-center px-4 py-2.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white border border-emerald-200 rounded-lg transition-all shadow-2xs cursor-pointer min-h-[44px]">
                    <span class="mr-1.5">💬</span> Kirim ke Driver (WA)
                </button>
                <button onclick="window.print()" class="inline-flex items-center px-4 py-2.5 bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white border border-blue-200 rounded-lg transition-all shadow-2xs cursor-pointer min-h-[44px]">
                    <span class="mr-1.5">📄</span> Cetak PDF / Kertas
                </button>
                <a href="/hr/daftar-belanja/export/excel" class="inline-flex items-center px-4 py-2.5 bg-slate-50 text-slate-700 hover:bg-slate-700 hover:text-white border border-slate-200 rounded-lg transition-all shadow-2xs cursor-pointer min-h-[44px]">
                    <span class="mr-1.5">📊</span> Unduh Excel
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
                            <th class="py-4 px-6">Jumlah Belanja</th>
                            <th class="py-4 px-6">Satuan</th>
                            <th class="py-4 px-6">Rencana Lokasi Rak Asal</th>
                            <th class="py-4 px-6 text-center no-print">Konfirmasi Item</th>
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
                            <td class="py-4 px-6 font-semibold text-slate-500">{{ $request->unit }}</td>
                            <td class="py-4 px-6 text-xs text-slate-600 font-semibold">{{ $request->item?->rack_location ?? '-' }}</td>
                            <td class="py-4 px-6 text-center no-print">
                                <form action="/hr/daftar-belanja/{{ $request->id }}/complete" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" title="Tandai item ini selesai dibelanjakan" class="px-2.5 py-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white border border-emerald-200 text-[10px] font-bold rounded-lg transition-all cursor-pointer min-h-[44px]">Selesai</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-4 px-6 text-center text-slate-500">Belum ada permintaan yang disetujui.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="no-print p-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/30 text-xs text-slate-500">
                <span>Menampilkan {{ $requests->count() }} item belanja aktif untuk Driver</span>
            </div>
        </div>

        <!-- ================= KONFIRMASI SELESAI BELANJA (BULK) ================= -->
        @if($requests->count() > 0)
        <div class="no-print flex items-center justify-end pt-2">
            <form action="/hr/daftar-belanja/complete" method="POST" onsubmit="return confirm('Apakah Driver sudah kembali membawa barang fisik dan menyerahkannya ke Gudang?');">
                @csrf
                <button type="submit" class="px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center space-x-2 cursor-pointer min-h-[44px]">
                    <span>&check; Konfirmasi Selesai Dibelanjakan</span>
                </button>
            </form>
        </div>
        @endif

    </div>

    <script>
        @php
            $waItems = $requests->map(fn ($r) => [
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
                alert('💬 Format teks WhatsApp berhasil disalin ke clipboard!\n\nBuka WA Driver dan lakukan paste (Ctrl+V).');
            }).catch(() => {
                alert('Gagal menyalin ke clipboard. Silakan salin manual.');
            });
        }
    </script>
</x-layout>
