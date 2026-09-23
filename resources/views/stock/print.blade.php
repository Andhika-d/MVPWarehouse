<x-print-shell
    title="Cetak Stok Barang"
    :orientation="$orientation"
    :styles="'prints.styles.stock'"
    :show-toolbar="true"
    :back-url="$backUrl"
>
    <main class="sheet">
        @include('prints.document-header', [
            'title' => 'STOK BARANG',
            'subtitle' => 'THI2-WAREHOUSE - Cuplikan inventaris terfilter',
            'printedAt' => $printedAt,
            'printedBy' => $printedBy,
            'printedRole' => $printedRole,
        ])

        <section class="summary">
            <div>Lokasi<strong>{{ number_format($summary['locations']) }}</strong></div>
            <div>Jenis Barang<strong>{{ number_format($summary['items']) }}</strong></div>
            <div>Total Stok<strong>{{ number_format($summary['stock']) }}</strong></div>
            <div>Stok Menipis<strong>{{ number_format($summary['low_stock']) }}</strong></div>
            <div>Barang Kosong<strong>{{ number_format($summary['out_of_stock']) }}</strong></div>
        </section>

        <div class="filters">
            @foreach($filters as $label => $value)<span><strong>{{ $label }}:</strong> {{ $value }}</span>@endforeach
        </div>

        @if($orientation === 'landscape')
        <table class="data stock-table stock-table--landscape">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode Tag</th>
                    <th>Rak</th>
                    <th>Sub Lokasi</th>
                    <th>Nama Barang</th>
                    <th>Ukuran</th>
                    <th>Stok</th>
                    <th>Satuan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @php($row = 0)
                @forelse($locations as $location)
                    @forelse($location->items as $item)
                    <tr>
                        <td class="number">{{ ++$row }}</td><td>{{ $location->code }}</td><td>{{ $location->rack }}</td><td>{{ $location->sub_location ?: '-' }}</td>
                        <td>{{ $item->name }}</td><td>{{ $item->size ?: '-' }}</td><td class="number">{{ number_format($item->stock) }}</td><td>{{ $item->unit }}</td>
                        <td>{{ $item->stock === 0 ? 'Terisi (Barang Kosong)' : 'Terisi' }}</td>
                    </tr>
                    @empty
                    <tr><td class="number">{{ ++$row }}</td><td>{{ $location->code }}</td><td>{{ $location->rack }}</td><td>{{ $location->sub_location ?: '-' }}</td><td class="empty">Lokasi Kosong</td><td>-</td><td class="number">-</td><td>-</td><td>Kosong</td></tr>
                    @endforelse
                @empty
                    <tr><td colspan="9" class="center empty">Tidak ada data sesuai filter.</td></tr>
                @endforelse
            </tbody>
        </table>
        @else
        <table class="data stock-table stock-table--portrait">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode Tag</th>
                    <th>Lokasi</th>
                    <th>Barang</th>
                    <th>Stok</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @php($row = 0)
                @forelse($locations as $location)
                    @forelse($location->items as $item)
                    <tr>
                        <td class="number">{{ ++$row }}</td>
                        <td><span class="cell-primary">{{ $location->code }}</span></td>
                        <td>
                            <span class="cell-primary">Rak {{ $location->rack }}</span>
                            <span class="cell-meta">Sub: {{ $location->sub_location ?: '-' }}</span>
                        </td>
                        <td>
                            <span class="cell-primary">{{ $item->name }}</span>
                            <span class="cell-meta">Ukuran: {{ $item->size ?: '-' }}</span>
                        </td>
                        <td>
                            <span class="cell-primary">{{ number_format($item->stock) }}</span>
                            <span class="cell-meta">{{ $item->unit }}</span>
                        </td>
                        <td>{{ $item->stock === 0 ? 'Terisi (Barang Kosong)' : 'Terisi' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td class="number">{{ ++$row }}</td>
                        <td><span class="cell-primary">{{ $location->code }}</span></td>
                        <td>
                            <span class="cell-primary">Rak {{ $location->rack }}</span>
                            <span class="cell-meta">Sub: {{ $location->sub_location ?: '-' }}</span>
                        </td>
                        <td><span class="empty">Lokasi Kosong</span><span class="cell-meta">Ukuran: -</span></td>
                        <td><span class="cell-primary">-</span><span class="cell-meta">-</span></td>
                        <td>Kosong</td>
                    </tr>
                    @endforelse
                @empty
                    <tr><td colspan="6" class="center empty">Tidak ada data sesuai filter.</td></tr>
                @endforelse
            </tbody>
        </table>
        @endif
        <footer class="doc-footer">Dokumen ini merupakan cuplikan pada waktu cetak dan dapat berubah mengikuti transaksi stok.</footer>
    </main>
</x-print-shell>