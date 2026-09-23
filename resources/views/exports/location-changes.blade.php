<x-print-shell
    :title="$title"
    :orientation="$orientation"
    :styles="'prints.styles.locations'"
>
    <main class="sheet">
        @include('prints.document-header', [
            'title' => 'RIWAYAT PENGAJUAN LOKASI',
            'subtitle' => 'THI2-WAREHOUSE · Dokumen rekap pengajuan perubahan lokasi barang',
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
        <table class="data location-table--portrait">
            <thead>
                <tr>
                    <th>No.</th><th>Barang</th><th>Asal</th><th>Tujuan</th><th>Pemohon</th><th>Status</th><th>Aksi</th><th>Diajukan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $index => $row)
                <tr>
                    <td><span class="cell-primary">{{ $index + 1 }}</span></td>
                    <td><span class="cell-primary">{{ $row['barang'] ?? '—' }}</span></td>
                    <td><span class="cell-primary">{{ $row['kode_asal'] ?? '—' }}</span><span class="cell-meta">Sub: {{ $row['sub_asal'] ?? '—' }}</span></td>
                    <td><span class="cell-primary">{{ $row['kode_tujuan'] ?? '—' }}</span><span class="cell-meta">Sub: {{ $row['sub_tujuan'] ?? '—' }}</span></td>
                    <td>{{ $row['pemohon'] ?? '—' }}</td>
                    <td><span class="status">{{ $row['status'] ?? '—' }}</span></td>
                    <td>{{ $row['aksi'] ?? '—' }}</td>
                    <td>{{ $row['diajukan'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <table class="data">
            <thead>
                <tr>
                    <th>No.</th><th>Barang</th><th>Kode Asal</th><th>Sub Asal</th><th>Kode Tujuan</th><th>Sub Tujuan</th><th>Pemohon</th><th>Status</th><th>Aksi</th><th>Diajukan</th><th>Diputuskan</th><th>Alasan</th><th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['barang'] ?? '—' }}</td>
                    <td>{{ $row['kode_asal'] ?? '—' }}</td>
                    <td>{{ $row['sub_asal'] ?? '—' }}</td>
                    <td>{{ $row['kode_tujuan'] ?? '—' }}</td>
                    <td>{{ $row['sub_tujuan'] ?? '—' }}</td>
                    <td>{{ $row['pemohon'] ?? '—' }}</td>
                    <td><span class="status">{{ $row['status'] ?? '—' }}</span></td>
                    <td>{{ $row['aksi'] ?? '—' }}</td>
                    <td>{{ $row['diajukan'] ?? '—' }}</td>
                    <td>{{ $row['diputuskan'] ?? '—' }}</td>
                    <td>{{ $row['alasan'] ?? '—' }}</td>
                    <td>{{ $row['catatan'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <footer class="doc-footer">THI2-WAREHOUSE · Dokumen rekap otomatis dari data pengajuan lokasi barang.</footer>
    </main>
</x-print-shell>