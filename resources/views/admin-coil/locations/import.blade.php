<x-layout :title="__('Import Lokasi')" :headerTitle="__('Import Lokasi Coil')">
    <div class="mx-auto max-w-2xl space-y-6">

        <a href="{{ route('admin-coil.coil-tags.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 font-medium">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            {{ __('Kembali') }}
        </a>

        {{-- Master info --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Lokasi Terdaftar') }}</h3>
                <a href="{{ route('admin-coil.locations.index') }}" class="action-link action-link--primary">{{ __('Daftar Lokasi') }}</a>
            </div>
            <div class="flex flex-wrap gap-2">
                @forelse ($locations as $location)
                    <span class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm font-semibold text-slate-700">{{ $location }}</span>
                @empty
                    <span class="text-sm text-slate-400">{{ __('Belum ada lokasi terdaftar.') }}</span>
                @endforelse
            </div>
            <p class="text-[11px] text-slate-400 mt-3">{{ __('Kode lokasi baru di dalam file akan otomatis ditambahkan ke daftar.') }}</p>
        </div>

        {{-- Import form --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <form method="POST" action="{{ route('admin-coil.locations.import.preview') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('File Excel (.xlsx)') }}</label>
                    <input type="file" name="file" accept=".xlsx" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-corpblue-50 file:text-corpblue-600 hover:file:bg-corpblue-100 file:cursor-pointer">
                    <p class="text-xs text-slate-400 mt-1">{{ __('Format kolom: Nomor Coil | Lokasi') }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ __('File bersifat penambahan (incremental): hanya baris yang ada di file yang diperbarui, lokasi lain tidak diubah.') }}</p>
                </div>

                <button type="submit" class="w-full px-4 py-2.5 bg-corpblue-500 hover:bg-corpblue-600 text-white rounded-lg text-sm font-semibold transition-colors cursor-pointer">
                    {{ __('Preview Data') }}
                </button>
            </form>
        </div>
    </div>
</x-layout>
