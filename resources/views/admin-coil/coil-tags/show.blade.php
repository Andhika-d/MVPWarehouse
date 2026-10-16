<x-layout :title="__('Detail Coil Tag')" :headerTitle="$coilTag->nomor_coil">
    <div class="mx-auto max-w-4xl space-y-6">

        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">{{ __('Admin Coil') }} / {{ __('Coil Tag') }}</p>
                <h1 class="mt-1 text-xl font-bold text-slate-900">{{ $coilTag->nomor_coil }}</h1>
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
            </div>
            <a href="{{ route('admin-coil.coil-tags.index') }}" class="btn btn--secondary">← {{ __('Semua Data') }}</a>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">{{ __('Berat Bersih') }}</p>
                <p class="mt-1 text-lg font-bold text-slate-900">{{ $coilTag->beratAsScanned() }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">{{ __('Tag Taehang') }}</p>
                <p class="mt-1 truncate text-lg font-bold text-corpblue-700">{{ $coilTag->tag_taehang ?: '—' }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">{{ __('Ukuran') }}</p>
                <p class="mt-1 text-lg font-bold text-slate-900">{{ $coilTag->ukuran ?: '—' }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">{{ __('Dipindai Oleh') }}</p>
                <p class="mt-1 truncate text-lg font-bold text-slate-900">{{ $coilTag->user?->name ?? '—' }}</p>
            </div>
        </div>

        {{-- Location editor --}}
        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ __('Ubah Lokasi') }}</h2>
            <form action="{{ route('admin-coil.coil-tags.location', $coilTag) }}" method="POST" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf
                <div class="flex-1">
                    <label for="lokasi_coil" class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Lokasi Coil') }}</label>
                    <input
                        id="lokasi_coil"
                        name="lokasi_coil"
                        type="text"
                        list="coilLocationOptions"
                        value="{{ old('lokasi_coil', $coilTag->lokasi_coil) }}"
                        placeholder="{{ __('Contoh: AL') }}"
                        autocomplete="off"
                        spellcheck="false"
                        maxlength="50"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-corpblue-500 focus:ring-2 focus:ring-corpblue-500 outline-none"
                    >
                    <datalist id="coilLocationOptions">
                        @foreach ($locations as $location)
                            <option value="{{ $location }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <button type="submit" class="btn btn--primary shrink-0">{{ __('Simpan Lokasi') }}</button>
            </form>
            @error('lokasi_coil')
                <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-[11px] text-slate-400">{{ __('Kosongkan kolom untuk menghapus lokasi. Setiap perubahan akan tercatat di riwayat.') }}</p>
        </section>

        {{-- Processed data --}}
        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ __('Data Terproses') }}</h2>
            <dl class="mt-4 grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
                @foreach ([
                    __('Nomor Coil') => $coilTag->nomor_coil,
                    __('Grade') => $coilTag->grade,
                    __('Tebal') => rtrim(rtrim((string) $coilTag->tebal, '0'), '.'),
                    __('Lebar') => rtrim(rtrim((string) $coilTag->lebar, '0'), '.'),
                    __('Ukuran') => $coilTag->ukuran,
                    __('Berat') => $coilTag->beratAsScanned(),
                    __('Company') => $coilTag->company ?: '-',
                    __('Field 1') => $coilTag->field_1 ?? '-',
                    __('Field 6') => $coilTag->field_6 ?? '-',
                    __('Field 9') => $coilTag->field_9 ?? '-',
                    __('Tag Taehang') => $coilTag->tag_taehang,
                    __('Lokasi Coil') => $coilTag->lokasi_coil ?? '-',
                    __('Dipindai Oleh') => $coilTag->user?->name ?? '-',
                    __('Scanned At') => $coilTag->scanned_at?->format('Y-m-d H:i:s'),
                    __('Created At') => $coilTag->created_at?->format('Y-m-d H:i:s'),
                    __('Updated At') => $coilTag->updated_at?->format('Y-m-d H:i:s'),
                ] as $label => $value)
                    <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
                        <dt class="text-xs font-semibold text-slate-500">{{ $label }}</dt>
                        <dd class="text-sm font-medium text-slate-900 text-right break-words">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Location history --}}
        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ __('Riwayat Lokasi') }}</h2>

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
