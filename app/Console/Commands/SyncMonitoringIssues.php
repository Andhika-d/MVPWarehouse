<?php

namespace App\Console\Commands;

use App\Services\Monitoring\MonitoringIssueSynchronizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncMonitoringIssues extends Command
{
    protected $signature = 'monitoring:sync-issues';

    protected $description = 'Scan proses berjalan dan perbarui issue monitoring secara otomatis tanpa duplikat.';

    public function handle(MonitoringIssueSynchronizer $synchronizer): int
    {
        $lock = Cache::lock('monitoring:sync-issues', 300);

        if (! $lock->get()) {
            $this->comment('Sinkronisasi monitoring sedang berjalan di proses lain.');

            return self::SUCCESS;
        }

        try {
            $stats = $synchronizer->sync();

            $this->info('Issue monitoring diperbarui: created=' . $stats['created']
                . ', reopened=' . $stats['reopened']
                . ', updated=' . $stats['updated']
                . ', escalated=' . $stats['escalated']
                . ', resolved=' . $stats['resolved']);

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}