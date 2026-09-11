<div id="hrRequestCloseModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="hrRequestCloseModalTitle">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeHrRequestCloseModal()"></div>
    <div class="relative z-[101] w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
        <h3 id="hrRequestCloseModalTitle" class="mb-1 text-base font-bold text-slate-900">Tutup Sisa Request</h3>
        <p id="hrRequestCloseModalDescription" class="mb-4 text-xs text-slate-500">Sisa yang belum diterima tidak akan diproses lebih lanjut. Aksi ini permanen.</p>
        <form id="hrRequestCloseForm" method="POST" data-submit-once>
            @csrf
            <div class="flex flex-col gap-3">
                <div>
                    <span class="block text-xs font-medium text-slate-500">Barang</span>
                    <p id="hrRequestCloseItem" class="mt-0.5 text-sm font-semibold text-slate-900"></p>
                </div>
                <div>
                    <span class="block text-xs font-medium text-slate-500">Sisa yang Ditutup</span>
                    <p id="hrRequestCloseRemaining" class="mt-0.5 text-sm font-bold text-amber-700"></p>
                </div>
                <div>
                    <label for="hrRequestCloseNote" class="mb-0.5 block text-xs font-medium text-slate-500">Alasan <span class="text-red-600">*</span></label>
                    <textarea name="note" id="hrRequestCloseNote" rows="3" required maxlength="255" placeholder="Contoh: Kebutuhan sudah tidak diperlukan" class="w-full resize-none rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm transition-colors focus:border-corpblue-500 focus:bg-white focus:outline-none"></textarea>
                    <p class="mt-1 text-xs text-slate-500">Wajib diisi dan tidak dapat diubah setelah request ditutup.</p>
                </div>
            </div>
            <div class="mt-5 flex gap-3">
                <button type="button" onclick="closeHrRequestCloseModal()" class="btn btn--secondary flex-1">Batal</button>
                <button id="hrRequestCloseSubmit" type="submit" class="btn btn--warning flex-1">Tutup Sisa</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openHrRequestCloseModal(button) {
        const isCancellation = button.dataset.mode === 'cancel';
        const submit = document.getElementById('hrRequestCloseSubmit');

        document.getElementById('hrRequestCloseForm').action = button.dataset.url;
        document.getElementById('hrRequestCloseItem').textContent = button.dataset.name;
        document.getElementById('hrRequestCloseRemaining').textContent = button.dataset.remain + ' ' + button.dataset.unit;
        document.getElementById('hrRequestCloseNote').value = '';
        document.getElementById('hrRequestCloseModalTitle').textContent = isCancellation ? 'Batalkan Request' : 'Tutup Sisa Request';
        document.getElementById('hrRequestCloseModalDescription').textContent = isCancellation
            ? 'Barang belum diterima sama sekali. Request akan diakhiri dengan status Dibatalkan.'
            : 'Sisa yang belum diterima tidak akan diproses lagi. Request akan berstatus Ditutup Sebagian.';
        submit.textContent = isCancellation ? 'Batalkan Request' : 'Tutup Sisa';
        submit.classList.toggle('btn--danger', isCancellation);
        submit.classList.toggle('btn--warning', !isCancellation);

        openModal('hrRequestCloseModal');
    }

    function closeHrRequestCloseModal() {
        closeModal('hrRequestCloseModal');
    }
</script>
