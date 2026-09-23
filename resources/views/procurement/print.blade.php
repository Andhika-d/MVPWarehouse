<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        @page { size: A4 {{ $orientation }}; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #0f172a; background: #eef2f7; font: 12px Arial, sans-serif; }
        .toolbar { display: flex; justify-content: flex-end; gap: 8px; max-width: 297mm; margin: 16px auto 0; }
        .toolbar a, .toolbar button { border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 14px; color: #334155; background: white; font-weight: 700; text-decoration: none; cursor: pointer; }
        .toolbar a.active { border-color: #93c5fd; color: #1d4ed8; background: #eff6ff; }
        .toolbar button { border-color: #1d4ed8; color: white; background: #1d4ed8; }
        .sheet { max-width: {{ $orientation === 'portrait' ? '210mm' : '297mm' }}; min-height: {{ $orientation === 'portrait' ? '297mm' : '210mm' }}; margin: 12px auto 24px; padding: 10mm; background: white; box-shadow: 0 8px 30px rgba(15, 23, 42, .1); break-after: page; }
        .sheet:last-child { break-after: auto; }
        header { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; padding-bottom: 12px; border-bottom: 3px solid #1d4ed8; }
        h1 { margin: 0 0 5px; font-size: 22px; letter-spacing: .02em; }
        .subtitle, .meta { color: #475569; }
        .meta { text-align: right; line-height: 1.6; font-size: 10px; }
        .header-approval { width: 78mm; }
        .approval-grid { width: 78mm; height: 28mm; margin-top: 8px; border-collapse: collapse; table-layout: fixed; color: #0f172a; font-size: 9px; line-height: 1.05; }
        .approval-grid td { padding: 0; border: 1.5px solid #111827; text-align: center; vertical-align: middle; }
        .approval-grid .approval-label { width: 7mm; font-size: 12px; line-height: 1.8; }
        .approval-grid .approval-heading { height: 7mm; font-size: 9px; }
        .approval-grid .approval-heading span { display: block; margin-top: 1px; font-size: 8px; }
        .approval-grid .approval-signature { height: 16mm; }
        .approval-grid .approval-date { height: 5mm; font-size: 11px; font-style: italic; }
        .filters { margin-bottom: 10px; padding: 8px 10px; border: 1px solid #bfdbfe; background: #eff6ff; font-size: 10px; }
        .filters span { margin-right: 24px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; page-break-inside: avoid; }
        th, td { padding: 6px 7px; border: 1px solid #94a3b8; text-align: left; vertical-align: top; }
        th { color: white; background: #1e3a8a; font-size: 9px; text-transform: uppercase; letter-spacing: .04em; }
        td.number { text-align: right; white-space: nowrap; }
        .status { white-space: nowrap; font-weight: 700; }
        .cell-primary { display: block; font-weight: 700; color: #0f172a; }
        .cell-meta { display: block; margin-top: 2px; color: #475569; font-size: 9px; }
        .procurement-table--portrait th:first-child, .procurement-table--portrait td:first-child { width: 15mm; }
        .procurement-table--portrait th:nth-child(3), .procurement-table--portrait td:nth-child(3) { width: 30mm; }
        footer { margin-top: 10px; color: #64748b; font-size: 9px; text-align: right; }
        @media print {
            body { background: white; }
            .toolbar { display: none; }
            .sheet { max-width: none; min-height: 0; margin: 0; padding: 0; box-shadow: none; break-after: page; }
            .sheet:last-child { break-after: auto; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ $backUrl }}">Kembali</a>
        <a href="{{ request()->fullUrlWithQuery(['orientation' => 'landscape']) }}" class="{{ $orientation === 'landscape' ? 'active' : '' }}">Landscape</a>
        <a href="{{ request()->fullUrlWithQuery(['orientation' => 'portrait']) }}" class="{{ $orientation === 'portrait' ? 'active' : '' }}">Portrait</a>
        <button type="button" onclick="window.print()">Cetak A4 {{ ucfirst($orientation) }}</button>
    </div>

    @foreach($notes as $note)
    <main class="sheet">
        <header>
            <div>
                <h1>NOTA PENGADAAN</h1>
                <div class="subtitle">THI2-WAREHOUSE · {{ $note->number }} · {{ $note->request_date->translatedFormat('d F Y') }} · {{ $note->statusLabel() }}</div>
            </div>
            <div class="header-approval">
                <div class="meta"><strong>{{ $printedAt->translatedFormat('d F Y, H:i') }}</strong><br>Dicetak oleh {{ $printedBy }} ({{ $printedRole }})</div>
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

        <div class="filters">
            <span><strong>Periode:</strong> {{ $periodLabel }}</span>
            <span><strong>Request:</strong> {{ $note->requests->count() }}</span>
            <span><strong>Status:</strong> {{ $note->statusLabel() }}</span>
        </div>

        @if($orientation === 'portrait')
        <table class="procurement-table--portrait">
            <thead>
                <tr>
                    <th>ID</th><th>Barang</th><th>Jumlah</th><th>Status</th><th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($note->requests as $stockRequest)
                @php
                    $activeRemaining = $stockRequest->canReceive() ? max(0, $stockRequest->quantity - $stockRequest->received_quantity) : null;
                @endphp
                <tr>
                    <td>
                        <span class="cell-primary">#{{ $stockRequest->id }}</span>
                        <span class="cell-meta">{{ $stockRequest->created_at->format('H:i') }}</span>
                    </td>
                    <td>
                        <span class="cell-primary">{{ $stockRequest->item?->name ?? $stockRequest->item_name ?? 'Barang' }}</span>
                        <span class="cell-meta">{{ $stockRequest->user?->name ?? '—' }}</span>
                    </td>
                    <td>
                        <span class="cell-primary">Diminta {{ $stockRequest->quantity }} {{ $stockRequest->unit }}</span>
                        <span class="cell-meta">Diterima {{ $stockRequest->received_quantity }} {{ $stockRequest->unit }}</span>
                        <span class="cell-meta">Sisa {{ $activeRemaining === null ? '—' : $activeRemaining.' '.$stockRequest->unit }}</span>
                    </td>
                    <td><span class="status">{{ $stockRequest->status }}</span></td>
                    <td>{{ $stockRequest->close_note ?? $stockRequest->review_note ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Waktu</th><th>Barang</th><th>Pemohon</th><th class="number">Diminta</th><th class="number">Diterima</th><th class="number">Sisa</th><th>Status</th><th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($note->requests as $stockRequest)
                @php
                    $closedQuantity = in_array($stockRequest->status, \App\Models\StockRequest::CLOSED_STATUSES, true) ? max(0, $stockRequest->quantity - $stockRequest->received_quantity) : null;
                    $activeRemaining = $stockRequest->canReceive() ? max(0, $stockRequest->quantity - $stockRequest->received_quantity) : null;
                @endphp
                <tr>
                    <td>#{{ $stockRequest->id }}</td>
                    <td>{{ $stockRequest->created_at->format('H:i') }}</td>
                    <td>{{ $stockRequest->item?->name ?? $stockRequest->item_name ?? 'Barang' }}</td>
                    <td>{{ $stockRequest->user?->name ?? '—' }}</td>
                    <td class="number">{{ $stockRequest->quantity }} {{ $stockRequest->unit }}</td>
                    <td class="number">{{ $stockRequest->received_quantity }} {{ $stockRequest->unit }}</td>
                    <td class="number">{{ $activeRemaining === null ? '—' : $activeRemaining.' '.$stockRequest->unit }}</td>
                    <td class="status">{{ $stockRequest->status }}</td>
                    <td>{{ $stockRequest->close_note ?? $stockRequest->review_note ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <footer>THI2-WAREHOUSE · {{ $note->number }} · Dokumen rekap otomatis dari data request dan penerimaan barang.</footer>
    </main>
    @endforeach
</body>
</html>