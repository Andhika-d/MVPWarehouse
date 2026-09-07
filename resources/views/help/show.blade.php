<x-layout>
    <x-slot:title>{{ $guide->title }} — Bantuan</x-slot:title>
    <x-slot:headerTitle>{{ $guide->title }}</x-slot:headerTitle>
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3"><a href="{{ route('help.index') }}" class="inline-flex text-sm font-semibold text-corpblue-600 hover:text-corpblue-800">← Semua Panduan</a><button type="button" onclick="history.back()" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm hover:border-corpblue-300 hover:text-corpblue-700">← Kembali</button></div>
        <div class="rounded-xl border border-slate-200 bg-white p-6"><p class="text-xs font-bold uppercase tracking-widest text-corpblue-500">{{ $guide->category }}</p><h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $guide->title }}</h2><p class="mt-2 text-sm leading-6 text-slate-500">{{ $guide->description }}</p></div>
        @foreach($guide->images as $image)
            <div class="rounded-xl border border-slate-200 bg-white p-4 md:p-6">
                <div class="relative overflow-hidden rounded-lg border border-slate-200 bg-slate-50"><img src="{{ Storage::disk('public')->url($image->image_path) }}" alt="{{ $image->image_alt ?: $guide->title }}" class="block w-full"><div class="pointer-events-none absolute inset-0">@foreach($image->markers as $marker)<span class="absolute flex h-7 w-7 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border-2 border-white bg-corpblue-500 text-xs font-bold text-white shadow-lg" style="left: {{ $marker->position_x }}%; top: {{ $marker->position_y }}%">{{ $marker->number }}</span>@endforeach</div></div>
                @if($image->markers->isNotEmpty())<div class="mt-5 space-y-3">@foreach($image->markers as $marker)<div class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-corpblue-50 text-xs font-bold text-corpblue-700">{{ $marker->number }}</span><div><p class="text-sm font-semibold text-slate-900">{{ $marker->title }}</p>@if($marker->description)<p class="mt-0.5 text-sm leading-6 text-slate-500">{{ $marker->description }}</p>@endif</div></div>@endforeach</div>@endif
            </div>
        @endforeach
    </div>
</x-layout>
