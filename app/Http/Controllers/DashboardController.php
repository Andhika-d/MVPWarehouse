<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockRequest;
use App\Models\StorageLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function gudangDashboard()
    {
        $requests = StockRequest::with('item')
            ->where('user_id', Auth::id())
            ->latest()
            ->take(5)
            ->get();

        $totalRequests = StockRequest::where('user_id', Auth::id())->count();
        $pendingRequests = StockRequest::where('user_id', Auth::id())
            ->whereIn('status', ['Menunggu Review', 'Pending'])
            ->count();
        $approvedRequests = StockRequest::where('user_id', Auth::id())
            ->where('status', 'Disetujui')
            ->count();
        $rejectedRequests = StockRequest::where('user_id', Auth::id())
            ->where('status', 'Ditolak')
            ->count();
        $urgentRequests = StockRequest::where('user_id', Auth::id())
            ->where('priority', 'Mendesak')
            ->whereIn('status', StockRequest::ACTIONABLE_STATUSES)
            ->count();
        $waitingReceipt = StockRequest::where('user_id', Auth::id())
            ->whereIn('status', ['Disetujui', 'Sebagian Diterima'])
            ->whereColumn('received_quantity', '<', 'quantity')
            ->count();

        return view('gudang.dashboard', compact('requests', 'totalRequests', 'pendingRequests', 'approvedRequests', 'rejectedRequests', 'urgentRequests', 'waitingReceipt'));
    }

    public function gudangStock()
    {
        $racks = ['A', 'B', 'C', 'D', 'E'];

        $rackData = collect($racks)->map(function ($rack) {
            $total = StorageLocation::where('rack', $rack)->count();
            $occupied = StorageLocation::where('rack', $rack)
                ->where('status', StorageLocation::STATUS_OCCUPIED)->count();
            $totalStock = (int) DB::table('storage_locations')
                ->join('items', 'items.storage_location_id', '=', 'storage_locations.id')
                ->where('storage_locations.rack', $rack)
                ->where('storage_locations.status', StorageLocation::STATUS_OCCUPIED)
                ->sum('items.stock');

            return [
                'rack' => $rack,
                'total' => $total,
                'totalItems' => $occupied,
                'totalStock' => $totalStock,
            ];
        });

        $activeRack = request('rack');
        $search = request('search');
        $status = request('status');

        $query = StorageLocation::with('items');

        if ($activeRack) {
            $query->where('rack', $activeRack);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('sub_location', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($iq) use ($search) {
                        $iq->where('name', 'like', "%{$search}%")
                            ->orWhere('size', 'like', "%{$search}%");
                    });
            });
        }

        if ($status === 'occupied') {
            $query->where('status', StorageLocation::STATUS_OCCUPIED);
        } elseif ($status === 'empty') {
            $query->where('status', StorageLocation::STATUS_EMPTY);
        } elseif ($status === 'item_empty') {
            $query->whereHas('items', function ($itemQuery) {
                $itemQuery->where('stock', 0);
            });
        }

        $locations = $query->orderBy('sub_location', 'asc')
            ->orderBy('number', 'asc')
            ->paginate(20)
            ->appends(request()->query());

        $basePath = '/gudang/stock';

        $summary = $this->stockSummary();

        return view('gudang.stock', compact('rackData', 'racks', 'activeRack', 'locations', 'basePath', 'search', 'status', 'summary'));
    }

    public function hrStock()
    {
        $racks = ['A', 'B', 'C', 'D', 'E'];

        $rackData = collect($racks)->map(function ($rack) {
            $total = StorageLocation::where('rack', $rack)->count();
            $occupied = StorageLocation::where('rack', $rack)
                ->where('status', StorageLocation::STATUS_OCCUPIED)->count();
            $totalStock = (int) DB::table('storage_locations')
                ->join('items', 'items.storage_location_id', '=', 'storage_locations.id')
                ->where('storage_locations.rack', $rack)
                ->where('storage_locations.status', StorageLocation::STATUS_OCCUPIED)
                ->sum('items.stock');

            return [
                'rack' => $rack,
                'total' => $total,
                'totalItems' => $occupied,
                'totalStock' => $totalStock,
            ];
        });

        $activeRack = request('rack');
        $search = request('search');
        $status = request('status');

        $query = StorageLocation::with('items');

        if ($activeRack) {
            $query->where('rack', $activeRack);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('sub_location', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($iq) use ($search) {
                        $iq->where('name', 'like', "%{$search}%")
                            ->orWhere('size', 'like', "%{$search}%");
                    });
            });
        }

        if ($status === 'occupied') {
            $query->where('status', StorageLocation::STATUS_OCCUPIED);
        } elseif ($status === 'empty') {
            $query->where('status', StorageLocation::STATUS_EMPTY);
        } elseif ($status === 'item_empty') {
            $query->whereHas('items', function ($itemQuery) {
                $itemQuery->where('stock', 0);
            });
        }

        $locations = $query->orderBy('sub_location', 'asc')
            ->orderBy('number', 'asc')
            ->paginate(20)
            ->appends(request()->query());

        $basePath = '/hr/stock';

        $summary = $this->stockSummary();

        return view('gudang.stock', compact('rackData', 'racks', 'activeRack', 'locations', 'basePath', 'search', 'status', 'summary'));
    }

    private function stockSummary(): array
    {
        return [
            'total_items' => StorageLocation::count(),
            'total_stock' => (int) Item::sum('stock'),
            'low_stock' => Item::where('stock', '>', 0)->where('stock', '<=', 5)->count(),
            'out_of_stock' => Item::where('stock', 0)->count(),
        ];
    }

    public function hrDashboard()
    {
        $actionableRequests = StockRequest::with(['item', 'user'])
            ->whereIn('status', ['Menunggu Review', 'Pending'])
            ->orderByRaw("CASE WHEN priority = 'Mendesak' THEN 0 ELSE 1 END")
            ->latest()
            ->get();

        $pendingRequests = StockRequest::whereIn('status', ['Menunggu Review', 'Pending'])->count();
        $urgentRequests = StockRequest::where('priority', 'Mendesak')
            ->whereIn('status', ['Menunggu Review', 'Pending'])
            ->count();
        $approvedRequests = StockRequest::where('status', 'Disetujui')->count();
        $rejectedRequests = StockRequest::where('status', 'Ditolak')->count();
        $waitingReceipt = StockRequest::whereIn('status', ['Disetujui', 'Sebagian Diterima'])
            ->whereColumn('received_quantity', '<', 'quantity')
            ->count();
        $completedRequests = StockRequest::where('status', 'Diterima Penuh')->count();

        $now = now();
        $monthlyTotal = StockRequest::whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->count();

        $statusDistribution = StockRequest::whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('hr.dashboard', compact(
            'actionableRequests',
            'pendingRequests',
            'urgentRequests',
            'approvedRequests',
            'rejectedRequests',
            'waitingReceipt',
            'completedRequests',
            'monthlyTotal',
            'statusDistribution',
        ));
    }
}
