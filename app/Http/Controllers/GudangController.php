<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockMovement;
use App\Models\StockRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GudangController extends Controller
{
    public function penerimaanIndex(Request $request)
    {
        $query = StockRequest::with('item')
            ->whereIn('status', ['Disetujui', 'Sebagian Diterima'])
            ->whereColumn('received_quantity', '<', 'quantity');

        if ($request->filled('month')) {
            $date = \Carbon\Carbon::parse($request->input('month'));
            $query->whereYear('created_at', $date->year)
                  ->whereMonth('created_at', $date->month);
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

            $remaining = $stockRequest->remainingQuantity();
            $receiveQty = min((int) $data['received_quantity'], $remaining);

            $stockRequest->increment('received_quantity', $receiveQty);

            $stockRequest->refresh();

            if ($stockRequest->received_quantity >= $stockRequest->quantity) {
                $stockRequest->update(['status' => 'Diterima Penuh']);
            } else {
                $stockRequest->update(['status' => 'Sebagian Diterima']);
            }

            $item = $stockRequest->item;
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
                'balance_after' => $item->stock,
                'note' => $data['note'] ?? null,
                'occurred_at' => now(),
            ]);

            $stockRequest->requestHistories()->create([
                'user_id' => Auth::id(),
                'status' => $stockRequest->status,
                'note' => 'Diterima ' . $receiveQty . ' ' . $stockRequest->unit . ($data['note'] ? ' — ' . $data['note'] : ''),
            ]);

            return back()->with('success', $receiveQty . ' ' . $stockRequest->unit . ' berhasil diterima dan stok telah bertambah.');
        });
    }

    public function barangKeluarIndex()
    {
        $items = Item::where('stock', '>', 0)->orderBy('name')->get();

        $itemOptions = $items->map(fn ($item) => [
            'id' => $item->id,
            'label' => $item->display_name,
            'stock' => $item->stock,
            'unit' => $item->unit,
            'rack_location' => $item->rack_location,
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
                'balance_after' => $item->stock,
                'note' => $data['note'] ?? null,
                'occurred_at' => now(),
            ]);

            return back()->with('success', $data['quantity'] . ' ' . $item->unit . ' ' . $item->name . ' berhasil dicatat keluar. Stok tersisa: ' . $item->stock);
        });
    }

    public function movementsIndex(Request $request)
    {
        $query = StockMovement::with('item', 'stockRequest', 'user')->orderByDesc('occurred_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        if ($request->filled('date_from')) {
            $query->where('occurred_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('occurred_at', '<=', $request->date_to . ' 23:59:59');
        }

        $movements = $query->paginate(20)->withQueryString();
        $items = Item::orderBy('name')->get();

        return view('gudang.movements', compact('movements', 'items'));
    }
}
