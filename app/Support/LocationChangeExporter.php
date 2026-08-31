<?php

namespace App\Support;

use App\Models\LocationChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class LocationChangeExporter
{
    public static function query(Request $request, string $scope)
    {
        $query = LocationChangeRequest::with([
            'item',
            'fromLocation',
            'toLocation',
            'swapItem',
            'requestedBy',
            'approvedBy',
        ]);

        if ($scope === 'gudang') {
            $query->where('requested_by', Auth::id());
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        return $query->latest();
    }

    public static function buildRows(Collection $changes): array
    {
        $label = function (?string $action): string {
            return match ($action) {
                'swap' => 'Tukar Tempat',
                'stack' => 'Tumpuk',
                default => '—',
            };
        };

        return $changes->map(function (LocationChangeRequest $change) use ($label) {
            $itemName = $change->item
                ? $change->item->name . ($change->item->size ? ' (' . $change->item->size . ')' : '')
                : '—';

            return [
                'id' => $change->id,
                'barang' => $itemName,
                'kode_asal' => $change->fromLocation?->code ?? '—',
                'sub_asal' => $change->from_sub_location ?? '—',
                'kode_tujuan' => $change->toLocation?->code ?? '—',
                'sub_tujuan' => $change->isSubLocationChange()
                    ? ($change->target_sub_location ?? '—')
                    : ($change->toLocation?->sub_location ?? '—'),
                'pemohon' => $change->requestedBy?->name ?? '—',
                'status' => $change->status,
                'aksi' => $label($change->resolution_action),
                'diajukan' => $change->created_at?->format('d M Y, H:i') ?? '—',
                'diputuskan' => $change->decided_at?->format('d M Y, H:i') ?? '—',
                'alasan' => $change->reason ?? '—',
                'catatan' => $change->admin_note ?? '—',
            ];
        })->all();
    }
}