<x-layout :title="__('Detail Coil Tag')" :headerTitle="$coilTag->nomor_coil">
    <div class="mx-auto max-w-4xl space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">{{ __('Admin Coil') }} / {{ __('Coil Tag') }}</p>
                <h1 class="mt-1 text-xl font-bold text-slate-900">{{ $coilTag->nomor_coil }}</h1>
            </div>
            <a href="{{ route('admin-coil.coil-tags.index') }}" class="btn btn--secondary">← {{ __('Semua Data') }}</a>
        </div>

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

        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ __('Jejak Scan') }}</h2>
            <dl class="mt-4 space-y-4">
                <div>
                    <dt class="mb-1 text-xs font-semibold text-slate-500">{{ __('Raw KP') }}</dt>
                    <dd><pre class="overflow-x-auto rounded-lg bg-slate-50 p-3 text-xs text-slate-700">{{ $coilTag->raw_kp }}</pre></dd>
                </div>
                <div>
                    <dt class="mb-1 text-xs font-semibold text-slate-500">{{ __('Raw Taehang') }}</dt>
                    <dd><pre class="overflow-x-auto rounded-lg bg-slate-50 p-3 text-xs text-slate-700">{{ $coilTag->raw_taehang }}</pre></dd>
                </div>
            </dl>
        </section>
    </div>
</x-layout>
