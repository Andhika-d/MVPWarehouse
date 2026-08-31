<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export Pengajuan Lokasi</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #111827; }
        h2 { margin: 0; }
        .meta { color: #6b7280; font-size: 10px; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 5px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; white-space: nowrap; }
        td { word-break: break-word; }
    </style>
</head>
<body>
    <h2>Riwayat Pengajuan Lokasi</h2>
    <div class="meta">Dibuat: {{ now()->format('d M Y, H:i') }}</div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Barang</th>
                <th>Kode Asal</th>
                <th>Sub Asal</th>
                <th>Kode Tujuan</th>
                <th>Sub Tujuan</th>
                <th>Pemohon</th>
                <th>Status</th>
                <th>Aksi</th>
                <th>Diajukan</th>
                <th>Diputuskan</th>
                <th>Alasan</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['id'] ?? '' }}</td>
                    <td>{{ $row['barang'] ?? '' }}</td>
                    <td>{{ $row['kode_asal'] ?? '' }}</td>
                    <td>{{ $row['sub_asal'] ?? '' }}</td>
                    <td>{{ $row['kode_tujuan'] ?? '' }}</td>
                    <td>{{ $row['sub_tujuan'] ?? '' }}</td>
                    <td>{{ $row['pemohon'] ?? '' }}</td>
                    <td>{{ $row['status'] ?? '' }}</td>
                    <td>{{ $row['aksi'] ?? '' }}</td>
                    <td>{{ $row['diajukan'] ?? '' }}</td>
                    <td>{{ $row['diputuskan'] ?? '' }}</td>
                    <td>{{ $row['alasan'] ?? '' }}</td>
                    <td>{{ $row['catatan'] ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>