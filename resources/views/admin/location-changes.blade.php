<x-layout :title="'Pengajuan Lokasi — MVPWarehouse'" :headerTitle="'Pengajuan Lokasi'">
    <div class="space-y-6">

        {{-- Filter --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form method="GET" action="{{ route('admin.location-changes.index') }}" data-auto-filter class="flex flex-col sm:flex-row gap-3">
                <select name="status" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    <option value="all">Semua Status</option>
                    <option value="Menunggu Konfirmasi" {{ request('status') === 'Menunggu Konfirmasi' ? 'selected' : '' }}>Menunggu Konfirmasi</option>
                    <option value="Disetujui" {{ request('status') === 'Disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="Ditolak" {{ request('status') === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
                @if(request()->filled('status') && request('status') !== 'all')
                <a href="{{ route('admin.location-changes.index') }}" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-sm font-medium">Reset</a>
                @endif
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.location-changes.export-preview', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-lg text-sm font-medium transition-colors">Preview Export</a>
                </div>
            </form>
        </div>

        {{-- List --}}
        <div class="space-y-3">
            @forelse($changes as $change)
            @php
                $isSub = $change->target_sub_location !== null;
                $toLocation = $change->toLocation;
                $toItems = $toLocation ? $toLocation->items->where('id', '!=', $change->item_id)->values() : collect();
                $toOccupied = $toItems->isNotEmpty();
                $needsResolution = $change->isPending() && $isSub && $toOccupied;
                $swapPalette = fn ($items) => $items->map(fn ($i) => [
                    'id' => $i->id,
                    'label' => trim($i->name.($i->size ? ' ('.$i->size.')' : '')).' · '.($i->unit ?? ''),
                ])->values()->all();
            @endphp
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        {{-- Status badge --}}
                        <div class="flex items-center gap-2 flex-wrap mb-2">
                            @if($change->isPending())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold">Menunggu</span>
                            @elseif($change->status === 'Disetujui')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">Disetujui</span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-xs font-semibold">Ditolak</span>
                            @endif

                            @if($isSub)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold">Pemindahan</span>
                            @elseif($toOccupied)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 text-xs font-semibold">Pertukaran</span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold">Pemindahan</span>
                            @endif

                            @if($change->status === 'Disetujui' && $change->resolution_action === 'swap')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 text-xs font-semibold">Tukar Tempat</span>
                            @elseif($change->status === 'Disetujui' && $change->resolution_action === 'stack')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold">Tumpuk</span>
                            @endif
                        </div>

                        {{-- Item info --}}
                        <div class="space-y-2">
                            {{-- Item yang diajukan --}}
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-semibold text-slate-900">{{ $change->item->name }}{{ $change->item->size ? ' ('.$change->item->size.')' : '' }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-mono whitespace-nowrap">{{ $change->fromLocation->code }}</span>
                                @if($isSub)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-xs font-mono whitespace-nowrap">{{ $change->from_sub_location ?? '—' }}</span>
                                @endif
                            </div>

                            {{-- Arrow --}}
                            <div class="flex items-center gap-2 text-slate-400 pl-1">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                <span class="text-xs">ke</span>
                            </div>

                            {{-- Target location --}}
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-corpblue-50 text-corpblue-700 text-xs font-mono whitespace-nowrap">{{ $change->toLocation?->code ?? '—' }}</span>
                                @if($isSub)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-xs font-mono whitespace-nowrap">{{ $change->target_sub_location ?? '—' }}</span>
                                @endif
                                @if($toOccupied)
                                <span class="text-xs text-slate-500">berisi {{ $toItems->take(3)->pluck('name')->implode(', ') }}</span>
                                @else
                                <span class="text-xs text-slate-400">kosong</span>
                                @endif
                            </div>

                            @if($needsResolution)
                            <p class="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mt-1">Slot tujuan terisi. Pilih: <strong>Tukar Tempat</strong> (barang tujuan pindah ke slot asal) atau <strong>Tumpuk</strong> (barang ditumpuk di slot tujuan).</p>
                            @endif
                        </div>

                        {{-- Meta --}}
                        <p class="text-xs text-slate-400 mt-2">Diajukan oleh {{ $change->requestedBy?->name ?? '—' }} &middot; {{ $change->created_at->diffForHumans() }}</p>
                        @if($change->reason)
                        <p class="text-xs text-slate-500 mt-1 italic">"{{ $change->reason }}"</p>
                        @endif
                        @if($change->admin_note)
                        <p class="text-xs text-blue-600 mt-1">Catatan Admin: {{ $change->admin_note }}</p>
                        @endif
                    </div>

                    @if($change->isPending())
                    <div class="flex items-center gap-2 shrink-0">
                        @if($needsResolution)
                        <button type="button"
                            data-change-id="{{ $change->id }}"
                            data-items='{{ json_encode($swapPalette($toItems)) }}'
                            onclick="openApproveModal(this)"
                            class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold rounded-lg transition-colors cursor-pointer">Setujui</button>
                        @else
                        <form method="POST" action="{{ route('admin.location-changes.approve', $change) }}" data-confirm="Setujui {{ $toOccupied ? 'pertukaran' : 'pemindahan' }} ini?" data-confirm-title="Setujui Pengajuan" data-confirm-tone="success" data-confirm-button="Setujui">
                            @csrf
                            @if($toOccupied && !$isSub)
                            <input type="hidden" name="action" value="swap">
                            @endif
                            <button type="submit" class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold rounded-lg transition-colors cursor-pointer">Setujui</button>
                        </form>
                        @endif
                        <button onclick="openRejectModal({{ $change->id }})" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-semibold rounded-lg transition-colors cursor-pointer">Tolak</button>
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
                <svg class="mx-auto text-slate-300" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <p class="text-sm text-slate-400 mt-3">Belum ada pengajuan perubahan lokasi.</p>
            </div>
            @endforelse
        </div>

        @if($changes->hasPages())
        <div>{{ $changes->links() }}</div>
        @endif

    </div>

    <x-slot:modals>
        <div id="approveModal" class="hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/50 backdrop-blur-sm" role="dialog" aria-modal="true">
            <div class="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-md mx-4 overflow-hidden" onclick="event.stopPropagation()">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">Setujui Pengajuan Lokasi</h3>
                    <button onclick="closeModal('approveModal')" aria-label="Tutup" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="approveForm" method="POST" class="p-5 space-y-4">
                    @csrf
                    <p class="text-xs text-slate-500">Slot tujuan sudah terisi. Bagaimana memproses pengajuan ini?</p>

                    <div class="space-y-2">
                        <label class="flex items-start gap-3 p-3 border border-corpblue-300 bg-corpblue-50 rounded-lg cursor-pointer">
                            <input type="radio" name="action" value="swap" checked class="mt-0.5 accent-corpblue-600">
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">Tukar Tempat</span>
                                <span class="block text-xs text-slate-500">Barang tujuan pindah ke slot asal. Pilih barang yang ditukar di bawah.</span>
                            </span>
                        </label>
                        <label class="flex items-start gap-3 p-3 border border-slate-200 rounded-lg cursor-pointer">
                            <input type="radio" name="action" value="stack" class="mt-0.5 accent-corpblue-600">
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">Tumpuk Barang</span>
                                <span class="block text-xs text-slate-500">Barang ditumpuk di slot tujuan (beberapa barang dalam satu slot).</span>
                            </span>
                        </label>
                    </div>

                    <div id="swapItemWrap">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Barang di slot tujuan yang ditukar</label>
                        <select name="swap_item_id" id="swapItemSelect" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                            <option value="">Pilih barang</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" onclick="closeModal('approveModal')" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer">Batal</button>
                        <button type="submit" class="px-4 py-2 text-sm bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg font-medium cursor-pointer">Setujui</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="rejectModal" class="hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/50 backdrop-blur-sm" role="dialog" aria-modal="true">
            <div class="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-md mx-4 overflow-hidden" onclick="event.stopPropagation()">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">Tolak Pengajuan</h3>
                    <button onclick="closeModal('rejectModal')" aria-label="Tutup" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="rejectForm" method="POST" class="p-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Catatan (opsional)</label>
                        <textarea name="admin_note" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none" placeholder="Alasan penolakan..."></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="closeModal('rejectModal')" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer">Batal</button>
                        <button type="submit" class="px-4 py-2 text-sm bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium cursor-pointer">Tolak</button>
                    </div>
                </form>
            </div>
        </div>
    </x-slot:modals>

    <x-slot:scripts>
        <script>
            function openRejectModal(id) {
                document.getElementById('rejectForm').action = '/admin/location-changes/' + id + '/reject';
                openModal('rejectModal');
            }

            var approveForm = document.getElementById('approveForm');
            var swapItemWrap = document.getElementById('swapItemWrap');
            var swapItemSelect = document.getElementById('swapItemSelect');

            document.querySelectorAll('input[name="action"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    var stack = this.value === 'stack';
                    swapItemWrap.classList.toggle('hidden', stack);
                    swapItemSelect.disabled = stack;
                    if (stack) {
                        swapItemSelect.value = '';
                    } else {
                        swapItemSelect.required = true;
                    }
                });
            });

            function openApproveModal(btn) {
                var id = btn.dataset.changeId;
                var items = JSON.parse(btn.dataset.items || '[]');

                approveForm.action = '/admin/location-changes/' + id + '/approve';
                swapItemSelect.innerHTML = '';
                var placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = 'Pilih barang';
                swapItemSelect.appendChild(placeholder);

                items.forEach(function (item) {
                    var opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.label;
                    swapItemSelect.appendChild(opt);
                });

                swapItemWrap.classList.remove('hidden');
                swapItemSelect.disabled = false;
                swapItemSelect.required = true;
                document.querySelector('input[name="action"][value="swap"]').checked = true;

                openModal('approveModal');
            }
        </script>
    </x-slot:scripts>
</x-layout>
