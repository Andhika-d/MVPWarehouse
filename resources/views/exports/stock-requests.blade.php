<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export Permintaan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h2>Daftar Permintaan Barang</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Pemohon</th>
                <th>Barang</th>
                <th>Jumlah</th>
                <th>Satuan</th>
                <th>Prioritas</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row['id'] ?? '' }}</td>
                    <td>{{ $row['pemohon'] ?? '' }}</td>
                    <td>{{ $row['barang'] ?? '' }}</td>
                    <td>{{ $row['jumlah'] ?? '' }}</td>
                    <td>{{ $row['satuan'] ?? '' }}</td>
                    <td>{{ $row['prioritas'] ?? '' }}</td>
                    <td>{{ $row['status'] ?? '' }}</td>
                    <td>{{ $row['tanggal'] ?? '' }}</td>
                    <td>{{ $row['catatan'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
