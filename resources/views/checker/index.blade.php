<x-layout :title="__('Coil Tag Checker')" :headerTitle="__('Coil Tag Checker')">
    <div class="mx-auto max-w-3xl space-y-6">

        {{-- Workflow steps --}}
        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-4">
            <span class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-bold {{ $kp ? 'bg-emerald-50 text-emerald-700' : 'bg-corpblue-50 text-corpblue-700' }}">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white/70 text-[10px]">1</span>{{ __('SCAN KP') }}
            </span>
            <span class="h-px flex-1 bg-slate-200"></span>
            <span class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-bold {{ $taehang ? 'bg-emerald-50 text-emerald-700' : ($kp ? 'bg-corpblue-50 text-corpblue-700' : 'bg-slate-100 text-slate-400') }}">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white/70 text-[10px]">2</span>{{ __('SCAN TAEHANG') }}
            </span>
            <span class="h-px flex-1 bg-slate-200"></span>
            <span class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-bold {{ $taehang ? 'bg-corpblue-50 text-corpblue-700' : 'bg-slate-100 text-slate-400' }}">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white/70 text-[10px]">3</span>{{ __('SIMPAN') }}
            </span>
        </div>

        {{-- Scan section --}}
        <section class="rounded-xl border border-slate-200 bg-white p-6">
            @if (! $kp)
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">{{ __('Langkah Pertama') }}</p>
                <h2 class="mt-1 text-lg font-bold text-slate-900">{{ __('SIAP SCAN KP') }}</h2>
                <form class="mt-4" action="{{ route('checker.kp') }}" method="POST">
                    @csrf
                    <label for="raw_kp" class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Data KP') }}</label>
                    <div class="flex gap-2">
                        <input
                            id="raw_kp"
                            name="raw_kp"
                            type="text"
                            value="{{ old('raw_kp') }}"
                            placeholder="{{ __('Arahkan scanner ke barcode KP') }}"
                            autocomplete="off"
                            autocapitalize="off"
                            spellcheck="false"
                            autofocus
                            required
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-corpblue-500 focus:ring-2 focus:ring-corpblue-500 outline-none"
                        >
                        <button type="submit" class="btn btn--primary shrink-0">{{ __('Scan') }}</button>
                    </div>
                </form>
            @else
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">{{ $taehang ? __('Data Siap Diperiksa') : __('Langkah Kedua') }}</p>
                <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $taehang ? __('DATA SCAN LENGKAP') : __('SIAP SCAN TAEHANG') }}</h2>

                @if (! $taehang)
                    <form class="mt-4" action="{{ route('checker.taehang') }}" method="POST">
                        @csrf
                        <label for="raw_taehang" class="mb-1 block text-xs font-semibold text-slate-600">{{ __('QR Taehang') }}</label>
                        <div class="flex gap-2">
                            <input
                                id="raw_taehang"
                                name="raw_taehang"
                                type="text"
                                value="{{ old('raw_taehang') }}"
                                placeholder="{{ __('Arahkan scanner ke QR Taehang') }}"
                                autocomplete="off"
                                autocapitalize="off"
                                spellcheck="false"
                                autofocus
                                required
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-corpblue-500 focus:ring-2 focus:ring-corpblue-500 outline-none"
                            >
                            <button type="submit" class="btn btn--primary shrink-0">{{ __('Scan') }}</button>
                        </div>
                    </form>
                @endif
            @endif
        </section>

        {{-- Preview section --}}
        @if ($kp)
            <section class="rounded-xl border border-slate-200 bg-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">{{ $taehang ? __('Konfirmasi') : __('Hasil Pembacaan') }}</p>
                        <h3 class="mt-1 text-base font-bold text-slate-900">{{ $taehang ? __('Periksa data sebelum disimpan') : __('Preview KP') }}</h3>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700">{{ $taehang ? __('Siap') : __('KP Valid') }}</span>
                </div>

                <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-1.5">
                        <dt class="text-xs font-semibold text-slate-500">{{ __('Nomor Coil') }}</dt>
                        <dd class="text-sm font-semibold text-slate-900">{{ $kp['nomor_coil'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-1.5">
                        <dt class="text-xs font-semibold text-slate-500">{{ __('Grade') }}</dt>
                        <dd class="text-sm font-semibold text-slate-900">{{ $kp['grade'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-1.5">
                        <dt class="text-xs font-semibold text-slate-500">{{ __('Tebal') }}</dt>
                        <dd class="text-sm font-semibold text-slate-900">{{ $kp['tebal'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-1.5">
                        <dt class="text-xs font-semibold text-slate-500">{{ __('Lebar') }}</dt>
                        <dd class="text-sm font-semibold text-slate-900">{{ $kp['lebar'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-1.5">
                        <dt class="text-xs font-semibold text-slate-500">{{ __('Ukuran') }}</dt>
                        <dd class="text-sm font-semibold text-slate-900">{{ $kp['ukuran'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-1.5">
                        <dt class="text-xs font-semibold text-slate-500">{{ __('Berat') }}</dt>
                        <dd class="text-sm font-semibold text-slate-900">{{ $kp['berat'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-1.5">
                        <dt class="text-xs font-semibold text-slate-500">{{ __('Company') }}</dt>
                        <dd class="text-sm font-semibold text-slate-900">{{ $kp['company'] !== '' ? $kp['company'] : '-' }}</dd>
                    </div>
                    @if ($taehang)
                        <div class="flex justify-between gap-3 border-b border-slate-100 pb-1.5">
                            <dt class="text-xs font-semibold text-slate-500">{{ __('Tag Taehang') }}</dt>
                            <dd class="text-sm font-bold text-corpblue-600">{{ $taehang['tag_taehang'] }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($taehang)
                    <form action="{{ route('checker.save') }}" method="POST" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <label for="lokasi_coil" class="mb-1 block text-xs font-semibold text-slate-600">
                                {{ __('Lokasi') }} <span class="font-normal text-slate-400">{{ __('(opsional)') }}</span>
                            </label>
                            <input
                                id="lokasi_coil"
                                name="lokasi_coil"
                                type="text"
                                list="coilLocationOptions"
                                value="{{ old('lokasi_coil') }}"
                                placeholder="{{ __('Kosongkan jika belum diketahui') }}"
                                autocomplete="off"
                                spellcheck="false"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-corpblue-500 focus:ring-2 focus:ring-corpblue-500 outline-none"
                            >
                            <datalist id="coilLocationOptions">
                                @foreach ($locations as $location)
                                    <option value="{{ $location }}"></option>
                                @endforeach
                            </datalist>
                            <p class="mt-1 text-[11px] text-slate-400">{{ __('Boleh dikosongkan, lokasi bisa diisi menyusul oleh Admin Coil.') }}</p>
                        </div>
                        <button class="btn btn--primary w-full" type="submit">{{ __('SIMPAN DATA') }}</button>
                    </form>
                @endif
            </section>
        @endif
    </div>
</x-layout>
