<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockRequest;
use Illuminate\Support\Facades\Auth;

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
            $items = Item::where('rack_location', $rack)->get();
            return [
                'rack' => $rack,
                'items' => $items,
                'totalItems' => $items->count(),
                'totalStock' => $items->sum('stock'),
            ];
        });

        $activeRack = request('rack');
        $items = $activeRack
            ? Item::where('rack_location', $activeRack)->get()
            : collect();

        return view('gudang.stock', compact('rackData', 'racks', 'activeRack', 'items'));
    }

    public function hrDashboard()
    {
        $requests = StockRequest::with(['item', 'user'])
            ->latest()
            ->take(5)
            ->get();

        $pendingRequests = StockRequest::whereIn('status', ['Menunggu Review', 'Pending'])->count();
        $urgentRequests = StockRequest::where('priority', 'Mendesak')
            ->whereIn('status', ['Menunggu Review', 'Pending'])
            ->count();
        $approvedRequests = StockRequest::where('status', 'Disetujui')
            ->whereNull('completed_at')
            ->count();
        $rejectedRequests = StockRequest::where('status', 'Ditolak')->count();
        $monthlyRequests = StockRequest::selectRaw('COUNT(*) as total, strftime("%m", created_at) as month')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('hr.dashboard', compact('requests', 'pendingRequests', 'urgentRequests', 'approvedRequests', 'rejectedRequests', 'monthlyRequests'));
    }
}
