<div id="approveModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="approveModalTitle">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeApproveModal()"></div>
    <div class="relative bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md flex flex-col overflow-hidden max-h-[90vh] z-[101]">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
            <div>
                <h3 id="approveModalTitle" class="text-sm font-bold text-slate-900">{{ __('Konfirmasi Persetujuan') }}</h3>
                <p id="approveModalTarget" class="text-xs text-emerald-600 mt-0.5 font-semibold"></p>
            </div>
            <button onclick="closeApproveModal()" type="button" class="text-slate-400 hover:text-slate-600 p-2 text-2xl font-light leading-none cursor-pointer transition-colors" aria-label="{{ __('Tutup') }}">&times;</button>
        </div>
        <form id="approveForm" method="POST">
            @csrf
            <div class="p-5 space-y-4 bg-white flex-1 overflow-y-auto">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">{{ __('Jumlah Diminta') }}</label>
                    <div class="px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700">
                        <span id="approveRequestedQty">-</span> <span id="approveRequestedUnit"></span>
                    </div>
                </div>
                <div>
                    <label for="approveQuantityInput" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">{{ __('Jumlah Disetujui') }} <span class="text-slate-400 font-normal normal-case">({{ __('opsional, bisa dikurangi') }})</span></label>
                    <input type="number" id="approveQuantityInput" name="approved_quantity" step="any" min="0.01" inputmode="decimal" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-600 focus:bg-white transition-all text-slate-900" placeholder="{{ __('Kosongkan jika sesuai permintaan') }}">
                    <p class="mt-1 text-[11px] text-slate-500">{{ __('Kosongkan jika menyetujui sesuai jumlah permintaan. Isi jika hanya menyetujui sebagian.') }}</p>
                </div>
                <div>
                    <label for="approveNoteText" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">{{ __('Catatan Persetujuan') }} <span class="text-slate-400 font-normal normal-case">({{ __('opsional') }})</span></label>
                    <textarea id="approveNoteText" name="note" rows="3" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-600 focus:bg-white transition-all text-slate-900 resize-none" placeholder="{{ __('Tambahkan catatan jika perlu (contoh: hanya dibeli sebagian)...') }}"></textarea>
                </div>
            </div>
            <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end space-x-2 shrink-0">
                <button onclick="closeApproveModal()" type="button" class="btn btn--secondary">{{ __('Batal') }}</button>
                <button type="submit" class="btn btn--success">{{ __('Setujui') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
    if (typeof openApproveModal !== 'function') {
        function openApproveModal(button) {
            var form = document.getElementById('approveForm');
            if (form) {
                form.action = button.getAttribute('data-action') || form.action;
                form.querySelectorAll('[data-bulk-request-id]').forEach(function (input) { input.remove(); });
            }
            var target = document.getElementById('approveModalTarget');
            if (target) target.innerText = @json(__('Menyetujui') . ': ') + (button.getAttribute('data-name') || @json(__('Barang')));
            const qty = button.getAttribute('data-quantity') || '-';
            const unit = button.getAttribute('data-unit') || '';
            var reqQty = document.getElementById('approveRequestedQty');
            var reqUnit = document.getElementById('approveRequestedUnit');
            if (reqQty) reqQty.innerText = qty;
            if (reqUnit) reqUnit.innerText = unit;
            var qtyInput = document.getElementById('approveQuantityInput');
            if (qtyInput) {
                qtyInput.value = '';
                qtyInput.max = qty;
                if (!qtyInput._stepHandlerAttached) {
                    qtyInput.addEventListener('keydown', function(e) {
                        if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
                            e.preventDefault();
                            var current = parseFloat(this.value);
                            var max = parseFloat(this.max);
                            var min = parseFloat(this.min || '0.01');
                            if (isNaN(current)) current = e.key === 'ArrowUp' ? 0 : (isNaN(max) ? 1 : max);
                            var next = e.key === 'ArrowUp' ? current + 1 : current - 1;
                            if (!isNaN(max)) next = Math.min(next, max);
                            if (!isNaN(min)) next = Math.max(next, min);
                            if (Math.abs(next - Math.round(next)) < 1e-9) next = Math.round(next);
                            this.value = next;
                        }
                    });
                    qtyInput._stepHandlerAttached = true;
                }
            }
            var noteInput = document.getElementById('approveNoteText');
            if (noteInput) noteInput.value = '';
            var modal = document.getElementById('approveModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.setAttribute('aria-hidden', 'false');
            }
            if (typeof openModal === 'function') {
                try { openModal('approveModal'); } catch(e) {}
            }
            if (noteInput) {
                try { noteInput.focus(); } catch(e) {}
            }
            return false;
        }
    }

    if (typeof closeApproveModal !== 'function') {
        function closeApproveModal() {
            if (typeof closeModal === 'function') {
                closeModal('approveModal');
            } else {
                const modal = document.getElementById('approveModal');
                if (modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            }
        }
    }
</script>
