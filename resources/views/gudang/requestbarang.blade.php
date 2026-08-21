<x-layout>
    <x-slot:title>Buat Permintaan — MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Buat Permintaan</x-slot:headerTitle>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        {{-- Form --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <form id="requestForm" action="/gudang/request-barang" method="POST" enctype="multipart/form-data" class="p-5 md:p-6 space-y-5">
                @csrf

                {{-- 1. Nama Barang --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Barang</label>
                    <input type="hidden" name="item_id" id="item_id" value="">
                    <input type="hidden" name="item_name" id="item_name_hidden" value="">

                    <div class="relative">
                        <input
                            id="itemSearch"
                            type="text"
                            autocomplete="off"
                            placeholder="Ketik untuk mencari barang..."
                            oninput="filterItems()"
                            onfocus="openItemList()"
                            class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all"
                        >
                        <button type="button" id="itemClear" onclick="clearItemSelection()" title="Bersihkan" style="display:none" class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all cursor-pointer">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                        <div id="itemList" class="hidden absolute left-0 right-0 mt-1 max-h-56 overflow-y-auto bg-white border border-slate-200 rounded-lg shadow-xl z-30"></div>
                    </div>

                    {{-- Item info box --}}
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
                        </div>
                    </div>
                </div>

                {{-- 2. Jumlah --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Jumlah</label>
                    <input type="number" name="quantity" min="1" required placeholder="0" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all">
                    <input type="hidden" name="unit" id="unit-field" value="">
                </div>

                {{-- 3. Prioritas --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Prioritas</label>
                    <div class="flex gap-3">
                        <label class="flex-1 flex items-center justify-center p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50 has-[:checked]:border-corpblue-500 has-[:checked]:bg-corpblue-50 transition-all">
                            <input type="radio" name="priority" value="Biasa" checked class="sr-only">
                            <span class="text-sm text-slate-700 font-medium">Biasa</span>
                        </label>
                        <label class="flex-1 flex items-center justify-center p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50 has-[:checked]:border-red-400 has-[:checked]:bg-red-50 transition-all">
                            <input type="radio" name="priority" value="Mendesak" class="sr-only">
                            <span class="text-sm text-slate-700 font-medium">Mendesak</span>
                        </label>
                    </div>
                </div>

                {{-- 4. Alasan --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Alasan <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea name="reason" rows="2" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all placeholder:text-slate-400 resize-none" placeholder="Alasan pengajuan barang..."></textarea>
                </div>

                {{-- 5. Lampiran --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Lampiran <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xlsx,.xls" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600 file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-corpblue-50 file:text-corpblue-600 hover:file:bg-corpblue-100 file:cursor-pointer">
                    <p class="mt-1 text-[11px] text-slate-400">PDF, JPG, PNG, DOC, DOCX, XLSX, XLS. Maks 2 MB.</p>
                </div>

                {{-- Footer --}}
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="/gudang/dashboard" class="px-4 py-2.5 border border-slate-200 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50 transition-all">Batal</a>
                    <button type="submit" class="px-5 py-2.5 bg-corpblue-500 text-white font-semibold rounded-lg text-sm hover:bg-corpblue-600 transition-all min-h-[44px]">Kirim Pengajuan</button>
                </div>
            </form>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-4">
            {{-- Alur --}}
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 mb-3">Alur Pengajuan</h3>
                <ol class="space-y-3 text-xs text-slate-500">
                    <li class="flex gap-3">
                        <span class="flex items-center justify-center w-5 h-5 bg-corpblue-50 text-corpblue-600 rounded-full text-[10px] font-bold shrink-0">1</span>
                        <span>Isi formulir dan kirim pengajuan</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex items-center justify-center w-5 h-5 bg-slate-100 text-slate-500 rounded-full text-[10px] font-bold shrink-0">2</span>
                        <span>HR meninjau dan memutuskan</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex items-center justify-center w-5 h-5 bg-slate-100 text-slate-500 rounded-full text-[10px] font-bold shrink-0">3</span>
                        <span>Barang siap diambil atau ditolak</span>
                    </li>
                </ol>
            </div>

            {{-- PIC --}}
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 mb-2">Hubungi GA</h3>
                <p class="text-xs text-slate-500 mb-3">Untuk pengajuan mendesak atau koreksi data.</p>
                <div class="bg-slate-50 p-3 rounded-lg">
                    <p class="text-xs font-semibold text-slate-900">Andhika Dwiky Fauzi</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">General Affair &middot; Ext: 104</p>
                </div>
            </div>
        </div>

    </div>
</x-layout>

<script>
    const itemsData = @json($itemOptions);

    const searchInput = document.getElementById('itemSearch');
    const listBox = document.getElementById('itemList');
    const itemClearBtn = document.getElementById('itemClear');
    const itemIdInput = document.getElementById('item_id');
    const itemNameHidden = document.getElementById('item_name_hidden');
    const unitField = document.getElementById('unit-field');
    const infoBox = document.getElementById('itemInfoBox');
    let activeIndex = -1;
    let activeButtons = [];

    function setActiveIndex(index) {
        activeButtons.forEach((btn) => btn.classList.remove('bg-corpblue-50', 'text-corpblue-700'));
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

        itemsData.forEach((item) => {
            if (q && item.label.toLowerCase().indexOf(q) === -1) return;
            hasMatch = true;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.dataset.id = item.id;
            btn.className = 'w-full text-left px-3 py-2.5 text-sm text-slate-700 hover:bg-corpblue-50 hover:text-corpblue-700 font-medium transition-all flex items-center justify-between';
            btn.innerHTML = '<span>' + item.label + '</span><span class="text-[11px] text-slate-400 font-normal">' + item.stock + ' ' + item.unit + '</span>';
            btn.addEventListener('click', () => selectItem(item));
            activeButtons.push(btn);
            listBox.appendChild(btn);
        });

        if (!hasMatch) {
            const empty = document.createElement('div');
            empty.className = 'px-3 py-3 text-xs text-slate-400 text-center';
            empty.textContent = 'Barang tidak ditemukan.';
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
        itemNameHidden.value = item.label;
        searchInput.value = item.label;
        if (listBox) listBox.classList.add('hidden');
        syncClearButton();
        unitField.value = item.unit || '';

        document.getElementById('infoItemName').textContent = item.label;
        document.getElementById('infoItemDetail').textContent = item.stock > 0 ? 'Tersedia' : 'Stok habis';
        document.getElementById('infoItemStock').textContent = item.stock;
        document.getElementById('infoItemStock').className = 'text-base font-bold ' + (item.stock > 0 ? 'text-slate-900' : 'text-red-500');
        document.getElementById('infoItemUnit').textContent = item.unit || '—';
        document.getElementById('infoItemRack').textContent = item.rack_location || '—';
        infoBox.classList.remove('hidden');
    }

    function clearItemSelection() {
        itemIdInput.value = '';
        itemNameHidden.value = '';
        searchInput.value = '';
        unitField.value = '';
        infoBox.classList.add('hidden');
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
        searchInput.addEventListener('keydown', (e) => {
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
                    const item = itemsData.find((entry) => entry.id === id);
                    if (item) selectItem(item);
                }
            } else if (e.key === 'Escape') {
                if (listBox) listBox.classList.add('hidden');
            }
        });
    }

    document.addEventListener('click', (e) => {
        if (!searchInput) return;
        const wrapper = searchInput.closest('.relative');
        if (wrapper && !wrapper.contains(e.target) && listBox) {
            listBox.classList.add('hidden');
        }
    });

    document.getElementById('requestForm').addEventListener('submit', (e) => {
        if (!itemIdInput.value) {
            e.preventDefault();
            searchInput.focus();
            openItemList();
            alert('Silakan pilih barang dari inventaris.');
        }
    });
</script>
