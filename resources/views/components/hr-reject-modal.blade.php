<div id="rejectModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="rejectModalTitle">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeRejectModal()"></div>
    <div class="relative bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md flex flex-col overflow-hidden max-h-[90vh] z-[101]">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
            <div>
                <h3 id="rejectModalTitle" class="text-sm font-bold text-slate-900">Konfirmasi Penolakan</h3>
                <p id="rejectModalTarget" class="text-xs text-red-600 mt-0.5 font-semibold"></p>
            </div>
            <button onclick="closeRejectModal()" type="button" class="text-slate-400 hover:text-slate-600 p-2 text-2xl font-light leading-none cursor-pointer transition-colors" aria-label="Tutup">&times;</button>
        </div>
        <form id="rejectForm" method="POST">
            @csrf
            <div class="p-5 space-y-4 bg-white flex-1 overflow-y-auto">
                <div>
                    <label for="rejectReasonText" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Alasan Penolakan (Wajib Diisi)</label>
                    <textarea id="rejectReasonText" name="note" rows="3" required class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-red-600 focus:bg-white transition-all text-slate-900 resize-none" placeholder="Tuliskan alasan penolakan secara jelas agar dibaca oleh staf Gudang..."></textarea>
                </div>
            </div>
            <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end space-x-2 shrink-0">
                <button onclick="closeRejectModal()" type="button" class="btn btn--secondary">Batal</button>
                <button type="submit" class="btn btn--danger">Tolak</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRejectModal(button) {
        document.getElementById('rejectForm').action = button.getAttribute('data-action');
        document.getElementById('rejectModalTarget').innerText = 'Mencoret: ' + (button.getAttribute('data-name') || 'Barang');
        document.getElementById('rejectReasonText').value = '';
        openModal('rejectModal');
    }

    function closeRejectModal() {
        closeModal('rejectModal');
        document.getElementById('rejectReasonText').value = '';
    }
</script>