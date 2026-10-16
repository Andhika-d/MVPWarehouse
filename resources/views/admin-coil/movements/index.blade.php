<x-layout :title="__('Pergerakan Coil')" :headerTitle="__('Pergerakan Coil')">
    <div class="space-y-6">

        {{-- Intro --}}
        <p class="text-sm text-slate-500">{{ __('Gabungan riwayat scan dan perpindahan lokasi seluruh coil.') }}</p>

        {{-- Filters --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form id="movementFilters" method="GET" action="{{ route('admin-coil.movements.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ $filters['search'] }}" aria-label="{{ __('Pencarian') }}" placeholder="{{ __('Nomor coil, tag, grade, company') }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                </div>
                <select name="type" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    <option value="">{{ __('Semua jenis') }}</option>
                    <option value="scan" @selected($filters['type'] === 'scan')>{{ __('Scan') }}</option>
                    <option value="lokasi" @selected($filters['type'] === 'lokasi')>{{ __('Pindah Lokasi') }}</option>
                </select>
                <select name="location" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    <option value="">{{ __('Semua lokasi') }}</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location['value'] }}" @selected($filters['location'] === $location['value'])>{{ $location['label'] }}</option>
                    @endforeach
                </select>
                <x-period-filter-button :period="$period" />
                @if ($filters['search'] !== '' || $filters['location'] !== '' || $filters['type'] !== '' || $period)
                    <a href="{{ route('admin-coil.movements.index') }}" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-sm font-medium">{{ __('Reset') }}</a>
                @endif
                <button type="submit" class="btn btn--primary whitespace-nowrap">{{ __('Filter') }}</button>
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
                                        <span class="inline-flex items-center rounded-full bg-corpblue-50 px-2.5 py-0.5 text-xs font-semibold text-corpblue-700">{{ __('Scan') }}</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">{{ __('Pindah Lokasi') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-900 whitespace-nowrap">{{ $event->nomor_coil }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $event->company ?: '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    @if ($event->event_type === 'scan')
                                        <div class="space-y-1">
                                            <pre class="max-w-xs overflow-x-auto rounded bg-slate-50 px-2 py-1 text-[11px] text-slate-600">{{ $event->raw_kp }}</pre>
                                            <pre class="max-w-xs overflow-x-auto rounded bg-slate-50 px-2 py-1 text-[11px] text-slate-600">{{ $event->raw_taehang }}</pre>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-xs">
                                            <span class="font-semibold text-slate-700">{{ $event->from_location ?: __('Tanpa Lokasi') }}</span>
                                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-slate-400"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
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
