@props([
    'name' => 'cube',
    'size' => 16,
    'stroke' => 2,
])

@php
    /*
     * Pustaka ikon garis (24x24) dipakai bersama di modul coil.
     * Disimpan di satu tempat agar tidak ada path SVG raksasa terduplikasi di view.
     */
    $icons = [
        'cube' => ['M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        'scale' => ['M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3'],
        'calendar' => ['M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        'pin' => ['M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z', 'M15 11a3 3 0 11-6 0 3 3 0 016 0z'],
        'tray-down' => ['M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4'],
        'chevron-left' => ['M15 19l-7-7 7-7'],
        'chevron-right' => ['M9 5l7 7-7 7'],
        'arrow-right' => ['M14 5l7 7m0 0l-7 7m7-7H3'],
        'scan-frame' => ['M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z'],
        'arrows-vertical' => ['M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4'],
        'users' => ['M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
    ];

    $paths = $icons[$name] ?? $icons['cube'];
@endphp

<svg {{ $attributes->merge([
    'width' => $size,
    'height' => $size,
    'fill' => 'none',
    'stroke' => 'currentColor',
    'stroke-width' => $stroke,
    'viewBox' => '0 0 24 24',
    'aria-hidden' => 'true',
]) }}>
    @foreach ($paths as $path)
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/>
    @endforeach
</svg>
