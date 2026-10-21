<x-layout :title="__('Preview Import Lokasi')" :headerTitle="__('Preview Import Lokasi Coil')">
    <div class="mx-auto max-w-4xl space-y-6">

        <a href="{{ route('admin-coil.locations.import') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 font-medium">
            <x-icon name="chevron-left" :size="16" />
            {{ __('Kembali') }}
        </a>

        {{-- Summary --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Ringkasan Import') }}</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ count($preview) }} {{ __('baris') }} &middot; {{ $skipped }} {{ __('baris dilewati') }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="status-badge status-badge--info">{{ $counts['update'] }} {{ __('akan diperbarui') }}</span>
                    <span class="status-badge status-badge--neutral">{{ $counts['unchanged'] }} {{ __('tanpa perubahan') }}</span>
                    @if ($counts['not_found'] > 0)
                        <span class="status-badge status-badge--danger">{{ $counts['not_found'] }} {{ __('tidak ditemukan') }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Preview table --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">#</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Nomor Coil') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Lokasi Lama') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Lokasi Baru') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($preview as $index => $row)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2.5 text-slate-400">{{ $index + 1 }}</td>
                                <td class="px-4 py-2.5 font-medium text-slate-900">{{ $row['nomor_coil'] }}</td>
                                <td class="px-4 py-2.5 text-slate-600">{{ $row['current_location'] ?: '—' }}</td>
                                <td class="px-4 py-2.5"><span class="status-badge status-badge--success">{{ $row['lokasi'] }}</span></td>
                                <td class="px-4 py-2.5">
                                    @if ($row['status'] === 'update')
                                        <span class="status-badge status-badge--info">{{ __('Diperbarui') }}</span>
                                    @elseif ($row['status'] === 'unchanged')
                                        <span class="status-badge status-badge--neutral">{{ __('Sama') }}</span>
                                    @else
                                        <span class="status-badge status-badge--danger">{{ __('Tidak ditemukan') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-400">{{ __('Tidak ada baris valid untuk diimpor.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Skipped rows detail --}}
        @if (! empty($skippedRows))
            <div class="bg-white rounded-xl border border-amber-200 overflow-hidden">
                <div class="border-b border-amber-100 bg-amber-50 px-4 py-3">
                    <h3 class="text-sm font-semibold text-amber-800">{{ __('Baris dilewati') }}</h3>
                    <p class="text-xs text-amber-700 mt-0.5">{{ __('Baris tanpa Nomor Coil atau Lokasi tidak diproses.') }} @if ($skipped > count($skippedRows)) {{ __('Menampilkan :shown dari :total baris.', ['shown' => count($skippedRows), 'total' => $skipped]) }} @endif</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50">
                                <th class="px-4 py-2.5 text-left font-semibold text-slate-600">{{ __('Baris') }}</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-slate-600">{{ __('Nomor Coil') }}</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-slate-600">{{ __('Alasan') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($skippedRows as $skippedRow)
                                <tr>
                                    <td class="px-4 py-2.5 text-slate-400">{{ $skippedRow['row'] }}</td>
                                    <td class="px-4 py-2.5 text-slate-600">{{ $skippedRow['nomor_coil'] ?: '—' }}</td>
                                    <td class="px-4 py-2.5 text-slate-500">{{ $skippedRow['reason'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Confirm import --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <form method="POST" action="{{ route('admin-coil.locations.import.execute') }}" data-confirm="{{ __('Konfirmasi import :count baris lokasi?', ['count' => count($preview)]) }}" data-confirm-title="{{ __('Import Lokasi') }}" data-confirm-tone="info" data-confirm-button="{{ __('Import Sekarang') }}" class="space-y-4">
                @csrf
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-600">{{ __(':count baris akan diproses', ['count' => count($preview)]) }}</p>
                    <div class="flex gap-2">
                        <a href="{{ route('admin-coil.locations.import') }}" class="btn btn--ghost">{{ __('Batal') }}</a>
                        <button type="submit" class="btn btn--success" @disabled(count($preview) === 0)>
                            {{ __('Konfirmasi Import') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layout>
