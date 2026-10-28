@props([
    'href',
    'label',
    'active' => false,
    'count' => null,
    'bold' => false,
    'uppercase' => false,
])

<a href="{{ $href }}" {{ $attributes->class([
    'flex items-center gap-2 whitespace-nowrap rounded-lg border px-4 py-2.5 text-sm font-medium transition-all',
    $active
        ? 'border-corpblue-300 bg-corpblue-50 text-corpblue-700'
        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300',
]) }}>
    <span @class(['font-bold' => $bold, 'uppercase' => $uppercase])>{{ $label }}</span>
    @if ($count !== null)
        <span @class(['text-xs', $active ? 'text-corpblue-500' : 'text-slate-400'])>{{ $count }}</span>
    @endif
</a>
