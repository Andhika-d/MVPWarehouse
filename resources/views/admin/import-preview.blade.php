<x-layout :title="'Preview Import — MVPWarehouse'" :headerTitle="'Preview Import Barang'">
    <div class="max-w-4xl mx-auto space-y-6">

        <a href="{{ route('admin.import.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 font-medium">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Kembali
        </a>

        {{-- Summary --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Ringkasan Import</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Rak {{ $rack }} &middot; {{ count($preview) }} baris &middot; {{ $skipped }} baris dilewati</p>
                </div>
                <div class="flex items-center gap-2">
                    @if($existingEmpty > 0)
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">{{ $existingEmpty }} slot kosong dipakai</span>
                    @endif
                    @if($newSlotsNeeded > 0)
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold">+{{ $newSlotsNeeded }} slot baru dibuat</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Preview Table --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">#</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Kode Tag</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Sub Lokasi</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Nama Barang</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Size</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Qty</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Satuan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($preview as $index => $row)
                        <tr class="{{ $row['is_empty_location'] ?? false ? 'bg-slate-50/50' : 'hover:bg-slate-50' }}">
                            <td class="px-4 py-2.5 text-slate-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-2.5">
                                @php $isNew = $index >= $existingEmpty; @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md {{ $isNew ? 'bg-amber-50 text-amber-700' : 'bg-corpblue-50 text-corpblue-700' }} text-xs font-mono font-semibold whitespace-nowrap">{{ $locationCodes[$index] }}</span>
                                @if($isNew)
                                <span class="text-[10px] text-amber-500 ml-1">baru</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5">
                                @if($row['sub_location'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-xs font-mono font-semibold">{{ $row['sub_location'] }}</span>
                                @else
                                <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 font-medium {{ ($row['is_empty_location'] ?? false) ? 'text-slate-400 italic' : 'text-slate-900' }}">
                                {{ $row['name'] ?: '— Lokasi Kosong —' }}
                            </td>
                            <td class="px-4 py-2.5 text-slate-600">{{ $row['size'] ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold {{ ($row['is_empty_location'] ?? false) ? 'text-slate-400' : 'text-slate-900' }}">{{ $row['stock'] }}</td>
                            <td class="px-4 py-2.5 text-slate-600">{{ $row['unit'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Confirm Import --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <form method="POST" action="{{ route('admin.import.execute') }}" data-confirm="Konfirmasi import {{ count($preview) }} baris ke Rak {{ $rack }}?" data-confirm-title="Import Barang" data-confirm-tone="info" data-confirm-button="Import Sekarang" class="space-y-4">
                @csrf

                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-600">{{ count($preview) }} baris akan diimpor ke Rak {{ $rack }}</p>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.import.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">Batal</a>
                        <button type="submit" class="px-6 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-sm font-semibold transition-colors cursor-pointer">
                            Konfirmasi Import
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>
</x-layout>
