<x-print-shell
    :title="$title"
    :orientation="$orientation"
    :styles="'prints.styles.procurement'"
    :show-toolbar="true"
    :back-url="$backUrl"
>
    @foreach($notes as $note)
    <main class="sheet">
        @include('prints.document-header', [
            'title' => 'NOTA PENGADAAN',
            'subtitle' => 'THI2-WAREHOUSE · '.$note->number.' · '.$note->request_date->translatedFormat('d F Y').' · '.$note->statusLabel(),
            'printedAt' => $printedAt,
            'printedBy' => $printedBy,
            'printedRole' => $printedRole,
        ])

        <div class="filters">
            <span><strong>Periode:</strong> {{ $periodLabel }}</span>
            <span><strong>Request:</strong> {{ $note->requests->count() }}</span>
            <span><strong>Status:</strong> {{ $note->statusLabel() }}</span>
        </div>

        @if($orientation === 'portrait')
        <table class="data procurement-table--portrait">
            <thead>
                <tr>
                    <th>No.</th><th>Waktu</th><th>Barang</th><th>Jumlah</th><th>Status</th><th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($note->requests as $index => $stockRequest)
                @php
                    $remaining = max(0, $stockRequest->quantity - $stockRequest->received_quantity);
                @endphp
                <tr>
                    <td><span class="cell-primary">{{ $index + 1 }}</span></td>
                    <td><span class="cell-meta">{{ $stockRequest->created_at->format('H:i') }}</span></td>
                    <td>
                        <span class="cell-primary">{{ $stockRequest->item?->name ?? $stockRequest->item_name ?? 'Barang' }}</span>
                        <span class="cell-meta">{{ $stockRequest->user?->name ?? '—' }}</span>
                    </td>
                    <td>
                        <span class="cell-primary">Diminta {{ $stockRequest->quantity }} {{ $stockRequest->unit }}</span>
                        <span class="cell-meta">Diterima {{ $stockRequest->received_quantity }} {{ $stockRequest->unit }}</span>
                        <span class="cell-meta">Sisa {{ $remaining }} {{ $stockRequest->unit }}</span>
                    </td>
                    <td><span class="status">{{ $stockRequest->status }}</span></td>
                    <td>{{ $stockRequest->close_note ?? $stockRequest->review_note ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <table class="data">
            <thead>
                <tr>
                    <th>No.</th><th>Waktu</th><th>Barang</th><th>Pemohon</th><th class="number">Diminta</th><th class="number">Diterima</th><th class="number">Sisa</th><th>Status</th><th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($note->requests as $index => $stockRequest)
                @php
                    $remaining = max(0, $stockRequest->quantity - $stockRequest->received_quantity);
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $stockRequest->created_at->format('H:i') }}</td>
                    <td>{{ $stockRequest->item?->name ?? $stockRequest->item_name ?? 'Barang' }}</td>
                    <td>{{ $stockRequest->user?->name ?? '—' }}</td>
                    <td class="number">{{ $stockRequest->quantity }} {{ $stockRequest->unit }}</td>
                    <td class="number">{{ $stockRequest->received_quantity }} {{ $stockRequest->unit }}</td>
                    <td class="number">{{ $remaining }} {{ $stockRequest->unit }}</td>
                    <td class="status">{{ $stockRequest->status }}</td>
                    <td>{{ $stockRequest->close_note ?? $stockRequest->review_note ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <footer class="doc-footer">THI2-WAREHOUSE · {{ $note->number }} · Dokumen rekap otomatis dari data request dan penerimaan barang.</footer>
    </main>
    @endforeach
</x-print-shell>