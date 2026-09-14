<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hard Copy Stock Barang</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #0f172a; background: #eef2f7; font: 12px Arial, sans-serif; }
        .toolbar { display: flex; justify-content: flex-end; gap: 8px; max-width: 297mm; margin: 16px auto 0; }
        .toolbar a, .toolbar button { border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 14px; color: #334155; background: white; font-weight: 700; text-decoration: none; cursor: pointer; }
        .toolbar button { border-color: #1d4ed8; color: white; background: #1d4ed8; }
        .sheet { max-width: 297mm; min-height: 210mm; margin: 12px auto 24px; padding: 10mm; background: white; box-shadow: 0 8px 30px rgba(15, 23, 42, .1); }
        header { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; padding-bottom: 12px; border-bottom: 3px solid #1d4ed8; }
        h1 { margin: 0 0 5px; font-size: 24px; letter-spacing: .02em; }
        .subtitle, .meta { color: #475569; }
        .meta { text-align: right; line-height: 1.6; }
        .header-approval { width: 78mm; }
        .approval-grid { width: 78mm; height: 28mm; margin-top: 8px; border-collapse: collapse; table-layout: fixed; color: #0f172a; font-size: 9px; line-height: 1.05; }
        .approval-grid td { padding: 0; border: 1.5px solid #111827; text-align: center; vertical-align: middle; }
        .approval-grid .approval-label { width: 7mm; font-size: 12px; line-height: 1.8; }
        .approval-grid .approval-heading { height: 7mm; font-size: 9px; }
        .approval-grid .approval-heading span { display: block; margin-top: 1px; font-size: 8px; }
        .approval-grid .approval-signature { height: 16mm; }
        .approval-grid .approval-date { height: 5mm; font-size: 11px; font-style: italic; }
        .summary { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin: 12px 0; }
        .summary div { padding: 9px 10px; border: 1px solid #cbd5e1; background: #f8fafc; }
        .summary strong { display: block; margin-top: 3px; font-size: 16px; }
        .filters { margin-bottom: 10px; padding: 8px 10px; border: 1px solid #bfdbfe; background: #eff6ff; }
        .filters span { margin-right: 24px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; page-break-inside: avoid; }
        th, td { padding: 6px 7px; border: 1px solid #94a3b8; text-align: left; vertical-align: top; }
        th { color: white; background: #1e3a8a; font-size: 9px; text-transform: uppercase; letter-spacing: .04em; }
        td.number { text-align: right; }
        td.center { text-align: center; }
        .empty { color: #64748b; font-style: italic; }
        footer { margin-top: 10px; color: #64748b; font-size: 9px; text-align: right; }
        @media print {
            body { background: white; }
            .toolbar { display: none; }
            .sheet { max-width: none; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar"><a href="{{ $backUrl }}">Kembali</a><button type="button" onclick="window.print()">Cetak A4 Landscape</button></div>
    <main class="sheet">
        <header>
            <div><h1>STOCK BARANG</h1><div class="subtitle">THI-Cilegon Warehouse - Snapshot inventaris terfilter</div></div>
            <div class="header-approval">
                <div class="meta"><strong>{{ $printedAt->translatedFormat('d F Y, H:i') }}</strong><br>Dicetak oleh {{ $printedBy }} ({{ $role }})</div>
                <table class="approval-grid" aria-label="Approval">
                    <tbody>
                        <tr>
                            <td class="approval-label" rowspan="3">결<br>재</td>
                            <td class="approval-heading">Made<span>작 성</span></td>
                            <td class="approval-heading" colspan="2">Check<span>검 토</span></td>
                            <td class="approval-heading">Approve<span>승 인</span></td>
                        </tr>
                        <tr>
                            <td class="approval-signature"></td>
                            <td class="approval-signature"></td>
                            <td class="approval-signature"></td>
                            <td class="approval-signature"></td>
                        </tr>
                        <tr>
                            <td class="approval-date">/</td>
                            <td class="approval-date">/</td>
                            <td class="approval-date">/</td>
                            <td class="approval-date">/</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </header>

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

        <table>
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
        <footer>Dokumen ini merupakan snapshot pada waktu cetak dan dapat berubah mengikuti transaksi stok.</footer>
    </main>
</body>
</html>
