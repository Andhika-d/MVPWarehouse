@props([
    'title' => '',
    'orientation' => 'landscape',
    'styles' => null,
    'showToolbar' => false,
    'backUrl' => null,
])

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

        .sheet { max-width: {{ $orientation === 'portrait' ? '210mm' : '297mm' }}; min-height: {{ $orientation === 'portrait' ? '297mm' : '210mm' }}; margin: 12px auto 24px; padding: 10mm; background: white; box-shadow: 0 8px 30px rgba(15, 23, 42, .1); }

        .doc-header { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .doc-header-title { padding: 0 0 12px; vertical-align: top; }
        .doc-header-meta { width: 78mm; padding: 0 0 12px; vertical-align: top; }
        h1 { margin: 0 0 5px; font-size: 22px; letter-spacing: .02em; }
        .subtitle { color: #475569; }
        .meta { color: #475569; text-align: right; line-height: 1.6; font-size: 10px; }

        .approval-grid { width: 78mm; height: 28mm; margin: 8px 0 0 auto; border-collapse: collapse; table-layout: fixed; color: #0f172a; font-size: 9px; line-height: 1.05; font-family: "NanumGothic", "Nanum Gothic", "Malgun Gothic", "Apple SD Gothic Neo", sans-serif; }
        .approval-grid td { padding: 0; border: 1.5px solid #111827; text-align: center; vertical-align: middle; }
        .approval-grid .approval-label { width: 7mm; font-size: 12px; line-height: 1.8; }
        .approval-grid .approval-heading { height: 7mm; font-size: 9px; }
        .approval-grid .approval-heading span { display: block; margin-top: 1px; font-size: 8px; }
        .approval-grid .approval-signature { height: 16mm; }
        .approval-grid .approval-date { height: 5mm; font-size: 11px; font-style: italic; }

        .filters { margin-bottom: 10px; padding: 8px 10px; border: 1px solid #bfdbfe; background: #eff6ff; font-size: 10px; }
        .filters span { margin-right: 24px; }

        table.data { width: 100%; border-collapse: collapse; font-size: 10px; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; page-break-inside: avoid; }
        .data th, .data td { padding: 6px 7px; border: 1px solid #94a3b8; text-align: left; vertical-align: top; }
        .data th { color: white; background: #1e3a8a; font-size: 9px; text-transform: uppercase; letter-spacing: .04em; }
        .data th.number, .data td.number { text-align: right; white-space: nowrap; }
        .data th.center, .data td.center { text-align: center; }

        .status { white-space: nowrap; font-weight: 700; }
        .cell-primary { display: block; font-weight: 700; color: #0f172a; }
        .cell-meta { display: block; margin-top: 2px; color: #475569; font-size: 9px; }
        .empty { color: #64748b; font-style: italic; }

        footer.doc-footer { margin-top: 10px; color: #64748b; font-size: 9px; text-align: right; }

        @media print {
            body { background: white; }
            .toolbar { display: none; }
            .sheet { max-width: none; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
        }

        @if($styles)@include($styles)@endif
    </style>
</head>
<body>
    @if($showToolbar)
        @include('prints.toolbar', ['backUrl' => $backUrl, 'orientation' => $orientation])
    @endif
    {{ $slot }}
</body>
</html>