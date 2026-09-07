<x-layout>
    <x-slot:title>Pengajuan Lokasi — MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Ajukan Pemindahan Lokasi</x-slot:headerTitle>

    <div class="max-w-5xl mx-auto space-y-6">

        {{-- Info --}}
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-700 space-y-1">
            <p>Pilih barang, lalu cari <strong>slot tujuan</strong> (Kode Tag + Sub Lokasi) yang baru.</p>
            <p>Barang akan <strong>dipindahkan ke slot tujuan</strong>. Jika slot tujuan sudah terisi, Admin yang menentukan akan <strong>ditukar</strong> atau <strong>ditumpuk</strong>.</p>
            <p>Ketik minimal 2 karakter untuk mencari slot yang sudah terdaftar.</p>
        </div>

        {{-- Form --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6 md:p-8">
            <form id="locationChangeForm" method="POST" action="/gudang/location-change" data-confirm="Ajukan pengajuan pemindahan lokasi ini?" data-confirm-title="Pengajuan Lokasi" data-confirm-tone="info" data-confirm-button="Ajukan" class="space-y-5">
                @csrf

                <div class="grid gap-5 lg:grid-cols-[1fr_auto_1fr] lg:items-stretch">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Barang</label>
                    <input type="hidden" name="item_id" id="itemSelect" value="">
                    <div id="itemPicker" class="relative">
                        <input type="text" id="itemSearch" autocomplete="off" placeholder="Cari nama barang, kode tag, atau lokasi..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                        <div id="itemResults" class="hidden absolute z-20 mt-1 w-full max-h-64 overflow-y-auto bg-white border border-slate-200 rounded-lg shadow-lg"></div>
                    </div>
                    <div id="itemSelected" class="hidden mt-3 bg-white border border-slate-200 rounded-lg p-3 text-xs text-slate-600">
                        <p id="itemSelectedName" class="font-semibold text-slate-900"></p>
                        <p id="itemSelectedMeta" class="mt-0.5 text-slate-500"></p>
                    </div>
                    <div id="itemOptions" class="hidden">
                        @foreach($items as $item)
                        <div data-id="{{ $item->id }}"
                            data-code="{{ $item->storageLocation?->code ?? '' }}"
                            data-rack="{{ $item->storageLocation?->rack ?? '' }}"
                            data-sub-location="{{ $item->storageLocation?->sub_location ?? '' }}"
                            data-stock="{{ $item->stock }}"
                            data-unit="{{ $item->unit }}" data-name="{{ $item->name }}" data-size="{{ $item->size ?? '' }}"></div>
                        @endforeach
                    </div>
                    <div id="itemDetail" class="hidden mt-3 bg-white border border-slate-200 rounded-lg p-3 text-xs text-slate-500 space-y-0.5">
                        <p>Kode Tag: <span id="itemFromCode" class="font-mono font-semibold text-slate-700"></span> <span id="itemFromRack" class="text-slate-500 text-[11px]"></span></p>
                        <p>Sub Lokasi saat ini: <span id="itemFromSub" class="font-mono font-semibold text-indigo-700"></span></p>
                        <p>Stok: <span id="itemStock" class="font-semibold text-slate-700"></span></p>
                    </div>
                </div>

                <div class="hidden lg:flex items-center justify-center text-corpblue-500" aria-hidden="true">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6l6 6-6 6"/></svg>
                </div>

                <div class="rounded-xl border border-corpblue-200 bg-corpblue-50/60 p-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Slot Tujuan</label>
                    <input type="hidden" name="target_location_id" id="targetLocationId" value="">
                    <div id="targetSlotWrap" class="relative">
                        <input type="text" id="targetSlotSearch" autocomplete="off" placeholder="Ketik Kode Tag / Sub Lokasi tujuan, contoh: 151, a.1.5.1"
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                        <div id="slotResults" class="hidden absolute z-10 mt-1 w-full max-h-64 overflow-y-auto bg-white border border-slate-200 rounded-lg shadow-lg"></div>
                    </div>
                    <div id="slotSelected" class="hidden mt-3 bg-white border border-corpblue-200 rounded-lg p-3 text-xs text-slate-600 space-y-0.5">
                        <p>Kode Tag: <span id="slotSelectedCode" class="font-mono font-semibold text-corpblue-700"></span> <span id="slotSelectedRack" class="text-slate-500 text-[11px]"></span></p>
                        <p>Sub Lokasi: <span id="slotSelectedSub" class="font-mono font-semibold text-indigo-700"></span></p>
                        <p>Status: <span id="slotSelectedStatus" class="font-semibold text-slate-700"></span><span id="slotSelectedItems" class="text-slate-500"></span></p>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Wajib memilih salah satu slot hasil pencarian.</p>
                </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
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
            var itemSelect = document.getElementById('itemSelect');
            var itemSearch = document.getElementById('itemSearch');
            var itemResults = document.getElementById('itemResults');
            var itemPicker = document.getElementById('itemPicker');
            var itemSelected = document.getElementById('itemSelected');
            var itemOptions = Array.from(document.querySelectorAll('#itemOptions > div'));
            var activeItemIndex = -1;

            function renderItemResults(query) {
                itemResults.innerHTML = '';
                var needle = query.trim().toLowerCase();
                var matches = itemOptions.filter(function (opt) {
                    return [opt.dataset.name, opt.dataset.size, opt.dataset.code, opt.dataset.rack, opt.dataset.subLocation].join(' ').toLowerCase().includes(needle);
                });
                if (!matches.length) {
                    itemResults.innerHTML = '<div class="px-3 py-3 text-xs text-slate-400">Tidak ada barang yang cocok.</div>';
                    return;
                }
                matches.slice(0, 50).forEach(function (opt, index) {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.dataset.index = index;
                    button.className = 'item-result w-full text-left px-3 py-2.5 border-b border-slate-100 last:border-0 hover:bg-corpblue-50 cursor-pointer';
                    var name = opt.dataset.name + (opt.dataset.size ? ' (' + opt.dataset.size + ')' : '');
                    var location = opt.dataset.code || 'Tanpa lokasi';
                    if (opt.dataset.rack) location += ' · Rak ' + opt.dataset.rack;
                    if (opt.dataset.subLocation) location += ' · ' + opt.dataset.subLocation;
                    button.innerHTML = '<span class="block text-sm font-semibold text-slate-800">' + name + '</span><span class="mt-0.5 block text-[11px] text-slate-400">' + location + ' · Stok ' + opt.dataset.stock + ' ' + opt.dataset.unit + '</span>';
                    button.addEventListener('click', function () { selectItem(opt); });
                    itemResults.appendChild(button);
                });
            }

            function selectItem(opt) {
                itemSelect.value = opt.dataset.id;
                itemSearch.value = opt.dataset.name + (opt.dataset.size ? ' (' + opt.dataset.size + ')' : '');
                document.getElementById('itemSelectedName').textContent = itemSearch.value;
                document.getElementById('itemSelectedMeta').textContent = (opt.dataset.code || 'Tanpa lokasi') + (opt.dataset.subLocation ? ' · ' + opt.dataset.subLocation : '') + ' · Stok ' + opt.dataset.stock + ' ' + opt.dataset.unit;
                itemSelected.classList.remove('hidden');
                itemResults.classList.add('hidden');
                var detail = document.getElementById('itemDetail');
                document.getElementById('itemFromCode').textContent = opt.dataset.code || '—';
                document.getElementById('itemFromRack').textContent = opt.dataset.rack ? '(Rak ' + opt.dataset.rack + ')' : '';
                document.getElementById('itemFromSub').textContent = opt.dataset.subLocation || '—';
                document.getElementById('itemStock').textContent = opt.dataset.stock + ' ' + opt.dataset.unit;
                detail.classList.remove('hidden');
            }

            itemSearch.addEventListener('focus', function () { renderItemResults(this.value); itemResults.classList.remove('hidden'); });
            itemSearch.addEventListener('input', function () { itemSelect.value = ''; itemSelected.classList.add('hidden'); renderItemResults(this.value); itemResults.classList.remove('hidden'); });
            itemSearch.addEventListener('keydown', function (event) {
                var results = Array.from(itemResults.querySelectorAll('.item-result'));
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') { event.preventDefault(); activeItemIndex = Math.max(0, Math.min(results.length - 1, activeItemIndex + (event.key === 'ArrowDown' ? 1 : -1))); results.forEach((r, i) => r.classList.toggle('bg-corpblue-50', i === activeItemIndex)); }
                if (event.key === 'Enter' && results[activeItemIndex]) { event.preventDefault(); results[activeItemIndex].click(); }
                if (event.key === 'Escape') itemResults.classList.add('hidden');
            });
            document.addEventListener('click', function (event) { if (!itemPicker.contains(event.target)) itemResults.classList.add('hidden'); });

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

            document.getElementById('locationChangeForm').addEventListener('submit', function (e) {
                if (!targetId.value) {
                    e.preventDefault();
                    alert('Pilih slot tujuan dari hasil pencarian terlebih dahulu.');
                }
            });
        </script>
    </x-slot:scripts>
</x-layout>
