<x-layout>
    <x-slot:title>Buat Permintaan — MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Formulir Request Barang</x-slot:headerTitle>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8 items-start">

        <!-- KOLOM KIRI (2/3): FORMULIR -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="bg-slate-50 p-4 border-b border-slate-100 flex items-start space-x-3 text-xs text-slate-600">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-corpblue-500 shrink-0 mt-0.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div>
                    <span class="font-semibold text-slate-900 block mb-0.5">Informasi Sistem:</span>
                    <p>Sistem otomatis mencatat Pemohon sebagai <strong class="text-corpblue-500">{{ auth()->user()->name }}</strong> dan menyematkan waktu saat dikirim.</p>
                </div>
            </div>

            <form id="requestForm" action="/gudang/request-barang" method="POST" enctype="multipart/form-data" class="p-4 md:p-6 space-y-5 md:space-y-6">
                @csrf

                <!-- 1. PILIHAN NAMA BARANG -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Barang / Logistik</label>
                    <input type="hidden" name="item_id" id="item_id" value="">

                    @if($items->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-3 sm:gap-4 mb-3">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="radio" name="input_mode" value="search" checked onchange="switchMode('search')" class="w-4 h-4 text-corpblue-500">
                            <span class="text-sm font-medium text-slate-700">Pilih dari Inventaris</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="radio" name="input_mode" value="manual" onchange="switchMode('manual')" class="w-4 h-4 text-corpblue-500">
                            <span class="text-sm font-medium text-slate-700">Ketik Manual</span>
                        </label>
                    </div>
                    @endif

                    <!-- MODE SEARCH -->
                    <div id="mode-search" class="{{ $items->isEmpty() ? 'hidden' : '' }}">
                        <div class="relative">
                            <input
                                id="itemSearch"
                                type="text"
                                autocomplete="off"
                                placeholder="Ketik untuk mencari barang..."
                                oninput="filterItems()"
                                onfocus="openItemList()"
                                class="w-full pl-3 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all text-slate-800 font-medium"
                            >
                            <button type="button" id="itemClear" onclick="clearItemSelection()" title="Bersihkan" style="display:none" class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all cursor-pointer">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                            <div id="itemList" class="hidden absolute left-0 right-0 mt-1 max-h-64 overflow-y-auto bg-white border border-slate-200 rounded-lg shadow-xl z-30 divide-y divide-slate-50"></div>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500">Pilih barang dari stok yang tersedia. ↑/↓ + Enter untuk navigasi keyboard.</p>
                    </div>

                    <!-- MODE MANUAL -->
                    <div id="mode-manual" class="{{ $items->isEmpty() ? '' : 'hidden' }}">
                        <input
                            type="text"
                            name="item_name"
                            id="item_name_manual"
                            placeholder="Contoh: Spidol Whiteboard, Tinta Stempel, dll."
                            class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all text-slate-800 font-medium"
                        >
                        <p class="mt-1 text-[11px] text-slate-500">Ketik nama barang sesuai yang Anda butuhkan.</p>
                    </div>
                </div>

                <!-- 2. JUMLAH & SATUAN -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Jumlah Permintaan</label>
                        <input type="number" name="quantity" min="1" required class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all text-slate-900" placeholder="Masukkan angka kuantitas...">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Satuan Ukuran</label>
                        <input id="unit-field" type="text" name="unit" value="" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all text-slate-900" placeholder="Contoh: Pcs, Box, Rim, Roll...">
                        <p class="mt-1 text-[11px] text-slate-500" id="unit-hint">Isi satuan barang (otomatis jika dari inventaris).</p>
                    </div>
                </div>

                <!-- 3. PRIORITAS -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2.5">Tingkat Prioritas Kebutuhan</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-center justify-between p-3.5 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 hover:border-slate-300 transition-all">
                            <div class="flex items-center space-x-2.5">
                                <span class="w-2.5 h-2.5 bg-slate-400 rounded-full"></span>
                                <span class="text-sm font-semibold text-slate-700">Kebutuhan Biasa (Rutin)</span>
                            </div>
                            <input type="radio" name="priority" value="Biasa" checked class="w-4 h-4 text-corpblue-500 focus:ring-corpblue-500 cursor-pointer">
                        </label>
                        <label class="flex items-center justify-between p-3.5 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 hover:border-slate-300 transition-all">
                            <div class="flex items-center space-x-2.5">
                                <span class="w-2.5 h-2.5 bg-red-500 rounded-full animate-pulse"></span>
                                <span class="text-sm font-semibold text-slate-700">Mendesak (Urgent)</span>
                            </div>
                            <input type="radio" name="priority" value="Mendesak" class="w-4 h-4 text-corpblue-500 focus:ring-corpblue-500 cursor-pointer">
                        </label>
                    </div>
                </div>

                <!-- 4. ALASAN -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Alasan Permintaan Barang</label>
                    <textarea name="reason" rows="3" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-corpblue-500 focus:bg-white transition-all text-slate-900 placeholder:text-slate-400" placeholder="Tuliskan alasan mengapa barang ini segera dibutuhkan..."></textarea>
                </div>

                <!-- 5. LAMPIRAN -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Lampiran Dokumen / Bukti Kebutuhan</label>
                    <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xlsx,.xls" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700">
                    <p class="mt-1 text-[11px] text-slate-500">Format: PDF, JPG, PNG, DOC, DOCX, XLSX, XLS. Maks 2 MB.</p>
                </div>

                <!-- FOOTER -->
                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3">
                    <a href="/gudang/dashboard" class="px-5 py-3 border border-slate-200 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50 transition-all cursor-pointer text-center">Batalkan</a>
                    <button type="submit" class="px-6 py-3 bg-corpblue-500 text-white font-semibold rounded-lg text-sm shadow-sm hover:bg-corpblue-600 transition-all cursor-pointer min-h-[44px]">Kirim Pengajuan</button>
                </div>
            </form>
        </div>

        <!-- KOLOM KANAN (1/3 Layar): WIDGET GABUNGAN PANDUAN ALUR & KONTAK PIC -->
        <div class="space-y-6">
            
            <!-- SUB-WIDGET 1: PANDUAN PENGONTROLAN ALUR -->
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center space-x-2">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-slate-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <span>Alur & Prosedur Approval</span>
                </h3>
                
                <!-- Timeline Alur Kerja Sederhana -->
                <ol class="relative border-l border-slate-200 pl-4 space-y-4 text-xs">
                    <li class="relative">
                        <span class="absolute -left-[22px] mt-0.5 w-3 h-3 bg-corpblue-500 rounded-full ring-4 ring-white"></span>
                        <h4 class="font-bold text-slate-900 mb-0.5">Tahap 1: Pengajuan</h4>
                        <p class="text-slate-500 leading-relaxed">Staf gudang mengisi formulir dengan data yang valid dan alasan yang jelas.</p>
                    </li>
                    <li class="relative">
                        <span class="absolute -left-[22px] mt-0.5 w-3 h-3 bg-slate-200 rounded-full ring-4 ring-white"></span>
                        <h4 class="font-semibold text-slate-700 mb-0.5">Tahap 2: Verifikasi HRD</h4>
                        <p class="text-slate-500 leading-relaxed">HRD meninjau dan memutuskan persetujuan atau penolakan.</p>
                    </li>
                    <li class="relative">
                        <span class="absolute -left-[22px] mt-0.5 w-3 h-3 bg-slate-200 rounded-full ring-4 ring-white"></span>
                        <h4 class="font-semibold text-slate-700 mb-0.5">Tahap 3: Keputusan Akhir</h4>
                        <p class="text-slate-500 leading-relaxed">Jika disetujui barang siap diambil. Jika ditolak, alasan akan tertera di menu History.</p>
                    </li>
                </ol>
            </div> 

            <!-- SUB-WIDGET 2: KONTAK PENANGGUNG JAWAB --> 
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm"> 
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center space-x-2"> 
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-slate-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg> 
                    <span>Hubungi Penanggung Jawab (GA)</span> 
                </h3> 
                <p class="text-xs text-slate-500 leading-relaxed mb-4">Jika pengajuan Anda bersifat <span class="font-semibold text-red-600">Mendesak</span> atau ada kesalahan input data, silakan hubungi tim General Affair berikut:</p> 
                <div class="bg-slate-50 p-3 rounded-lg flex items-center justify-between"> 
                    <div> 
                        <p class="text-xs font-bold text-slate-900">Andhika Dwiky Fauzi</p> 
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">General Affair</p> 
                    </div> 
                    <span class="text-[11px] bg-white border border-slate-200 text-slate-700 font-medium px-2.5 py-1 rounded-md shadow-xs"> Ext: 104 </span> 
                </div> 
            </div> 

        </div> 
    </div> 
</x-layout>

<script>
    const itemsData = @json($itemOptions);
    let currentMode = '{{ $items->isEmpty() ? "manual" : "search" }}';

    function switchMode(mode) {
        currentMode = mode;
        const searchDiv = document.getElementById('mode-search');
        const manualDiv = document.getElementById('mode-manual');
        const unitField = document.getElementById('unit-field');
        const unitHint = document.getElementById('unit-hint');

        if (mode === 'search') {
            searchDiv.classList.remove('hidden');
            manualDiv.classList.add('hidden');
            document.getElementById('item_name_manual').value = '';
            document.getElementById('item_id').value = '';
            unitField.value = '';
            unitField.readOnly = true;
            unitHint.textContent = 'Otomatis dari master barang.';
        } else {
            searchDiv.classList.add('hidden');
            manualDiv.classList.remove('hidden');
            document.getElementById('item_id').value = '';
            if (document.getElementById('itemSearch')) document.getElementById('itemSearch').value = '';
            unitField.readOnly = false;
            unitField.value = '';
            unitHint.textContent = 'Isi satuan barang secara manual.';
            document.getElementById('item_name_manual').focus();
        }
    }

    // --- SEARCH MODE functions ---
    const searchInput = document.getElementById('itemSearch');
    const listBox = document.getElementById('itemList');
    const itemClearBtn = document.getElementById('itemClear');
    const itemIdInput = document.getElementById('item_id');
    const unitField = document.getElementById('unit-field');

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
            btn.className = 'w-full text-left px-4 py-2.5 text-sm text-slate-700 hover:bg-corpblue-50 hover:text-corpblue-700 font-medium transition-all';
            btn.textContent = item.label;
            btn.addEventListener('click', () => selectItem(item));
            activeButtons.push(btn);
            listBox.appendChild(btn);
        });

        if (!hasMatch) {
            const empty = document.createElement('div');
            empty.className = 'px-4 py-3 text-xs text-slate-400';
            empty.textContent = 'Barang tidak ditemukan. Ketik nama barang di mode Manual.';
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
        if (listBox) listBox.classList.add('hidden');
        syncClearButton();
        unitField.value = item.unit || '';
        unitField.readOnly = true;
    }

    function clearItemSelection() {
        itemIdInput.value = '';
        searchInput.value = '';
        unitField.value = '';
        unitField.readOnly = true;
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

    // Submit handler
    document.getElementById('requestForm').addEventListener('submit', (e) => {
        if (currentMode === 'search' && !itemIdInput.value) {
            e.preventDefault();
            searchInput.focus();
            openItemList();
            alert('Silakan pilih barang dari inventaris, atau pilih mode "Ketik Manual".');
        }
    });

    // Init
    switchMode(currentMode);
</script> 
