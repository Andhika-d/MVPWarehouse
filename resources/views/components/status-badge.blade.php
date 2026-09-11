@props([
    'status',
    'domain' => 'request',
    'dot' => false,
    'label' => null,
])

@php
    $value = trim((string) $status);
    $normalized = preg_replace('/\s*\(.*/', '', $value);

    $semantic = match ($domain) {
        'request' => match ($normalized) {
            'Menunggu Review', 'Disetujui' => 'info',
            'Pending' => 'warning',
            'Sebagian Diterima' => 'progress',
            'Diterima Penuh' => 'success',
            'Ditolak' => 'danger',
            'Ditutup Sebagian' => 'neutral',
            'Dibatalkan' => 'muted',
            default => 'neutral',
        },
        'procurement' => match ($normalized) {
            'Draft' => 'warning',
            'Diterbitkan' => 'info',
            'Sebagian Diterima' => 'progress',
            'Selesai' => 'success',
            'Dibatalkan' => 'muted',
            default => 'neutral',
        },
        'receipt' => match ($normalized) {
            'Belum Diterima' => 'warning',
            'Diterima Sebagian' => 'progress',
            'Diterima Penuh' => 'success',
            'Ditutup Sebagian' => 'neutral',
            'Dibatalkan' => 'muted',
            default => 'neutral',
        },
        'location' => $normalized === 'Terisi' ? 'success' : 'neutral',
        'location-change' => match ($normalized) {
            'Menunggu Konfirmasi' => 'warning',
            'Disetujui' => 'success',
            'Ditolak' => 'danger',
            default => 'neutral',
        },
        'account' => $normalized === 'Aktif' ? 'success' : 'danger',
        'help' => match ($normalized) {
            'published' => 'success',
            'draft' => 'warning',
            default => 'neutral',
        },
        'priority' => $normalized === 'Mendesak' ? 'danger' : 'neutral',
        'severity' => match ($normalized) {
            'Kritis' => 'danger',
            'Peringatan' => 'warning',
            default => 'neutral',
        },
        'issue' => match ($normalized) {
            'Open' => 'danger',
            'Dalam Tinjauan' => 'warning',
            'Selesai' => 'success',
            default => 'neutral',
        },
        'role' => match ($normalized) {
            'hr', 'director' => 'progress',
            'gudang' => 'info',
            default => 'neutral',
        },
        default => 'neutral',
    };

    $displayLabel = $label ?? match ($domain) {
        'help' => match ($normalized) {
            'published' => 'Terbit',
            'archived' => 'Diarsipkan',
            default => 'Draft',
        },
        'priority' => $normalized === 'Mendesak' ? 'Mendesak' : 'Normal',
        'role' => match ($normalized) {
            'admin' => 'ADMIN',
            'hr' => 'HR',
            'director' => 'DIREKTUR',
            default => 'GUDANG',
        },
        default => $value,
    };
@endphp

<span {{ $attributes->class(['status-badge', 'status-badge--'.$semantic]) }}>
    @if($dot)<span class="status-badge__dot" aria-hidden="true"></span>@endif
    {{ $displayLabel }}
</span>
