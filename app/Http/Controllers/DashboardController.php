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
            ->count();

        return view('gudang.dashboard', compact('requests', 'totalRequests', 'pendingRequests', 'approvedRequests', 'rejectedRequests', 'urgentRequests'));
    }

    public function gudangStock()
    {
        $items = Item::all();
        $racks = ['A', 'B', 'C', 'D', 'E'];

        return view('gudang.stock', compact('items', 'racks'));
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
