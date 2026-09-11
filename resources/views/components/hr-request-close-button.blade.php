@props(['request'])

@php
    $isCancellation = $request->received_quantity === 0;
@endphp

<button
    type="button"
    onclick="openHrRequestCloseModal(this)"
    data-url="{{ route('hr.requests.close', $request) }}"
    data-name="{{ $request->item?->name ?? $request->item_name ?? 'Barang' }}"
    data-remain="{{ $request->remainingQuantity() }}"
    data-unit="{{ $request->unit }}"
    data-mode="{{ $isCancellation ? 'cancel' : 'close' }}"
    {{ $attributes->class(['action-link', $isCancellation ? 'action-link--danger' : 'action-link--warning']) }}
>
    {{ $isCancellation ? 'Batalkan Request' : 'Tutup Sisa' }}
</button>
