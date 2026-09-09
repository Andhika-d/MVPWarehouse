<?php

namespace App\Http\Controllers;

use App\Exports\AuditLogExport;
use App\Exports\LocationChangeExport;
use App\Models\AuditLog;
use App\Models\Item;
use App\Models\LocationChangeRequest;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\StockRequest;
use App\Models\StorageLocation;
use App\Models\User;
use App\Support\LocationChangeExporter;
use App\Support\XlsxParser;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    // ── Dashboard ──────────────────────────────────────────────────

    public function dashboard()
    {
        $totalItems = Item::count();
        $totalLocations = StorageLocation::count();
        $occupiedLocations = StorageLocation::where('status', StorageLocation::STATUS_OCCUPIED)->count();
        $pendingLocationChanges = LocationChangeRequest::where('status', LocationChangeRequest::STATUS_PENDING)->count();
        $totalUsers = User::count();
        $auditLogs = AuditLog::with('user')->latest()->take(10)->get();

        $backups = collect(glob(storage_path('app/backups/*.zip')) ?: [])
            ->sortByDesc(fn (string $path) => filemtime($path))
            ->map(fn (string $path) => [
                'name' => basename($path),
                'size' => $this->formatBytes(filesize($path)),
                'time' => date('d M Y, H:i', filemtime($path)),
            ])
            ->values();

        return view('admin.dashboard', compact(
            'totalItems',
            'totalLocations',
            'occupiedLocations',
            'pendingLocationChanges',
            'totalUsers',
            'auditLogs',
            'backups'
        ));
    }

    // ── Master Items ───────────────────────────────────────────────

    public function itemsIndex(Request $request)
    {
        $query = StorageLocation::with('items');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('sub_location', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhereHas('items', function ($iq) use ($search) {
                      $iq->where('name', 'like', "%{$search}%")
                         ->orWhere('size', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('rack') && $request->input('rack') !== 'all') {
            $query->where('rack', $request->input('rack'));
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('unit') && $request->input('unit') !== 'all') {
            $query->whereHas('items', function ($q) use ($request) {
                $q->where('unit', $request->input('unit'));
            });
        }

        if ($request->filled('low_stock')) {
            $query->whereHas('items', function ($q) {
                $q->where('stock', '<=', 5);
            });
        }

        $locations = $query->orderBy('storage_locations.sub_location', 'asc')
            ->orderBy('storage_locations.number', 'asc')
            ->paginate(20)->withQueryString();

        $totalItems = Item::count();
        $totalStock = Item::sum('stock');
        $lowStockCount = Item::where('stock', '<=', 5)->whereNotNull('storage_location_id')->count();
        $racks = ['A', 'B', 'C', 'D', 'E'];

        return view('admin.items', compact('locations', 'totalItems', 'totalStock', 'lowStockCount', 'racks'));
    }

    public function storeItem(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:255'],
            'storage_location_id' => ['required', 'exists:storage_locations,id'],
            'stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'in:'.implode(',', Item::UNITS)],
        ]);

        DB::transaction(function () use ($data) {
            $location = StorageLocation::lockForUpdate()->find($data['storage_location_id']);

            if ($location->isOccupied()) {
                return back()->with('error', 'Lokasi '.$location->code.' sudah terisi.');
            }

            $item = Item::create($data);
            $location->update(['status' => StorageLocation::STATUS_OCCUPIED]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created_item',
                'target_type' => Item::class,
                'target_id' => $item->id,
                'details' => 'Menambahkan item '.$item->name.' di '.$location->code,
            ]);
        });

        return redirect()->route('admin.items.index')->with('success', 'Item berhasil ditambahkan.');
    }

    public function updateItem(Request $request, Item $item)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:255'],
            'unit' => ['required', 'in:'.implode(',', Item::UNITS)],
        ]);

        $item->update($data);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'updated_item',
            'target_type' => Item::class,
            'target_id' => $item->id,
            'details' => 'Mengubah data item '.$item->name,
        ]);

        return redirect()->route('admin.items.index')->with('success', 'Item berhasil diperbarui.');
    }

    public function adjustStock(Request $request, Item $item)
    {
        $data = $request->validate([
            'new_stock' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($item, $data) {
            $oldStock = $item->stock;
            $newStock = $data['new_stock'];
            $delta = $newStock - $oldStock;

            $item->update(['stock' => $newStock]);

            StockMovement::create([
                'item_id' => $item->id,
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'quantity' => abs($delta),
                'unit' => $item->unit,
                'reason' => $data['reason'],
                'stock_request_id' => null,
                'user_id' => Auth::id(),
                'balance_before' => $oldStock,
                'balance_after' => $newStock,
                'note' => 'Penyesuaian stok dari '.$oldStock.' ke '.$newStock,
                'occurred_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'adjusted_stock',
                'target_type' => Item::class,
                'target_id' => $item->id,
                'details' => 'Menyesuaikan stok '.$item->name.' dari '.$oldStock.' ke '.$newStock.' ('.$data['reason'].')',
            ]);
        });

        return redirect()->route('admin.items.index')->with('success', 'Stok '.$item->name.' berhasil disesuaikan.');
    }

    public function destroyItem(Item $item)
    {
        $name = $item->name;
        $location = $item->storageLocation;

        DB::transaction(function () use ($item, $location) {
            $item->delete();

            if ($location) {
                $location->update(['status' => StorageLocation::STATUS_EMPTY]);
            }
        });

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'deleted_item',
            'target_type' => Item::class,
            'target_id' => null,
            'details' => 'Menghapus item '.$name,
        ]);

        return redirect()->route('admin.items.index')->with('success', 'Item berhasil dihapus.');
    }

    // ── Import Excel ───────────────────────────────────────────────

    public function importIndex()
    {
        $racks = ['A', 'B', 'C', 'D', 'E'];

        $rackInfo = collect($racks)->map(function ($rack) {
            $totalCount = StorageLocation::where('rack', $rack)->count();
            $occupiedCount = StorageLocation::where('rack', $rack)
                ->where('status', StorageLocation::STATUS_OCCUPIED)
                ->count();
            $emptyCount = $totalCount - $occupiedCount;

            return [
                'rack' => $rack,
                'prefix' => StorageLocation::getPrefixForRack($rack),
                'total' => $totalCount,
                'occupied' => $occupiedCount,
                'empty_slots' => $emptyCount,
            ];
        });

        return view('admin.import', compact('racks', 'rackInfo'));
    }

    public function importPreview(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:2048'],
            'rack_location' => ['required', 'in:A,B,C,D,E'],
        ]);

        $rack = $data['rack_location'];

        try {
            $rows = XlsxParser::parse($request->file('file')->getRealPath());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        [$headers, $dataRows] = $this->normalizeImportRows($rows);
        $columns = $this->mapImportHeaders($headers);

        if ($columns['name'] === null) {
            return back()->with('error', 'Kolom "Nama Barang" tidak ditemukan di file Excel.');
        }

        $preview = [];
        $skipped = 0;

        foreach ($dataRows as $row) {
            $name = trim((string) ($row[$columns['name']] ?? ''));
            $subLocation = $columns['sub_location'] !== null
                ? trim((string) ($row[$columns['sub_location']] ?? ''))
                : '';

            $isEmptyLocation = ($name === '' && $subLocation !== '');

            if ($name === '' && !$isEmptyLocation) {
                $skipped++;
                continue;
            }

            $size = $columns['size'] !== null ? trim((string) ($row[$columns['size']] ?? '')) : '';
            $size = $size === '' ? null : $size;

            [$stock, $unit] = XlsxParser::parseQuantity(
                (string) ($columns['qty'] !== null ? ($row[$columns['qty']] ?? '') : '')
            );

            $preview[] = [
                'name' => $name,
                'size' => $size,
                'stock' => $stock,
                'unit' => $unit,
                'sub_location' => $subLocation !== '' ? $subLocation : null,
                'is_empty_location' => $isEmptyLocation,
            ];
        }

        $totalRows = count($preview);
        $existingEmpty = StorageLocation::where('rack', $rack)
            ->where('status', StorageLocation::STATUS_EMPTY)
            ->count();
        $newSlotsNeeded = max(0, $totalRows - $existingEmpty);

        $prefix = StorageLocation::getPrefixForRack($rack);
        $existingSlots = StorageLocation::where('rack', $rack)
            ->where('status', StorageLocation::STATUS_EMPTY)
            ->orderBy('number')
            ->pluck('code')
            ->toArray();
        $maxNumber = StorageLocation::where('rack', $rack)->max('number') ?? 0;

        $locationCodes = [];
        foreach (range(0, $totalRows - 1) as $i) {
            if ($i < count($existingSlots)) {
                $locationCodes[] = $existingSlots[$i];
            } else {
                $num = $maxNumber + ($i - count($existingSlots)) + 1;
                $locationCodes[] = $prefix . '-' . str_pad($num, 3, '0', STR_PAD_LEFT);
            }
        }

        session(['import_preview' => ['rack' => $rack, 'items' => $preview]]);

        return view('admin.import-preview', compact('preview', 'rack', 'skipped', 'existingEmpty', 'newSlotsNeeded', 'locationCodes'));
    }

    public function importExecute(Request $request)
    {
        $sessionData = session('import_preview');
        if (!$sessionData || !isset($sessionData['rack'], $sessionData['items'])) {
            return redirect()->route('admin.import.index')->with('error', 'Session import expired. Silakan upload ulang.');
        }

        $rack = $sessionData['rack'];
        $data = ['rack_location' => $rack, 'items' => $sessionData['items']];

        session()->forget('import_preview');

        $emptySlots = StorageLocation::where('rack', $rack)
            ->where('status', StorageLocation::STATUS_EMPTY)
            ->orderBy('number')
            ->get();

        $totalRows = count($data['items']);
        $needed = max(0, $totalRows - $emptySlots->count());

        $imported = 0;
        $locationsCreated = 0;

        DB::transaction(function () use ($data, $rack, $emptySlots, $needed, &$imported, &$locationsCreated) {
            $locations = $emptySlots->values();

            if ($needed > 0) {
                $maxNumber = StorageLocation::where('rack', $rack)->max('number') ?? 0;
                $prefix = StorageLocation::getPrefixForRack($rack);

                for ($i = 1; $i <= $needed; $i++) {
                    $num = $maxNumber + $i;
                    $locations->push(StorageLocation::create([
                        'code' => $prefix . '-' . str_pad($num, 3, '0', STR_PAD_LEFT),
                        'rack' => $rack,
                        'number' => $num,
                        'status' => StorageLocation::STATUS_EMPTY,
                    ]));
                }
            }

            foreach ($data['items'] as $index => $itemData) {
                $location = $locations[$index];
                $isEmptyLocation = !empty($itemData['is_empty_location']);

                $subLocation = $itemData['sub_location'] ?? null;
                if ($subLocation !== null) {
                    $location->update(['sub_location' => $subLocation]);
                }

                if ($isEmptyLocation) {
                    $location->update(['status' => StorageLocation::STATUS_EMPTY]);
                    $locationsCreated++;
                    continue;
                }

                $item = Item::create([
                    'name' => $itemData['name'],
                    'size' => $itemData['size'] ?? null,
                    'storage_location_id' => $location->id,
                    'stock' => $itemData['stock'],
                    'unit' => $itemData['unit'],
                ]);

                $location->update(['status' => StorageLocation::STATUS_OCCUPIED]);

                if ($itemData['stock'] > 0) {
                    StockMovement::create([
                        'item_id' => $item->id,
                        'type' => StockMovement::TYPE_ADJUSTMENT,
                        'quantity' => $itemData['stock'],
                        'unit' => $itemData['unit'],
                        'reason' => 'Saldo awal dari import',
                        'stock_request_id' => null,
                        'user_id' => Auth::id(),
                        'balance_before' => 0,
                        'balance_after' => $itemData['stock'],
                        'note' => 'Dibuat otomatis saat import',
                        'occurred_at' => now(),
                    ]);
                }

                $imported++;
            }
        });

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'imported_items',
            'target_type' => Item::class,
            'target_id' => null,
            'details' => 'Mengimpor '.$imported.' item dan '.$locationsCreated.' lokasi kosong ke Rak '.$rack,
        ]);

        $message = $imported.' item berhasil diimpor ke Rak '.$rack.'.';
        if ($locationsCreated > 0) {
            $message .= ' '.$locationsCreated.' lokasi kosong juga dibuat.';
        }

        return redirect()->route('admin.items.index')->with('success', $message);
    }

    // ── Storage Locations ──────────────────────────────────────────

    public function locationsIndex(Request $request)
    {
        $query = StorageLocation::with('items');

        $activeRack = $request->input('rack', 'A');
        $query->where('rack', $activeRack);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('sub_location', 'like', "%{$search}%")
                  ->orWhereHas('items', function ($iq) use ($search) {
                      $iq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $locations = $query->orderBy('number')->paginate(30)->withQueryString();

        $racks = ['A', 'B', 'C', 'D', 'E'];
        $rackStats = collect($racks)->map(function ($rack) {
            $total = StorageLocation::where('rack', $rack)->count();
            $occupied = StorageLocation::where('rack', $rack)
                ->where('status', StorageLocation::STATUS_OCCUPIED)
                ->count();

            return [
                'rack' => $rack,
                'prefix' => StorageLocation::getPrefixForRack($rack),
                'total' => $total,
                'occupied' => $occupied,
                'empty' => $total - $occupied,
            ];
        });

        $totalLocations = StorageLocation::count();
        $totalOccupied = StorageLocation::where('status', StorageLocation::STATUS_OCCUPIED)->count();
        $totalEmpty = $totalLocations - $totalOccupied;

        return view('admin.locations', compact('locations', 'activeRack', 'racks', 'rackStats', 'totalLocations', 'totalOccupied', 'totalEmpty'));
    }

    // ── Location Change Requests ───────────────────────────────────

    public function locationChangesIndex(Request $request)
    {
        $query = LocationChangeRequest::with(['item', 'fromLocation', 'toLocation.items', 'swapItem', 'requestedBy', 'approvedBy']);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        $changes = $query->latest()->paginate(20)->withQueryString();

        return view('admin.location-changes', compact('changes'));
    }

    public function exportLocationChangesExcel(Request $request)
    {
        $changes = LocationChangeExporter::query($request, 'admin')->get();
        $rows = LocationChangeExporter::buildRows($changes);

        if (empty($rows)) {
            return back()->with('error', 'Tidak ada data untuk di-export dengan filter yang dipilih.');
        }

        $export = new LocationChangeExport($rows);

        return response($export->toXlsx(), 200)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="riwayat-pengajuan-lokasi.xlsx"');
    }

    public function previewLocationChangesExport(Request $request)
    {
        $rows = LocationChangeExporter::buildRows(LocationChangeExporter::query($request, 'admin')->get());

        if (empty($rows)) {
            return back()->with('error', 'Tidak ada data untuk di-export dengan filter yang dipilih.');
        }

        return $this->exportPreview(
            'Riwayat Pengajuan Lokasi',
            ['ID', 'Barang', 'Kode Asal', 'Sub Asal', 'Kode Tujuan', 'Sub Tujuan', 'Pemohon', 'Status', 'Aksi', 'Diajukan', 'Diputuskan', 'Alasan', 'Catatan'],
            $rows,
            $this->exportUrl('/admin/location-changes', $request),
            [
                ['format' => 'PDF', 'url' => $this->exportUrl('/admin/location-changes/export/pdf', $request)],
                ['format' => 'Excel', 'url' => $this->exportUrl('/admin/location-changes/export/excel', $request)],
            ]
        );
    }

    public function exportLocationChangesPdf(Request $request)
    {
        $changes = LocationChangeExporter::query($request, 'admin')->get();
        $rows = LocationChangeExporter::buildRows($changes);

        if (empty($rows)) {
            return back()->with('error', 'Tidak ada data untuk di-export dengan filter yang dipilih.');
        }

        $pdf = Pdf::loadView('exports.location-changes', compact('rows'));

        return $pdf->download('riwayat-pengajuan-lokasi.pdf');
    }

    public function approveLocationChange(LocationChangeRequest $change, Request $httpRequest)
    {
        if (! $change->isPending()) {
            return back()->with('error', 'Permintaan ini sudah diproses.');
        }

        $data = $httpRequest->validate([
            'action' => ['nullable', 'string', 'in:swap,stack'],
            'swap_item_id' => ['nullable', 'integer', 'exists:items,id'],
        ]);

        $result = DB::transaction(function () use ($change, $data) {
            $locked = LocationChangeRequest::lockForUpdate()->find($change->id);

            if (! $locked || ! $locked->isPending()) {
                return 'already_processed';
            }

            $item = Item::lockForUpdate()->find($locked->item_id);
            $toLocation = StorageLocation::lockForUpdate()->find($locked->to_location_id);
            $fromLocation = StorageLocation::lockForUpdate()->find($locked->from_location_id);

            if (! $item || ! $fromLocation || ! $toLocation) {
                return 'invalid_location';
            }

            if ($item->storage_location_id !== $fromLocation->id) {
                $locked->update([
                    'status' => LocationChangeRequest::STATUS_APPROVED,
                    'admin_note' => 'Dibatalkan otomatis: lokasi item sudah berubah.',
                ]);

                return 'location_changed';
            }

            $targetItems = Item::where('storage_location_id', $toLocation->id)
                ->where('id', '!=', $item->id)
                ->lockForUpdate()
                ->get();

            // Slot tujuan kosong → pindahkan langsung
            if ($targetItems->isEmpty()) {
                $item->update(['storage_location_id' => $toLocation->id]);
                $fromLocation->syncStatus();
                $toLocation->syncStatus();
                $locked->approve(Auth::id());

                return 'approved_move';
            }

            $action = $data['action'] ?? null;
            $isLegacy = $locked->target_sub_location === null;

            // Tumpuk: barang ditumpuk di slot tujuan, slot asal menjadi kosong
            if ($action === 'stack') {
                $item->update(['storage_location_id' => $toLocation->id]);
                $fromLocation->syncStatus();
                $toLocation->syncStatus();
                $locked->update(['resolution_action' => 'stack']);
                $locked->approve(Auth::id());

                return 'approved_stack';
            }

            // Tukar tempat: pilih barang di slot tujuan yang ikut ditukar
            $requestedSwapId = (int) ($data['swap_item_id'] ?? 0);
            $swapItem = $targetItems->firstWhere('id', $requestedSwapId);

            if ($isLegacy && $requestedSwapId === 0) {
                $swapItem = $swapItem ?: $targetItems->first();
            }

            if (! $swapItem) {
                return 'invalid_swap_item';
            }

            $item->update(['storage_location_id' => $toLocation->id]);
            $swapItem->update(['storage_location_id' => $fromLocation->id]);
            $fromLocation->syncStatus();
            $toLocation->syncStatus();
            $locked->update(['resolution_action' => 'swap', 'swap_item_id' => $swapItem->id]);
            $locked->approve(Auth::id());

            return 'approved_swap';
        });

        return match ($result) {
            'already_processed' => back()->with('error', 'Permintaan ini sudah diproses.'),
            'location_changed' => back()->with('error', 'Lokasi item sudah berubah sejak pengajuan dibuat.'),
            'invalid_location' => back()->with('error', 'Lokasi tidak valid.'),
            'invalid_swap_item' => back()->with('error', 'Pilih barang di slot tujuan yang akan ditukar.'),
            default => $this->finishLocationApproval($change, $result),
        };
    }

    protected function finishLocationApproval(LocationChangeRequest $change, string $result)
    {
        $change->load(['item', 'fromLocation', 'toLocation', 'swapItem']);

        $details = 'Menyetujui ';
        $message = match ($result) {
            'approved_move' => 'Pengajuan pemindahan lokasi berhasil diproses.',
            'approved_swap' => 'Pengajuan pertukaran lokasi berhasil diproses.',
            'approved_stack' => 'Pengajuan penumpukan lokasi berhasil diproses.',
            default => 'Pengajuan lokasi berhasil diproses.',
        };

        if ($result === 'approved_move') {
            $details .= 'pemindahan: '.$change->item->name.' dari '.$change->fromLocation->code.' ke '.$change->toLocation->code;
        } elseif ($result === 'approved_swap') {
            $swapName = $change->swapItem?->name
                ?? Item::where('storage_location_id', $change->from_location_id)
                    ->where('id', '!=', $change->item_id)
                    ->value('name')
                ?? 'barang lain';
            $details .= 'pertukaran: '.$change->item->name.' → '.$change->toLocation->code.' dan '.$swapName.' → '.$change->fromLocation->code;
        } elseif ($result === 'approved_stack') {
            $details .= 'penumpukan: '.$change->item->name.' → '.$change->toLocation->code.' (ditumpuk dengan barang lain)';
        } else {
            return back()->with('error', 'Pengajuan sudah diproses.');
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'approved_location_change',
            'target_type' => LocationChangeRequest::class,
            'target_id' => $change->id,
            'details' => $details,
        ]);

        return back()->with('success', $message);
    }

    public function rejectLocationChange(LocationChangeRequest $change, Request $httpRequest)
    {
        if (! $change->isPending()) {
            return back()->with('error', 'Permintaan ini sudah diproses.');
        }

        $data = $httpRequest->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $change->reject(Auth::id(), $data['admin_note'] ?? null);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'rejected_location_change',
            'target_type' => LocationChangeRequest::class,
            'target_id' => $change->id,
            'details' => 'Menolak perubahan lokasi item '.$change->item->name,
        ]);

        return back()->with('success', 'Perubahan lokasi ditolak.');
    }

    // ── Users ──────────────────────────────────────────────────────

    public function usersIndex(Request $request)
    {
        $query = User::withCount('stockRequests');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && $request->input('role') !== 'all') {
            $query->where('role', $request->input('role'));
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'in:gudang,hr,admin,director'],
        ]);

        $generatedPassword = filled($data['password'] ?? null) ? $data['password'] : Str::password(12);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($generatedPassword),
            'role' => $data['role'],
            'must_change_password' => blank($data['password'] ?? null),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'created_user',
            'target_type' => User::class,
            'target_id' => $user->id,
            'details' => 'Membuat akun '.$user->email,
        ]);

        $message = 'Pengguna berhasil ditambahkan.';
        if (blank($data['password'] ?? null)) {
            $message .= ' Password sementara: '.$generatedPassword;
        }

        return redirect()->route('admin.users.index')->with('success', $message);
    }

    public function resetUserPassword(User $user)
    {
        $generatedPassword = Str::password(12);

        $user->update([
            'password' => Hash::make($generatedPassword),
            'must_change_password' => true,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'reset_password',
            'target_type' => User::class,
            'target_id' => $user->id,
            'details' => 'Mereset password akun '.$user->email,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Password direset. Password sementara: '.$generatedPassword);
    }

    public function toggleUserStatus(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Tidak bisa mengubah status akun sendiri.');
        }

        if ($user->is_active && $this->isLastActiveAdmin($user)) {
            return back()->with('error', 'Tidak bisa menonaktifkan admin aktif terakhir.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'toggle_user_status',
            'target_type' => User::class,
            'target_id' => $user->id,
            'details' => ($user->is_active ? 'Mengaktifkan' : 'Menonaktifkan').' akun '.$user->email,
        ]);

        return back()->with('success', 'Status akun berhasil diperbarui.');
    }

    public function updateUserRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', 'in:gudang,hr,admin,director'],
        ]);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Tidak bisa mengubah role akun sendiri.');
        }

        if ($user->role === 'admin' && $user->is_active && $data['role'] !== 'admin' && $this->isLastActiveAdmin($user)) {
            return back()->with('error', 'Tidak bisa mengubah role admin aktif terakhir.');
        }

        $user->update(['role' => $data['role']]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'updated_user_role',
            'target_type' => User::class,
            'target_id' => $user->id,
            'details' => 'Mengubah role akun '.$user->email.' menjadi '.$data['role'],
        ]);

        return back()->with('success', 'Role pengguna berhasil diperbarui.');
    }

    public function deleteUser(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        if ($user->role === 'admin' && $user->is_active && $this->isLastActiveAdmin($user)) {
            return back()->with('error', 'Tidak bisa menghapus admin aktif terakhir.');
        }

        $email = $user->email;
        $user->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'deleted_user',
            'target_type' => User::class,
            'target_id' => null,
            'details' => 'Menghapus akun '.$email,
        ]);

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    public function loginAs(User $user, Request $request)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Tidak bisa login sebagai diri sendiri.');
        }

        if ($user->role === 'admin') {
            return back()->with('error', 'Login As hanya untuk akun non-admin.');
        }

        if (! $user->is_active) {
            return back()->with('error', 'Akun target nonaktif.');
        }

        $adminId = Auth::id();

        AuditLog::create([
            'user_id' => $adminId,
            'action' => 'impersonated_user',
            'target_type' => User::class,
            'target_id' => $user->id,
            'details' => 'Admin login sebagai '.$user->email.' ('.$user->role.')',
        ]);

        Auth::login($user);

        session(['impersonate_by' => $adminId]);
        $request->session()->regenerate();

        return redirect($this->homeRouteFor($user->role));
    }

    public function stopImpersonation(Request $request)
    {
        $adminId = session('impersonate_by');

        if (! $adminId) {
            return redirect()->route('admin.dashboard');
        }

        $admin = User::find($adminId);

        session()->forget('impersonate_by');

        if ($admin) {
            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'impersonation_stopped',
                'target_type' => User::class,
                'target_id' => $admin->id,
                'details' => 'Admin kembali ke akun asli',
            ]);

            Auth::login($admin);
            $request->session()->regenerate();
        }

        return redirect()->route('admin.dashboard');
    }

    // ── Audit Log ──────────────────────────────────────────────────

    public function auditIndex(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('details', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->latest()->paginate(30)->withQueryString();

        $actions = AuditLog::distinct()->pluck('action')->sort()->values();

        return view('admin.audit', compact('logs', 'actions'));
    }

    public function exportAuditExcel(Request $request)
    {
        $rows = $this->buildAuditExportRows($request);

        if (empty($rows)) {
            return back()->with('error', 'Tidak ada data untuk di-export dengan filter yang dipilih.');
        }

        $export = new AuditLogExport($rows);

        return response($export->toXlsx(), 200)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="audit-log.xlsx"');
    }

    public function previewAuditExport(Request $request)
    {
        $rows = $this->buildAuditExportRows($request);

        if (empty($rows)) {
            return back()->with('error', 'Tidak ada data untuk di-export dengan filter yang dipilih.');
        }

        return $this->exportPreview(
            'Audit Log',
            ['Waktu', 'User', 'Aksi', 'Target', 'Detail'],
            $rows,
            $this->exportUrl('/admin/audit', $request),
            [['format' => 'Excel', 'url' => $this->exportUrl('/admin/audit/export/excel', $request)]]
        );
    }

    protected function buildAuditExportRows(Request $request): array
    {
        $query = AuditLog::with('user');

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('details', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        return $query->latest()->get()->map(function (AuditLog $log) {
            return [
                'waktu' => $log->created_at?->format('d M Y, H:i') ?? '—',
                'user' => $log->user?->name ?? 'System',
                'aksi' => $log->action,
                'target' => $log->target_type
                    ? class_basename($log->target_type) . ($log->target_id ? ' #' . $log->target_id : '')
                    : '—',
                'detail' => $log->details ?? '—',
            ];
        })->all();
    }

    protected function exportUrl(string $path, Request $request): string
    {
        return url($path).($request->getQueryString() ? '?'.$request->getQueryString() : '');
    }

    // ── Developer Mode ─────────────────────────────────────────────

    public function toggleDevMode()
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $nowActive = ! Setting::enabled('dev_mode');
        Setting::set('dev_mode', $nowActive ? '1' : '0');

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $nowActive ? 'dev_mode_enabled' : 'dev_mode_disabled',
            'target_type' => Setting::class,
            'target_id' => null,
            'details' => $nowActive ? 'Admin mengaktifkan Developer Mode' : 'Admin menonaktifkan Developer Mode',
        ]);

        return redirect()->route('admin.dashboard')->with(
            'success',
            $nowActive ? 'Developer Mode aktif.' : 'Developer Mode nonaktif.'
        );
    }

    // ── Backup & Restore ───────────────────────────────────────────

    public function backupIndex()
    {
        $backups = collect(glob(storage_path('app/backups/*.zip')) ?: [])
            ->sortByDesc(fn (string $path) => filemtime($path))
            ->map(fn (string $path) => [
                'name' => basename($path),
                'size' => $this->formatBytes(filesize($path)),
                'time' => date('d M Y, H:i', filemtime($path)),
            ])
            ->values();

        return view('admin.backup', compact('backups'));
    }

    public function createBackup()
    {
        $dbPath = config('database.connections.sqlite.database');

        try {
            DB::select('PRAGMA wal_checkpoint(TRUNCATE)');
        } catch (\Throwable) {
        }

        if (! is_file($dbPath)) {
            return back()->with('error', 'Backup gagal: file database tidak ditemukan.');
        }

        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'backup_mvpwarehouse_'.now()->format('Y_m_d_His').'.zip';
        $zip = new \ZipArchive;

        if ($zip->open($dir.'/'.$filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Backup gagal: tidak dapat membuat arsip.');
        }

        $zip->addFile($dbPath, 'database.sqlite');
        $zip->close();

        $this->pruneBackups($dir);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'backup_created',
            'target_type' => null,
            'target_id' => null,
            'details' => 'Membuat backup database '.$filename,
        ]);

        return redirect()->route('admin.backups.index')
            ->with('success', 'Backup berhasil: '.$filename);
    }

    public function downloadBackup(string $file)
    {
        $path = $this->resolveBackupPath($file);
        if ($path === null) {
            abort(404);
        }

        return response()->download($path);
    }

    public function deleteBackup(string $file)
    {
        $path = $this->resolveBackupPath($file);
        if ($path === null) {
            return redirect()->route('admin.backups.index')->with('error', 'File backup tidak ditemukan.');
        }

        unlink($path);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'backup_deleted',
            'target_type' => null,
            'target_id' => null,
            'details' => 'Menghapus backup '.$file,
        ]);

        return redirect()->route('admin.backups.index')->with('success', 'Backup berhasil dihapus.');
    }

    public function restoreBackup(string $file, Request $httpRequest)
    {
        $path = $this->resolveBackupPath($file);
        if ($path === null) {
            return redirect()->route('admin.backups.index')->with('error', 'File backup tidak ditemukan.');
        }

        $dbPath = config('database.connections.sqlite.database');

        try {
            DB::select('PRAGMA wal_checkpoint(TRUNCATE)');
        } catch (\Throwable) {
        }

        $dir = storage_path('app/backups');
        $safetyName = 'pre_restore_'.now()->format('Y_m_d_His').'.zip';
        if (is_file($dbPath)) {
            $safetyZip = new \ZipArchive;
            if ($safetyZip->open($dir.'/'.$safetyName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                $safetyZip->addFile($dbPath, 'database.sqlite');
                $safetyZip->close();
            }
        }

        $tmpDir = $dir.'/_restore_'.bin2hex(random_bytes(4));
        mkdir($tmpDir, 0755, true);

        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            $this->deleteDir($tmpDir);
            return redirect()->route('admin.backups.index')->with('error', 'Restore gagal: arsip backup rusak.');
        }

        $zip->extractTo($tmpDir);
        $zip->close();

        $extracted = $tmpDir.'/database.sqlite';
        if (! is_file($extracted)) {
            $this->deleteDir($tmpDir);
            return redirect()->route('admin.backups.index')->with('error', 'Restore gagal: isi arsip tidak valid.');
        }

        if (! $this->isValidSqlite($extracted)) {
            $this->deleteDir($tmpDir);
            return redirect()->route('admin.backups.index')->with('error', 'Restore gagal: database backup rusak.');
        }

        $admin = Auth::user();

        DB::disconnect();
        @unlink($dbPath.'-wal');
        @unlink($dbPath.'-shm');
        copy($extracted, $dbPath);
        $this->deleteDir($tmpDir);

        try {
            DB::connection()->getPdo()->query('SELECT 1');
        } catch (\Throwable) {
            return redirect()->route('admin.backups.index')->with('error', 'Restore gagal: database hasil restore rusak.');
        }

        AuditLog::create([
            'user_id' => $admin?->id,
            'action' => 'backup_restored',
            'target_type' => null,
            'target_id' => null,
            'details' => 'Memulihkan database dari backup '.$file,
        ]);

        if ($admin) {
            Auth::login($admin);
            $httpRequest->session()->regenerate();
        }

        return redirect()->route('admin.backups.index')->with('success', 'Database berhasil dipulihkan dari '.$file.'.');
    }

    // ── Reset Maintenance ──────────────────────────────────────────

    public function resetIndex()
    {
        $stats = [
            'items' => Item::count(),
            'storage_locations' => StorageLocation::count(),
            'stock_movements' => StockMovement::count(),
            'stock_requests' => StockRequest::count(),
            'location_change_requests' => LocationChangeRequest::count(),
            'audit_logs' => AuditLog::count(),
        ];

        return view('admin.reset', compact('stats'));
    }

    public function resetMasterItems()
    {
        $this->createSafetyBackup();

        $count = DB::transaction(function () {
            $count = Item::count();

            Item::query()->forceDelete();
            StorageLocation::query()->update([
                'status' => StorageLocation::STATUS_EMPTY,
                'sub_location' => null,
            ]);

            return $count;
        });

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'reset_master_items',
            'target_type' => null,
            'target_id' => null,
            'details' => 'Reset Master Barang: '.$count.' item dan seluruh lokasi di-reset.',
        ]);

        return back()->with('success', 'Master Barang berhasil di-reset. '.$count.' item dihapus, seluruh lokasi dikosongkan.');
    }

    public function resetStockMovements()
    {
        $this->createSafetyBackup();

        $count = DB::transaction(function () {
            $count = StockMovement::count();

            Item::query()->update(['stock' => 0]);
            StockMovement::query()->forceDelete();

            return $count;
        });

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'reset_stock_movements',
            'target_type' => null,
            'target_id' => null,
            'details' => 'Reset Pergerakan Stok: '.$count.' record dihapus. Stok seluruh item di-reset ke 0.',
        ]);

        return back()->with('success', 'Pergerakan Stok berhasil di-reset. '.$count.' record dihapus. Stok seluruh item di-reset ke 0.');
    }

    public function resetStockRequests()
    {
        $this->createSafetyBackup();

        $count = DB::transaction(function () {
            $count = StockRequest::count();

            StockRequest::query()->forceDelete();
            DB::table('request_histories')->delete();

            return $count;
        });

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'reset_stock_requests',
            'target_type' => null,
            'target_id' => null,
            'details' => 'Reset Request Barang: '.$count.' request dihapus.',
        ]);

        return back()->with('success', 'Request Barang berhasil di-reset. '.$count.' request dihapus.');
    }

    public function resetLocationChanges()
    {
        $this->createSafetyBackup();

        $count = LocationChangeRequest::count();
        LocationChangeRequest::query()->forceDelete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'reset_location_changes',
            'target_type' => null,
            'target_id' => null,
            'details' => 'Reset Pengajuan Lokasi: '.$count.' pengajuan dihapus.',
        ]);

        return back()->with('success', 'Pengajuan Lokasi berhasil di-reset. '.$count.' pengajuan dihapus.');
    }

    // ── Helpers ────────────────────────────────────────────────────

    protected function createSafetyBackup(): void
    {
        $dbPath = config('database.connections.sqlite.database');

        try {
            DB::select('PRAGMA wal_checkpoint(TRUNCATE)');
        } catch (\Throwable) {
        }

        if (! is_file($dbPath)) {
            return;
        }

        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'pre_reset_'.now()->format('Y_m_d_His').'.zip';
        $zip = new \ZipArchive;

        if ($zip->open($dir.'/'.$filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $zip->addFile($dbPath, 'database.sqlite');
            $zip->close();
        }
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }
        return $bytes.' B';
    }

    protected function normalizeImportRows(array $rows): array
    {
        foreach ($rows as $index => $row) {
            $hasContent = collect($row)->contains(fn ($value) => trim((string) $value) !== '');
            if ($hasContent) {
                return [$row, array_slice($rows, $index + 1)];
            }
        }
        return [[], []];
    }

    protected function mapImportHeaders(array $headers): array
    {
        $map = ['name' => null, 'size' => null, 'qty' => null, 'sub_location' => null];

        foreach ($headers as $index => $header) {
            $key = preg_replace('/[^a-z0-9]+/', ' ', strtolower(trim((string) $header)));

            if (in_array($key, ['nama barang', 'nama', 'daftar barang', 'barang', 'name'], true)) {
                $map['name'] ??= $index;
            } elseif (in_array($key, ['size', 'ukuran'], true)) {
                $map['size'] ??= $index;
            } elseif (in_array($key, ['qty', 'quantity', 'jumlah', 'stok', 'stock', 'kuantitas'], true)) {
                $map['qty'] ??= $index;
            } elseif (in_array($key, ['sub lokasi', 'sub_location', 'sublokasi', 'lokasi aktual', 'lokasi'], true)) {
                $map['sub_location'] ??= $index;
            }
        }

        return $map;
    }

    protected function isLastActiveAdmin(User $user): bool
    {
        return User::where('role', 'admin')
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->count() === 0;
    }

    protected function homeRouteFor(string $role): string
    {
        return match ($role) {
            'hr' => '/hr/dashboard',
            'admin' => '/admin/dashboard',
            default => '/gudang/dashboard',
        };
    }

    protected function isValidSqlite(string $path): bool
    {
        try {
            $pdo = new \PDO('sqlite:'.$path);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $integrity = $pdo->query('PRAGMA integrity_check')->fetchColumn();
            return $integrity === 'ok';
        } catch (\Throwable) {
            return false;
        }
    }

    protected function pruneBackups(string $dir, int $keep = 10): void
    {
        $files = collect(glob($dir.'/backup_mvpwarehouse_*.zip') ?: [])
            ->merge(glob($dir.'/pre_restore_*.zip') ?: [])
            ->merge(glob($dir.'/pre_reset_*.zip') ?: [])
            ->sortByDesc(fn (string $path) => filemtime($path))
            ->values();

        foreach ($files->slice($keep)->all() as $path) {
            @unlink($path);
        }
    }

    protected function resolveBackupPath(string $file): ?string
    {
        if (! preg_match('/^[A-Za-z0-9_\-]+\.zip$/', $file)) {
            return null;
        }
        $path = storage_path('app/backups/'.$file);
        return is_file($path) ? $path : null;
    }

    protected function deleteDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (glob($dir.'/*') ?: [] as $file) {
            is_dir($file) ? $this->deleteDir($file) : @unlink($file);
        }
        @rmdir($dir);
    }
}
