<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\LocationChangeRequest;
use App\Models\StockMovement;
use App\Models\StockRequest;
use App\Models\StorageLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DirectorController extends Controller
{
    public function dashboard()
    {
        $now = Carbon::now();

        // === Core KPI ===
        $pendingCount = StockRequest::whereIn('status', ['Menunggu Review', 'Pending'])->count();
        $urgentPendingCount = StockRequest::where('priority', 'Mendesak')
            ->whereIn('status', ['Menunggu Review', 'Pending'])
            ->count();
        $waitingReceiptCount = StockRequest::whereIn('status', ['Disetujui', 'Sebagian Diterima'])
            ->whereColumn('received_quantity', '<', 'quantity')
            ->count();

        $warehouseSummary = [
            'item_types' => Item::count(),
            'total_stock' => (int) Item::sum('stock'),
            'occupied_locations' => StorageLocation::whereHas('items')->count(),
            'total_locations' => StorageLocation::count(),
        ];

        $stageLabels = [
            'request_to_approval' => 'Request → Approval',
            'approval_to_receipt' => 'Approval → Penerimaan Awal',
            'receipt_to_complete' => 'Penerimaan Awal → Selesai',
        ];
        $delayThresholdSeconds = 3 * 86400;
        $activeStageCounts = array_fill_keys(array_keys($stageLabels), 0);

        $activeBottlenecks = StockRequest::with(['item', 'user', 'requestHistories', 'stockMovements'])
            ->whereIn('status', ['Menunggu Review', 'Pending', 'Disetujui', 'Sebagian Diterima'])
            ->get()
            ->map(function ($stockRequest) use ($now, $delayThresholdSeconds, $stageLabels) {
                $approvalAt = $stockRequest->approved_at
                    ?? $stockRequest->requestHistories->where('status', 'Disetujui')->sortBy('created_at')->first()?->created_at;
                $firstReceiptMovement = $stockRequest->stockMovements
                    ->where('type', StockMovement::TYPE_IN)
                    ->sortBy(fn ($movement) => $movement->occurred_at ?? $movement->created_at)
                    ->first();
                $firstReceiptAt = $firstReceiptMovement?->occurred_at
                    ?? $firstReceiptMovement?->created_at
                    ?? $stockRequest->requestHistories->where('status', 'Sebagian Diterima')->sortBy('created_at')->first()?->created_at;

                if (in_array($stockRequest->status, ['Menunggu Review', 'Pending'], true)) {
                    $stage = 'request_to_approval';
                    $stageStartedAt = $stockRequest->created_at;
                } elseif ($stockRequest->status === 'Disetujui') {
                    $stage = 'approval_to_receipt';
                    $stageStartedAt = $approvalAt ?? $stockRequest->created_at;
                } else {
                    $stage = 'receipt_to_complete';
                    $stageStartedAt = $firstReceiptAt ?? $approvalAt ?? $stockRequest->created_at;
                }

                $elapsedSeconds = $stageStartedAt->diffInSeconds($now);
                if ($elapsedSeconds <= $delayThresholdSeconds) {
                    return null;
                }

                return [
                    'request' => $stockRequest,
                    'stage' => $stage,
                    'stage_label' => $stageLabels[$stage],
                    'days_open' => (int) floor($elapsedSeconds / 86400),
                    'is_urgent' => $stockRequest->priority === 'Mendesak',
                ];
            })
            ->filter()
            ->sortByDesc(fn ($item) => ($item['is_urgent'] ? 100000 : 0) + $item['days_open'])
            ->values();

        foreach ($activeBottlenecks as $item) {
            $activeStageCounts[$item['stage']]++;
        }
        $overdueCount = $activeBottlenecks->count();

        // === Average and historical processing time (last 30 days) ===
        $completedRequests = StockRequest::with('requestHistories', 'stockMovements')
            ->where('status', 'Diterima Penuh')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $now->copy()->subDays(30))
            ->get();

        $avgDays = null;
        if ($completedRequests->count() > 0) {
            $totalDays = $completedRequests->sum(function ($r) {
                return $r->created_at->diffInDays($r->completed_at);
            });
            $avgDays = round($totalDays / $completedRequests->count(), 1);
        }

        $historicalStageCounts = array_fill_keys(array_keys($stageLabels), 0);
        $historicalDelayedRequests = 0;
        foreach ($completedRequests as $stockRequest) {
            $approvalAt = $stockRequest->approved_at
                ?? $stockRequest->requestHistories->where('status', 'Disetujui')->sortBy('created_at')->first()?->created_at;
            $firstReceiptMovement = $stockRequest->stockMovements
                ->where('type', StockMovement::TYPE_IN)
                ->sortBy(fn ($movement) => $movement->occurred_at ?? $movement->created_at)
                ->first();
            $firstReceiptAt = $firstReceiptMovement?->occurred_at ?? $firstReceiptMovement?->created_at;
            $hasDelay = false;

            if ($approvalAt && $stockRequest->created_at->diffInSeconds($approvalAt) > $delayThresholdSeconds) {
                $historicalStageCounts['request_to_approval']++;
                $hasDelay = true;
            }
            if ($approvalAt && $firstReceiptAt && $approvalAt->diffInSeconds($firstReceiptAt) > $delayThresholdSeconds) {
                $historicalStageCounts['approval_to_receipt']++;
                $hasDelay = true;
            }
            if ($firstReceiptAt && $firstReceiptAt->diffInSeconds($stockRequest->completed_at) > $delayThresholdSeconds) {
                $historicalStageCounts['receipt_to_complete']++;
                $hasDelay = true;
            }
            if ($hasDelay) {
                $historicalDelayedRequests++;
            }
        }

        $processBottlenecks = [
            'labels' => $stageLabels,
            'active_counts' => $activeStageCounts,
            'active_items' => $activeBottlenecks,
            'historical_counts' => $historicalStageCounts,
            'historical_delayed_requests' => $historicalDelayedRequests,
            'historical_total' => $completedRequests->count(),
        ];

        // === Recent critical events (last 7 days) ===
        $weekAgo = $now->copy()->subDays(7);
        $recentMovements = StockMovement::with('item', 'user')
            ->where('occurred_at', '>=', $weekAgo)
            ->orderByDesc('occurred_at')
            ->take(8)
            ->get();

        $recentLocationChanges = LocationChangeRequest::with('item', 'fromLocation', 'toLocation', 'requestedBy')
            ->where('created_at', '>=', $weekAgo)
            ->latest()
            ->take(5)
            ->get();

        $warehouseSummary['incoming_week'] = (int) StockMovement::where('type', StockMovement::TYPE_IN)
            ->where('occurred_at', '>=', $weekAgo)
            ->sum('quantity');
        $warehouseSummary['outgoing_week'] = (int) StockMovement::where('type', StockMovement::TYPE_OUT)
            ->where('occurred_at', '>=', $weekAgo)
            ->sum('quantity');
        $warehouseSummary['capacity_percentage'] = $warehouseSummary['total_locations'] > 0
            ? (int) round(($warehouseSummary['occupied_locations'] / $warehouseSummary['total_locations']) * 100)
            : 0;

        return view('director.dashboard', compact(
            'pendingCount', 'urgentPendingCount', 'waitingReceiptCount',
            'overdueCount', 'avgDays', 'processBottlenecks',
            'recentMovements', 'recentLocationChanges',
            'warehouseSummary',
        ));
    }

    public function requests()
    {
        $status = request('status');
        $priority = request('priority');
        $search = request('search');

        $query = StockRequest::with('item', 'user', 'requestHistories.user');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($priority && $priority !== 'all') {
            $query->where('priority', $priority);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $requests = $query->orderByRaw("CASE WHEN priority = 'Mendesak' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(20)
            ->appends(request()->query());

        return view('director.requests', compact('requests', 'status', 'priority', 'search'));
    }

    public function requestDetail(StockRequest $request)
    {
        $request->load(['item', 'user', 'reviewedBy', 'requestHistories.user', 'stockMovements.user', 'stockMovements.item']);

        $histories = $request->requestHistories->sortBy('created_at');

        $allMovements = $request->stockMovements
            ->sortBy(fn ($movement) => $movement->occurred_at ?? $movement->created_at)
            ->values();
        $movements = $allMovements;

        $timeline = collect();

        $timeline->push([
            'icon' => 'create',
            'color' => 'slate',
            'label' => 'Request Dibuat',
            'user' => $request->user?->name ?? '—',
            'time' => $request->created_at,
            'detail' => "Barang: {$request->item_name}\nJumlah: {$request->quantity} {$request->unit}\nPrioritas: {$request->priority}\nAlasan: {$request->reason}",
        ]);

        if ($request->reviewed_by) {
            $reviewStatus = $request->requestHistories
                ->whereIn('status', ['Disetujui', 'Ditolak'])
                ->sortBy('created_at')
                ->first()?->status;
            $reviewer = $request->reviewedBy;
            $timeline->push([
                'icon' => 'review',
                'color' => 'blue',
                'label' => 'Direview',
                'user' => $request->requestHistories->where('status', 'Menunggu Review')->first()?->user?->name ?? '—',
                'time' => $request->requestHistories->where('status', 'Menunggu Review')->first()?->created_at ?? $request->approved_at,
                'decision' => $reviewStatus ?? ($request->status === 'Ditolak' ? 'Ditolak' : 'Disetujui'),
                'processor' => $reviewer?->name ?? '—',
                'detail' => $request->review_note,
            ]);
        }

        // Build cumulative received tracking
        $cumulativeReceived = 0;

        foreach ($histories as $h) {
            $isReceiptStatus = in_array($h->status, ['Sebagian Diterima', 'Diterima Penuh']);

            if ($isReceiptStatus) {
                $receiptMovements = $movements->filter(function ($m) {
                    return $m->type === StockMovement::TYPE_IN;
                });

                $matchedMovement = $receiptMovements->first(function ($m) use ($cumulativeReceived) {
                    return true;
                });

                if ($matchedMovement) {
                    $cumulativeReceived += $matchedMovement->quantity;
                    $note = $h->note ?? $matchedMovement->note;
                    $detail = "Diterima {$matchedMovement->quantity} dari {$request->quantity} {$request->unit} (total: {$cumulativeReceived} {$request->unit})";
                    if ($note) {
                        $detail .= "\nKeterangan: {$note}";
                    }

                    $timeline->push([
                        'icon' => 'status',
                        'color' => 'emerald',
                        'label' => $h->status === 'Diterima Penuh' ? 'Penerimaan Penuh' : 'Penerimaan Sebagian',
                        'user' => $h->user?->name ?? '—',
                        'time' => $h->created_at,
                        'detail' => $detail,
                    ]);

                    $movements = $movements->reject(fn ($m) => $m->id === $matchedMovement->id);
                } else {
                    $timeline->push([
                        'icon' => 'status',
                        'color' => 'emerald',
                        'label' => $h->status,
                        'user' => $h->user?->name ?? '—',
                        'time' => $h->created_at,
                        'detail' => $h->note,
                    ]);
                }
            } elseif (in_array($h->status, StockRequest::CLOSED_STATUSES)) {
                $timeline->push([
                    'icon' => 'status',
                    'color' => $h->status === 'Ditutup Sebagian' ? 'amber' : 'red',
                    'label' => $h->status === 'Ditutup Sebagian' ? 'Sisa Request Ditutup' : 'Request Dibatalkan',
                    'user' => $h->user?->name ?? '—',
                    'time' => $h->created_at,
                    'detail' => $h->note,
                ]);
            } else {
                $color = match ($h->status) {
                    'Disetujui' => 'emerald',
                    'Ditolak' => 'red',
                    'Pending', 'Ditunda' => 'amber',
                    default => 'slate',
                };
                $timeline->push([
                    'icon' => 'status',
                    'color' => $color,
                    'label' => $h->status,
                    'user' => $h->user?->name ?? '—',
                    'time' => $h->created_at,
                    'detail' => $h->note,
                ]);
            }
        }

        // Remaining movements not matched to any history
        foreach ($movements as $m) {
            $label = match ($m->type) {
                'IN' => 'Barang Masuk',
                'OUT' => 'Barang Keluar',
                'ADJUSTMENT' => 'Penyesuaian Stok',
                default => $m->type,
            };
            $color = match ($m->type) {
                'IN' => 'emerald',
                'OUT' => 'red',
                'ADJUSTMENT' => 'amber',
                default => 'slate',
            };
            $detail = "{$m->quantity} {$m->unit} — {$m->reason}";
            if ($m->note) {
                $detail .= "\nKeterangan: {$m->note}";
            }
            $timeline->push([
                'icon' => 'movement',
                'color' => $color,
                'label' => $label,
                'user' => $m->user?->name ?? '—',
                'time' => $m->occurred_at ?? $m->created_at,
                'detail' => $detail,
            ]);
        }

        $timeline = $timeline->sortByDesc('time')->values();

        $createdAt = $request->created_at;
        $firstApproval = $histories->where('status', 'Disetujui')->first()?->created_at ?? $request->approved_at;
        $receiptMovements = $allMovements->where('type', StockMovement::TYPE_IN)->values();
        $firstReceiptMovement = $receiptMovements->first();
        $firstReceipt = $firstReceiptMovement?->occurred_at ?? $firstReceiptMovement?->created_at;
        $completedAt = $request->completed_at;

        $durations = [
            'request_to_approval' => null,
            'approval_to_first_receipt' => null,
            'request_to_first_receipt' => null,
            'fulfillment_duration' => null,
            'fulfillment_state' => 'not_started',
            'request_to_complete' => null,
            'request_to_close' => null,
            'total_calendar_days' => null,
            'receipt_count' => $receiptMovements->count(),
            'received_quantity' => (int) $request->received_quantity,
            'requested_quantity' => (int) $request->quantity,
            'unit' => $request->unit,
        ];

        if ($firstApproval) {
            $durations['request_to_approval'] = $createdAt->diffForHumans($firstApproval, true);
        }
        if ($firstApproval && $firstReceipt) {
            $durations['approval_to_first_receipt'] = $firstApproval->diffForHumans($firstReceipt, true);
        }
        if ($firstReceipt) {
            $durations['request_to_first_receipt'] = $createdAt->diffForHumans($firstReceipt, true);

            if ($completedAt && $receiptMovements->count() === 1) {
                $durations['fulfillment_duration'] = 'Langsung penuh';
                $durations['fulfillment_state'] = 'completed';
            } elseif ($completedAt) {
                $durations['fulfillment_duration'] = $firstReceipt->diffForHumans($completedAt, true);
                $durations['fulfillment_state'] = 'completed';
            } elseif ($request->closed_at) {
                $durations['fulfillment_duration'] = $request->status === 'Ditutup Sebagian'
                    ? 'Ditutup setelah '.$firstReceipt->diffForHumans($request->closed_at, true)
                    : 'Dibatalkan';
                $durations['fulfillment_state'] = 'closed';
            } else {
                $durations['fulfillment_duration'] = $firstReceipt->diffForHumans(now(), true).' berjalan';
                $durations['fulfillment_state'] = 'ongoing';
            }
        }
        if ($completedAt) {
            $durations['request_to_complete'] = $createdAt->diffForHumans($completedAt, true);
            $durations['total_calendar_days'] = (int) floor($createdAt->diffInSeconds($completedAt) / 86400);
        }
        if ($request->closed_at && ! $request->completed_at) {
            $durations['request_to_close'] = $createdAt->diffForHumans($request->closed_at, true);
            $durations['total_calendar_days'] = (int) floor($createdAt->diffInSeconds($request->closed_at) / 86400);
        }

        return view('director.request-detail', compact('request', 'timeline', 'durations'));
    }

    public function movements()
    {
        $type = request('type');
        $search = request('search');

        $query = StockMovement::with('item', 'user', 'stockRequest');

        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('item', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $movements = $query->orderByDesc('occurred_at')->paginate(30)->appends(request()->query());

        return view('director.movements', compact('movements', 'type', 'search'));
    }

    public function timeline()
    {
        $search = request('search');
        $perPage = 50;

        $auditLogs = AuditLog::with('user')->latest()->take(500)->get();

        $stockRequests = StockRequest::with('user', 'item', 'requestHistories.user')->latest()->take(200)->get();

        // Build map of receipt movements by stock_request_id for deduplication
        $receiptMovementsByRequest = StockMovement::where('type', StockMovement::TYPE_IN)
            ->whereNotNull('stock_request_id')
            ->whereIn('stock_request_id', $stockRequests->pluck('id'))
            ->get()
            ->groupBy('stock_request_id');

        $requestEvents = $stockRequests->map(function ($r) use ($receiptMovementsByRequest) {
            $events = collect();
            $events->push([
                'time' => $r->created_at,
                'type' => 'Request Dibuat',
                'user' => $r->user?->name ?? '—',
                'detail' => "{$r->item_name} — {$r->quantity} {$r->unit} ({$r->priority})",
                'icon' => 'create',
            ]);

            $latestHistory = $r->requestHistories->sortByDesc('created_at')->first();
            $isReceiptStatus = in_array($r->status, ['Sebagian Diterima', 'Diterima Penuh', 'Disetujui']);

            if ($isReceiptStatus && $r->status !== 'Disetujui') {
                $movements = $receiptMovementsByRequest->get($r->id, collect())->sortBy('occurred_at');
                $cumulative = 0;
                foreach ($movements as $m) {
                    $cumulative += $m->quantity;
                    $isLast = $cumulative >= $r->quantity;
                    $isFull = $r->status === 'Diterima Penuh' && $isLast;
                    $label = $isFull ? 'Penerimaan Penuh' : 'Penerimaan Sebagian';
                    $detail = "{$r->item_name} — {$m->quantity} {$r->unit} diterima";
                    if ($m->note) {
                        $detail .= "\nKeterangan: {$m->note}";
                    }
                    $events->push([
                        'time' => $m->occurred_at ?? $r->created_at,
                        'type' => $label,
                        'user' => $m->user?->name ?? ($latestHistory?->user?->name ?? '—'),
                        'detail' => $detail,
                        'icon' => 'approved',
                    ]);
                }
                if ($movements->isEmpty()) {
                    $reviewer = $latestHistory?->user?->name ?? $r->reviewedBy?->name ?? '—';
                    $events->push([
                        'time' => $r->approved_at ?? $r->created_at,
                        'type' => $r->status,
                        'user' => $reviewer,
                        'detail' => "{$r->item_name}",
                        'icon' => 'approved',
                    ]);
                }
            } elseif ($r->status === 'Disetujui') {
                $reviewer = $latestHistory?->user?->name ?? $r->reviewedBy?->name ?? '—';
                $note = $latestHistory?->note;
                $detail = "{$r->item_name}";
                if ($note) {
                    $detail .= "\nKeterangan: {$note}";
                }
                $events->push([
                    'time' => $r->approved_at ?? $r->created_at,
                    'type' => 'Disetujui',
                    'user' => $reviewer,
                    'detail' => $detail,
                    'icon' => 'approved',
                ]);
            }

            if ($r->status === 'Ditolak') {
                $reviewer = $latestHistory?->user?->name ?? $r->reviewedBy?->name ?? '—';
                $events->push([
                    'time' => $r->created_at,
                    'type' => 'Ditolak',
                    'user' => $reviewer,
                    'detail' => "{$r->item_name}",
                    'icon' => 'rejected',
                ]);
            }

            if (in_array($r->status, StockRequest::CLOSED_STATUSES)) {
                $closureHistory = $r->requestHistories->firstWhere('status', $r->status);
                $closer = $closureHistory?->user?->name ?? $r->closedBy?->name ?? '—';
                $events->push([
                    'time' => $r->closed_at ?? $closureHistory?->created_at ?? $r->updated_at,
                    'type' => $r->status === 'Ditutup Sebagian' ? 'Sisa Request Ditutup' : 'Request Dibatalkan',
                    'user' => $closer,
                    'detail' => "{$r->item_name} — {$r->remainingQuantity()} {$r->unit} ditutup"
                        . ($r->close_note ? "\nAlasan: {$r->close_note}" : ''),
                    'icon' => 'status',
                ]);
            }
            return $events;
        })->flatten(1);

        // Get all receipt movement IDs that are already represented in requestEvents
        $receiptRequestIds = $stockRequests
            ->whereIn('status', ['Sebagian Diterima', 'Diterima Penuh'])
            ->pluck('id');

        $stockMovements = StockMovement::with('item', 'user')
            ->where('type', '!=', StockMovement::TYPE_IN)
            ->latest('occurred_at')
            ->take(200)
            ->get();

        // Also add IN movements not linked to any request in our set
        $orphanInMovements = StockMovement::with('item', 'user')
            ->where('type', StockMovement::TYPE_IN)
            ->whereNull('stock_request_id')
            ->latest('occurred_at')
            ->take(100)
            ->get();

        $stockMovements = $stockMovements->merge($orphanInMovements);

        $movementEvents = $stockMovements->map(function ($m) {
            $label = match ($m->type) {
                'IN' => 'Barang Masuk',
                'OUT' => 'Barang Keluar',
                'ADJUSTMENT' => 'Penyesuaian Stok',
                default => $m->type,
            };
            $detail = ($m->item?->name ?? '—') . " — {$m->quantity} {$m->unit}";
            if ($m->reason) {
                $detail .= "\nKeterangan: {$m->reason}";
            }
            if ($m->note) {
                $detail .= "\nCatatan: {$m->note}";
            }
            return [
                'time' => $m->occurred_at ?? $m->created_at,
                'type' => $label,
                'user' => $m->user?->name ?? '—',
                'detail' => $detail,
                'icon' => strtolower($m->type),
            ];
        });

        $locationChanges = LocationChangeRequest::with('item', 'fromLocation', 'toLocation', 'requestedBy', 'approvedBy')
            ->latest()->take(100)->get();
        $locationEvents = $locationChanges->map(function ($lc) {
            $status = $lc->status;
            $detail = $lc->target_sub_location !== null
                ? ($lc->item?->name ?? '—')." — ".($lc->fromLocation?->code ?? '—')." · ".($lc->from_sub_location ?? '—')." → ".($lc->toLocation?->code ?? '—')." · ".($lc->target_sub_location ?? '—')
                : ($lc->item?->name ?? '—')." — ".($lc->fromLocation?->code ?? '—')." → ".($lc->toLocation?->code ?? '—');

            return [
                'time' => $lc->created_at,
                'type' => "Pengajuan Lokasi: {$status}",
                'user' => $lc->requestedBy?->name ?? '—',
                'detail' => $detail,
                'icon' => 'location',
            ];
        });

        $allEvents = $requestEvents->merge($movementEvents)->merge($locationEvents)->sortByDesc('time')->values();

        if ($search) {
            $allEvents = $allEvents->filter(function ($e) use ($search) {
                return str_contains(strtolower($e['type']), strtolower($search))
                    || str_contains(strtolower($e['user']), strtolower($search))
                    || str_contains(strtolower($e['detail']), strtolower($search));
            })->values();
        }

        $paginated = $allEvents->slice(0, 500);

        return view('director.timeline', compact('paginated', 'search'));
    }

    public function stock()
    {
        $search = request('search');
        $rack = request('rack');
        $status = request('status');

        $query = StorageLocation::with('items');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('sub_location', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('size', 'like', "%{$search}%");
                    });
            });
        }
        if ($rack && $rack !== 'all') {
            $query->where('rack', $rack);
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
            ->paginate(50)
            ->appends(request()->query());

        $totalLocations = StorageLocation::count();
        $occupiedSlots = StorageLocation::where('status', StorageLocation::STATUS_OCCUPIED)->count();
        $totalStock = (int) Item::sum('stock');
        $outOfStock = StorageLocation::whereHas('items', function ($q) {
            $q->where('stock', 0);
        })->count();
        $lowStockCount = StorageLocation::whereHas('items', function ($q) {
            $q->where('stock', '>', 0)->where('stock', '<=', 5);
        })->count();

        $summary = [
            'total_items' => $totalLocations,
            'total_stock' => $totalStock,
            'low_stock' => $lowStockCount,
            'out_of_stock' => $outOfStock,
        ];

        $racks = ['A', 'B', 'C', 'D', 'E'];

        $rackStats = collect($racks)->map(function ($rackLetter) {
            $total = StorageLocation::where('rack', $rackLetter)->count();
            $occupied = StorageLocation::where('rack', $rackLetter)
                ->where('status', StorageLocation::STATUS_OCCUPIED)->count();

            return [
                'rack' => $rackLetter,
                'prefix' => StorageLocation::getPrefixForRack($rackLetter),
                'total' => $total,
                'occupied' => $occupied,
            ];
        });

        return view('director.stock', compact('locations', 'summary', 'racks', 'rackStats', 'rack', 'search', 'status'));
    }

    public function issues()
    {
        $status = request('status');

        $query = \App\Models\MonitoringIssue::with('stockRequest.item', 'user');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $issues = $query->latest()->paginate(30)->appends(request()->query());

        return view('director.issues', compact('issues', 'status'));
    }
}
