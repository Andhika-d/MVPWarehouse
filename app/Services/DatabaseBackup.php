<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Backup & restore database yang mendukung SQLite dan MySQL/MariaDB.
 *
 * - SQLite  : salinan file database (entry "database.sqlite" di dalam zip).
 * - MySQL   : dump SQL via mysqldump bila tersedia, dengan fallback murni PHP
 *             (entry "database.sql" di dalam zip).
 */
class DatabaseBackup
{
    public const SQLITE_ENTRY = 'database.sqlite';

    public const SQL_ENTRY = 'database.sql';

    public static function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    public static function isSqlite(): bool
    {
        return self::driver() === 'sqlite';
    }

    public static function isMysql(): bool
    {
        return in_array(self::driver(), ['mysql', 'mariadb'], true);
    }

    /**
     * Tulis dump database aktif ke $target.
     *
     * @return string nama entri yang harus dipakai di dalam arsip zip
     */
    public static function dump(string $target): string
    {
        if (self::isSqlite()) {
            return self::dumpSqlite($target);
        }

        if (self::isMysql()) {
            self::dumpMysql($target);

            return self::SQL_ENTRY;
        }

        throw new RuntimeException(__('Backup gagal: jenis database tidak didukung.'));
    }

    private static function dumpSqlite(string $target): string
    {
        try {
            DB::select('PRAGMA wal_checkpoint(TRUNCATE)');
        } catch (\Throwable) {
            // Tidak semua koneksi SQLite mendukung WAL.
        }

        $source = (string) config('database.connections.sqlite.database');

        if ($source === '' || ! is_file($source)) {
            throw new RuntimeException(__('Backup gagal: file database tidak ditemukan.'));
        }

        if (! @copy($source, $target)) {
            throw new RuntimeException(__('Backup gagal: tidak dapat menyalin database.'));
        }

        return self::SQLITE_ENTRY;
    }

    private static function dumpMysql(string $target): void
    {
        $binary = self::findMysqldump();

        if ($binary !== null && self::runMysqldump($binary, $target)) {
            return;
        }

        self::dumpMysqlWithPhp($target);
    }

    private static function findMysqldump(): ?string
    {
        $configured = env('MYSQLDUMP_PATH');

        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        $found = (new ExecutableFinder)->find('mysqldump');

        if (is_string($found) && $found !== '') {
            return $found;
        }

        $patterns = [
            'C:/laragon/bin/mysql/*/bin/mysqldump.exe',
            'C:/xampp/mysql/bin/mysqldump.exe',
            'C:/wamp64/bin/mysql/*/bin/mysqldump.exe',
            'C:/Program Files/MySQL/*/bin/mysqldump.exe',
            'C:/Program Files/MariaDB*/bin/mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
        ];

        $candidates = [];

        foreach ($patterns as $pattern) {
            if (is_file($pattern)) {
                $candidates[] = $pattern;
            }

            foreach (glob($pattern) ?: [] as $path) {
                $candidates[] = $path;
            }
        }

        rsort($candidates);

        return $candidates[0] ?? null;
    }

    private static function runMysqldump(string $binary, string $target): bool
    {
        $config = (array) config('database.connections.mysql');

        $common = [
            $binary,
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.($config['port'] ?? '3306'),
            '--user='.($config['username'] ?? 'root'),
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--default-character-set=utf8mb4',
            '--add-drop-table',
        ];

        if (! empty($config['unix_socket'])) {
            $common[] = '--socket='.$config['unix_socket'];
        }

        $database = (string) ($config['database'] ?? '');

        if ($database === '') {
            return false;
        }

        $attempts = [
            array_merge($common, ['--set-gtid-purged=OFF', '--no-tablespaces', $database]),
            array_merge($common, [$database]),
        ];

        $env = [];

        if (! empty($config['password'])) {
            $env['MYSQL_PWD'] = (string) $config['password'];
        }

        foreach ($attempts as $args) {
            try {
                $process = new Process($args, base_path(), $env);
                $process->setTimeout(300);
                $process->run();

                if (! $process->isSuccessful()) {
                    continue;
                }

                $output = $process->getOutput();

                if (trim($output) === '' || ! str_contains($output, 'SET NAMES') && ! str_contains($output, 'CREATE TABLE') && ! str_contains($output, 'INSERT INTO')) {
                    continue;
                }

                return file_put_contents($target, $output) !== false;
            } catch (\Throwable) {
                // Coba strategi berikutnya.
            }
        }

        return false;
    }

