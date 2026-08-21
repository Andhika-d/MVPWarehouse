<?php

namespace App\Http\Controllers;

use App\Exports\StockRequestExport;
use App\Models\AuditLog;
use App\Models\Item;
use App\Models\RequestHistory;
use App\Models\StockRequest;
use App\Models\User;
use App\Notifications\NewRequestNotification;
use App\Notifications\RequestApprovedNotification;
use App\Notifications\RequestCompletedNotification;
use App\Notifications\RequestDelayedNotification;
use App\Notifications\RequestRejectedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class RequestController extends Controller
{
    public function create()
    {
        $items = Item::all();

        $itemOptions = $items->map(fn ($item) => [
            'id' => $item->id,
            'label' => $item->display_name,
            'stock' => $item->stock,
            'unit' => $item->unit,
            'rack_location' => $item->rack_location,
        ])->values();

        return view('gudang.requestbarang', compact('items', 'itemOptions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_id' => ['nullable', Rule::exists('items', 'id')->whereNull('deleted_at')],
            'item_name' => ['required_without:item_id', 'nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit' => ['nullable', 'string', 'max:50'],
            'priority' => ['required', 'in:Biasa,Mendesak'],
            'reason' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xlsx,xls', 'max:2048'],
        ]);

        $item = $data['item_id'] ? Item::find($data['item_id']) : null;
        $unit = $item?->unit ?? $data['unit'] ?? 'Pcs';

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        $stockRequest = StockRequest::create([
            'user_id' => Auth::id(),
            'item_id' => $item?->id,
            'item_name' => $item?->name ?? $data['item_name'],
            'quantity' => $data['quantity'],
            'unit' => $unit,
            'priority' => $data['priority'],
            'reason' => $data['reason'],
            'attachment_path' => $attachmentPath,
            'status' => 'Menunggu Review',
        ]);

        $stockRequest->requestHistories()->create([
            'user_id' => Auth::id(),
            'status' => 'Menunggu Review',
            'note' => 'Permintaan dibuat oleh gudang',
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'created_request',
            'target_type' => StockRequest::class,
            'target_id' => $stockRequest->id,
            'details' => 'Gudang membuat permintaan ' . $stockRequest->quantity . ' ' . $stockRequest->unit . ' ' . ($item?->name ?? $data['item_name']),
        ]);

        $hrUsers = User::where('role', 'hr')->where('is_active', true)->get();
        if ($hrUsers->isNotEmpty()) {
            Notification::send($hrUsers, new NewRequestNotification($stockRequest));
        }

        return redirect('/gudang/history')->with('success', 'Permintaan berhasil dibuat.');
    }

    public function history(Request $request)
    {
        $query = StockRequest::with(['item', 'user', 'requestHistories.user'])
            ->where('user_id', Auth::id());

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('item', function ($itemQuery) use ($search) {
                    $itemQuery->where('name', 'like', "%{$search}%");
                })->orWhere('item_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('month')) {
            $date = Carbon::parse($request->input('month'));
            $query->whereYear('created_at', $date->year)
                  ->whereMonth('created_at', $date->month);
        }

        $requests = $query->latest()->paginate(20)->withQueryString();

        return view('gudang.history', compact('requests'));
    }

    public function detail(StockRequest $request)
    {
        abort_unless($request->user_id === Auth::id(), 403);

        $request->load(['item', 'user', 'requestHistories.user']);

        return view('gudang.detail', compact('request'));
    }

    public function approvalIndex(Request $request)
    {
        $query = StockRequest::with(['item', 'user', 'requestHistories.user'])
            ->where(function ($query) {
                $query->where('status', 'Menunggu Review')->orWhere('status', 'Pending');
            });

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('item', function ($itemQuery) use ($search) {
                    $itemQuery->where('name', 'like', "%{$search}%");
                })->orWhere('item_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        $requests = $query->latest()->paginate(20)->withQueryString();

        $notas = $requests->getCollection()
            ->sortBy('created_at')
            ->groupBy(fn ($stockRequest) => $stockRequest->created_at->format('Y-m-d'))
            ->sortKeysDesc();

        return view('hr.approval', compact('notas', 'requests'));
    }

    public function approvalDetail(StockRequest $request)
    {
        abort_unless($request->isActionable(), 403);

        $request->load(['item', 'user', 'requestHistories.user']);

        return view('hr.approval-detail', compact('request'));
    }

    public function shoppingList(Request $request)
    {
        $query = StockRequest::with('item')
            ->where('status', 'Disetujui')
            ->whereNull('completed_at');

        if ($request->filled('month')) {
            $date = Carbon::parse($request->input('month'));
            $query->whereYear('created_at', $date->year)
                  ->whereMonth('created_at', $date->month);
        }

        $requests = $query->latest()->paginate(20)->withQueryString();

        return view('hr.daftar-belanja', compact('requests'));
    }

    public function exportShoppingListExcel()
    {
        $requests = StockRequest::with('item')
            ->where('status', 'Disetujui')
            ->whereNull('completed_at')
            ->latest()
            ->get();

        $rows = $requests->map(function ($stockRequest) {
            return [
                $stockRequest->id,
                $stockRequest->user?->name,
                $stockRequest->item?->name,
                $stockRequest->quantity,
                $stockRequest->unit,
                $stockRequest->priority,
                $stockRequest->status,
                $stockRequest->created_at?->translatedFormat('d M Y, H:i'),
                $stockRequest->review_note,
            ];
        })->toArray();

        $export = new StockRequestExport($rows);

        return response($export->toXlsx(), 200)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="daftar-belanja-driver.xlsx"');
    }

    public function completeShopping()
    {
        $requests = StockRequest::with('item')
            ->where('status', 'Disetujui')
            ->whereNull('completed_at')
            ->get();

        $count = 0;
        foreach ($requests as $stockRequest) {
            if ($this->applyCompletion($stockRequest, Auth::id())) {
                $count++;
            }
        }

        return redirect('/hr/daftar-belanja')->with('success', $count . ' item belanja berhasil dikonfirmasi selesai.');
    }

    public function completeRequest(StockRequest $request)
    {
        $applied = $this->applyCompletion($request, Auth::id());

        if (! $applied) {
            return redirect()->back()->with('error', 'Permintaan tidak dapat diselesaikan pada status ini.');
        }

        return redirect('/hr/daftar-belanja')->with('success', 'Item belanja berhasil dikonfirmasi selesai.');
    }

    protected function applyCompletion(StockRequest $request, int $userId): bool
    {
        return DB::transaction(function () use ($request, $userId) {
            $updated = StockRequest::whereKey($request->id)
                ->where('status', 'Disetujui')
                ->whereNull('completed_at')
                ->update([
                    'status' => 'Selesai Dibelanjakan',
                    'completed_at' => now(),
                ]);

            if ($updated === 0) {
                return false;
            }

            $request->refresh();
            $request->load('item');

            $request->requestHistories()->create([
                'user_id' => $userId,
                'status' => 'Selesai Dibelanjakan',
                'note' => 'Barang berhasil dibelanjakan Driver dan telah diterima Gudang',
            ]);

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'completed_shopping',
                'target_type' => StockRequest::class,
                'target_id' => $request->id,
                'details' => 'HR mengonfirmasi pembelian selesai: ' . ($request->item?->name ?? 'Barang') . ' (' . $request->quantity . ' ' . $request->unit . ')',
            ]);

            if ($request->user) {
                $request->user->notify(new RequestCompletedNotification($request));
            }

            return true;
        });
    }

    public function exportHistoryPdf(Request $request)
    {
        $requests = $this->buildExportQuery($request, 'gudang')->latest()->get();
        $rows = $this->buildExportRows($requests);

        return $this->downloadPdf('riwayat-permintaan.pdf', $rows);
    }

    public function exportHistoryExcel(Request $request)
    {
        $requests = $this->buildExportQuery($request, 'gudang')->latest()->get();
        $rows = array_map('array_values', $this->buildExportRows($requests));

        return $this->downloadXlsx('riwayat-permintaan.xlsx', $rows);
    }

    public function exportApprovalPdf(Request $request)
    {
        $requests = $this->buildExportQuery($request, 'approval')->latest()->get();
        $rows = $this->buildExportRows($requests);

        return $this->downloadPdf('approval-permintaan.pdf', $rows);
    }

    public function exportApprovalExcel(Request $request)
    {
        $requests = $this->buildExportQuery($request, 'approval')->latest()->get();
        $rows = array_map('array_values', $this->buildExportRows($requests));

        return $this->downloadXlsx('approval-permintaan.xlsx', $rows);
    }

    public function hrHistory(Request $request)
    {
        $query = $this->buildExportQuery($request, 'hr')->with('requestHistories.user');

        $requests = $query->latest()->get();

        return view('hr.history', compact('requests'));
    }

    public function exportHrHistoryPdf(Request $request)
    {
        $requests = $this->buildExportQuery($request, 'hr')->latest()->get();
        $rows = $this->buildExportRows($requests);

        return $this->downloadPdf('riwayat-hr.pdf', $rows);
    }

    public function exportHrHistoryExcel(Request $request)
    {
        $requests = $this->buildExportQuery($request, 'hr')->latest()->get();
        $rows = array_map('array_values', $this->buildExportRows($requests));

        return $this->downloadXlsx('riwayat-hr.xlsx', $rows);
    }

    protected function buildExportQuery(Request $request, string $scope)
    {
        $query = StockRequest::with(['item', 'user']);

        if ($scope === 'gudang') {
            $query->where('user_id', Auth::id());
        } elseif ($scope === 'approval') {
            $query->where(function ($q) {
                $q->where('status', 'Menunggu Review')->orWhere('status', 'Pending');
            });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search, $scope) {
                $q->whereHas('item', function ($itemQuery) use ($search) {
                    $itemQuery->where('name', 'like', "%{$search}%");
                })->orWhere('item_name', 'like', "%{$search}%");

                if ($scope === 'hr') {
                    $q->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    });
                }
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        return $query;
    }

    protected function downloadPdf(string $filename, array $rows)
    {
        $pdf = Pdf::loadView('exports.stock-requests', ['rows' => $rows]);

        return $pdf->download($filename);
    }

    protected function downloadXlsx(string $filename, array $rows)
    {
        $export = new StockRequestExport($rows);

        return response($export->toXlsx(), 200)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    protected function buildExportRows($requests): array
    {
        return $requests->map(function ($stockRequest) {
            return [
                'id' => $stockRequest->id,
                'pemohon' => $stockRequest->user?->name,
                'barang' => $stockRequest->item?->name ?? $stockRequest->item_name ?? 'Barang',
                'jumlah' => $stockRequest->quantity,
                'satuan' => $stockRequest->unit,
                'prioritas' => $stockRequest->priority,
                'status' => $stockRequest->status,
                'tanggal' => $stockRequest->created_at?->translatedFormat('d M Y, H:i'),
                'catatan' => $stockRequest->review_note,
            ];
        })->toArray();
    }

    public function approve(StockRequest $request, Request $httpRequest)
    {
        $data = $httpRequest->validate([
            'note' => ['nullable', 'string'],
        ]);

        $applied = $this->applyApproval($request, Auth::id(), $data['note'] ?? null);

        if (! $applied) {
            return redirect()->back()->with('error', 'Permintaan sudah diproses, tidak bisa diulang.');
        }

        return redirect('/hr/approval')->with('success', 'Permintaan disetujui dan masuk ke daftar belanja.');
    }

    public function reject(StockRequest $request, Request $httpRequest)
    {
        $data = $httpRequest->validate([
            'note' => ['required', 'string'],
        ]);

        $applied = $this->applyRejection($request, Auth::id(), $data['note']);

        if (! $applied) {
            return redirect()->back()->with('error', 'Permintaan sudah diproses, tidak bisa diulang.');
        }

        return redirect('/hr/approval')->with('success', 'Permintaan ditolak.');
    }

    public function delay(StockRequest $request, Request $httpRequest)
    {
        $data = $httpRequest->validate([
            'note' => ['nullable', 'string'],
        ]);

        $applied = $this->applyDelay($request, Auth::id(), $data['note'] ?? null);

        if (! $applied) {
            return redirect()->back()->with('error', 'Permintaan sudah diproses, tidak bisa diulang.');
        }

        return redirect('/hr/approval')->with('success', 'Permintaan ditunda (Pending).');
    }

    public function approveAll(string $date, Request $httpRequest)
    {
        $data = $httpRequest->validate([
            'note' => ['nullable', 'string'],
        ]);

        $requests = StockRequest::with('item')
            ->whereDate('created_at', $date)
            ->whereIn('status', ['Menunggu Review', 'Pending'])
            ->get();

        $count = 0;
        foreach ($requests as $stockRequest) {
            if ($this->applyApproval($stockRequest, Auth::id(), $data['note'] ?? null)) {
                $count++;
            }
        }

        return redirect('/hr/approval')->with('success', $count . ' item dalam nota berhasil disetujui.');
    }

    public function rejectAll(string $date, Request $httpRequest)
    {
        $data = $httpRequest->validate([
            'note' => ['required', 'string'],
        ]);

        $requests = StockRequest::with('item')
            ->whereDate('created_at', $date)
            ->whereIn('status', ['Menunggu Review', 'Pending'])
            ->get();

        $count = 0;
        foreach ($requests as $stockRequest) {
            if ($this->applyRejection($stockRequest, Auth::id(), $data['note'])) {
                $count++;
            }
        }

        return redirect('/hr/approval')->with('success', $count . ' item dalam nota berhasil ditolak.');
    }

    public function delayAll(string $date, Request $httpRequest)
    {
        $data = $httpRequest->validate([
            'note' => ['nullable', 'string'],
        ]);

        $requests = StockRequest::with('item')
            ->whereDate('created_at', $date)
            ->whereIn('status', ['Menunggu Review', 'Pending'])
            ->get();

        $count = 0;
        foreach ($requests as $stockRequest) {
            if ($this->applyDelay($stockRequest, Auth::id(), $data['note'] ?? null)) {
                $count++;
            }
        }

        return redirect('/hr/approval')->with('success', $count . ' item dalam nota ditunda (Pending).');
    }

    protected function applyApproval(StockRequest $request, int $userId, ?string $note): bool
    {
        return DB::transaction(function () use ($request, $userId, $note) {
            $updated = StockRequest::whereKey($request->id)
                ->whereIn('status', StockRequest::ACTIONABLE_STATUSES)
                ->update([
                    'status' => 'Disetujui',
                    'reviewed_by' => $userId,
                    'review_note' => $note,
                    'approved_at' => now(),
                ]);

            if ($updated === 0) {
                return false;
            }

            $request->refresh();
            $request->load('item');

            $request->requestHistories()->create([
                'user_id' => $userId,
                'status' => 'Disetujui',
                'note' => $note ?? 'Disetujui oleh HR, menunggu proses pembelian',
            ]);

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'approved_request',
                'target_type' => StockRequest::class,
                'target_id' => $request->id,
                'details' => 'HR menyetujui permintaan ' . ($request->item?->name ?? 'Barang') . ' (' . $request->quantity . ' ' . $request->unit . ')',
            ]);

            if ($request->user) {
                $request->user->notify(new RequestApprovedNotification($request));
            }

            return true;
        });
    }

    protected function applyRejection(StockRequest $request, int $userId, string $note): bool
    {
        return DB::transaction(function () use ($request, $userId, $note) {
            $updated = StockRequest::whereKey($request->id)
                ->whereIn('status', StockRequest::ACTIONABLE_STATUSES)
                ->update([
                    'status' => 'Ditolak',
                    'reviewed_by' => $userId,
                    'review_note' => $note,
                ]);

            if ($updated === 0) {
                return false;
            }

            $request->refresh();
            $request->load('item');

            $request->requestHistories()->create([
                'user_id' => $userId,
                'status' => 'Ditolak',
                'note' => $note,
            ]);

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'rejected_request',
                'target_type' => StockRequest::class,
                'target_id' => $request->id,
                'details' => 'HR menolak permintaan ' . ($request->item?->name ?? 'Barang') . ': ' . $note,
            ]);

            if ($request->user) {
                $request->user->notify(new RequestRejectedNotification($request));
            }

            return true;
        });
    }

    protected function applyDelay(StockRequest $request, int $userId, ?string $note): bool
    {
        return DB::transaction(function () use ($request, $userId, $note) {
            $updated = StockRequest::whereKey($request->id)
                ->whereIn('status', StockRequest::ACTIONABLE_STATUSES)
                ->update([
                    'status' => 'Pending',
                    'reviewed_by' => $userId,
                    'review_note' => $note,
                ]);

            if ($updated === 0) {
                return false;
            }

            $request->refresh();
            $request->load('item');

            $request->requestHistories()->create([
                'user_id' => $userId,
                'status' => 'Pending',
                'note' => $note ?? 'Permintaan ditunda oleh HR',
            ]);

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'delayed_request',
                'target_type' => StockRequest::class,
                'target_id' => $request->id,
                'details' => 'HR menunda permintaan ' . ($request->item?->name ?? 'Barang'),
            ]);

            if ($request->user) {
                $request->user->notify(new RequestDelayedNotification($request));
            }

            return true;
        });
    }
}
