<x-layout>
    <x-slot:title>Pengajuan Lokasi — MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Ajukan Pemindahan Lokasi</x-slot:headerTitle>

    <div class="max-w-2xl mx-auto space-y-6">

        {{-- Info --}}
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-700 space-y-1">
            <p>Pilih barang, lalu cari <strong>slot tujuan</strong> (Kode Tag + Sub Lokasi) yang baru.</p>
            <p>Barang akan <strong>dipindahkan ke slot tujuan</strong>. Jika slot tujuan sudah terisi, Admin yang menentukan akan <strong>ditukar</strong> atau <strong>ditumpuk</strong>.</p>
            <p>Ketik minimal 2 karakter untuk mencari slot yang sudah terdaftar.</p>
        </div>

        {{-- Form --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <form method="POST" action="/gudang/location-change" data-confirm="Ajukan pengajuan pemindahan lokasi ini?" data-confirm-title="Pengajuan Lokasi" data-confirm-tone="info" data-confirm-button="Ajukan" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Barang</label>
                    <select name="item_id" id="itemSelect" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                        <option value="">Pilih barang</option>
                        @foreach($items as $item)
                        <option value="{{ $item->id }}"
                            data-code="{{ $item->storageLocation?->code ?? '' }}"
                            data-rack="{{ $item->storageLocation?->rack ?? '' }}"
                            data-sub-location="{{ $item->storageLocation?->sub_location ?? '' }}"
                            data-stock="{{ $item->stock }}"
                            data-unit="{{ $item->unit }}">
                            {{ $item->name }}{{ $item->size ? ' ('.$item->size.')' : '' }} — {{ $item->storageLocation?->code ?? 'Tanpa lokasi' }}
                        </option>
                        @endforeach
                    </select>
                    <div id="itemDetail" class="hidden mt-2 bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs text-slate-500 space-y-0.5">
                        <p>Kode Tag: <span id="itemFromCode" class="font-mono font-semibold text-slate-700"></span> <span id="itemFromRack" class="text-slate-500 text-[11px]"></span></p>
                        <p>Sub Lokasi saat ini: <span id="itemFromSub" class="font-mono font-semibold text-indigo-700"></span></p>
                        <p>Stok: <span id="itemStock" class="font-semibold text-slate-700"></span></p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Slot Tujuan</label>
                    <input type="hidden" name="target_location_id" id="targetLocationId" value="">
                    <div id="targetSlotWrap" class="relative">
                        <input type="text" id="targetSlotSearch" autocomplete="off" placeholder="Ketik Kode Tag / Sub Lokasi tujuan, contoh: 151, a.1.5.1"
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                        <div id="slotResults" class="hidden absolute z-10 mt-1 w-full max-h-64 overflow-y-auto bg-white border border-slate-200 rounded-lg shadow-lg"></div>
                    </div>
                    <div id="slotSelected" class="hidden mt-2 bg-corpblue-50 border border-corpblue-200 rounded-lg p-3 text-xs text-slate-600 space-y-0.5">
                        <p>Kode Tag: <span id="slotSelectedCode" class="font-mono font-semibold text-corpblue-700"></span> <span id="slotSelectedRack" class="text-slate-500 text-[11px]"></span></p>
                        <p>Sub Lokasi: <span id="slotSelectedSub" class="font-mono font-semibold text-indigo-700"></span></p>
                        <p>Status: <span id="slotSelectedStatus" class="font-semibold text-slate-700"></span><span id="slotSelectedItems" class="text-slate-500"></span></p>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Wajib memilih salah satu slot hasil pencarian.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Alasan Perpindahan</label>
                    <textarea name="reason" rows="3" required placeholder="Jelaskan alasan pemindahan barang..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none resize-none"></textarea>
                </div>

                @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-600">
                    @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                    @endforeach
                </div>
                @endif

                <button type="submit" class="w-full px-4 py-2.5 bg-corpblue-500 hover:bg-corpblue-600 text-white rounded-lg text-sm font-semibold transition-colors cursor-pointer">
                    Ajukan Pengajuan Lokasi
                </button>
            </form>
        </div>

        {{-- History --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h3 class="text-sm font-semibold text-slate-900">Riwayat Pengajuan Saya</h3>
                <div class="flex items-center gap-2">
                    <a href="/gudang/location-change/export/pdf{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="border border-slate-200 bg-white px-3 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">PDF</a>
                    <a href="/gudang/location-change/export/excel{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="border border-slate-200 bg-white px-3 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">Excel</a>
                </div>
            </div>
            @forelse($myChanges as $change)
            <div class="py-3 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-sm text-slate-900">{{ $change->item->name }}{{ $change->item->size ? ' ('.$change->item->size.')' : '' }}</span>
                        @if($change->isPending())
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">Menunggu</span>
                        @elseif($change->status === 'Disetujui')
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">Disetujui</span>
                        @else
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-50 text-red-700">Ditolak</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if($change->status === 'Disetujui' && $change->resolution_action === 'swap')
                        <span class="text-xs px-2 py-0.5 rounded-full bg-purple-50 text-purple-700">Tukar Tempat</span>
                        @elseif($change->status === 'Disetujui' && $change->resolution_action === 'stack')
                        <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">Tumpuk</span>
                        @endif
                        <span class="text-xs text-slate-400">{{ $change->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                @if($change->target_sub_location !== null)
                <div class="flex flex-wrap items-center gap-2 mt-1.5 text-xs text-slate-500">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-mono whitespace-nowrap">{{ $change->fromLocation->code }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 font-mono whitespace-nowrap">{{ $change->from_sub_location ?? '—' }}</span>
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-mono whitespace-nowrap">{{ $change->toLocation?->code ?? '—' }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-corpblue-50 text-corpblue-700 font-mono whitespace-nowrap">{{ $change->target_sub_location }}</span>
                </div>
                @else
                <div class="flex items-center gap-2 mt-1.5 text-xs text-slate-500">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-mono whitespace-nowrap">{{ $change->fromLocation->code }}</span>
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-corpblue-50 text-corpblue-700 font-mono whitespace-nowrap">{{ $change->toLocation?->code }}</span>
                    @if($change->toLocation?->items->where('id', '!=', $change->item_id)->isNotEmpty())
                    <span class="text-slate-400">&larr; slot terisi</span>
                    @endif
                </div>
                @endif
                @if($change->reason)
                <p class="text-xs text-slate-400 mt-1 italic">"{{ $change->reason }}"</p>
                @endif
                @if($change->admin_note)
                <p class="text-xs text-blue-600 mt-1">Catatan Admin: {{ $change->admin_note }}</p>
                @endif
            </div>
            @empty
            <p class="text-xs text-slate-400 text-center py-4">Belum ada pengajuan.</p>
            @endforelse

            @if($myChanges->hasPages())
            <div class="mt-3">{{ $myChanges->links() }}</div>
            @endif
        </div>

    </div>

    <x-slot:scripts>
        <script>
            document.getElementById('itemSelect').addEventListener('change', function () {
                var opt = this.options[this.selectedIndex];
                var detail = document.getElementById('itemDetail');
                if (this.value) {
                    document.getElementById('itemFromCode').textContent = opt.dataset.code || '—';
                    document.getElementById('itemFromRack').textContent = opt.dataset.rack ? '(Rak ' + opt.dataset.rack + ')' : '';
                    document.getElementById('itemFromSub').textContent = opt.dataset.subLocation || '—';
                    document.getElementById('itemStock').textContent = opt.dataset.stock + ' ' + opt.dataset.unit;
                    detail.classList.remove('hidden');
                } else {
                    detail.classList.add('hidden');
                }
            });

            var searchInput = document.getElementById('targetSlotSearch');
            var targetId = document.getElementById('targetLocationId');
            var resultsBox = document.getElementById('slotResults');
            var selectedBox = document.getElementById('slotSelected');
            var debounceTimer;

            function hideResults() {
                resultsBox.classList.add('hidden');
            }

            searchInput.addEventListener('input', function () {
                targetId.value = '';
                selectedBox.classList.add('hidden');
                var q = this.value.trim();
                clearTimeout(debounceTimer);
                if (q.length < 2) {
                    hideResults();
                    return;
                }
                debounceTimer = setTimeout(function () {
                    fetch('/gudang/location-change/search?q=' + encodeURIComponent(q), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        resultsBox.innerHTML = '';
                        if (data.length === 0) {
                            var empty = document.createElement('div');
                            empty.className = 'px-3 py-2 text-xs text-slate-400';
                            empty.textContent = 'Tidak ada slot yang cocok.';
                            resultsBox.appendChild(empty);
                        } else {
                            data.forEach(function (slot) {
                                var el = document.createElement('button');
                                el.type = 'button';
                                el.className = 'w-full text-left px-3 py-2 text-sm text-slate-700 hover:bg-corpblue-50 hover:text-corpblue-700 cursor-pointer';
                                var line = document.createElement('div');
                                line.className = 'flex items-center justify-between gap-2';
                                var left = document.createElement('span');
                                left.className = 'font-mono font-semibold';
                                left.textContent = slot.code + ' · ' + slot.sub_location;
                                var right = document.createElement('span');
                                right.className = 'text-[11px] text-slate-400';
                                right.textContent = 'Rak ' + (slot.rack || '—') + ' · ' + (slot.status === 'Terisi' ? 'terisi ' + slot.items_count + ' ' + (slot.items_count > 1 ? 'barang' : 'barang') : 'kosong');
                                line.appendChild(left);
                                line.appendChild(right);
                                el.appendChild(line);
                                el.addEventListener('click', function () {
                                    targetId.value = slot.id;
                                    searchInput.value = slot.code + ' · ' + slot.sub_location;
                                    document.getElementById('slotSelectedCode').textContent = slot.code;
                                    document.getElementById('slotSelectedRack').textContent = slot.rack ? '(Rak ' + slot.rack + ')' : '';
                                    document.getElementById('slotSelectedSub').textContent = slot.sub_location;
                                    document.getElementById('slotSelectedStatus').textContent = slot.status === 'Terisi' ? 'Terisi' : 'Kosong';
                                    document.getElementById('slotSelectedItems').textContent = slot.status === 'Terisi' ? ' (' + slot.items_count + ' barang)' : '';
                                    selectedBox.classList.remove('hidden');
                                    hideResults();
                                });
                                resultsBox.appendChild(el);
                            });
                        }
                        resultsBox.classList.remove('hidden');
                    });
                }, 250);
            });

            document.addEventListener('click', function (e) {
                if (!document.getElementById('targetSlotWrap').contains(e.target)) {
                    hideResults();
                }
            });

            document.querySelector('form').addEventListener('submit', function (e) {
                if (!targetId.value) {
                    e.preventDefault();
                    alert('Pilih slot tujuan dari hasil pencarian terlebih dahulu.');
                }
            });
        </script>
    </x-slot:scripts>
</x-layout>