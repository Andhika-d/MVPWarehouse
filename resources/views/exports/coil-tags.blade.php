<x-print-shell
    :title="$title"
    :orientation="$orientation"
    :styles="'prints.styles.coil'"
    :show-toolbar="$showToolbar ?? false"
    :back-url="$backUrl ?? null"
>
    <main class="sheet">
        @include('prints.document-header', [
            'title' => $title,
            'subtitle' => __('THI2-WAREHOUSE · Dokumen rekap coil tagging'),
            'printedAt' => $printedAt,
            'printedBy' => $printedBy,
            'printedRole' => $printedRole,
        ])

        <div class="filters">
            <span><strong>{{ __('Periode') }}:</strong> {{ $periodLabel }}</span>
            <span><strong>{{ __('Qty') }}:</strong> {{ $coilTags->count() }}</span>
            <span><strong>{{ __('Berat Bersih') }}:</strong> {{ number_format((float) $coilTags->sum('berat'), 3, '.', '') }} kg</span>
            @foreach($filters as $label => $value)<span><strong>{{ $label }}:</strong> {{ $value }}</span>@endforeach
        </div>

        <table class="data {{ $orientation === 'portrait' ? 'coil-table--portrait' : '' }}">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>{{ __('Company') }}</th>
                    <th>{{ __('Tag Taehang') }}</th>
                    <th>{{ __('Nomor Coil') }}</th>
                    <th>{{ __('Spec') }}</th>
                    <th>{{ __('Tebal') }}</th>
                    <th>{{ __('Lebar') }}</th>
                    <th>{{ __('Net WT/Berat Bersih') }}</th>
                    <th>{{ __('Qty.') }}</th>
                    <th>{{ __('Lokasi') }}</th>
                    <th>{{ __('Scanned At') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($coilTags as $coilTag)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $coilTag->company ?: '-' }}</td>
                        <td>{{ $coilTag->tag_taehang }}</td>
                        <td>{{ $coilTag->nomor_coil }}</td>
                        <td>{{ $coilTag->grade }}</td>
                        <td class="number">{{ rtrim(rtrim((string) $coilTag->tebal, '0'), '.') }}</td>
                        <td class="number">{{ rtrim(rtrim((string) $coilTag->lebar, '0'), '.') }}</td>
                        <td class="number">{{ $coilTag->beratAsScanned() }}</td>
                        <td class="number">1</td>
                        <td>{{ $coilTag->lokasi_coil ?: '-' }}</td>
                        <td>{{ $coilTag->scanned_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="empty">{{ __('Tidak ada data untuk filter ini.') }}</td></tr>
                @endforelse
            </tbody>
        </table>

        <footer class="doc-footer">{{ __('THI2-WAREHOUSE · Dokumen rekap otomatis dari data coil tagging.') }}</footer>
    </main>
</x-print-shell>
