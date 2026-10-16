<x-layout :title="__('Preview Import Lokasi')" :headerTitle="__('Preview Import Lokasi Coil')">
    <div class="mx-auto max-w-4xl space-y-6">

        <a href="{{ route('admin-coil.locations.import') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 font-medium">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
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
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-corpblue-50 text-corpblue-700 text-xs font-semibold">{{ $counts['update'] }} {{ __('akan diperbarui') }}</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold">{{ $counts['unchanged'] }} {{ __('tanpa perubahan') }}</span>
                    @if ($counts['not_found'] > 0)
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-red-50 text-red-600 text-xs font-semibold">{{ $counts['not_found'] }} {{ __('tidak ditemukan') }}</span>
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
                                <td class="px-4 py-2.5"><span class="inline-flex items-center px-2 py-0.5 rounded-md bg-corpblue-50 text-corpblue-700 text-xs font-mono font-semibold">{{ $row['lokasi'] }}</span></td>
                                <td class="px-4 py-2.5">
                                    @if ($row['status'] === 'update')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-corpblue-50 text-corpblue-700 text-xs font-semibold">{{ __('Diperbarui') }}</span>
                                    @elseif ($row['status'] === 'unchanged')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold">{{ __('Sama') }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-red-50 text-red-600 text-xs font-semibold">{{ __('Tidak ditemukan') }}</span>
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

        {{-- Confirm import --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <form method="POST" action="{{ route('admin-coil.locations.import.execute') }}" data-confirm="{{ __('Konfirmasi import :count baris lokasi?', ['count' => count($preview)]) }}" data-confirm-title="{{ __('Import Lokasi') }}" data-confirm-tone="info" data-confirm-button="{{ __('Import Sekarang') }}" class="space-y-4">
                @csrf
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-600">{{ __(':count baris akan diproses', ['count' => count($preview)]) }}</p>
                    <div class="flex gap-2">
                        <a href="{{ route('admin-coil.locations.import') }}" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">{{ __('Batal') }}</a>
                        <button type="submit" class="px-6 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-sm font-semibold transition-colors cursor-pointer" @disabled(count($preview) === 0)>
                            {{ __('Konfirmasi Import') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layout>
