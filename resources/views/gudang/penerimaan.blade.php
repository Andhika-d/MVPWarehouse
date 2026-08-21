<x-layout>
    <x-slot:title>Penerimaan Barang — MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Penerimaan Barang</x-slot:headerTitle>

    <div class="space-y-4">

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold px-4 py-3 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 text-sm font-semibold px-4 py-3 rounded-lg">{{ session('error') }}</div>
        @endif

        {{-- Filter --}}
        <form method="GET" action="/gudang/penerimaan" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center gap-3">
            <input type="month" name="month" value="{{ request('month') }}" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-corpblue-500">
            <button type="submit" class="bg-corpblue-500 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-corpblue-600 transition-all">Filter</button>
            @if(request()->has('month'))
                <a href="/gudang/penerimaan" class="text-xs font-medium text-slate-500 hover:text-slate-700">Reset</a>
            @endif
        </form>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900">Request Menunggu Penerimaan</h3>
                <p class="text-xs text-slate-500 mt-0.5">Daftar barang yang sudah disetujui HR dan menunggu diterima gudang.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-5">Barang</th>
                            <th class="py-3 px-5 text-center">Diminta</th>
                            <th class="py-3 px-5 text-center">Diterima</th>
                            <th class="py-3 px-5 text-center">Sisa</th>
                            <th class="py-3 px-5">Pemohon</th>
                            <th class="py-3 px-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($requests as $req)
                        <tr class="hover:bg-slate-50/50 transition-all">
                            <td class="py-3 px-5">
                                <p class="font-semibold text-slate-900">{{ $req->item?->name ?? $req->item_name ?? 'Barang' }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">Rak {{ $req->item?->rack_location ?? '—' }} &middot; {{ $req->unit }}</p>
                            </td>
                            <td class="py-3 px-5 text-center font-bold text-blue-600">{{ $req->quantity }}</td>
                            <td class="py-3 px-5 text-center font-semibold text-emerald-600">{{ $req->received_quantity }}</td>
                            <td class="py-3 px-5 text-center">
                                <span class="font-bold {{ $req->remainingQuantity() > 0 ? 'text-amber-600' : 'text-slate-400' }}">{{ $req->remainingQuantity() }}</span>
                            </td>
                            <td class="py-3 px-5 text-xs text-slate-500">{{ $req->user?->name ?? '—' }}</td>
                            <td class="py-3 px-5 text-right">
                                <button onclick="openReceiveModal({{ $req->id }}, '{{ addslashes($req->item?->name ?? $req->item_name ?? 'Barang') }}', {{ $req->quantity }}, {{ $req->received_quantity }}, {{ $req->remainingQuantity() }}, '{{ $req->unit }}')" class="px-3 py-1.5 bg-corpblue-500 text-white text-xs font-semibold rounded-lg hover:bg-corpblue-600 transition-all cursor-pointer">Terima</button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-slate-400 text-sm">Tidak ada request yang menunggu penerimaan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requests->hasPages())
            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
                {{ $requests->links() }}
            </div>
            @endif
        </div>

    </div>

    {{-- Receive Modal --}}
    <div id="receiveModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeReceiveModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-sm p-6">
            <h3 class="text-base font-bold text-slate-900 mb-4">Terima Barang</h3>
            <form id="receiveForm" method="POST" action="/gudang/penerimaan">
                @csrf
                <input type="hidden" name="stock_request_id" id="receive_request_id">

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-0.5">Barang</label>
                        <p id="receive_item_name" class="text-sm font-semibold text-slate-900"></p>
                    </div>

                    <div class="grid grid-cols-3 gap-3 text-xs">
                        <div><span class="text-slate-500">Diminta</span><p id="receive_qty" class="font-bold text-slate-900 mt-0.5"></p></div>
                        <div><span class="text-slate-500">Sudah Diterima</span><p id="receive_already" class="font-bold text-emerald-600 mt-0.5"></p></div>
                        <div><span class="text-slate-500">Sisa</span><p id="receive_remain" class="font-bold text-amber-600 mt-0.5"></p></div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-0.5">Jumlah Diterima Sekarang</label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="received_quantity" id="receive_input" min="1" required class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all">
                            <span id="receive_unit" class="text-xs font-semibold text-slate-500"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-0.5">Catatan <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input type="text" name="note" placeholder="Contoh: supplier hanya menyediakan sebagian" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all">
                    </div>
                </div>

                <div class="flex gap-3 mt-5">
                    <button type="button" onclick="closeReceiveModal()" class="flex-1 px-4 py-2.5 border border-slate-200 text-slate-600 text-sm font-semibold rounded-lg hover:bg-slate-50 transition-all">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-corpblue-500 text-white text-sm font-semibold rounded-lg hover:bg-corpblue-600 transition-all">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openReceiveModal(id, name, qty, received, remain, unit) {
            document.getElementById('receive_request_id').value = id;
            document.getElementById('receive_item_name').textContent = name;
            document.getElementById('receive_qty').textContent = qty + ' ' + unit;
            document.getElementById('receive_already').textContent = received + ' ' + unit;
            document.getElementById('receive_remain').textContent = remain + ' ' + unit;
            document.getElementById('receive_input').max = remain;
            document.getElementById('receive_input').value = remain;
            document.getElementById('receive_unit').textContent = unit;
            var modal = document.getElementById('receiveModal');
            modal.style.display = 'flex';
        }
        function closeReceiveModal() {
            document.getElementById('receiveModal').style.display = 'none';
        }
    </script>
</x-layout>
