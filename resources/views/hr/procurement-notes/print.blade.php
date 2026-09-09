<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $procurementNote->number }}</title>
    <style>
        body { margin: 28px; color: #0f172a; font: 13px Arial, sans-serif; }
        .head { display: flex; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 14px; }
        h1 { margin: 0 0 5px; font-size: 20px; } p { margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 22px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; }
        th { background: #f1f5f9; font-size: 11px; text-transform: uppercase; }
        .signatures { display: flex; justify-content: space-between; margin-top: 55px; text-align: center; }
        .signatures div { width: 190px; } .line { border-top: 1px solid #334155; margin-top: 65px; padding-top: 5px; }
        .no-print { display: flex; gap: 8px; margin-bottom: 20px; } .no-print a, .no-print button { border: 1px solid #cbd5e1; border-radius: 6px; background: white; color: #334155; padding: 8px 12px; text-decoration: none; cursor: pointer; } .no-print button { background: #2563eb; color: white; border-color: #2563eb; } .no-print button:disabled { cursor: not-allowed; opacity: .6; } @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="no-print"><a href="{{ route('hr.procurement-notes.show', $procurementNote) }}">Kembali ke Detail Nota</a><button type="button" onclick="printNote(this)">Cetak Nota</button></div>
    <div class="head"><div><h1>NOTA PENGADAAN BARANG</h1><p><b>{{ $procurementNote->number }}</b></p><p>Tanggal Nota: {{ $procurementNote->issued_at?->translatedFormat('d F Y, H:i') }}</p><p>Tanggal Cetak: {{ $procurementNote->last_printed_at?->translatedFormat('d F Y, H:i') }}</p></div><div><b>MVPWarehouse</b><p>Status: {{ $procurementNote->status }}</p><p>Driver: {{ $procurementNote->driver_name ?: '-' }}</p></div></div>
    @if($procurementNote->notes)<p><b>Catatan:</b> {{ $procurementNote->notes }}</p>@endif
    <table><thead><tr><th>No</th><th>Barang</th><th>Jumlah</th><th>Status Penerimaan</th><th>Prioritas</th><th>Pemohon</th><th>Catatan HR</th></tr></thead><tbody>@foreach($procurementNote->items as $item)<tr><td>{{ $loop->iteration }}</td><td>{{ $item->item_name }}</td><td>{{ $item->quantity }} {{ $item->unit }}</td><td>{{ $item->receiptStatusLabel() }}</td><td>{{ $item->priority ?: '-' }}</td><td>{{ $item->requester_name ?: '-' }}</td><td>{{ $item->review_note ?: '-' }}</td></tr>@endforeach</tbody></table>
    <div class="signatures"><div>Disiapkan oleh<div class="line">{{ $procurementNote->creator?->name ?? 'HR' }}</div></div><div>Driver<div class="line">{{ $procurementNote->driver_name ?: '(........................)' }}</div></div><div>Diterima Gudang<div class="line">(........................)</div></div></div>
    <script>function printNote(button){if(button.disabled)return;button.disabled=true;window.print();window.setTimeout(function(){button.disabled=false;},1000);}</script>
</body>
</html>
