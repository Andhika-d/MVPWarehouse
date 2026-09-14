@props([
    'period' => null,
    'formId',
    'modalId' => 'periodFilterModal',
])

@php
    $activeMode = $period?->mode === 'legacy' ? 'flexible' : ($period?->mode ?? 'weekly');
    $startValue = $period?->start?->toDateString() ?? now()->toDateString();
    $endValue = $period?->end?->toDateString() ?? now()->addDays(7)->toDateString();
@endphp

<div id="{{ $modalId }}" class="fixed inset-0 z-[100] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}Title">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeModal('{{ $modalId }}')"></div>
    <div class="relative z-[101] w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-start justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h3 id="{{ $modalId }}Title" class="text-base font-bold text-slate-900">Filter Periode</h3>
                <p class="mt-1 text-xs text-slate-500">Pilih pola periode dan periksa rentang sebelum diterapkan.</p>
            </div>
            <button type="button" onclick="closeModal('{{ $modalId }}')" class="touch-target rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Tutup">&times;</button>
        </div>

        <div class="space-y-5 p-5">
            <fieldset>
                <legend class="ui-label mb-2">Jenis Periode</legend>
                <div class="grid grid-cols-3 gap-2">
                    @foreach(['weekly' => 'Weekly', 'monthly' => 'Monthly', 'flexible' => 'Flexible'] as $value => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="{{ $modalId }}Mode" value="{{ $value }}" class="peer sr-only" @checked($activeMode === $value)>
                        <span class="flex min-h-11 items-center justify-center rounded-lg border border-slate-200 px-2 text-sm font-semibold text-slate-600 transition-colors peer-checked:border-corpblue-500 peer-checked:bg-corpblue-50 peer-checked:text-corpblue-700">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </fieldset>

            <div>
                <label for="{{ $modalId }}Start" class="ui-label mb-1 block">Tanggal Awal</label>
                <input id="{{ $modalId }}Start" type="date" value="{{ $startValue }}" class="min-h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-corpblue-500 focus:outline-none">
            </div>

            <div id="{{ $modalId }}EndWrap" class="{{ $activeMode === 'flexible' ? '' : 'hidden' }}">
                <label for="{{ $modalId }}End" class="ui-label mb-1 block">Tanggal Akhir</label>
                <input id="{{ $modalId }}End" type="date" value="{{ $endValue }}" class="min-h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-corpblue-500 focus:outline-none">
            </div>

            <div class="rounded-xl border border-corpblue-100 bg-corpblue-50 p-4">
                <p class="ui-heading text-corpblue-700">Preview Periode</p>
                <p id="{{ $modalId }}Preview" class="mt-1 text-sm font-semibold text-corpblue-900"></p>
                <p id="{{ $modalId }}Error" class="mt-1 hidden text-xs font-semibold text-red-700"></p>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-between">
            <button id="{{ $modalId }}Clear" type="button" class="btn btn--ghost">Hapus Periode</button>
            <div class="flex gap-2">
                <button type="button" onclick="closeModal('{{ $modalId }}')" class="btn btn--secondary flex-1 sm:flex-none">Batal</button>
                <button id="{{ $modalId }}Apply" type="button" class="btn btn--primary flex-1 sm:flex-none">Terapkan</button>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const id = @json($modalId);
        const form = document.getElementById(@json($formId));
        const start = document.getElementById(id + 'Start');
        const end = document.getElementById(id + 'End');
        const endWrap = document.getElementById(id + 'EndWrap');
        const preview = document.getElementById(id + 'Preview');
        const error = document.getElementById(id + 'Error');
        const apply = document.getElementById(id + 'Apply');
        const modes = Array.from(document.querySelectorAll(`input[name="${id}Mode"]`));
        const hiddenMode = document.getElementById(id + 'ModeValue');
        const hiddenStart = document.getElementById(id + 'StartValue');
        const hiddenEnd = document.getElementById(id + 'EndValue');

        if (!form || !start || !end || !hiddenMode || !hiddenStart || !hiddenEnd) return;

        const parseDate = value => {
            const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');
            return match ? new Date(Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3]))) : null;
        };
        const formatInput = date => `${date.getUTCFullYear()}-${String(date.getUTCMonth() + 1).padStart(2, '0')}-${String(date.getUTCDate()).padStart(2, '0')}`;
        const formatDisplay = date => new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(date);
        const selectedMode = () => modes.find(input => input.checked)?.value || 'weekly';
        const resolvedEnd = (mode, startDate) => {
            if (mode === 'weekly') {
                const result = new Date(startDate);
                result.setUTCDate(result.getUTCDate() + 7);
                return result;
            }
            if (mode === 'monthly') {
                const targetMonth = startDate.getUTCMonth() + 1;
                const targetYear = startDate.getUTCFullYear() + Math.floor(targetMonth / 12);
                const normalizedMonth = targetMonth % 12;
                const lastDay = new Date(Date.UTC(targetYear, normalizedMonth + 1, 0)).getUTCDate();
                return new Date(Date.UTC(targetYear, normalizedMonth, Math.min(startDate.getUTCDate(), lastDay)));
            }
            return parseDate(end.value);
        };
        const updatePreview = () => {
            const mode = selectedMode();
            const startDate = parseDate(start.value);
            const endDate = startDate ? resolvedEnd(mode, startDate) : null;
            endWrap.classList.toggle('hidden', mode !== 'flexible');

            const invalid = !startDate || !endDate || endDate < startDate;
            error.classList.toggle('hidden', !invalid);
            error.textContent = invalid ? 'Tanggal akhir harus sama dengan atau setelah tanggal awal.' : '';
            preview.textContent = invalid ? 'Periode belum valid' : `${formatDisplay(startDate)} - ${formatDisplay(endDate)}`;
            apply.disabled = invalid;
            return invalid ? null : { mode, start: formatInput(startDate), end: formatInput(endDate) };
        };

        modes.forEach(input => input.addEventListener('change', updatePreview));
        start.addEventListener('change', updatePreview);
        end.addEventListener('change', updatePreview);
        apply.addEventListener('click', () => {
            const range = updatePreview();
            if (!range) return;
            hiddenMode.disabled = hiddenStart.disabled = hiddenEnd.disabled = false;
            hiddenMode.value = range.mode;
            hiddenStart.value = range.start;
            hiddenEnd.value = range.mode === 'flexible' ? range.end : '';
            form.requestSubmit();
        });
        document.getElementById(id + 'Clear').addEventListener('click', () => {
            hiddenMode.disabled = hiddenStart.disabled = hiddenEnd.disabled = true;
            form.requestSubmit();
        });
        updatePreview();
    })();
</script>
