@props([
    'label',
    'value',
    'icon' => null,
    'tone' => 'blue',
    'accent' => false,
])

@php
    $tileClass = match ($tone) {
        'amber' => 'bg-amber-50',
        'emerald' => 'bg-emerald-50',
        default => 'bg-corpblue-50',
    };

    $iconClass = match ($tone) {
        'amber' => 'text-amber-500',
        'emerald' => 'text-emerald-500',
        default => 'text-corpblue-500',
    };

    $valueClass = match ($tone) {
        'amber' => 'text-amber-600',
        'emerald' => 'text-emerald-600',
        default => $accent ? 'text-corpblue-700' : 'text-slate-900',
    };
@endphp

<div {{ $attributes->class(['flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4']) }}>
    @if ($icon)
        <div @class(['flex h-10 w-10 shrink-0 items-center justify-center rounded-lg', $tileClass])>
            <x-icon :name="$icon" :size="18" :class="$iconClass" />
        </div>
    @endif
    <div class="min-w-0">
        <p @class(['truncate text-xl font-bold', $valueClass])>{{ $value }}</p>
        <p class="text-xs text-slate-500">{{ $label }}</p>
    </div>
</div>
