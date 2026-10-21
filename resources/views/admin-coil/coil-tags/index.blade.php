<x-layout :title="__('Admin Coil — Coil Tags')" :headerTitle="__('Data Coil Tag')">
    <div class="space-y-6">

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card icon="cube" :label="__('Total Qty')" :value="number_format($summary['total_qty'])" />
            <x-stat-card icon="scale" accent :label="__('Total Net WT/Berat Bersih')" :value="number_format($summary['total_weight'], 3, '.', '')" />
            <x-stat-card icon="calendar" tone="amber" :label="__('Scan Hari Ini')" :value="number_format($summary['today'])" />
            <x-stat-card icon="pin" tone="emerald" :label="__('Lokasi')" :value="number_format($summary['locations'])" />
        </div>

        {{-- Location pills --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-1">
            <x-filter-chip
                :href="route('admin-coil.coil-tags.index', request()->except('location'))"
                :active="$filters['location'] === ''"
                :label="__('Semua')"
            />
            @foreach($locationData as $locationRow)
                <x-filter-chip
                    :href="route('admin-coil.coil-tags.index', array_merge(request()->except('location'), ['location' => $locationRow['location']]))"
                    :active="$filters['location'] === $locationRow['location']"
                    :label="$locationRow['label']"
                    :count="$locationRow['total']"
                    :bold="true"
                    :uppercase="$locationRow['location'] !== \App\Models\CoilLocation::FILTER_NONE"
                />
            @endforeach
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form id="coilTagFilters" method="GET" action="{{ route('admin-coil.coil-tags.index') }}" data-auto-filter class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <input type="hidden" name="location" value="{{ $filters['location'] }}">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ $filters['search'] }}" aria-label="{{ __('Pencarian') }}" placeholder="{{ __('Nomor coil, tag, grade, lokasi') }}" class="field w-full">
                </div>
                <x-period-filter-button :period="$period" />
                @if($filters['search'] !== '' || $filters['location'] !== '' || $period)
                <a href="{{ route('admin-coil.coil-tags.index') }}" class="btn btn--ghost whitespace-nowrap">{{ __('Reset') }}</a>
                @endif
                <button type="submit" class="btn btn--primary whitespace-nowrap">{{ __('Filter') }}</button>
                <div class="flex flex-wrap items-center gap-2 lg:ml-auto">
                    <a href="{{ route('admin-coil.locations.import') }}" class="btn btn--ghost whitespace-nowrap">
                        <x-icon name="tray-down" :size="16" />
                        {{ __('Import Lokasi') }}
                    </a>
                    <a href="{{ route('admin-coil.coil-tags.export', request()->query()) }}" class="btn btn--secondary whitespace-nowrap">{{ __('Export Excel') }}</a>
                    <a href="{{ route('admin-coil.coil-tags.print', request()->query()) }}" class="btn btn--secondary whitespace-nowrap">{{ __('Cetak') }}</a>
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
                                <td class="px-4 py-3">
                                    @if ($coilTag->lokasi_coil)
                                        <span class="status-badge status-badge--success">{{ $coilTag->lokasi_coil }}</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
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
