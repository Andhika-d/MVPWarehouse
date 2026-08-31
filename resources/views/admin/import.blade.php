<x-layout :title="'Import Barang — MVPWarehouse'" :headerTitle="'Import Barang dari Excel'">
    <div class="max-w-2xl mx-auto space-y-6">

        <a href="{{ route('admin.items.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 font-medium">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Kembali ke Master Barang
        </a>

        {{-- Rack Info --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-sm font-semibold text-slate-900 mb-3">Info Rak</h3>
            <div class="grid grid-cols-5 gap-3">
                @foreach($rackInfo as $rack)
                <div class="text-center p-3 rounded-lg bg-slate-50 border border-slate-200">
                    <p class="text-lg font-bold text-slate-900">{{ $rack['prefix'] }}</p>
                    <p class="text-xs text-slate-500">{{ $rack['occupied'] }}/{{ $rack['total'] }} terisi</p>
                </div>
                @endforeach
            </div>
            <p class="text-[11px] text-slate-400 mt-3">Slot baru akan dibuat otomatis jika diperlukan.</p>
        </div>

        {{-- Import Form --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <form method="POST" action="{{ route('admin.import.preview') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Rak Tujuan</label>
                    <select name="rack_location" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                        <option value="">Pilih Rak</option>
                        @foreach($rackInfo as $rack)
                        <option value="{{ $rack['rack'] }}">
                            Rak {{ $rack['rack'] }} ({{ $rack['prefix'] }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">File Excel (.xlsx)</label>
                    <input type="file" name="file" accept=".xlsx" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-corpblue-50 file:text-corpblue-600 hover:file:bg-corpblue-100 file:cursor-pointer">
                    <p class="text-xs text-slate-400 mt-1">Format: Nama Barang | Size | Qty | Sub Lokasi</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Baris kosong (lokasi tanpa barang) tetap akan dibuat jika kolom Sub Lokasi terisi.</p>
                </div>

                <button type="submit" class="w-full px-4 py-2.5 bg-corpblue-500 hover:bg-corpblue-600 text-white rounded-lg text-sm font-semibold transition-colors cursor-pointer">
                    Preview Data
                </button>
            </form>
        </div>

    </div>
</x-layout>
