<x-layout :title="__('Detail Coil Tag')" :headerTitle="$coilTag->nomor_coil">
    <div class="mx-auto max-w-4xl space-y-6">

        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">{{ __('Admin Coil') }} / {{ __('Coil Tag') }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-corpblue-50 px-3 py-1 text-xs font-semibold text-corpblue-700">
                        <x-icon name="pin" :size="12" />
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
                    <x-icon name="chevron-left" :size="16" />
                    {{ __('Kembali') }}
                </a>
                <a href="{{ route('admin-coil.movements.index', ['search' => $coilTag->nomor_coil]) }}" class="btn btn--secondary whitespace-nowrap">{{ __('Lihat Pergerakan') }}</a>
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-stat-card icon="scale" accent :label="__('Berat Bersih')" :value="$coilTag->beratAsScanned()" />
            <x-stat-card icon="scan-frame" :label="__('Tag Taehang')" :value="$coilTag->tag_taehang ?: '—'" />
            <x-stat-card icon="arrows-vertical" :label="__('Ukuran')" :value="$coilTag->ukuran ?: '—'" />
            <x-stat-card icon="users" :label="__('Dipindai Oleh')" :value="$coilTag->user?->name ?? '—'" />
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
                            class="field w-full"
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
                            class="field w-full"
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
                    <x-icon name="chevron-right" :size="14" class="shrink-0 transition-transform group-open:rotate-90" />
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
                                <x-icon name="arrow-right" :size="14" class="text-slate-400" />
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
