<x-layout :title="__('Barang Lainnya') . ' — THI2-WAREHOUSE'" :headerTitle="__('Barang Lainnya')">
    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Permintaan di luar rak A–E') }}</h2>
                <p class="text-xs text-slate-500 mt-1">{{ __('Barang yang diajukan gudang secara manual. Daftarkan ke rak bila akan dipakai lagi, atau biarkan di kategori Lainnya.') }}</p>
            </div>
            <a href="{{ route('admin.items.index') }}" class="btn btn--secondary whitespace-nowrap">{{ __('Master Barang') }}</a>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">#</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Nama Barang') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Ukuran') }}</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">{{ __('Diminta') }}</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">{{ __('Diterima') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Pemohon') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Rak') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">{{ __('Aksi') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($otherRequests as $request)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 text-slate-400 font-mono text-xs">#{{ $request->id }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ $request->item_name }}
                                <span class="ml-1 text-[10px] font-semibold uppercase tracking-wide text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded">{{ __('Lainnya') }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $request->item_size ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ $request->quantity }} {{ $request->unit }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $request->received_quantity }} {{ $request->unit }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $request->user?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <x-status-badge domain="request" :status="$request->status" />
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if($request->item?->storageLocation)
                                <span class="font-mono font-semibold text-corpblue-700">{{ $request->item->storageLocation->code }}</span>
                                <span class="text-slate-400 block">{{ $request->item->storageLocation->sub_location }}</span>
                                @else
                                <span class="text-slate-400">{{ __('Belum ada') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($request->item_id)
                                <span class="text-xs font-semibold text-emerald-600">{{ __('Sudah terdaftar') }}</span>
                                @else
                                <button type="button" onclick="openPromoteModal(this, {{ $request->id }})" data-name="{{ $request->displayName() }}" class="action-link action-link--primary">{{ __('Daftarkan ke Rak') }}</button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-sm text-slate-400">{{ __('Belum ada barang Lainnya.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($otherRequests->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">{{ $otherRequests->links() }}</div>
            @endif
        </div>
    </div>

    <x-slot:modals>
        <div id="promoteOtherModal" class="hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/50 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="promoteOtherModalTitle">
            <div class="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-lg mx-4 overflow-hidden" onclick="event.stopPropagation()">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 id="promoteOtherModalTitle" class="text-base font-semibold text-slate-900">{{ __('Daftarkan Barang ke Rak') }}</h3>
                    <button onclick="closeModal('promoteOtherModal')" aria-label="{{ __('Tutup') }}" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="promoteOtherForm" method="POST" class="p-5 space-y-4">
                    @csrf
                    <div class="bg-slate-50 rounded-lg p-3 text-sm">
                        <p class="font-medium text-slate-900" id="promote_item_name"></p>
                        <p class="text-xs text-slate-500 mt-1">{{ __('Stok awal akan diambil dari jumlah yang sudah diterima.') }}</p>
                    </div>

                    <div class="flex gap-3">
                        <label class="flex-1 flex items-center gap-2 p-3 border border-slate-200 rounded-lg cursor-pointer has-[:checked]:border-corpblue-500 has-[:checked]:bg-corpblue-50 text-sm text-slate-700">
                            <input type="radio" name="location_mode" value="existing" checked class="text-corpblue-500" onchange="toggleLocationMode()">
                            <span>{{ __('Slot kosong existing') }}</span>
                        </label>
                        <label class="flex-1 flex items-center gap-2 p-3 border border-slate-200 rounded-lg cursor-pointer has-[:checked]:border-corpblue-500 has-[:checked]:bg-corpblue-50 text-sm text-slate-700">
                            <input type="radio" name="location_mode" value="new" class="text-corpblue-500" onchange="toggleLocationMode()">
                            <span>{{ __('Sub lokasi baru') }}</span>
                        </label>
                    </div>

                    <div id="existingLocationWrap">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('Lokasi') }}</label>
                        <select name="storage_location_id" id="promote_location_id" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                            <option value="">{{ __('Pilih slot kosong') }}</option>
                            @forelse($emptyLocations as $emptyLocation)
                            <option value="{{ $emptyLocation->id }}">{{ $emptyLocation->code }} — {{ $emptyLocation->sub_location ?? '—' }}</option>
                            @empty
                            <option value="" disabled>{{ __('Tidak ada slot kosong') }}</option>
                            @endforelse
                        </select>
                    </div>

                    <div id="newLocationWrap" class="hidden grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('Rak') }}</label>
                            <select name="rack" id="promote_rack" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                                @foreach($racks as $rack)
                                <option value="{{ $rack }}">{{ $rack }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('Sub Lokasi') }}</label>
                            <input type="text" name="sub_location" id="promote_sub_location" maxlength="255" placeholder="D5.5.1" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('Catatan') }} <span class="text-slate-400 font-normal">({{ __('opsional') }})</span></label>
                        <input type="text" name="note" maxlength="255" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="closeModal('promoteOtherModal')" class="btn btn--secondary">{{ __('Batal') }}</button>
                        <button type="submit" class="btn btn--primary">{{ __('Daftarkan') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </x-slot:modals>

    <x-slot:scripts>
        <script>
            function toggleLocationMode() {
                const mode = document.querySelector('input[name="location_mode"]:checked')?.value;
                const isNew = mode === 'new';
                document.getElementById('existingLocationWrap').classList.toggle('hidden', isNew);
                document.getElementById('newLocationWrap').classList.toggle('hidden', !isNew);
                document.getElementById('promote_location_id').required = !isNew;
                document.getElementById('promote_sub_location').required = isNew;
            }

            function openPromoteModal(button, id) {
                document.getElementById('promote_item_name').textContent = button.dataset.name;
                document.getElementById('promoteOtherForm').action = '/admin/other-requests/' + id + '/promote';
                toggleLocationMode();
                openModal('promoteOtherModal');
            }
        </script>
    </x-slot:scripts>
</x-layout>
