<?php

namespace App\Http\Controllers;

use App\Exports\StockRequestExport;
use App\Models\AuditLog;
use App\Models\Item;
use App\Models\ProcurementNote;
use App\Models\RequestHistory;
use App\Models\StockRequest;
use App\Models\User;
use App\Notifications\NewRequestNotification;
use App\Notifications\RequestApprovedNotification;
use App\Notifications\RequestDelayedNotification;
use App\Notifications\RequestRejectedNotification;
use App\Support\ItemLocationSorter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class RequestController extends Controller
{
    private function safeMonth(?string $value): ?Carbon
    {
        if ($value === null || ! preg_match('/^\d{4}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception) {
            return null;
        }
    }

    private function safeDate(?string $value): ?Carbon
    {
        if ($value === null || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value);

            return $date->format('Y-m-d') === $value ? $date : null;
        } catch (\Exception) {
            return null;
        }
    }

    public function create()
    {
        $items = ItemLocationSorter::sort(
            Item::with('storageLocation')->get()
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
        $query = StockRequest::with(['item.storageLocation', 'user', 'requestHistories.user'])
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
            $date = $this->safeMonth($request->input('month'));
            if ($date) {
                $query->whereYear('created_at', $date->year)
                      ->whereMonth('created_at', $date->month);
            }
        }

        $requests = $query->latest()->paginate(20)->withQueryString();

        return view('gudang.history', compact('requests'));
    }

    public function detail(StockRequest $request)
    {
        abort_unless($request->user_id === Auth::id(), 403);

        $request->load(['item.storageLocation', 'user', 'requestHistories.user']);

        return view('gudang.detail', compact('request'));
    }

    public function approvalIndex(Request $request)
    {
        $query = $this->buildExportQuery($request, 'approval')->with('requestHistories.user');

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

        $request->load(['item.storageLocation', 'user', 'requestHistories.user']);

        return view('hr.approval-detail', compact('request'));
    }

    public function shoppingList(Request $request)
    {
        $queueQuery = StockRequest::with(['item.storageLocation', 'user'])
            ->where('status', 'Disetujui')
            ->whereNull('procurement_note_id');
        $notesQuery = ProcurementNote::with('creator')->withCount('items');

        $exactDate = $this->safeDate($request->input('date'));
        if ($exactDate) {
            $queueQuery->where(function ($query) use ($exactDate) {
                $query->where(function ($approved) use ($exactDate) {
                    $approved->whereNotNull('approved_at')->whereDate('approved_at', $exactDate->toDateString());
                })->orWhere(function ($created) use ($exactDate) {
                    $created->whereNull('approved_at')->whereDate('created_at', $exactDate->toDateString());
                });
            });
            $notesQuery->where(function ($query) use ($exactDate) {
                $query->where(function ($issued) use ($exactDate) {
                    $issued->whereNotNull('issued_at')->whereDate('issued_at', $exactDate->toDateString());
                })->orWhere(function ($created) use ($exactDate) {
                    $created->whereNull('issued_at')->whereDate('created_at', $exactDate->toDateString());
                });
            });
        }

        $requests = $queueQuery->latest('approved_at')->latest()->get();
        $notes = $notesQuery->latest()->paginate(20)->withQueryString();

        return view('hr.daftar-belanja', compact('requests', 'notes'));
    }

    public function exportShoppingListExcel(Request $request)
    {
        $rows = $this->buildShoppingListExportRows($request);
        $export = new StockRequestExport(array_map('array_values', $rows));

        return response($export->toXlsx(), 200)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="daftar-belanja-driver.xlsx"');
    }

    protected function buildShoppingListExportRows(Request $request): array
    {
        $query = StockRequest::with('item.storageLocation')
            ->where('status', 'Disetujui')
            ->whereNull('procurement_note_id');

        $exactDate = $this->safeDate($request->input('date'));
        if ($exactDate) {
            $query->where(function ($filter) use ($exactDate) {
                $filter->where(function ($approved) use ($exactDate) {
                    $approved->whereNotNull('approved_at')->whereDate('approved_at', $exactDate->toDateString());
                })->orWhere(function ($created) use ($exactDate) {
                    $created->whereNull('approved_at')->whereDate('created_at', $exactDate->toDateString());
                });
            });
        }

        $requests = $query->latest()->get();

        return $requests->map(function ($stockRequest) {
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
    }

    public function previewHistoryExport(Request $request)
    {
        return $this->previewRequestExport($request, 'gudang', 'Riwayat Permintaan', '/gudang/history');
    }

    public function previewApprovalExport(Request $request)
    {
        $date = $this->safeDate($request->input('date'));
        $title = $date ? 'Approval Nota #NOTA-'.$date->format('Ymd') : 'Approval Permintaan';

        return $this->previewRequestExport($request, 'approval', $title, '/hr/approval');
    }

    public function previewHrHistoryExport(Request $request)
    {
        $date = $this->safeDate($request->input('date'));
        $title = $date ? 'Nota Pengadaan #NOTA-'.$date->format('Ymd') : 'Riwayat Permintaan HR';

        return $this->previewRequestExport($request, 'hr', $title, '/hr/history');
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
        $date = $this->safeDate($request->input('date'));
        $filename = $date ? 'approval-nota-'.$date->format('Ymd').'.pdf' : 'approval-permintaan.pdf';
        $title = $date ? 'Approval Nota #NOTA-'.$date->format('Ymd') : 'Daftar Permintaan Barang';

        return $this->downloadPdf($filename, $rows, $title);
    }

    public function exportApprovalExcel(Request $request)
    {
        $requests = $this->buildExportQuery($request, 'approval')->latest()->get();
        $rows = array_map('array_values', $this->buildExportRows($requests));
        $date = $this->safeDate($request->input('date'));
        $filename = $date ? 'approval-nota-'.$date->format('Ymd').'.xlsx' : 'approval-permintaan.xlsx';

        return $this->downloadXlsx($filename, $rows);
    }

    public function hrHistory(Request $request)
    {
        $query = $this->buildExportQuery($request, 'hr')->with('requestHistories.user');

        $requests = $query->latest()->paginate(20)->withQueryString();

        return view('hr.history', compact('requests'));
    }

    public function exportHrHistoryPdf(Request $request)
    {
        $requests = $this->buildExportQuery($request, 'hr')->latest()->get();
        $rows = $this->buildExportRows($requests);
        $date = $this->safeDate($request->input('date'));
        $filename = $date ? 'nota-pengadaan-'.$date->format('Ymd').'.pdf' : 'riwayat-hr.pdf';
        $title = $date ? 'Nota Pengadaan #NOTA-'.$date->format('Ymd') : 'Daftar Permintaan Barang';

        return $this->downloadPdf($filename, $rows, $title);
    }

    public function exportHrHistoryExcel(Request $request)
    {
        $requests = $this->buildExportQuery($request, 'hr')->latest()->get();
        $rows = array_map('array_values', $this->buildExportRows($requests));
        $date = $this->safeDate($request->input('date'));
        $filename = $date ? 'nota-pengadaan-'.$date->format('Ymd').'.xlsx' : 'riwayat-hr.xlsx';

        return $this->downloadXlsx($filename, $rows);
    }

    public function hrCloseSisa(StockRequest $request, Request $httpRequest)
    {
        $data = $httpRequest->validate([
            'note' => 'required|string|max:255',
        ]);

        $result = $request->closeRemaining(Auth::id(), trim($data['note']));

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    protected function buildExportQuery(Request $request, string $scope)
    {
        $query = StockRequest::with(['item.storageLocation', 'user']);

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

        if (in_array($scope, ['approval', 'hr'], true) && ($date = $this->safeDate($request->input('date')))) {
            $query->whereDate('created_at', $date->format('Y-m-d'));
        }

        return $query;
    }

    protected function downloadPdf(string $filename, array $rows, string $title = 'Daftar Permintaan Barang')
    {
        $pdf = Pdf::loadView('exports.stock-requests', ['rows' => $rows, 'title' => $title]);

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

    protected function previewRequestExport(Request $request, string $scope, string $title, string $basePath)
    {
        $rows = $this->buildExportRows($this->buildExportQuery($request, $scope)->latest()->get());

        if (empty($rows)) {
            return back()->with('error', 'Tidak ada data untuk di-export dengan filter yang dipilih.');
        }

        return $this->exportPreview(
            $title,
            $this->stockRequestExportColumns(),
            $rows,
            $this->exportUrl($basePath, $request),
            [
                ['format' => 'PDF', 'url' => $this->exportUrl($basePath.'/export/pdf', $request)],
                ['format' => 'Excel', 'url' => $this->exportUrl($basePath.'/export/excel', $request)],
            ]
        );
    }

    protected function stockRequestExportColumns(): array
    {
        return ['ID', 'Pemohon', 'Barang', 'Jumlah', 'Satuan', 'Prioritas', 'Status', 'Tanggal', 'Catatan Review'];
    }

    protected function exportUrl(string $path, Request $request): string
    {
        return url($path).($request->getQueryString() ? '?'.$request->getQueryString() : '');
    }

    public function approve(StockRequest $request, Request $httpRequest)
    {
        $data = $httpRequest->validate([
            'note' => ['nullable', 'string', 'max:1000'],
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
            'note' => ['required', 'string', 'max:1000'],
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
            'note' => ['nullable', 'string', 'max:1000'],
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
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $count = DB::transaction(function () use ($date, $data) {
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

            return $count;
        });

        return redirect('/hr/approval')->with('success', $count . ' item dalam nota berhasil disetujui.');
    }

    public function rejectAll(string $date, Request $httpRequest)
    {
        $data = $httpRequest->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $count = DB::transaction(function () use ($date, $data) {
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

            return $count;
        });

        return redirect('/hr/approval')->with('success', $count . ' item dalam nota berhasil ditolak.');
    }

    public function delayAll(string $date, Request $httpRequest)
    {
        $data = $httpRequest->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $count = DB::transaction(function () use ($date, $data) {
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

            return $count;
        });

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
