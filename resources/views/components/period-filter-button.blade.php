@props([
    'period' => null,
    'modalId' => 'periodFilterModal',
])

@php
    $activeMode = $period?->mode === 'legacy' ? 'flexible' : $period?->mode;
@endphp

<input id="{{ $modalId }}ModeValue" type="hidden" name="period_mode" value="{{ $activeMode }}" @disabled(! $period)>
<input id="{{ $modalId }}StartValue" type="hidden" name="period_start" value="{{ $period?->start?->toDateString() }}" @disabled(! $period)>
<input id="{{ $modalId }}EndValue" type="hidden" name="period_end" value="{{ $activeMode === 'flexible' ? $period?->end?->toDateString() : '' }}" @disabled(! $period)>

<button type="button" onclick="openModal('{{ $modalId }}')" class="btn btn--secondary whitespace-nowrap" aria-label="Atur periode tanggal">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 2v3m8-3v3M3.5 9h17M5 4h14a2 2 0 012 2v13a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
    {{ $period ? $period->label() : 'Pilih Periode' }}
</button>
