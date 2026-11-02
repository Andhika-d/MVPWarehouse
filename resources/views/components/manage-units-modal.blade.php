<!-- Manage Units Modal -->
<div id="manageUnitsModal" class="hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/50 backdrop-blur-sm" role="dialog" aria-modal="true">
    <div class="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-2xl mx-4 overflow-hidden max-h-[90vh] flex flex-col" onclick="event.stopPropagation()">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-semibold text-slate-900">{{ __('Kelola Unit Konversi') }}</h3>
            <button onclick="closeModal('manageUnitsModal')" aria-label="{{ __('Tutup') }}" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        
        <!-- Item Info -->
        <div class="px-5 py-3 bg-slate-50 border-b border-slate-100">
            <p class="font-medium text-slate-900" id="units_item_name"></p>
            <p class="text-xs text-slate-500 mt-1">{{ __('Unit Dasar') }}: <span id="units_primary_unit" class="font-semibold"></span></p>
        </div>

        <!-- Units List -->
        <div class="flex-1 overflow-y-auto p-5">
            <div id="unitsList" class="space-y-3">
                <!-- Will be populated by JS -->
            </div>
            
            <!-- Add Unit Form -->
            <div class="mt-4 pt-4 border-t border-slate-200">
                <p class="text-sm font-semibold text-slate-700 mb-3">{{ __('Tambah Unit Baru') }}</p>
                <form id="addUnitForm" class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('Nama Unit') }}</label>
                            <input type="text" id="new_unit_name" required placeholder="Pack, Box, Kaleng..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('Konversi') }}</label>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-500">1</span>
                                <input type="text" id="new_unit_name_display" readonly class="w-16 px-2 py-1 bg-slate-50 border border-slate-200 rounded text-xs text-center" placeholder="Unit">
                                <span class="text-xs text-slate-500">=</span>
                                <input type="number" id="new_unit_conversion" required min="0.0001" step="0.0001" placeholder="200" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                                <span id="new_unit_primary_display" class="text-xs text-slate-600 font-medium"></span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('Konteks Penggunaan') }}</label>
                        <select id="new_unit_context" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                            <option value="purchase">{{ __('Pembelian (Request)') }}</option>
                            <option value="usage">{{ __('Pemakaian (Keluar)') }}</option>
                            <option value="both">{{ __('Keduanya') }}</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn--primary btn--sm w-full">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        {{ __('Tambah Unit') }}
                    </button>
                </form>
            </div>
        </div>

        <div class="px-5 py-4 border-t border-slate-100 flex justify-end">
            <button onclick="closeModal('manageUnitsModal')" class="btn btn--secondary">{{ __('Tutup') }}</button>
        </div>
    </div>
</div>

<script>
let currentItemId = null;
let currentItemData = null;

// Update display saat ketik unit name
document.getElementById('new_unit_name').addEventListener('input', function() {
    document.getElementById('new_unit_name_display').value = this.value;
});

async function openManageUnitsModal(itemId, itemName, primaryUnit) {
    currentItemId = itemId;
    document.getElementById('units_item_name').textContent = itemName;
    document.getElementById('units_primary_unit').textContent = primaryUnit;
    document.getElementById('new_unit_primary_display').textContent = primaryUnit;
    
    // Reset form
    document.getElementById('addUnitForm').reset();
    document.getElementById('new_unit_name_display').value = '';
    
    // Load units
    await loadItemUnits(itemId);
    
    openModal('manageUnitsModal');
}

async function loadItemUnits(itemId) {
    try {
        const response = await fetch(`/api/items/${itemId}/units`);
        const data = await response.json();
        currentItemData = data;
        
        const unitsList = document.getElementById('unitsList');
        
        if (data.units.length === 0) {
            unitsList.innerHTML = '<p class="text-sm text-slate-400 text-center py-4">{{ __("Belum ada unit alternatif") }}</p>';
            return;
        }
        
        unitsList.innerHTML = data.units.map(unit => `
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200">
                <div class="flex-1">
                    <p class="font-medium text-slate-900">${unit.name} ${unit.is_primary ? '<span class="text-xs text-emerald-600">(Unit Dasar)</span>' : ''}</p>
                    ${unit.conversion_text ? `<p class="text-xs text-slate-500 mt-1">${unit.conversion_text}</p>` : ''}
                </div>
                ${!unit.is_primary ? `
                <button onclick="deleteUnit(${itemId}, '${unit.name}')" class="text-red-600 hover:text-red-700 text-xs font-medium px-2 py-1">
                    {{ __('Hapus') }}
                </button>
                ` : ''}
            </div>
        `).join('');
        
    } catch (error) {
        console.error('Error loading units:', error);
        alert('{{ __("Gagal memuat data unit") }}');
    }
}

document.getElementById('addUnitForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const unitName = document.getElementById('new_unit_name').value.trim();
    const conversion = parseFloat(document.getElementById('new_unit_conversion').value);
    const context = document.getElementById('new_unit_context').value;
    
    if (!unitName || !conversion || conversion <= 0) {
        alert('{{ __("Mohon isi semua field dengan benar") }}');
        return;
    }
    
    try {
        const response = await fetch(`/admin/items/${currentItemId}/units`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                unit_name: unitName,
                conversion_to_primary: conversion,
                context: context
            })
        });
        
        if (response.ok) {
            await loadItemUnits(currentItemId);
            this.reset();
            document.getElementById('new_unit_name_display').value = '';
        } else {
            const error = await response.json();
            alert(error.message || '{{ __("Gagal menambah unit") }}');
        }
    } catch (error) {
        console.error('Error adding unit:', error);
        alert('{{ __("Gagal menambah unit") }}');
    }
});

async function deleteUnit(itemId, unitName) {
    if (!confirm(`{{ __("Hapus unit") }} ${unitName}?`)) return;
    
    try {
        const response = await fetch(`/admin/items/${itemId}/units/${encodeURIComponent(unitName)}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        if (response.ok) {
            await loadItemUnits(itemId);
        } else {
            alert('{{ __("Gagal menghapus unit") }}');
        }
    } catch (error) {
        console.error('Error deleting unit:', error);
        alert('{{ __("Gagal menghapus unit") }}');
    }
}
</script>
