<x-layout :title="'Master Barang — MVPWarehouse'" :headerTitle="'Master Barang'">
    <div class="space-y-6">

        {{-- Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-corpblue-50 flex items-center justify-center shrink-0">
                    <svg class="text-corpblue-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-900">{{ $totalItems }}</p>
                    <p class="text-xs text-slate-500">Total Barang</p>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                    <svg class="text-emerald-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-900">{{ $totalStock }}</p>
                    <p class="text-xs text-slate-500">Total Stok</p>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                    <svg class="text-amber-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-900">{{ $lowStockCount }}</p>
                    <p class="text-xs text-slate-500">Stok Menipis</p>
                </div>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form method="GET" action="{{ route('admin.items.index') }}" data-auto-filter class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, size, atau sub lokasi..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                </div>
                <select name="rack" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    <option value="all">Semua Rak</option>
                    @foreach($racks as $rack)
                    <option value="{{ $rack }}" {{ request('rack') === $rack ? 'selected' : '' }}>Rak {{ $rack }}</option>
                    @endforeach
                </select>
                <select name="status" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    <option value="all">Semua Status</option>
                    <option value="Terisi" {{ request('status') === 'Terisi' ? 'selected' : '' }}>Terisi</option>
                    <option value="Kosong" {{ request('status') === 'Kosong' ? 'selected' : '' }}>Kosong</option>
                </select>
                <select name="unit" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    <option value="all">Semua Satuan</option>
                    @foreach(\App\Models\Item::UNITS as $unit)
                    <option value="{{ $unit }}" {{ request('unit') === $unit ? 'selected' : '' }}>{{ $unit }}</option>
                    @endforeach
                </select>
                <label class="flex items-center gap-2 px-3 py-2 border border-slate-200 rounded-lg text-sm cursor-pointer">
                    <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }} class="rounded text-corpblue-500 focus:ring-corpblue-500">
                    <span class="text-slate-600">Stok &le; 5</span>
                </label>
                @if(request()->hasAny(['search', 'rack', 'status', 'unit', 'low_stock']))
                <a href="{{ route('admin.items.index') }}" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-sm font-medium">Reset</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Kode Tag</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Sub Lokasi</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Nama Barang</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Size</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Satuan</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Stok</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Rak</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($locations as $loc)
                        @forelse($loc->items as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-corpblue-50 text-corpblue-700 text-xs font-mono font-semibold whitespace-nowrap">{{ $loc->code }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($loc->sub_location)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-xs font-mono font-semibold">{{ $loc->sub_location }}</span>
                                @else
                                <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ $item->name }}
                                @if($loc->items->count() > 1)
                                <span class="text-[11px] font-semibold text-indigo-600 ml-1">(+{{ $loc->items->count() - 1 }} ditumpuk)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->size ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->unit }}</td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-semibold {{ $item->stock <= 5 ? 'text-amber-600' : 'text-slate-900' }}">{{ $item->stock }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $loc->rack }}</td>
                            <td class="px-4 py-3 text-center">
                                <x-status-badge domain="location" status="Terisi" />
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button onclick="openEditModal({{ $item->id }}, '{{ addslashes($item->name) }}', '{{ addslashes($item->size ?? '') }}', '{{ $item->unit }}')" class="action-link action-link--primary">Edit</button>
                                    <button onclick="openAdjustModal({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->stock }}, '{{ $item->unit }}')" class="action-link action-link--warning">Adjust</button>
                                    <form method="POST" action="{{ route('admin.items.destroy', $item) }}" data-confirm="Hapus item {{ $item->name }}? Tindakan ini tidak dapat dibatalkan." data-confirm-title="Hapus Item" data-confirm-tone="danger" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="action-link action-link--danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="hover:bg-slate-50 transition-colors bg-slate-50/50">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 text-xs font-mono font-semibold whitespace-nowrap">{{ $loc->code }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($loc->sub_location)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-xs font-mono font-semibold">{{ $loc->sub_location }}</span>
                                @else
                                <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-400 italic">—</td>
                            <td class="px-4 py-3 text-slate-400">—</td>
                            <td class="px-4 py-3 text-slate-400">—</td>
                            <td class="px-4 py-3 text-right"><span class="text-slate-400">0</span></td>
                            <td class="px-4 py-3 text-slate-600">{{ $loc->rack }}</td>
                            <td class="px-4 py-3 text-center">
                                <x-status-badge domain="location" status="Kosong" />
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-xs text-slate-400">—</span>
                            </td>
                        </tr>
                        @endforelse
                        @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="text-slate-300" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    <p class="text-sm text-slate-400">Belum ada data lokasi</p>
                                    <a href="{{ route('admin.import.index') }}" class="text-xs text-corpblue-600 hover:text-corpblue-700 font-semibold">Import dari Excel</a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($locations->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $locations->links() }}
            </div>
            @endif
        </div>

    </div>

    {{-- Edit Modal --}}
    <x-slot:modals>
        {{-- Edit Modal --}}
        <div id="editItemModal" class="hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/50 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="editItemModalTitle">
            <div class="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-md mx-4 overflow-hidden" onclick="event.stopPropagation()">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 id="editItemModalTitle" class="text-base font-semibold text-slate-900">Edit Item</h3>
                    <button onclick="closeModal('editItemModal')" aria-label="Tutup" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="editItemForm" method="POST" class="p-5 space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Barang</label>
                        <input type="text" name="name" id="edit_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Size</label>
                            <input type="text" name="size" id="edit_size" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Satuan</label>
                            <select name="unit" id="edit_unit" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                                @foreach(\App\Models\Item::UNITS as $u)
                                <option value="{{ $u }}">{{ $u }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="closeModal('editItemModal')" class="btn btn--secondary">Batal</button>
                        <button type="submit" class="btn btn--primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Adjust Stock Modal --}}
        <div id="adjustStockModal" class="hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/50 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="adjustStockModalTitle">
            <div class="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-md mx-4 overflow-hidden" onclick="event.stopPropagation()">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 id="adjustStockModalTitle" class="text-base font-semibold text-slate-900">Penyesuaian Stok</h3>
                    <button onclick="closeModal('adjustStockModal')" aria-label="Tutup" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="adjustStockForm" method="POST" class="p-5 space-y-4">
                    @csrf
                    <div class="bg-slate-50 rounded-lg p-3 text-sm">
                        <p class="font-medium text-slate-900" id="adjust_item_name"></p>
                        <p class="text-slate-500 text-xs mt-1">Stok saat ini: <span id="adjust_current_stock" class="font-semibold"></span> <span id="adjust_item_unit"></span></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Stok Baru</label>
                        <input type="number" name="new_stock" id="adjust_new_stock" min="0" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Alasan <span class="text-red-500">*</span></label>
                        <input type="text" name="reason" id="adjust_reason" required maxlength="255" placeholder="Contoh: Koreksi stok fisik, Hasil audit, dll." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="closeModal('adjustStockModal')" class="btn btn--secondary">Batal</button>
                        <button type="submit" class="btn btn--warning">Simpan Penyesuaian</button>
                    </div>
                </form>
            </div>
        </div>
    </x-slot:modals>

    <x-slot:scripts>
        <script>
            function openEditModal(id, name, size, unit) {
                document.getElementById('editItemForm').action = '/admin/items/' + id;
                document.getElementById('edit_name').value = name;
                document.getElementById('edit_size').value = size;
                document.getElementById('edit_unit').value = unit;
                openModal('editItemModal');
            }
            function openAdjustModal(id, name, stock, unit) {
                document.getElementById('adjustStockForm').action = '/admin/items/' + id + '/adjust-stock';
                document.getElementById('adjust_item_name').textContent = name;
                document.getElementById('adjust_current_stock').textContent = stock;
                document.getElementById('adjust_item_unit').textContent = unit;
                document.getElementById('adjust_new_stock').value = stock;
                document.getElementById('adjust_reason').value = '';
                openModal('adjustStockModal');
            }
        </script>
    </x-slot:scripts>
</x-layout>
