<x-layout :title="__('Admin Coil — Coil Tags')" :headerTitle="__('Data Coil Tag')">
    <div class="space-y-6">

        {{-- Toolbar / Filter --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form method="GET" action="{{ route('admin-coil.coil-tags.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
                <div class="lg:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Pencarian') }}</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="{{ __('Nomor coil, tag, grade, lokasi') }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Tanggal') }}</label>
                    <select name="date_filter" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                        <option value="all" @selected($filters['date_filter'] === 'all')>{{ __('Semua') }}</option>
                        <option value="today" @selected($filters['date_filter'] === 'today')>{{ __('Hari ini') }}</option>
                        <option value="custom" @selected($filters['date_filter'] === 'custom')>{{ __('Rentang') }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Dari') }}</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Sampai') }}</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Lokasi') }}</label>
                    <select name="location" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                        <option value="">{{ __('Semua lokasi') }}</option>
                        @foreach (['AL', 'AR', 'BL', 'BR', 'CL', 'CR'] as $location)
                            <option value="{{ $location }}" @selected($filters['location'] === $location)>{{ $location }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-6">
                    <button type="submit" class="btn btn--primary">{{ __('Filter') }}</button>
                    <a href="{{ route('admin-coil.coil-tags.index') }}" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>

        {{-- Output bar --}}
        <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-600">
                <span class="font-semibold text-slate-900">{{ $coilTags->total() }}</span> {{ __('data') }}
                <span class="text-slate-400">·</span>
                <span class="text-xs text-slate-400">{{ $filterDescription }}</span>
            </p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin-coil.coil-tags.export', request()->query()) }}" class="btn btn--secondary">{{ __('Export Excel') }}</a>
                <a href="{{ route('admin-coil.coil-tags.print', request()->query()) }}" target="_blank" rel="noopener" class="btn btn--secondary">{{ __('Cetak') }}</a>
                <a href="{{ route('admin-coil.coil-tags.pdf', request()->query()) }}" class="btn btn--primary">{{ __('Unduh PDF') }}</a>
            </div>
        </div>

        {{-- Summary --}}
        <dl class="grid grid-cols-2 gap-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Total Qty') }}</dt>
                <dd class="mt-1 text-xl font-bold text-slate-900">{{ $totalQuantity }}</dd>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Total Net WT/Berat Bersih') }}</dt>
                <dd class="mt-1 text-xl font-bold text-slate-900">{{ $totalWeight }} kg</dd>
            </div>
        </dl>

        {{-- Table --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-3 py-3 text-left font-semibold text-slate-600">No.</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-600">{{ __('Company') }}</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-600">{{ __('Tag Taehang') }}</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-600">{{ __('Nomor Coil') }}</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-600">{{ __('Spec') }}</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-600">{{ __('Tebal') }}</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-600">{{ __('Lebar') }}</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-600">{{ __('Net WT/Berat Bersih') }}</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-600">{{ __('Qty.') }}</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-600">{{ __('Lokasi') }}</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-600">{{ __('Scanned At') }}</th>
                            <th class="px-3 py-3 text-center font-semibold text-slate-600"><span class="sr-only">{{ __('Aksi') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($coilTags as $coilTag)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-3 py-3 text-slate-500">{{ $coilTags->firstItem() + $loop->index }}</td>
                                <td class="px-3 py-3 text-slate-700">{{ $coilTag->company ?: '-' }}</td>
                                <td class="px-3 py-3 font-semibold text-corpblue-600">{{ $coilTag->tag_taehang }}</td>
                                <td class="px-3 py-3 font-medium text-slate-900">{{ $coilTag->nomor_coil }}</td>
                                <td class="px-3 py-3 text-slate-700">{{ $coilTag->grade }}</td>
                                <td class="px-3 py-3 text-slate-700">{{ rtrim(rtrim((string) $coilTag->tebal, '0'), '.') }}</td>
                                <td class="px-3 py-3 text-slate-700">{{ rtrim(rtrim((string) $coilTag->lebar, '0'), '.') }}</td>
                                <td class="px-3 py-3 text-slate-700">{{ $coilTag->beratAsScanned() }}</td>
                                <td class="px-3 py-3 text-slate-700">1</td>
                                <td class="px-3 py-3 text-slate-700">{{ $coilTag->lokasi_coil ?: '-' }}</td>
                                <td class="px-3 py-3 text-slate-500">{{ $coilTag->scanned_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-3 py-3 text-center">
                                    <a href="{{ route('admin-coil.coil-tags.show', $coilTag) }}" class="action-link action-link--primary">{{ __('Detail') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-4 py-12 text-center text-sm text-slate-500">{{ __('Tidak ada data untuk filter ini.') }}</td>
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
</x-layout>
