<x-layout :title="__('Kelola Lokasi')" :headerTitle="__('Kelola Lokasi')">
    <div class="space-y-6">

        <p class="text-sm text-slate-500">{{ __('Atur daftar lokasi yang dipakai untuk menyimpan coil. Kode lokasi bebas diubah menyesuaikan kode gudang.') }}</p>

        {{-- Unknown values found on coils --}}
        @if ($unknown->isNotEmpty())
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-amber-800">{{ __('Lokasi di data coil belum terdaftar') }}</h3>
                        <p class="text-xs text-amber-700 mt-1">{{ __('Nilai berikut tersimpan di coil tetapi belum ada di daftar master.') }}</p>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach ($unknown as $kode)
                                <span class="inline-flex items-center rounded-lg border border-amber-200 bg-white px-2.5 py-1 text-xs font-semibold text-amber-700">{{ $kode }} <span class="ml-1.5 text-amber-500">{{ $usage[$kode] ?? 0 }}</span></span>
                            @endforeach
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin-coil.locations.sync') }}">
                        @csrf
                        <button type="submit" class="btn btn--warning whitespace-nowrap">{{ __('Tarik ke Daftar') }}</button>
                    </form>
                </div>
            </div>
        @endif

        {{-- Add form --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-sm font-semibold text-slate-900 mb-3">{{ __('Tambah Lokasi') }}</h3>
            <form method="POST" action="{{ route('admin-coil.locations.store') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-[160px_1fr_120px_auto] sm:items-end">
                @csrf
                <div>
                    <label class="field-label">{{ __('Kode Lokasi') }}</label>
                    <input type="text" name="kode" value="{{ old('kode') }}" maxlength="50" required placeholder="{{ __('Contoh: AL') }}" class="field w-full uppercase">
                    @error('kode')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label">{{ __('Nama Lokasi') }} <span class="font-normal text-slate-400">{{ __('(opsional)') }}</span></label>
                    <input type="text" name="nama" value="{{ old('nama') }}" maxlength="100" placeholder="{{ __('Keterangan lokasi') }}" class="field w-full">
                </div>
                <div>
                    <label class="field-label">{{ __('Urutan') }}</label>
                    <input type="number" name="urutan" value="{{ old('urutan', $nextUrutan) }}" min="0" max="9999" class="field w-full">
                </div>
                <button type="submit" class="btn btn--primary whitespace-nowrap">{{ __('Tambah') }}</button>
            </form>
        </div>

        {{-- Master table --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Kode') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Nama Lokasi') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Urutan') }}</th>
                            <th class="hidden px-4 py-3 text-center font-semibold text-slate-600 md:table-cell">{{ __('Dipakai') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">{{ __('Aktif') }}</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600"><span class="sr-only">{{ __('Aksi') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($locations as $location)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-md bg-corpblue-50 px-2.5 py-0.5 text-xs font-mono font-semibold text-corpblue-700">{{ $location->kode }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span data-edit-view="{{ $location->id }}" class="text-slate-700">{{ $location->nama ?: '—' }}</span>
                                    <input form="loc-{{ $location->id }}" data-edit-input="{{ $location->id }}" type="text" name="nama" value="{{ $location->nama }}" maxlength="100" placeholder="—" class="field field--sm hidden w-full min-w-[10rem]">
                                </td>
                                <td class="px-4 py-3">
                                    <span data-edit-view="{{ $location->id }}" class="text-slate-700">{{ $location->urutan }}</span>
                                    <input form="loc-{{ $location->id }}" data-edit-input="{{ $location->id }}" type="number" name="urutan" value="{{ $location->urutan }}" min="0" max="9999" class="field field--sm hidden w-24">
                                </td>
                                <td class="hidden px-4 py-3 text-center text-slate-700 md:table-cell">{{ number_format($usage[$location->kode] ?? 0) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span data-edit-view="{{ $location->id }}">
                                        @if ($location->is_active)
                                            <span class="status-badge status-badge--success">{{ __('Aktif') }}</span>
                                        @else
                                            <span class="status-badge status-badge--neutral">{{ __('Nonaktif') }}</span>
                                        @endif
                                    </span>
                                    <input type="hidden" form="loc-{{ $location->id }}" name="is_active" value="0">
                                    <input form="loc-{{ $location->id }}" data-edit-input="{{ $location->id }}" type="checkbox" name="is_active" value="1" @checked($location->is_active) class="hidden h-4 w-4 rounded border-slate-300 text-corpblue-600 focus:ring-corpblue-500 cursor-pointer">
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                        <button type="button" data-edit-start="{{ $location->id }}" onclick="coilLocationRowEdit('{{ $location->id }}', true)" class="action-link action-link--primary">{{ __('Ubah') }}</button>
                                        <span data-edit-end="{{ $location->id }}" class="hidden">
                                            <button type="submit" form="loc-{{ $location->id }}" class="action-link action-link--primary">{{ __('Simpan') }}</button>
                                            <button type="button" onclick="coilLocationRowCancel('{{ $location->id }}')" class="action-link action-link--neutral ml-1.5">{{ __('Batal') }}</button>
                                        </span>
                                        <form method="POST" action="{{ route('admin-coil.locations.destroy', $location) }}" data-confirm="{{ __('Hapus lokasi :kode?', ['kode' => $location->kode]) }}" data-confirm-title="{{ __('Hapus Lokasi') }}" data-confirm-tone="danger" data-confirm-button="{{ __('Hapus') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-link action-link--danger">{{ __('Hapus') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">{{ __('Belum ada lokasi terdaftar.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Inline edit forms, kept outside the table so the markup stays valid. --}}
        @foreach ($locations as $location)
            <form id="loc-{{ $location->id }}" method="POST" action="{{ route('admin-coil.locations.update', $location) }}" class="hidden">
                @csrf
                @method('PUT')
            </form>
        @endforeach

        {{-- Progressive disclosure: input baris baru muncul setelah tombol "Ubah" ditekan. --}}
        <script>
            function coilLocationRowEdit(id, editing) {
                document.querySelectorAll('[data-edit-input="' + id + '"]').forEach(function (input) {
                    input.classList.toggle('hidden', !editing);
                });
                document.querySelectorAll('[data-edit-view="' + id + '"]').forEach(function (view) {
                    view.classList.toggle('hidden', editing);
                });
                document.querySelectorAll('[data-edit-start="' + id + '"]').forEach(function (button) {
                    button.classList.toggle('hidden', editing);
                });
                document.querySelectorAll('[data-edit-end="' + id + '"]').forEach(function (actions) {
                    actions.classList.toggle('hidden', !editing);
                });
            }

            function coilLocationRowCancel(id) {
                var form = document.getElementById('loc-' + id);
                if (form) {
                    form.reset();
                }
                coilLocationRowEdit(id, false);
            }
        </script>

        <p class="text-xs text-slate-400">{{ __('Lokasi nonaktif tidak muncul sebagai saran di checker, tetapi coil yang memakainya tetap tersimpan.') }}</p>
    </div>
</x-layout>
