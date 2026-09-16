<?php

namespace App\Services\Monitoring;

use App\Models\LocationChangeRequest;
use App\Models\MonitoringIssue;
use App\Models\StockMovement;
use App\Models\StockRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProcessDelayDetector
{
    public const WARNING_SECONDS = 3 * 86400;
    public const CRITICAL_SECONDS = 7 * 86400;

    public const RULE_LABELS = [
        MonitoringIssue::RULE_REQUEST_AWAITING_REVIEW => 'Request Menunggu Review',
        MonitoringIssue::RULE_AWAITING_FIRST_RECEIPT => 'Menunggu Penerimaan Awal',
        MonitoringIssue::RULE_PARTIAL_RECEIPT_STALLED => 'Penerimaan Parsial Berhenti',
        MonitoringIssue::RULE_LOCATION_CHANGE_AWAITING_CONFIRMATION => 'Perubahan Lokasi Menunggu Konfirmasi',
    ];

    public function scan(?Carbon $now = null): Collection
    {
        $now ??= Carbon::now();

        return collect()
            ->concat($this->requestsAwaitingReview($now))
            ->concat($this->awaitingFirstReceipt($now))
            ->concat($this->partialReceiptStalled($now))
            ->concat($this->locationChangesAwaitingConfirmation($now))
            ->values();
    }

    protected function requestsAwaitingReview(Carbon $now): Collection
    {
        return StockRequest::with('requestHistories', 'item', 'user')
            ->whereIn('status', StockRequest::ACTIONABLE_STATUSES)
            ->get()
            ->map(function (StockRequest $request) use ($now) {
                $pendingHistory = $request->requestHistories
                    ->where('status', 'Pending')
                    ->sortByDesc('created_at')
                    ->first();

                $startedAt = $request->status === 'Pending'
                    ? ($pendingHistory?->created_at ?? $request->created_at)
                    : $request->created_at;

                return $this->candidate(MonitoringIssue::RULE_REQUEST_AWAITING_REVIEW, $request, $startedAt, $now);
            })
            ->filter()
            ->values();
    }

    protected function awaitingFirstReceipt(Carbon $now): Collection
    {
        return StockRequest::with('requestHistories', 'item', 'user')
            ->where('status', 'Disetujui')
            ->whereColumn('received_quantity', '<', 'quantity')
            ->get()
            ->map(function (StockRequest $request) use ($now) {
                $approvalHistory = $request->requestHistories
                    ->where('status', 'Disetujui')
                    ->sortByDesc('created_at')
                    ->first();

                $startedAt = $request->approved_at
                    ?? $approvalHistory?->created_at
                    ?? $request->created_at;

                return $this->candidate(MonitoringIssue::RULE_AWAITING_FIRST_RECEIPT, $request, $startedAt, $now);
            })
            ->filter()
            ->values();
    }

    protected function partialReceiptStalled(Carbon $now): Collection
    {
        return StockRequest::with('requestHistories', 'stockMovements', 'item', 'user')
            ->where('status', 'Sebagian Diterima')
            ->whereColumn('received_quantity', '<', 'quantity')
            ->get()
            ->map(function (StockRequest $request) use ($now) {
                $lastMovement = $request->stockMovements
                    ->where('type', StockMovement::TYPE_IN)
                    ->sortByDesc(fn ($movement) => $movement->occurred_at ?? $movement->created_at)
                    ->first();

                $startedAt = $lastMovement?->occurred_at ?? $lastMovement?->created_at;

                if (! $startedAt) {
                    $firstHistory = $request->requestHistories
                        ->where('status', 'Sebagian Diterima')
                        ->sortByDesc('created_at')
                        ->first();

                    $startedAt = $firstHistory?->created_at ?? $request->created_at;
                }

                return $this->candidate(MonitoringIssue::RULE_PARTIAL_RECEIPT_STALLED, $request, $startedAt, $now);
            })
            ->filter()
            ->values();
    }

    protected function locationChangesAwaitingConfirmation(Carbon $now): Collection
    {
        return LocationChangeRequest::with('item', 'requestedBy')
            ->where('status', LocationChangeRequest::STATUS_PENDING)
            ->get()
            ->map(function (LocationChangeRequest $change) use ($now) {
                return $this->candidate(
                    MonitoringIssue::RULE_LOCATION_CHANGE_AWAITING_CONFIRMATION,
                    $change,
                    $change->created_at,
                    $now,
                );
            })
            ->filter()
            ->values();
    }

    protected function candidate(string $rule, object $subject, Carbon $startedAt, Carbon $now): ?array
    {
        $elapsedSeconds = (int) $startedAt->diffInSeconds($now);

        if ($elapsedSeconds < self::WARNING_SECONDS) {
            return null;
        }

        $days = (int) floor($elapsedSeconds / 86400);

        $description = match ($rule) {
            MonitoringIssue::RULE_REQUEST_AWAITING_REVIEW =>
                "Request {$subject->item_name} belum menerima keputusan selama {$days} hari.",
            MonitoringIssue::RULE_AWAITING_FIRST_RECEIPT =>
                "Belum ada penerimaan barang selama {$days} hari setelah approval.",
            MonitoringIssue::RULE_PARTIAL_RECEIPT_STALLED =>
                "Penerimaan barang tidak berlanjut selama {$days} hari sejak penerimaan terakhir.",
            MonitoringIssue::RULE_LOCATION_CHANGE_AWAITING_CONFIRMATION =>
                "Perubahan lokasi {$subject->item?->display_name} belum dikonfirmasi selama {$days} hari.",
            default => "Proses tertunda selama {$days} hari.",
        };

        return [
            'rule_key' => $rule,
            'category' => self::RULE_LABELS[$rule],
            'severity' => $this->severityFor($elapsedSeconds),
            'description' => $description,
            'dedupe_key' => $rule . ':' . strtolower(class_basename($subject)) . ':' . $subject->id,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'started_at' => $startedAt,
            'days' => $days,
            'elapsed_seconds' => $elapsedSeconds,
        ];
    }

    protected function severityFor(int $elapsedSeconds): string
    {
        return $elapsedSeconds >= self::CRITICAL_SECONDS
            ? MonitoringIssue::SEVERITY_CRITICAL
            : MonitoringIssue::SEVERITY_WARNING;
    }
}