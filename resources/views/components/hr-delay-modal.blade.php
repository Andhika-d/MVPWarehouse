<div id="delayModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="delayModalTitle">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeDelayModal()"></div>
    <div class="relative bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md flex flex-col overflow-hidden max-h-[90vh] z-[101]">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
            <div>
                <h3 id="delayModalTitle" class="text-sm font-bold text-slate-900">Konfirmasi Penundaan</h3>
                <p id="delayModalTarget" class="text-xs text-amber-600 mt-0.5 font-semibold"></p>
            </div>
            <button onclick="closeDelayModal()" type="button" class="text-slate-400 hover:text-slate-600 p-2 text-2xl font-light leading-none cursor-pointer transition-colors" aria-label="Tutup">&times;</button>
        </div>
        <form id="delayForm" method="POST">
            @csrf
            <div class="p-5 space-y-4 bg-white flex-1 overflow-y-auto">
                <div>
                    <label for="delayReasonText" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Catatan Penundaan <span class="text-slate-400 font-normal normal-case">(opsional)</span></label>
                    <textarea id="delayReasonText" name="note" rows="3" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-amber-500 focus:bg-white transition-all text-slate-900 resize-none" placeholder="Contoh: Menunggu anggaran bulan depan..."></textarea>
                </div>
            </div>
            <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end space-x-2 shrink-0">
                <button onclick="closeDelayModal()" type="button" class="btn btn--secondary">Batal</button>
                <button type="submit" class="btn btn--warning">Tunda</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openDelayModal(button) {
        document.getElementById('delayForm').action = button.getAttribute('data-action');
        document.getElementById('delayModalTarget').innerText = 'Menunda: ' + (button.getAttribute('data-name') || 'Barang');
        document.getElementById('delayReasonText').value = '';
        openModal('delayModal');
    }

    function closeDelayModal() {
        closeModal('delayModal');
        document.getElementById('delayReasonText').value = '';
    }
</script>