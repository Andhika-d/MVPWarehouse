<x-layout :title="__('Pergerakan Coil')" :headerTitle="__('Pergerakan Coil')">
    <div class="space-y-6">

        {{-- Intro --}}
        <p class="text-sm text-slate-500">{{ __('Gabungan riwayat scan dan perpindahan lokasi seluruh coil.') }}</p>

        {{-- Type chips --}}
        <div class="flex gap-2 overflow-x-auto pb-1">
            @php
                $typeChips = [
                    '' => __('Semua jenis'),
                    'scan' => __('Scan'),
                    'lokasi' => __('Pindah Lokasi'),
                ];
            @endphp
            @foreach ($typeChips as $value => $label)
                <x-filter-chip
                    :href="route('admin-coil.movements.index', array_merge(request()->except('type', 'page'), $value === '' ? [] : ['type' => $value]))"
                    :active="$filters['type'] === $value"
                    :label="$label"
                    :count="$counts[$value === '' ? 'all' : $value]"
                />
            @endforeach
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form id="movementFilters" method="GET" action="{{ route('admin-coil.movements.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ $filters['search'] }}" aria-label="{{ __('Pencarian') }}" placeholder="{{ __('Nomor coil, tag, grade, company') }}" class="field w-full">
                </div>
                <input type="hidden" name="type" value="{{ $filters['type'] }}">
                <select name="location" class="field">
                    <option value="">{{ __('Semua lokasi') }}</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location['value'] }}" @selected($filters['location'] === $location['value'])>{{ $location['label'] }}</option>
                    @endforeach
                </select>
                <x-period-filter-button :period="$period" />
                @if ($filters['search'] !== '' || $filters['location'] !== '' || $filters['type'] !== '' || $period)
                    <a href="{{ route('admin-coil.movements.index') }}" class="btn btn--ghost whitespace-nowrap">{{ __('Reset') }}</a>
                @endif
                <button type="submit" class="btn btn--primary whitespace-nowrap">{{ __('Filter') }}</button>
                <a href="{{ route('admin-coil.movements.export', request()->query()) }}" class="btn btn--secondary whitespace-nowrap lg:ml-auto">{{ __('Export Excel') }}</a>
            </form>
        </div>

        {{-- Result caption --}}
        <p class="text-xs text-slate-400">
            <span class="font-semibold text-slate-600">{{ $events->total() }}</span> {{ __('data') }}
        </p>

        {{-- Timeline table --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Waktu') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Jenis') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Nomor Coil') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Company') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Detail') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Lokasi') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Oleh') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600"><span class="sr-only">{{ __('Aksi') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($events as $event)
                            <tr class="hover:bg-slate-50 transition-colors align-top">
                                <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                                    {{ $event->occurred_at ? \Illuminate\Support\Carbon::parse($event->occurred_at)->format('Y-m-d H:i') : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($event->event_type === 'scan')
                                        <span class="status-badge status-badge--info">{{ __('Scan') }}</span>
                                    @else
                                        <span class="status-badge status-badge--warning">{{ __('Pindah Lokasi') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-900 whitespace-nowrap">{{ $event->nomor_coil }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $event->company ?: '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    @if ($event->event_type === 'scan')
                                        <div class="max-w-xs space-y-0.5 font-mono text-[11px] text-slate-500">
                                            <p class="truncate" title="{{ $event->raw_kp }}">{{ $event->raw_kp }}</p>
                                            <p class="truncate" title="{{ $event->raw_taehang }}">{{ $event->raw_taehang }}</p>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-xs">
                                            <span class="font-semibold text-slate-700">{{ $event->from_location ?: __('Tanpa Lokasi') }}</span>
                                            <x-icon name="arrow-right" :size="12" class="text-slate-400" />
                                            <span class="font-semibold text-corpblue-700">{{ $event->to_location ?: __('Tanpa Lokasi') }}</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $event->current_location ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ $event->actor_name ?? '—' }}</td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <a href="{{ route('admin-coil.coil-tags.show', $event->coil_tag_id) }}" class="action-link action-link--primary">{{ __('Detail') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400">{{ __('Tidak ada pergerakan untuk filter ini.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($events->hasPages())
                <div class="px-4 py-3 border-t border-slate-100">{{ $events->links() }}</div>
            @endif
        </div>
    </div>

    <x-slot:modals>
        <x-period-filter-modal :period="$period" form-id="movementFilters" />
    </x-slot:modals>
</x-layout>
