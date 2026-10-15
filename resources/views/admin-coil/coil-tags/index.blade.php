<x-layout :title="__('Admin Coil — Coil Tags')" :headerTitle="__('Data Coil Tag')">
    <div class="space-y-6">

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-corpblue-50 flex items-center justify-center shrink-0">
                    <svg class="text-corpblue-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div><p class="text-xl font-bold text-slate-900">{{ number_format($summary['total_qty']) }}</p><p class="text-xs text-slate-500">{{ __('Total Qty') }}</p></div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-corpblue-50 flex items-center justify-center shrink-0">
                    <svg class="text-corpblue-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                </div>
                <div><p class="text-xl font-bold text-corpblue-700">{{ number_format($summary['total_weight'], 3, '.', '') }}</p><p class="text-xs text-slate-500">{{ __('Total Net WT/Berat Bersih') }}</p></div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                    <svg class="text-amber-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div><p class="text-xl font-bold text-amber-600">{{ number_format($summary['today']) }}</p><p class="text-xs text-slate-500">{{ __('Scan Hari Ini') }}</p></div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                    <svg class="text-emerald-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div><p class="text-xl font-bold text-emerald-600">{{ number_format($summary['locations']) }}</p><p class="text-xs text-slate-500">{{ __('Lokasi') }}</p></div>
            </div>
        </div>

        {{-- Location pills --}}
        <div class="flex gap-2 overflow-x-auto pb-1">
            <a href="{{ route('admin-coil.coil-tags.index', request()->except('location')) }}" class="flex items-center gap-2 px-4 py-2.5 rounded-lg border text-sm font-medium whitespace-nowrap transition-all {{ $filters['location'] === '' ? 'bg-corpblue-50 border-corpblue-300 text-corpblue-700' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300' }}">{{ __('Semua') }}</a>
            @foreach($locationData as $locationRow)
            <a href="{{ route('admin-coil.coil-tags.index', array_merge(request()->except('location'), ['location' => $locationRow['location']])) }}" class="flex items-center gap-2 px-4 py-2.5 rounded-lg border text-sm font-medium whitespace-nowrap transition-all {{ $filters['location'] === $locationRow['location'] ? 'bg-corpblue-50 border-corpblue-300 text-corpblue-700' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300' }}"><span class="font-bold">{{ $locationRow['location'] }}</span><span class="text-xs {{ $filters['location'] === $locationRow['location'] ? 'text-corpblue-500' : 'text-slate-400' }}">{{ $locationRow['total'] }}</span></a>
            @endforeach
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form id="coilTagFilters" method="GET" action="{{ route('admin-coil.coil-tags.index') }}" data-auto-filter class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <input type="hidden" name="location" value="{{ $filters['location'] }}">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ $filters['search'] }}" aria-label="{{ __('Pencarian') }}" placeholder="{{ __('Nomor coil, tag, grade, lokasi') }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                </div>
                <x-period-filter-button :period="$period" />
                @if($filters['search'] !== '' || $filters['location'] !== '' || $period)
                <a href="{{ route('admin-coil.coil-tags.index') }}" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-sm font-medium">{{ __('Reset') }}</a>
                @endif
                <button type="submit" class="btn btn--primary whitespace-nowrap">{{ __('Filter') }}</button>
                <div class="flex flex-wrap gap-2 lg:ml-auto">
                    <a href="{{ route('admin-coil.coil-tags.export', request()->query()) }}" class="btn btn--secondary whitespace-nowrap">{{ __('Export Excel') }}</a>
                    <a href="{{ route('admin-coil.coil-tags.print', request()->query()) }}" target="_blank" rel="noopener" class="btn btn--secondary whitespace-nowrap">{{ __('Cetak') }}</a>
                    <a href="{{ route('admin-coil.coil-tags.pdf', request()->query()) }}" class="btn btn--primary whitespace-nowrap">{{ __('Unduh PDF') }}</a>
                </div>
            </form>
        </div>

        {{-- Result caption --}}
        <p class="text-xs text-slate-400">
            <span class="font-semibold text-slate-600">{{ $coilTags->total() }}</span> {{ __('data') }}
            <span class="text-slate-300">·</span>
            {{ $filterDescription }}
        </p>

        {{-- Table --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">No.</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Company') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Tag Taehang') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Nomor Coil') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Spec') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Tebal') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Lebar') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Net WT/Berat Bersih') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">{{ __('Qty.') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Lokasi') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Scanned At') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600"><span class="sr-only">{{ __('Aksi') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($coilTags as $coilTag)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 text-slate-500">{{ $coilTags->firstItem() + $loop->index }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $coilTag->company ?: '—' }}</td>
                                <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-md bg-corpblue-50 text-corpblue-700 text-xs font-mono font-semibold whitespace-nowrap">{{ $coilTag->tag_taehang }}</span></td>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $coilTag->nomor_coil }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $coilTag->grade }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ rtrim(rtrim((string) $coilTag->tebal, '0'), '.') }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ rtrim(rtrim((string) $coilTag->lebar, '0'), '.') }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $coilTag->beratAsScanned() }}</td>
                                <td class="px-4 py-3 text-center text-slate-700">1</td>
                                <td class="px-4 py-3 text-slate-700">{{ $coilTag->lokasi_coil ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ $coilTag->scanned_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('admin-coil.coil-tags.show', $coilTag) }}" class="action-link action-link--primary">{{ __('Detail') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-4 py-12 text-center text-sm text-slate-400">{{ __('Tidak ada data untuk filter ini.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($coilTags->hasPages())
                <div class="px-4 py-3 border-t border-slate-100">{{ $coilTags->links() }}</div>
            @endif
        </div>
    </div>

    <x-slot:modals>
        <x-period-filter-modal :period="$period" form-id="coilTagFilters" />
    </x-slot:modals>
</x-layout>
