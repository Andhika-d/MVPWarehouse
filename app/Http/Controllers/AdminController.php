<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\Setting;
use App\Models\User;
use App\Support\XlsxParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function dashboard()
    {
        $items = Item::latest()->paginate(10);
        $users = User::latest()->paginate(10);
        $auditLogs = AuditLog::with('user')->latest()->take(10)->get();

        $masterItems = Item::all()->map(fn (Item $item) => [
            'id' => $item->id,
            'name' => $item->name,
            'size' => $item->size,
            'rack_location' => $item->rack_location,
            'stock' => $item->stock,
            'unit' => $item->unit,
        ])->keyBy('id');

        $usersData = User::all()->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
        ])->keyBy('id');

        $backups = collect(glob(storage_path('app/backups/*.zip')) ?: [])
            ->sortByDesc(fn (string $path) => filemtime($path))
            ->map(fn (string $path) => [
                'name' => basename($path),
                'size' => $this->formatBytes(filesize($path)),
                'time' => date('d M Y, H:i', filemtime($path)),
            ])
            ->values();

        return view('admin.dashboard', compact('items', 'users', 'auditLogs', 'masterItems', 'usersData', 'backups'));
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    public function storeItem(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:255'],
            'rack_location' => ['required', 'in:A,B,C,D,E'],
            'stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'in:'.implode(',', Item::UNITS)],
        ]);

        $item = Item::create($data);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'created_item',
            'target_type' => Item::class,
            'target_id' => $item->id,
            'details' => 'Menambahkan item master '.$item->name,
        ]);

        return redirect('/admin/dashboard')->with('success', 'Item berhasil ditambahkan.');
    }

    public function updateItem(Request $request, Item $item)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:255'],
            'rack_location' => ['required', 'in:A,B,C,D,E'],
            'stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'in:'.implode(',', Item::UNITS)],
        ]);

        $item->update($data);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'updated_item',
            'target_type' => Item::class,
            'target_id' => $item->id,
            'details' => 'Mengubah item master '.$item->name,
        ]);

        return redirect('/admin/dashboard')->with('success', 'Item berhasil diperbarui.');
    }

    public function importItems(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:2048'],
            'rack_location' => ['required', 'in:A,B,C,D,E'],
        ]);

        try {
            $rows = XlsxParser::parse($request->file('file')->getRealPath());
        } catch (\RuntimeException $e) {
            return redirect('/admin/dashboard')->with('error', $e->getMessage());
        }

        [$headers, $dataRows] = $this->normalizeImportRows($rows);
        $columns = $this->mapImportHeaders($headers);

        if ($columns['name'] === null) {
            return redirect('/admin/dashboard')->with('error', 'Kolom "Nama Barang" tidak ditemukan di file Excel.');
        }

        $imported = 0;
        $skipped = 0;

        try {
            DB::transaction(function () use ($dataRows, $columns, $data, &$imported, &$skipped) {
                foreach ($dataRows as $row) {
                    $name = trim((string) ($row[$columns['name']] ?? ''));
                    if ($name === '') {
                        $skipped++;

                        continue;
                    }

                    $size = $columns['size'] !== null ? trim((string) ($row[$columns['size']] ?? '')) : '';
                    $size = $size === '' ? null : $size;

                    [$stock, $unit] = XlsxParser::parseQuantity(
                        (string) ($columns['qty'] !== null ? ($row[$columns['qty']] ?? '') : '')
                    );

                    Item::create([
                        'name' => $name,
                        'size' => $size,
                        'rack_location' => $data['rack_location'],
                        'stock' => $stock,
                        'unit' => $unit,
                    ]);

                    $imported++;
                }
            });
        } catch (\Throwable $e) {
            return redirect('/admin/dashboard')->with('error', 'Import gagal dan dibatalkan: '.$e->getMessage());
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'imported_items',
            'target_type' => Item::class,
            'target_id' => null,
            'details' => 'Mengimpor '.$imported.' item master ke Rak '.$data['rack_location'],
        ]);

        $message = $imported.' item berhasil diimpor ke Rak '.$data['rack_location'].'.';
        if ($skipped > 0) {
            $message .= ' '.$skipped.' baris dilewati (nama barang kosong).';
        }

        return redirect('/admin/dashboard')->with('success', $message);
    }

    protected function normalizeImportRows(array $rows): array
    {
        foreach ($rows as $index => $row) {
            $hasContent = collect($row)->contains(fn ($value) => trim((string) $value) !== '');

            if ($hasContent) {
                return [$row, array_slice($rows, $index + 1)];
            }
        }

        return [[], []];
    }

    protected function mapImportHeaders(array $headers): array
    {
        $map = ['name' => null, 'size' => null, 'qty' => null];

        foreach ($headers as $index => $header) {
            $key = preg_replace('/[^a-z0-9]+/', ' ', strtolower(trim((string) $header)));

            if (in_array($key, ['nama barang', 'nama', 'daftar barang', 'barang', 'name'], true)) {
                $map['name'] ??= $index;
            } elseif (in_array($key, ['size', 'ukuran'], true)) {
                $map['size'] ??= $index;
            } elseif (in_array($key, ['qty', 'quantity', 'jumlah', 'stok', 'stock', 'kuantitas'], true)) {
                $map['qty'] ??= $index;
            }
        }

        return $map;
    }

    public function destroyItem(Item $item)
    {
        $name = $item->name;
        $item->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'deleted_item',
            'target_type' => Item::class,
            'target_id' => null,
            'details' => 'Menghapus item master '.$name,
        ]);

        return redirect('/admin/dashboard')->with('success', 'Item berhasil dihapus.');
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'in:gudang,hr,admin'],
        ]);

        $generatedPassword = filled($data['password'] ?? null) ? $data['password'] : Str::password(12);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($generatedPassword),
            'role' => $data['role'],
            'must_change_password' => blank($data['password'] ?? null),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'created_user',
            'target_type' => User::class,
            'target_id' => $user->id,
            'details' => 'Membuat akun '.$user->email,
        ]);

        $message = 'Pengguna berhasil ditambahkan.';
        if (blank($data['password'] ?? null)) {
            $message .= ' Password sementara (tampilkan sekali, wajib diganti saat login): '.$generatedPassword;
        }

        return redirect('/admin/dashboard')->with('success', $message);
    }

    public function resetUserPassword(User $user)
    {
        $generatedPassword = Str::password(12);

        $user->update([
            'password' => Hash::make($generatedPassword),
            'must_change_password' => true,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'reset_password',
            'target_type' => User::class,
            'target_id' => $user->id,
            'details' => 'Mereset password akun '.$user->email,
        ]);

        return redirect('/admin/dashboard')->with(
            'success',
            'Password pengguna berhasil direset. Password sementara (tampilkan sekali): '.$generatedPassword
        );
    }

    public function toggleUserStatus(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect('/admin/dashboard')->with('error', 'Tidak bisa mengubah status akun sendiri.');
        }

        if ($user->is_active && $this->isLastActiveAdmin($user)) {
            return redirect('/admin/dashboard')->with('error', 'Tidak bisa menonaktifkan admin aktif terakhir.');
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'toggle_user_status',
            'target_type' => User::class,
            'target_id' => $user->id,
            'details' => ($user->is_active ? 'Mengaktifkan' : 'Menonaktifkan').' akun '.$user->email,
        ]);

        return redirect('/admin/dashboard')->with('success', 'Status akun berhasil diperbarui.');
    }

    public function updateUserRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', 'in:gudang,hr,admin'],
        ]);

        if ($user->id === Auth::id()) {
            return redirect('/admin/dashboard')->with('error', 'Tidak bisa mengubah role akun sendiri.');
        }

        if ($user->role === 'admin' && $user->is_active && $data['role'] !== 'admin' && $this->isLastActiveAdmin($user)) {
            return redirect('/admin/dashboard')->with('error', 'Tidak bisa mengubah role admin aktif terakhir.');
        }

        $user->update([
            'role' => $data['role'],
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'updated_user_role',
            'target_type' => User::class,
            'target_id' => $user->id,
            'details' => 'Mengubah role akun '.$user->email.' menjadi '.$data['role'],
        ]);

        return redirect('/admin/dashboard')->with('success', 'Role pengguna berhasil diperbarui.');
    }

    public function deleteUser(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect('/admin/dashboard')->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        if ($user->role === 'admin' && $user->is_active && $this->isLastActiveAdmin($user)) {
            return redirect('/admin/dashboard')->with('error', 'Tidak bisa menghapus admin aktif terakhir.');
        }

        $email = $user->email;
        $user->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'deleted_user',
            'target_type' => User::class,
            'target_id' => null,
            'details' => 'Menghapus akun '.$email,
        ]);

        return redirect('/admin/dashboard')->with('success', 'Pengguna berhasil dihapus.');
    }

    public function toggleDevMode()
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $nowActive = ! Setting::enabled('dev_mode');
        Setting::set('dev_mode', $nowActive ? '1' : '0');

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $nowActive ? 'dev_mode_enabled' : 'dev_mode_disabled',
            'target_type' => Setting::class,
            'target_id' => null,
            'details' => $nowActive
                ? 'Admin mengaktifkan Developer Mode (seluruh pembatasan role dibuka untuk testing)'
                : 'Admin menonaktifkan Developer Mode',
        ]);

        return redirect('/admin/dashboard')->with(
            'success',
            $nowActive
                ? 'Developer Mode aktif. Seluruh pembatasan role dibuka untuk keperluan testing.'
                : 'Developer Mode nonaktif.'
        );
    }

    public function loginAs(User $user, Request $request)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        if ($user->id === Auth::id()) {
            return redirect('/admin/dashboard')->with('error', 'Tidak bisa login sebagai diri sendiri.');
        }

        if ($user->role === 'admin') {
            return redirect('/admin/dashboard')->with('error', 'Login As hanya bisa untuk akun non-admin.');
        }

        if (! $user->is_active) {
            return redirect('/admin/dashboard')->with('error', 'Akun target nonaktif, tidak bisa digunakan untuk Login As.');
        }

        session(['impersonate_by' => Auth::id()]);
        $request->session()->regenerate();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'impersonated_user',
            'target_type' => User::class,
            'target_id' => $user->id,
            'details' => 'Admin login sebagai '.$user->email.' ('.$user->role.') untuk testing',
        ]);

        Auth::login($user);

        return redirect($this->homeRouteFor($user->role));
    }

    public function stopImpersonation()
    {
        $adminId = session('impersonate_by');

        if (! $adminId) {
            return redirect('/admin/dashboard');
        }

        $admin = User::find($adminId);

        if ($admin) {
            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'impersonation_stopped',
                'target_type' => User::class,
                'target_id' => $admin->id,
                'details' => 'Admin kembali ke akun asli setelah sesi Login As',
            ]);

            Auth::login($admin);
        }

        session()->forget('impersonate_by');

        return redirect('/admin/dashboard');
    }

    public function createBackup()
    {
        $dbPath = config('database.connections.sqlite.database');

        try {
            DB::select('PRAGMA wal_checkpoint(TRUNCATE)');
        } catch (\Throwable) {
            // abaikan — database in-memory saat testing
        }

        if (! is_file($dbPath)) {
            return redirect('/admin/dashboard')->with('error', 'Backup gagal: file database tidak ditemukan.');
        }

        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'backup_corplogistics_'.now()->format('Y_m_d_His').'.zip';
        $zip = new \ZipArchive;

        if ($zip->open($dir.'/'.$filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect('/admin/dashboard')->with('error', 'Backup gagal: tidak dapat membuat arsip.');
        }

        $zip->addFile($dbPath, 'database.sqlite');
        $zip->close();

        $this->pruneBackups($dir);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'backup_created',
            'target_type' => null,
            'target_id' => null,
            'details' => 'Membuat backup database '.$filename,
        ]);

        return redirect('/admin/dashboard')->with('success', 'Backup berhasil dibuat: '.$filename);
    }

    public function downloadBackup(string $file)
    {
        $path = $this->resolveBackupPath($file);

        if ($path === null) {
            abort(404);
        }

        return response()->download($path);
    }

    public function deleteBackup(string $file)
    {
        $path = $this->resolveBackupPath($file);

        if ($path === null) {
            return redirect('/admin/dashboard')->with('error', 'File backup tidak ditemukan.');
        }

        unlink($path);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'backup_deleted',
            'target_type' => null,
            'target_id' => null,
            'details' => 'Menghapus backup database '.$file,
        ]);

        return redirect('/admin/dashboard')->with('success', 'Backup '.$file.' berhasil dihapus.');
    }

    public function restoreBackup(string $file, Request $httpRequest)
    {
        $path = $this->resolveBackupPath($file);

        if ($path === null) {
            return redirect('/admin/dashboard')->with('error', 'File backup tidak ditemukan.');
        }

        $dbPath = config('database.connections.sqlite.database');

        try {
            DB::select('PRAGMA wal_checkpoint(TRUNCATE)');
        } catch (\Throwable) {
            // abaikan
        }

        $dir = storage_path('app/backups');

        $safetyName = 'pre_restore_'.now()->format('Y_m_d_His').'.zip';
        if (is_file($dbPath)) {
            $safetyZip = new \ZipArchive;
            if ($safetyZip->open($dir.'/'.$safetyName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                $safetyZip->addFile($dbPath, 'database.sqlite');
                $safetyZip->close();
            }
        }

        $tmpDir = $dir.'/_restore_'.bin2hex(random_bytes(4));
        mkdir($tmpDir, 0755, true);

        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            $this->deleteDir($tmpDir);

            return redirect('/admin/dashboard')->with('error', 'Restore gagal: arsip backup rusak.');
        }

        $zip->extractTo($tmpDir);
        $zip->close();

        $extracted = $tmpDir.'/database.sqlite';

        if (! is_file($extracted)) {
            $this->deleteDir($tmpDir);

            return redirect('/admin/dashboard')->with('error', 'Restore gagal: isi arsip tidak valid.');
        }

        if (! $this->isValidSqlite($extracted)) {
            $this->deleteDir($tmpDir);

            return redirect('/admin/dashboard')->with('error', 'Restore gagal: database backup rusak dan tidak dapat diverifikasi.');
        }

        $admin = Auth::user();

        DB::disconnect();

        @unlink($dbPath.'-wal');
        @unlink($dbPath.'-shm');

        copy($extracted, $dbPath);

        $this->deleteDir($tmpDir);

        try {
            DB::connection()->getPdo()->query('SELECT 1');
        } catch (\Throwable) {
            return redirect('/admin/dashboard')->with('error', 'Restore gagal: database hasil restore rusak.');
        }

        AuditLog::create([
            'user_id' => $admin?->id,
            'action' => 'backup_restored',
            'target_type' => null,
            'target_id' => null,
            'details' => 'Memulihkan database dari backup '.$file,
        ]);

        if ($admin) {
            Auth::login($admin);
            $httpRequest->session()->regenerate();
        }

        return redirect('/admin/dashboard')->with('success', 'Database berhasil dipulihkan dari '.$file.'.');
    }

    protected function isValidSqlite(string $path): bool
    {
        try {
            $pdo = new \PDO('sqlite:'.$path);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $integrity = $pdo->query('PRAGMA integrity_check')->fetchColumn();

            return $integrity === 'ok';
        } catch (\Throwable) {
            return false;
        }
    }

    protected function pruneBackups(string $dir, int $keep = 10): void
    {
        $files = collect(glob($dir.'/backup_corplogistics_*.zip') ?: [])
            ->merge(glob($dir.'/pre_restore_*.zip') ?: [])
            ->sortByDesc(fn (string $path) => filemtime($path))
            ->values();

        foreach ($files->slice($keep)->all() as $path) {
            @unlink($path);
        }
    }

    protected function resolveBackupPath(string $file): ?string    {
        if (! preg_match('/^[A-Za-z0-9_\-]+\.zip$/', $file)) {
            return null;
        }

        $path = storage_path('app/backups/'.$file);

        return is_file($path) ? $path : null;
    }

    protected function deleteDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir.'/*') ?: [] as $file) {
            is_dir($file) ? $this->deleteDir($file) : @unlink($file);
        }

        @rmdir($dir);
    }

    protected function isLastActiveAdmin(User $user): bool
    {
        return User::where('role', 'admin')
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->count() === 0;
    }

    protected function homeRouteFor(string $role): string
    {
        return match ($role) {
            'hr' => '/hr/dashboard',
            'admin' => '/admin/dashboard',
            default => '/gudang/dashboard',
        };
    }
}
