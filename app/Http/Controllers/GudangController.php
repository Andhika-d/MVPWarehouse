<?php

namespace App\Http\Controllers;

use App\Exports\LocationChangeExport;
use App\Exports\StockMovementExport;
use App\Models\Item;
use App\Models\LocationChangeRequest;
use App\Models\StockMovement;
use App\Models\StockRequest;
use App\Models\StorageLocation;
use App\Support\ItemLocationSorter;
use App\Support\LocationChangeExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GudangController extends Controller
{
    private function safeMonth(?string $value): ?\Carbon\Carbon
    {
        if ($value === null || ! preg_match('/^\d{4}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Exception) {
            return null;
        }
    }

    public function penerimaanIndex(Request $request)
    {
        $query = StockRequest::with('item.storageLocation')
            ->whereIn('status', ['Disetujui', 'Sebagian Diterima'])
            ->whereColumn('received_quantity', '<', 'quantity');

        if ($request->filled('month')) {
            $date = $this->safeMonth($request->input('month'));
            if ($date) {
                $query->whereYear('created_at', $date->year)
                      ->whereMonth('created_at', $date->month);
            }
        }

        $requests = $query->latest()->paginate(20)->withQueryString();

        return view('gudang.penerimaan', compact('requests'));
    }

    public function penerimaanStore(Request $request)
    {
        $data = $request->validate([
            'stock_request_id' => 'required|exists:stock_requests,id',
            'received_quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($data) {
            $stockRequest = StockRequest::lockForUpdate()->find($data['stock_request_id']);

            if (! $stockRequest->canReceive()) {
                return back()->with('error', 'Permintaan ini tidak dapat diterima.');
            }

            if (! $stockRequest->item) {
                return back()->with('error', 'Item terkait permintaan ini tidak ditemukan di master item.');
            }

            $remaining = $stockRequest->remainingQuantity();
            $receiveQty = (int) $data['received_quantity'];

            if ($receiveQty > $remaining) {
                return back()->with('error', 'Jumlah diterima (' . $receiveQty . ') melebihi sisa yang belum diterima (' . $remaining . ' ' . $stockRequest->unit . ').');
            }

            $item = Item::lockForUpdate()->find($stockRequest->item_id);

            if ($item->unit !== $stockRequest->unit) {
                return back()->with('error', 'Satuan tidak cocok: item menggunakan satuan "' . $item->unit . '" sedangkan permintaan menggunakan "' . $stockRequest->unit . '". Hubungi admin untuk memperbaiki data.');
            }

            $stockRequest->increment('received_quantity', $receiveQty);

            $stockRequest->refresh();

            if ($stockRequest->received_quantity >= $stockRequest->quantity) {
                $stockRequest->update(['status' => 'Diterima Penuh', 'completed_at' => now()]);
            } else {
                $stockRequest->update(['status' => 'Sebagian Diterima']);
            }

            $balanceBefore = $item->stock;
            $item->increment('stock', $receiveQty);

            $item->refresh();

            StockMovement::create([
                'item_id' => $item->id,
                'type' => StockMovement::TYPE_IN,
                'quantity' => $receiveQty,
                'unit' => $stockRequest->unit,
                'reason' => 'Penerimaan barang dari pembelian',
                'stock_request_id' => $stockRequest->id,
                'user_id' => Auth::id(),
                'balance_before' => $balanceBefore,
                'balance_after' => $item->stock,
                'note' => $data['note'] ?? null,
                'occurred_at' => now(),
            ]);

            $stockRequest->requestHistories()->create([
                'user_id' => Auth::id(),
                'status' => $stockRequest->status,
                'note' => 'Diterima ' . $receiveQty . ' ' . $stockRequest->unit . (($data['note'] ?? null) ? ' — ' . $data['note'] : ''),
            ]);

            return back()->with('success', $receiveQty . ' ' . $stockRequest->unit . ' berhasil diterima dan stok telah bertambah.');
        });
    }

    public function closeSisaStore(Request $request)
    {
        $data = $request->validate([
            'stock_request_id' => 'required|exists:stock_requests,id',
            'note' => 'required|string|max:255',
        ]);

        $stockRequest = StockRequest::find($data['stock_request_id']);

        if (! $stockRequest) {
            return back()->with('error', 'Request tidak ditemukan.');
        }

        $result = $stockRequest->closeRemaining(Auth::id(), trim($data['note']));

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    public function barangKeluarIndex()
    {
        $items = ItemLocationSorter::sort(
            Item::with('storageLocation')
                ->where('stock', '>', 0)
                ->get()
        );

        $itemOptions = $items->map(fn ($item) => [
            'id' => $item->id,
            'label' => $item->display_name,
            'stock' => $item->stock,
            'unit' => $item->unit,
            'rack' => $item->storageLocation?->rack,
            'location_code' => $item->storageLocation?->code,
            'sub_location' => $item->storageLocation?->sub_location,
        ])->values();

        return view('gudang.barang-keluar', compact('items', 'itemOptions'));
    }

    public function barangKeluarStore(Request $request)
    {
        $data = $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
            'note' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($data) {
            $item = Item::lockForUpdate()->find($data['item_id']);

            if ($item->stock < $data['quantity']) {
                return back()->with('error', 'Stok tidak mencukupi. Stok saat ini: ' . $item->stock . ' ' . $item->unit);
            }

            $balanceBefore = $item->stock;
            $item->decrement('stock', $data['quantity']);
            $item->refresh();

            StockMovement::create([
                'item_id' => $item->id,
                'type' => StockMovement::TYPE_OUT,
                'quantity' => $data['quantity'],
                'unit' => $item->unit,
                'reason' => $data['reason'],
                'stock_request_id' => null,
                'user_id' => Auth::id(),
                'balance_before' => $balanceBefore,
                'balance_after' => $item->stock,
                'note' => $data['note'] ?? null,
                'occurred_at' => now(),
            ]);

            return back()->with('success', $data['quantity'] . ' ' . $item->unit . ' ' . $item->name . ' berhasil dicatat keluar. Stok tersisa: ' . $item->stock);
        });
    }

    public function movementsIndex(Request $request)
    {
        $validated = $request->validate([
            'type' => ['nullable', 'in:IN,OUT,ADJUSTMENT'],
            'item_id' => ['nullable', 'exists:items,id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $query = StockMovement::with('item', 'stockRequest', 'user')->orderByDesc('occurred_at');

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (!empty($validated['item_id'])) {
            $query->where('item_id', $validated['item_id']);
        }

        if (!empty($validated['date_from'])) {
            $query->where('occurred_at', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->where('occurred_at', '<=', $validated['date_to'] . ' 23:59:59');
        }

        $movements = $query->paginate(20)->withQueryString();
        $items = Item::orderBy('name')->get();

        return view('gudang.movements', compact('movements', 'items'));
    }

    public function locationChangeIndex()
    {
        $items = Item::with('storageLocation')
            ->whereNotNull('storage_location_id')
            ->orderBy('name')
            ->get();

        $myChanges = LocationChangeRequest::with(['item', 'fromLocation', 'toLocation', 'swapItem'])
            ->where('requested_by', Auth::id())
            ->latest()
            ->paginate(10);

        return view('gudang.location-change', compact('items', 'myChanges'));
    }

    public function locationSearch(Request $request)
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $needle = self::normalizeLocationToken($term);

        $slots = StorageLocation::withCount('items')
            ->whereNotNull('sub_location')
            ->get()
            ->filter(function (StorageLocation $loc) use ($needle) {
                return str_contains(self::normalizeLocationToken($loc->sub_location ?? ''), $needle)
                    || str_contains(self::normalizeLocationToken($loc->code), $needle);
            })
            ->values();

        $sorted = $slots->sort(function ($a, $b) {
            $cmp = self::compareSubLocations($a->sub_location ?? '', $b->sub_location ?? '');
            if ($cmp !== 0) {
                return $cmp;
            }

            return $a->number <=> $b->number;
        });

        return response()->json($sorted->take(20)->map(function (StorageLocation $loc) {
            return [
                'id' => $loc->id,
                'sub_location' => $loc->sub_location,
                'code' => $loc->code,
                'rack' => $loc->rack,
                'status' => $loc->status,
                'items_count' => $loc->items_count,
            ];
        })->values());
    }

    private static function normalizeLocationToken(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($value)) ?? '';
    }

    private static function compareSubLocations(string $a, string $b): int
    {
        $segment = function (string $value): array {
            return array_map(fn ($part) => trim((string) $part), explode('.', trim($value)));
        };

        $partsA = $segment($a);
        $partsB = $segment($b);
        $length = max(count($partsA), count($partsB));

        for ($i = 0; $i < $length; $i++) {
            $x = $partsA[$i] ?? '';
            $y = $partsB[$i] ?? '';

            if ($x === $y) {
                continue;
            }

            $numX = is_numeric($x) ? (float) $x : null;
            $numY = is_numeric($y) ? (float) $y : null;

            if ($numX !== null && $numY !== null) {
                return $numX <=> $numY;
            }

            if ($numX !== null) {
                return -1;
            }

            if ($numY !== null) {
                return 1;
            }

            return strcmp($x, $y);
        }

        return 0;
    }

    public function locationChangeStore(Request $request)
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'target_location_id' => ['required', 'exists:storage_locations,id'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $item = Item::with('storageLocation')->find($data['item_id']);

        if (! $item->storageLocation) {
            return back()->with('error', 'Barang ini belum memiliki lokasi.');
        }

        $fromLocation = $item->storageLocation;
        $toLocation = StorageLocation::find($data['target_location_id']);

        if (! $toLocation) {
            return back()->with('error', 'Lokasi tujuan tidak valid.');
        }

        if ($fromLocation->id === $toLocation->id) {
            return back()->with('error', 'Slot asal dan tujuan sama.');
        }

        $pendingCount = LocationChangeRequest::where('item_id', $item->id)
            ->where('requested_by', Auth::id())
            ->where('status', LocationChangeRequest::STATUS_PENDING)
            ->count();

        if ($pendingCount > 0) {
            return back()->with('error', 'Sudah ada pengajuan pending untuk barang ini.');
        }

        LocationChangeRequest::create([
            'item_id' => $item->id,
            'from_location_id' => $fromLocation->id,
            'to_location_id' => $toLocation->id,
            'from_sub_location' => $fromLocation->sub_location,
            'target_sub_location' => $toLocation->sub_location,
            'requested_by' => Auth::id(),
            'status' => LocationChangeRequest::STATUS_PENDING,
            'reason' => $data['reason'],
        ]);

        return back()->with('success', 'Pengajuan pemindahan lokasi dikirim: '.$item->name.' ('.$fromLocation->code.' · '.($fromLocation->sub_location ?? '—').' → '.$toLocation->code.' · '.($toLocation->sub_location ?? '—').'). Menunggu persetujuan admin.');
    }

    public function exportMovementExcel(Request $request)
    {
        $validated = $request->validate([
            'export_type' => ['required', 'in:in,out,in_out,adjustment,all'],
            'item_id' => ['nullable', 'exists:items,id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $query = StockMovement::with('item', 'user')
            ->orderByDesc('occurred_at');

        $types = match ($validated['export_type']) {
            'in' => ['IN'],
            'out' => ['OUT'],
            'in_out' => ['IN', 'OUT'],
            'adjustment' => ['ADJUSTMENT'],
            'all' => ['IN', 'OUT', 'ADJUSTMENT'],
            default => ['IN', 'OUT'],
        };

        $query->whereIn('type', $types);

        if (!empty($validated['item_id'])) {
            $query->where('item_id', $validated['item_id']);
        }

        if (!empty($validated['date_from'])) {
            $query->where('occurred_at', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->where('occurred_at', '<=', $validated['date_to'] . ' 23:59:59');
        }

        $rows = [];
        $query->chunk(500, function ($movements) use (&$rows) {
            foreach ($movements as $m) {
                $typeLabel = match ($m->type) {
                    'IN' => 'Barang Masuk',
                    'OUT' => 'Barang Keluar',
                    'ADJUSTMENT' => 'Penyesuaian Stok',
                    default => $m->type,
                };
                $rows[] = [
                    $m->occurred_at?->format('d M Y, H:i'),
                    $m->item?->name ?? '—',
                    $typeLabel,
                    $m->type === 'OUT' ? '-' . $m->quantity : '+' . $m->quantity,
                    $m->unit,
                    $m->balance_before,
                    $m->balance_after,
                    $m->reason,
                    $m->user?->name ?? '—',
                ];
            }
        });

        if (empty($rows)) {
            return back()->with('error', 'Tidak ada data untuk di-export dengan filter yang dipilih.');
        }

        $export = new StockMovementExport($rows);

        $filenames = [
            'in' => 'barang-masuk.xlsx',
            'out' => 'barang-keluar.xlsx',
            'in_out' => 'barang-masuk-keluar.xlsx',
            'adjustment' => 'penyesuaian-stok.xlsx',
            'all' => 'semua-perubahan-stok.xlsx',
        ];

        $filename = $filenames[$validated['export_type']] ?? 'perubahan-stok.xlsx';

        return response($export->toXlsx(), 200)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function exportLocationChangesExcel(Request $request)
    {
        $changes = LocationChangeExporter::query($request, 'gudang')->get();
        $rows = LocationChangeExporter::buildRows($changes);

        if (empty($rows)) {
            return back()->with('error', 'Tidak ada data untuk di-export dengan filter yang dipilih.');
        }

        $export = new LocationChangeExport($rows);

        return response($export->toXlsx(), 200)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="riwayat-pengajuan-lokasi.xlsx"');
    }

    public function exportLocationChangesPdf(Request $request)
    {
        $changes = LocationChangeExporter::query($request, 'gudang')->get();
        $rows = LocationChangeExporter::buildRows($changes);

        if (empty($rows)) {
            return back()->with('error', 'Tidak ada data untuk di-export dengan filter yang dipilih.');
        }

        $pdf = Pdf::loadView('exports.location-changes', compact('rows'));

        return $pdf->download('riwayat-pengajuan-lokasi.pdf');
    }
}
