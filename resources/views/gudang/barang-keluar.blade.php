<x-layout>
    <x-slot:title>Barang Keluar — MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Catat Barang Keluar</x-slot:headerTitle>

    <div class="space-y-4">

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold px-4 py-3 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 text-sm font-semibold px-4 py-3 rounded-lg">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            {{-- Form --}}
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-5 md:p-6">
                <form id="outForm" action="/gudang/barang-keluar" method="POST" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Barang</label>
                        <input type="hidden" name="item_id" id="item_id" value="">
                        <div class="relative">
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <input id="itemSearch" type="text" autocomplete="off" placeholder="Ketik untuk mencari barang..." oninput="filterItems()" onfocus="openItemList()" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all">
                                    <button type="button" id="itemClear" onclick="clearItemSelection()" style="display:none" class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all cursor-pointer">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                                <div class="relative" id="rackDropdownWrap">
                                    <button type="button" id="rackDropdownBtn" onclick="toggleRackDropdown()" class="flex items-center gap-1.5 px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100 hover:border-slate-300 transition-all cursor-pointer whitespace-nowrap min-w-[100px] justify-center">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        <span id="rackDropdownLabel">Semua Rak</span>
                                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div id="rackDropdownMenu" class="hidden absolute right-0 mt-1 w-40 bg-white border border-slate-200 rounded-lg shadow-xl z-40 overflow-hidden">
                                        <button type="button" onclick="selectRack('')" class="rack-option w-full text-left px-3 py-2.5 text-sm font-medium hover:bg-corpblue-50 hover:text-corpblue-700 transition-all flex items-center gap-2 bg-corpblue-50 text-corpblue-700" data-rack="">
                                            <span class="w-2 h-2 rounded-full bg-slate-400"></span> Semua Rak
                                        </button>
                                        @foreach(['A','B','C','D','E'] as $rack)
                                        <button type="button" onclick="selectRack('{{ $rack }}')" class="rack-option w-full text-left px-3 py-2.5 text-sm font-medium hover:bg-corpblue-50 hover:text-corpblue-700 transition-all flex items-center gap-2 text-slate-700" data-rack="{{ $rack }}">
                                            <span class="w-2 h-2 rounded-full bg-corpblue-400"></span> Rak {{ $rack }}
                                        </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div id="itemList" class="hidden absolute left-0 right-0 mt-1 max-h-56 overflow-y-auto bg-white border border-slate-200 rounded-lg shadow-xl z-30"></div>
                        </div>

                        <div id="itemInfoBox" class="hidden mt-3 bg-slate-50 border border-slate-200 rounded-lg p-3.5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p id="infoItemName" class="text-sm font-semibold text-slate-900"></p>
                                    <p id="infoItemDetail" class="text-xs text-slate-500 mt-0.5"></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-slate-500">Stok</p>
                                    <p id="infoItemStock" class="text-base font-bold text-slate-900"></p>
                                </div>
                            </div>
                            <div class="mt-2 pt-2 border-t border-slate-200 flex items-center gap-4 text-xs text-slate-500">
                                <span>Satuan: <strong id="infoItemUnit" class="text-slate-700"></strong></span>
                                <span>Rak: <strong id="infoItemRack" class="text-corpblue-600 bg-corpblue-50 px-1.5 py-0.5 rounded text-[11px] font-bold"></strong></span>
                                <span id="infoSubLocWrap" class="hidden">Sub: <strong id="infoItemSubLoc" class="text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded text-[11px] font-bold font-mono"></strong></span>
                                <span id="infoTagWrap" class="hidden">Tag: <strong id="infoItemTag" class="text-corpblue-700 bg-corpblue-50 px-1.5 py-0.5 rounded text-[11px] font-bold font-mono"></strong></span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Jumlah Keluar</label>
                            <input type="number" name="quantity" id="qtyInput" min="1" required placeholder="0" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all">
                            <p id="qtyHelper" class="text-[11px] text-slate-400 mt-1 hidden">Maksimal: <span id="qtyMax"></span></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Keperluan</label>
                            <select name="reason" required class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all">
                                <option value="">Pilih keperluan...</option>
                                <option value="Pemakaian produksi">Pemakaian produksi</option>
                                <option value="Pemakaian kantor">Pemakaian kantor</option>
                                <option value="Rusak / hilang">Rusak / hilang</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Catatan <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <textarea name="note" rows="2" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all placeholder:text-slate-400 resize-none" placeholder="Catatan tambahan..."></textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <a href="/gudang/dashboard" class="px-4 py-2.5 border border-slate-200 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50 transition-all">Batal</a>
                        <button type="submit" class="px-5 py-2.5 bg-corpblue-500 text-white font-semibold rounded-lg text-sm hover:bg-corpblue-600 transition-all min-h-[44px]">Catat Keluar</button>
                    </div>
                </form>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-4">
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900 mb-2">Pencatatan Barang Keluar</h3>
                    <p class="text-xs text-slate-500 mb-3">Catat setiap barang yang keluar dari gudang untuk keperluan produksi atau operasional.</p>
                    <div class="space-y-2 text-xs text-slate-500">
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 bg-corpblue-500 rounded-full shrink-0"></span>
                            <span>Pilih barang dari inventaris</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 bg-corpblue-500 rounded-full shrink-0"></span>
                            <span>Isi jumlah yang keluar</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 bg-corpblue-500 rounded-full shrink-0"></span>
                            <span>Pilih keperluan</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 bg-corpblue-500 rounded-full shrink-0"></span>
                            <span>Stok otomatis berkurang</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        const itemsData = @json($itemOptions);

        const searchInput = document.getElementById('itemSearch');
        const listBox = document.getElementById('itemList');
        const itemClearBtn = document.getElementById('itemClear');
        const itemIdInput = document.getElementById('item_id');
        const qtyInput = document.getElementById('qtyInput');
        const qtyHelper = document.getElementById('qtyHelper');
        const qtyMax = document.getElementById('qtyMax');
        const infoBox = document.getElementById('itemInfoBox');
        let activeIndex = -1;
        let activeButtons = [];
        let selectedItem = null;
        let selectedRack = '';

        function toggleRackDropdown() {
            document.getElementById('rackDropdownMenu').classList.toggle('hidden');
        }

        function selectRack(rack) {
            selectedRack = rack;
            document.getElementById('rackDropdownLabel').textContent = rack ? 'Rak ' + rack : 'Semua Rak';
            document.getElementById('rackDropdownMenu').classList.add('hidden');

            document.querySelectorAll('.rack-option').forEach((btn) => {
                const r = btn.getAttribute('data-rack');
                if (r === rack) {
                    btn.classList.add('bg-corpblue-50', 'text-corpblue-700');
                    btn.classList.remove('text-slate-700');
                } else {
                    btn.classList.remove('bg-corpblue-50', 'text-corpblue-700');
                    btn.classList.add('text-slate-700');
                }
            });

            filterItems();
            if (listBox && !listBox.classList.contains('hidden')) {
                buildItemList(searchInput.value);
            }
        }

        document.addEventListener('click', (e) => {
            const wrap = document.getElementById('rackDropdownWrap');
            const menu = document.getElementById('rackDropdownMenu');
            if (wrap && menu && !wrap.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });

        function setActiveIndex(index) {
            activeButtons.forEach(btn => btn.classList.remove('bg-corpblue-50', 'text-corpblue-700'));
            activeIndex = index;
            if (activeButtons[activeIndex]) {
                activeButtons[activeIndex].classList.add('bg-corpblue-50', 'text-corpblue-700');
                activeButtons[activeIndex].scrollIntoView({ block: 'nearest' });
            }
        }

        function buildItemList(query) {
            if (!listBox) return;
            listBox.innerHTML = '';
            activeButtons = [];
            activeIndex = -1;
            const q = (query || '').toLowerCase().trim();
            let hasMatch = false;

            itemsData.forEach(item => {
                if (item.stock <= 0) return;
                if (q && item.label.toLowerCase().indexOf(q) === -1) return;
                if (selectedRack && item.rack !== selectedRack) return;
                hasMatch = true;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.dataset.id = item.id;
                btn.className = 'w-full text-left px-3 py-2.5 text-sm text-slate-700 hover:bg-corpblue-50 hover:text-corpblue-700 font-medium transition-all flex items-center justify-between';
                const location = item.sub_location ? ' · Lok ' + item.sub_location : '';
                btn.innerHTML = '<span>' + item.label + '</span><span class="text-[11px] text-slate-400 font-normal">' + item.stock + ' ' + item.unit + location + '</span>';
                btn.addEventListener('click', () => selectItem(item));
                activeButtons.push(btn);
                listBox.appendChild(btn);
            });

            if (!hasMatch) {
                const empty = document.createElement('div');
                empty.className = 'px-3 py-3 text-xs text-slate-400 text-center';
                empty.textContent = selectedRack
                    ? (q ? 'Barang "' + q + '" tidak ditemukan di Rak ' + selectedRack + '.' : 'Belum ada barang di Rak ' + selectedRack + '.')
                    : 'Barang tidak ditemukan.';
                listBox.appendChild(empty);
            } else {
                setActiveIndex(0);
            }
        }

        function openItemList() {
            if (!listBox) return;
            if (itemIdInput.value) buildItemList('');
            else buildItemList(searchInput.value);
            listBox.classList.remove('hidden');
            if (searchInput.value) searchInput.select();
        }

        function filterItems() {
            buildItemList(searchInput.value);
            if (listBox) listBox.classList.remove('hidden');
            syncClearButton();
        }

        function selectItem(item) {
            itemIdInput.value = item.id;
            searchInput.value = item.label;
            selectedItem = item;
            if (listBox) listBox.classList.add('hidden');
            syncClearButton();

            document.getElementById('infoItemName').textContent = item.label;
            document.getElementById('infoItemDetail').textContent = 'Tersedia';
            document.getElementById('infoItemStock').textContent = item.stock;
            document.getElementById('infoItemStock').className = 'text-base font-bold ' + (item.stock > 0 ? 'text-slate-900' : 'text-red-500');
            document.getElementById('infoItemUnit').textContent = item.unit;
            document.getElementById('infoItemRack').textContent = item.rack || '—';
            var subWrap = document.getElementById('infoSubLocWrap');
            if (item.sub_location) {
                document.getElementById('infoItemSubLoc').textContent = item.sub_location;
                subWrap.classList.remove('hidden');
            } else {
                subWrap.classList.add('hidden');
            }
            var tagWrap = document.getElementById('infoTagWrap');
            if (item.location_code) {
                document.getElementById('infoItemTag').textContent = item.location_code;
                tagWrap.classList.remove('hidden');
            } else {
                tagWrap.classList.add('hidden');
            }
            infoBox.classList.remove('hidden');

            qtyInput.max = item.stock;
            qtyInput.value = '';
            qtyHelper.classList.remove('hidden');
            qtyMax.textContent = item.stock + ' ' + item.unit;
            qtyInput.focus();
        }

        function clearItemSelection() {
            itemIdInput.value = '';
            searchInput.value = '';
            selectedItem = null;
            infoBox.classList.add('hidden');
            qtyHelper.classList.add('hidden');
            syncClearButton();
            if (listBox) {
                buildItemList('');
                listBox.classList.remove('hidden');
            }
            searchInput.focus();
        }

        function syncClearButton() {
            if (itemIdInput.value || searchInput.value) {
                itemClearBtn.style.display = 'flex';
            } else {
                itemClearBtn.style.display = 'none';
            }
        }

        if (searchInput) {
            searchInput.addEventListener('keydown', e => {
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (listBox.classList.contains('hidden')) openItemList();
                    if (!activeButtons.length) return;
                    const step = e.key === 'ArrowDown' ? 1 : -1;
                    setActiveIndex(Math.min(Math.max(activeIndex + step, 0), activeButtons.length - 1));
                } else if (e.key === 'Enter') {
                    if (!listBox.classList.contains('hidden') && activeButtons[activeIndex]) {
                        e.preventDefault();
                        const id = parseInt(activeButtons[activeIndex].dataset.id, 10);
                        const item = itemsData.find(entry => entry.id === id);
                        if (item) selectItem(item);
                    }
                } else if (e.key === 'Escape') {
                    if (listBox) listBox.classList.add('hidden');
                }
            });
        }

        document.addEventListener('click', e => {
            if (!searchInput) return;
            const wrapper = searchInput.closest('.relative');
            if (wrapper && !wrapper.contains(e.target) && listBox) {
                listBox.classList.add('hidden');
            }
        });

        document.getElementById('outForm').addEventListener('submit', e => {
            if (!itemIdInput.value) {
                e.preventDefault();
                searchInput.focus();
                openItemList();
                alert('Silakan pilih barang dari inventaris.');
            }
        });
    </script>
</x-layout>
