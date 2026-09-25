<x-print-shell
    :title="$title"
    :orientation="$orientation"
    :styles="'prints.styles.requests'"
    :show-toolbar="$showToolbar ?? false"
    :back-url="$backUrl ?? null"
>
    <main class="sheet">
        @include('prints.document-header', [
            'title' => $title,
            'subtitle' => 'THI2-WAREHOUSE · Dokumen rekap permintaan barang',
            'printedAt' => $printedAt,
            'printedBy' => $printedBy,
            'printedRole' => $printedRole,
        ])

        <div class="filters">
            <span><strong>Periode:</strong> {{ $periodLabel }}</span>
            <span><strong>Baris:</strong> {{ count($rows) }}</span>
            @foreach($filters as $label => $value)<span><strong>{{ $label }}:</strong> {{ $value }}</span>@endforeach
        </div>

        @if($orientation === 'portrait')
        <table class="data request-table--portrait">
            <thead>
                <tr>
                    <th>No.</th><th>Waktu</th><th>Barang</th><th>Pemohon</th><th>Jumlah</th><th>Prioritas</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $index => $row)
                <tr>
                    <td><span class="cell-primary">{{ $index + 1 }}</span></td>
                    <td><span class="cell-meta">{{ $row['tanggal'] ?? '—' }}</span></td>
                    <td><span class="cell-primary">{{ $row['barang'] ?? '—' }}</span></td>
                    <td>{{ $row['pemohon'] ?? '—' }}</td>
                    <td><span class="cell-primary">{{ $row['jumlah'] ?? '—' }}</span><span class="cell-meta">{{ $row['satuan'] ?? '—' }}</span></td>
                    <td>{{ $row['prioritas'] ?? '—' }}</td>
                    <td><span class="status">{{ $row['status'] ?? '—' }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <table class="data">
            <thead>
                <tr>
                    <th>No.</th><th>Waktu</th><th>Barang</th><th>Pemohon</th><th class="number">Jumlah</th><th>Satuan</th><th>Prioritas</th><th>Status</th><th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['tanggal'] ?? '—' }}</td>
                    <td>{{ $row['barang'] ?? '—' }}</td>
                    <td>{{ $row['pemohon'] ?? '—' }}</td>
                    <td class="number">{{ $row['jumlah'] ?? '—' }}</td>
                    <td>{{ $row['satuan'] ?? '—' }}</td>
                    <td>{{ $row['prioritas'] ?? '—' }}</td>
                    <td><span class="status">{{ $row['status'] ?? '—' }}</span></td>
                    <td>{{ $row['catatan'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <footer class="doc-footer">THI2-WAREHOUSE · Dokumen rekap otomatis dari data permintaan barang.</footer>
    </main>
</x-print-shell>