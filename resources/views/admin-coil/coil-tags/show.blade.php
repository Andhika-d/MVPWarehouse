<x-layout :title="__('Detail Coil Tag')" :headerTitle="$coilTag->nomor_coil">
    <div class="mx-auto max-w-4xl space-y-6">

        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">{{ __('Admin Coil') }} / {{ __('Coil Tag') }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-corpblue-50 px-3 py-1 text-xs font-semibold text-corpblue-700">
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $coilTag->lokasi_coil ?: __('Belum ada lokasi') }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">{{ $coilTag->company ?: '—' }}</span>
                    @if ($coilTag->scanned_at)
                        <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">{{ $coilTag->scanned_at->format('Y-m-d H:i') }}</span>
                    @endif
                </div>
                @php $lastChange = $histories->first(); @endphp
                @if ($lastChange)
                    <p class="mt-2 text-[11px] text-slate-400">
                        {{ __('Lokasi terakhir diubah :time oleh :name', [
                            'time' => $lastChange->created_at?->format('Y-m-d H:i'),
                            'name' => $lastChange->changedBy?->name ?? __('Sistem'),
                        ]) }}
                    </p>
                @endif
            </div>
            <div class="flex shrink-0 items-center gap-3">
                <a href="{{ route('admin-coil.coil-tags.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors hover:text-slate-700">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    {{ __('Kembali') }}
                </a>
                <a href="{{ route('admin-coil.movements.index', ['search' => $coilTag->nomor_coil]) }}" class="btn btn--secondary whitespace-nowrap">{{ __('Lihat Pergerakan') }}</a>
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-corpblue-50">
                    <svg class="text-corpblue-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-xl font-bold text-corpblue-700">{{ $coilTag->beratAsScanned() }}</p>
                    <p class="text-xs text-slate-500">{{ __('Berat Bersih') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-corpblue-50">
                    <svg class="text-corpblue-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-xl font-bold text-slate-900">{{ $coilTag->tag_taehang ?: '—' }}</p>
                    <p class="text-xs text-slate-500">{{ __('Tag Taehang') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-corpblue-50">
                    <svg class="text-corpblue-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-xl font-bold text-slate-900">{{ $coilTag->ukuran ?: '—' }}</p>
                    <p class="text-xs text-slate-500">{{ __('Ukuran') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-corpblue-50">
                    <svg class="text-corpblue-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-xl font-bold text-slate-900">{{ $coilTag->user?->name ?? '—' }}</p>
                    <p class="text-xs text-slate-500">{{ __('Dipindai Oleh') }}</p>
                </div>
            </div>
        </div>

        {{-- Location editor --}}
        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Ubah Lokasi') }}</h2>
            <form action="{{ route('admin-coil.coil-tags.location', $coilTag) }}" method="POST" class="mt-4 space-y-3">
                @csrf
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="lokasi_coil" class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Lokasi Coil') }}</label>
                        <input
                            id="lokasi_coil"
                            name="lokasi_coil"
                            type="text"
                            value="{{ old('lokasi_coil', $coilTag->lokasi_coil) }}"
                            placeholder="{{ __('Contoh: AL') }}"
                            autocomplete="off"
                            spellcheck="false"
                            maxlength="50"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-corpblue-500 focus:ring-2 focus:ring-corpblue-500 outline-none"
                        >
                        @if ($locations->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($locations->take(8) as $location)
                                    <button type="button" onclick="document.getElementById('lokasi_coil').value = '{{ $location }}'; document.getElementById('lokasi_coil').focus();" class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600 transition-colors hover:border-corpblue-300 hover:bg-corpblue-50 hover:text-corpblue-700">{{ $location }}</button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div>
                        <label for="note" class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Catatan') }} <span class="font-normal text-slate-400">{{ __('(opsional)') }}</span></label>
                        <input
                            id="note"
                            name="note"
                            type="text"
                            value="{{ old('note') }}"
                            maxlength="255"
                            placeholder="{{ __('Alasan atau keterangan perpindahan') }}"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-corpblue-500 focus:ring-2 focus:ring-corpblue-500 outline-none"
                        >
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="btn btn--primary shrink-0">{{ __('Simpan Lokasi') }}</button>
                </div>
            </form>
            @error('lokasi_coil')
                <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-[11px] text-slate-400">{{ __('Kosongkan kolom untuk menghapus lokasi. Setiap perubahan akan tercatat di riwayat.') }}</p>
        </section>

        {{-- Processed data --}}
        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Data Terproses') }}</h2>
            <dl class="mt-4 grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
                @foreach ([
                    __('Nomor Coil') => $coilTag->nomor_coil,
                    __('Grade') => $coilTag->grade,
                    __('Tebal') => rtrim(rtrim((string) $coilTag->tebal, '0'), '.'),
                    __('Lebar') => rtrim(rtrim((string) $coilTag->lebar, '0'), '.'),
                    __('Ukuran') => $coilTag->ukuran,
                    __('Berat') => $coilTag->beratAsScanned(),
                    __('Company') => $coilTag->company ?: '-',
                    __('Tag Taehang') => $coilTag->tag_taehang,
                    __('Lokasi Coil') => $coilTag->lokasi_coil ?? '-',
                    __('Dipindai Oleh') => $coilTag->user?->name ?? '-',
                    __('Scanned At') => $coilTag->scanned_at?->format('Y-m-d H:i:s'),
                ] as $label => $value)
                    <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
                        <dt class="text-xs font-semibold text-slate-500">{{ $label }}</dt>
                        <dd class="text-sm font-medium text-slate-900 text-right break-words">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>

            <details class="group mt-4">
                <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 text-xs font-semibold text-slate-500 transition-colors hover:text-slate-700 [&::-webkit-details-marker]:hidden">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="shrink-0 transition-transform group-open:rotate-90" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    {{ __('Info teknis') }}
                </summary>
                <dl class="mt-3 grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
                    @foreach ([
                        __('Field 1') => $coilTag->field_1 ?? '-',
                        __('Field 6') => $coilTag->field_6 ?? '-',
                        __('Field 9') => $coilTag->field_9 ?? '-',
                        __('Created At') => $coilTag->created_at?->format('Y-m-d H:i:s'),
                        __('Updated At') => $coilTag->updated_at?->format('Y-m-d H:i:s'),
                    ] as $label => $value)
                        <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
                            <dt class="text-xs font-semibold text-slate-500">{{ $label }}</dt>
                            <dd class="text-sm font-medium text-slate-900 text-right break-words">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </details>
        </section>

        {{-- Location history --}}
        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Riwayat Lokasi') }}</h2>

            @if ($histories->isEmpty())
                <p class="mt-4 text-sm text-slate-400">{{ __('Belum ada perpindahan lokasi.') }}</p>
            @else
                <ol class="mt-5 space-y-5">
                    @foreach ($histories as $history)
                        <li class="relative border-l-2 border-slate-100 pl-5">
                            <span class="absolute -left-[7px] top-1.5 h-3 w-3 rounded-full border-2 border-white {{ $history === $histories->first() ? 'bg-corpblue-500' : 'bg-slate-300' }}"></span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-slate-900">
                                    {{ $history->from_location ?: __('Tanpa Lokasi') }}
                                </span>
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-slate-400"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                <span class="text-sm font-semibold text-corpblue-700">
                                    {{ $history->to_location ?: __('Tanpa Lokasi') }}
                                </span>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                <span>{{ $history->created_at?->format('Y-m-d H:i:s') }}</span>
                                <span class="text-slate-300">·</span>
                                <span>{{ $history->sourceLabel() }}</span>
                                <span class="text-slate-300">·</span>
                                <span>{{ $history->changedBy?->name ?? __('Sistem') }}</span>
                            </div>
                            @if ($history->note)
                                <p class="mt-1 text-xs text-slate-500">{{ $history->note }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>
</x-layout>