    /**
     * Dump MySQL tanpa bergantung pada binary mysqldump.
     */
    private static function dumpMysqlWithPhp(string $target): void
    {
        $pdo = DB::connection()->getPdo();
        $handle = fopen($target, 'wb');

        if ($handle === false) {
            throw new RuntimeException(__('Backup gagal: tidak dapat menulis file backup.'));
        }

        try {
            fwrite($handle, "-- Backup database\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

            $tables = $pdo->query('SHOW FULL TABLES')->fetchAll(\PDO::FETCH_NUM);

            foreach ($tables as $table) {
                $name = (string) ($table[0] ?? '');
                $type = strtoupper((string) ($table[1] ?? 'BASE TABLE'));

                if ($name === '' || $type !== 'BASE TABLE') {
                    continue;
                }

                $quoted = '`'.str_replace('`', '``', $name).'`';

                $create = $pdo->query('SHOW CREATE TABLE '.$quoted)->fetch(\PDO::FETCH_NUM);
                $definition = (string) ($create[1] ?? '');

                if ($definition === '') {
                    continue;
                }

                fwrite($handle, "DROP TABLE IF EXISTS {$quoted};\n{$definition};\n\n");

                $rows = $pdo->query('SELECT * FROM '.$quoted);
                $batch = [];

                while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                    $values = array_map(
                        fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value),
                        $row,
                    );

                    $batch[] = '('.implode(',', $values).')';

                    if (count($batch) >= 100) {
                        fwrite($handle, "INSERT INTO {$quoted} VALUES\n".implode(",\n", $batch).";\n");
                        $batch = [];
                    }
                }

                if ($batch !== []) {
                    fwrite($handle, "INSERT INTO {$quoted} VALUES\n".implode(",\n", $batch).";\n");
                }

                fwrite($handle, "\n");
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($handle);
        }

        if (! is_file($target) || filesize($target) === 0) {
            throw new RuntimeException(__('Backup gagal: tidak dapat membuat dump database.'));
        }
    }

    /**
     * Pulihkan database dari direktori hasil ekstrak arsip backup.
     */
    public static function restoreFromDirectory(string $directory): void
    {
        if (self::isSqlite()) {
            $file = $directory.'/'.self::SQLITE_ENTRY;

            if (! is_file($file)) {
                throw new RuntimeException(__('Restore gagal: backup ini bukan backup SQLite.'));
            }

            if (! self::isValidSqlite($file)) {
                throw new RuntimeException(__('Restore gagal: database backup rusak.'));
            }

            $dbPath = (string) config('database.connections.sqlite.database');

            if ($dbPath === '') {
                throw new RuntimeException(__('Restore gagal: lokasi database tidak ditemukan.'));
            }

            DB::disconnect();
            @unlink($dbPath.'-wal');
            @unlink($dbPath.'-shm');

            if (! @copy($file, $dbPath)) {
                throw new RuntimeException(__('Restore gagal: tidak dapat menulis database.'));
            }

            return;
        }

        if (self::isMysql()) {
            $file = $directory.'/'.self::SQL_ENTRY;

            if (! is_file($file)) {
                throw new RuntimeException(__('Restore gagal: backup ini bukan backup MySQL.'));
            }

            self::importSql($file);

            return;
        }

        throw new RuntimeException(__('Restore gagal: jenis database tidak didukung.'));
    }

    /**
     * Jalankan file dump SQL ke koneksi MySQL aktif.
     */
    public static function importSql(string $path): void
    {
        $sql = file_get_contents($path);

        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException(__('Restore gagal: isi arsip tidak valid.'));
        }

        $pdo = DB::connection()->getPdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach (self::splitSql($sql) as $statement) {
                $statement = trim($statement);

                if ($statement === '') {
                    continue;
                }

                $pdo->exec($statement);
            }
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                __('Restore gagal: :message', ['message' => $exception->getMessage()]),
                0,
                $exception,
            );
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Pecah dump SQL menjadi statement, dengan menghormati string literal
     * dan komentar sehingga titik koma di dalam data tidak memotong statement.
     *
     * @return array<int, string>
     */
    public static function splitSql(string $sql): array
    {
        $statements = [];
        $current = '';
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            if (! $inSingle && ! $inDouble && ! $inBacktick) {
                if ($char === '-' && $next === '-' && in_array($sql[$i + 2] ?? ' ', [' ', "\t", "\n", "\r"], true)) {
                    while ($i < $length && $sql[$i] !== "\n") {
                        $i++;
                    }
                    $current .= "\n";

                    continue;
                }

                if ($char === '#') {
                    while ($i < $length && $sql[$i] !== "\n") {
                        $i++;
                    }
                    $current .= "\n";

                    continue;
                }

                if ($char === '/' && $next === '*') {
                    $i += 2;

                    while ($i < $length - 1 && ! ($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                        $i++;
                    }

                    $i++;

                    continue;
                }
            }

            if ($char === "'" && ! $inDouble && ! $inBacktick) {
                $inSingle = self::toggleQuote($sql, $i, $inSingle);
            } elseif ($char === '"' && ! $inSingle && ! $inBacktick) {
                $inDouble = self::toggleQuote($sql, $i, $inDouble);
            } elseif ($char === '`' && ! $inSingle && ! $inDouble) {
                $inBacktick = ! $inBacktick;
            } elseif ($char === ';' && ! $inSingle && ! $inDouble && ! $inBacktick) {
                $statements[] = $current;
                $current = '';

                continue;
            }

            $current .= $char;
        }

        if (trim($current) !== '') {
            $statements[] = $current;
        }

        return $statements;
    }

    public static function isValidSqlite(string $path): bool
    {
        try {
            $pdo = new \PDO('sqlite:'.$path);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            return $pdo->query('PRAGMA integrity_check')->fetchColumn() === 'ok';
        } catch (\Throwable) {
            return false;
        }
    }

    private static function toggleQuote(string $sql, int $index, bool $state): bool
    {
        $backslashes = 0;
        $cursor = $index - 1;

        while ($cursor >= 0 && $sql[$cursor] === '\\') {
            $backslashes++;
            $cursor--;
        }

        return $backslashes % 2 === 0 ? ! $state : $state;
    }
}
