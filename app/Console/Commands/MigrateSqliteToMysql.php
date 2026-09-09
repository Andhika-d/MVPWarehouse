<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

#[Signature('db:migrate-sqlite-to-mysql {--source= : Path to the SQLite database} {--chunk=500 : Number of rows per insert batch} {--truncate : Empty destination tables before importing}')]
#[Description('Copy SQLite data into the configured MySQL database and compare row counts')]
class MigrateSqliteToMysql extends Command
{
    private const TABLES = [
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'items',
        'audit_logs',
        'procurement_note_sequences',
        'procurement_notes',
        'stock_requests',
        'procurement_note_items',
        'request_histories',
        'notifications',
        'settings',
        'stock_movements',
        'storage_locations',
        'location_change_requests',
        'monitoring_issues',
    ];

    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->error('Destination connection must be MySQL. Check DB_CONNECTION in .env.');
            return self::FAILURE;
        }

        $source = $this->option('source') ?: database_path('database.sqlite');
        if (! is_file($source)) {
            $this->error("SQLite source database not found: {$source}");
            return self::FAILURE;
        }

        $chunk = max(1, (int) $this->option('chunk'));
        $sqlite = DB::build([
            'driver' => 'sqlite',
            'database' => $source,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        $sqlite->getPdo();
        $availableTables = array_values(array_filter(self::TABLES, fn (string $table): bool => $sqlite->getSchemaBuilder()->hasTable($table)));
        $missingTables = array_values(array_diff(self::TABLES, $availableTables));

        if ($missingTables !== []) {
            $this->warn('Tables missing from SQLite source: '.implode(', ', $missingTables));
        }

        if ($this->option('truncate') && ! $this->confirm('This will delete destination data in the imported tables. Continue?', false)) {
            $this->info('Migration cancelled.');
            return self::SUCCESS;
        }

        $counts = [];

        try {
            DB::transaction(function () use ($sqlite, $availableTables, $chunk, &$counts): void {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');

                try {
                    if ($this->option('truncate')) {
                        foreach (array_reverse($availableTables) as $table) {
                            DB::table($table)->truncate();
                        }
                    }

                    foreach ($availableTables as $table) {
                        $this->output->write("Importing {$table}... ");
                        $inserted = 0;

                        $sqlite->table($table)->orderBy('rowid')->chunk($chunk, function ($rows) use ($table, &$inserted): void {
                            $payload = $rows->map(static fn ($row): array => (array) $row)->all();
                            if ($payload !== []) {
                                $primaryKey = $this->primaryKeyFor($table, $payload[0]);
                                if ($primaryKey !== null) {
                                    DB::table($table)->upsert($payload, [$primaryKey]);
                                } else {
                                    DB::table($table)->insertOrIgnore($payload);
                                }
                                $inserted += count($payload);
                            }
                        });

                        $counts[$table] = ['source' => $inserted, 'destination' => DB::table($table)->count()];
                        $this->output->writeln("{$inserted} rows");
                    }
                } finally {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');
                }
            });
        } catch (Throwable $exception) {
            $this->newLine();
            $this->error('Migration failed and the destination transaction was rolled back.');
            $this->error($exception->getMessage());
            return self::FAILURE;
        } finally {
            $sqlite->disconnect();
        }

        $this->newLine();
        $this->table(['Table', 'SQLite', 'MySQL', 'Status'], array_map(
            static fn (string $table): array => [
                $table,
                $counts[$table]['source'] ?? 0,
                $counts[$table]['destination'] ?? 0,
                ($counts[$table]['source'] ?? 0) === ($counts[$table]['destination'] ?? 0) ? 'MATCH' : 'MISMATCH',
            ],
            $availableTables,
        ));

        $mismatches = array_filter($counts, static fn (array $count): bool => $count['source'] !== $count['destination']);
        if ($mismatches !== []) {
            $this->error('Migration completed with row-count mismatches.');
            return self::FAILURE;
        }

        $this->info('Migration completed successfully. All imported table counts match.');
        return self::SUCCESS;
    }

    private function primaryKeyFor(string $table, array $row): ?string
    {
        if (array_key_exists('id', $row)) {
            return 'id';
        }

        return match ($table) {
            'password_reset_tokens' => 'email',
            'sessions' => 'id',
            'cache', 'cache_locks' => 'key',
            default => null,
        };
    }
}
