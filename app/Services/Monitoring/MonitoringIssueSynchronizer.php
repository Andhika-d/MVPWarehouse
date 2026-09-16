<?php

namespace App\Services\Monitoring;

use App\Models\MonitoringIssue;
use Carbon\Carbon;

class MonitoringIssueSynchronizer
{
    public function sync(?Carbon $now = null): array
    {
        $now ??= Carbon::now();

        $stats = [
            'created' => 0,
            'reopened' => 0,
            'updated' => 0,
            'escalated' => 0,
            'resolved' => 0,
        ];

        $candidates = (new ProcessDelayDetector())->scan($now);
        $candidateKeys = $candidates->pluck('dedupe_key')->all();

        $existing = collect($candidateKeys)->count() > 0
            ? MonitoringIssue::whereIn('dedupe_key', $candidateKeys)->get()->keyBy('dedupe_key')
            : collect();

        foreach ($candidates as $candidate) {
            $issue = $existing->get($candidate['dedupe_key']);

            if (! $issue) {
                MonitoringIssue::create([
                    'source' => MonitoringIssue::SOURCE_DETECTOR,
                    'rule_key' => $candidate['rule_key'],
                    'subject_type' => $candidate['subject_type'],
                    'subject_id' => $candidate['subject_id'],
                    'category' => $candidate['category'],
                    'severity' => $candidate['severity'],
                    'status' => MonitoringIssue::STATUS_OPEN,
                    'description' => $candidate['description'],
                    'dedupe_key' => $candidate['dedupe_key'],
                    'detected_at' => $now,
                    'last_seen_at' => $now,
                    'context' => ['started_at' => $candidate['started_at']->toDateTimeString()],
                    'occurrence_count' => 1,
                ]);
                $stats['created']++;

                continue;
            }

            if ($issue->isResolved()) {
                $issue->update([
                    'status' => MonitoringIssue::STATUS_OPEN,
                    'severity' => $candidate['severity'],
                    'description' => $candidate['description'],
                    'detected_at' => $now,
                    'last_seen_at' => $now,
                    'resolved_at' => null,
                    'context' => ['started_at' => $candidate['started_at']->toDateTimeString()],
                    'occurrence_count' => $issue->occurrence_count + 1,
                ]);
                $stats['reopened']++;

                continue;
            }

            $updates = [
                'description' => $candidate['description'],
                'last_seen_at' => $now,
            ];

            if ($issue->severity !== $candidate['severity']) {
                $updates['severity'] = $candidate['severity'];
                $stats['escalated']++;
            }

            $issue->update($updates);
            $stats['updated']++;
        }

        $active = MonitoringIssue::whereIn('status', [
            MonitoringIssue::STATUS_OPEN,
            MonitoringIssue::STATUS_IN_REVIEW,
        ]);

        if (count($candidateKeys) > 0) {
            $active->whereNotIn('dedupe_key', $candidateKeys);
        }

        foreach ($active->get() as $issue) {
            $issue->update([
                'status' => MonitoringIssue::STATUS_RESOLVED,
                'resolved_at' => $now,
                'context' => array_merge($issue->context ?: [], [
                    'resolution' => 'Otomatis: kondisi tidak lagi terdeteksi.',
                ]),
            ]);
            $stats['resolved']++;
        }

        return $stats;
    }
}