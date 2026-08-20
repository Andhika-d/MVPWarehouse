<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BackupsTest extends TestCase
{
    protected string $dbPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbPath = sys_get_temp_dir().'/rbv_backup_'.uniqid().'.sqlite';
        touch($this->dbPath);

        config(['database.connections.sqlite.database' => $this->dbPath]);
        DB::purge('sqlite');
        DB::reconnect();
        $this->artisan('migrate', ['--database' => 'sqlite']);

        $this->cleanupBackupFiles();
    }

    protected function tearDown(): void
    {
        $this->cleanupBackupFiles();

        foreach ([$this->dbPath, $this->dbPath.'-wal', $this->dbPath.'-shm'] as $path) {
            if (file_exists($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    protected function cleanupBackupFiles(): void
    {
        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir.'/*') ?: [] as $entry) {
            if (is_dir($entry)) {
                foreach (glob($entry.'/*') ?: [] as $file) {
                    @unlink($file);
                }
                @rmdir($entry);
            } else {
                @unlink($entry);
            }
        }
    }

    protected function makeAdmin(): User
    {
        return User::create([
            'name' => 'Admin Backup',
            'email' => 'admin-backup-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }

    protected function latestBackupName(): ?string
    {
        $files = glob(storage_path('app/backups/backup_corplogistics_*.zip')) ?: [];

        return $files === [] ? null : basename($files[0]);
    }

    public function test_only_admin_can_create_backup(): void
    {
        $gudang = User::create([
            'name' => 'Gudang Backup',
            'email' => 'gudang-backup@example.com',
            'password' => Hash::make('password'),
            'role' => 'gudang',
        ]);

        $this->actingAs($gudang)->post('/admin/backups')->assertForbidden();

        $this->assertSame([], glob(storage_path('app/backups/*.zip')) ?: []);
    }

    public function test_admin_can_create_and_download_backup(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/admin/backups')->assertRedirect('/admin/dashboard');

        $backup = $this->latestBackupName();
        $this->assertNotNull($backup);
        $this->assertFileExists(storage_path('app/backups/'.$backup));

        $response = $this->actingAs($admin)->get('/admin/backups/'.$backup.'/download');
        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename='.$backup);
    }

    public function test_admin_can_delete_backup(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/admin/backups')->assertRedirect('/admin/dashboard');

        $backup = $this->latestBackupName();
        $this->assertNotNull($backup);

        $this->actingAs($admin)->delete('/admin/backups/'.$backup)->assertRedirect('/admin/dashboard');

        $this->assertFileDoesNotExist(storage_path('app/backups/'.$backup));
    }

    public function test_admin_can_restore_database_from_backup(): void
    {
        $admin = $this->makeAdmin();

        User::create([
            'name' => 'Akan Dihapus',
            'email' => 'akan-dihapus@example.com',
            'password' => Hash::make('password'),
            'role' => 'gudang',
        ]);
        $this->assertDatabaseHas('users', ['email' => 'akan-dihapus@example.com']);

        $this->actingAs($admin)->post('/admin/backups')->assertRedirect('/admin/dashboard');

        $backup = $this->latestBackupName();
        $this->assertNotNull($backup);

        DB::table('users')->where('email', 'akan-dihapus@example.com')->delete();
        $this->assertDatabaseMissing('users', ['email' => 'akan-dihapus@example.com']);

        $this->actingAs($admin)->post('/admin/backups/'.$backup.'/restore')
            ->assertRedirect('/admin/dashboard');

        $this->assertDatabaseHas('users', ['email' => 'akan-dihapus@example.com']);
    }

    public function test_backup_retention_keeps_only_latest_ten(): void
    {
        $admin = $this->makeAdmin();
        $dir = storage_path('app/backups');
        $this->assertTrue(is_dir($dir) || mkdir($dir, 0755, true));

        foreach (range(1, 13) as $i) {
            $file = $dir.'/backup_corplogistics_2020010'.$i.'000000.zip';
            file_put_contents($file, 'dummy');
        }

        $this->actingAs($admin)->post('/admin/backups');

        $backups = glob($dir.'/backup_corplogistics_*.zip') ?: [];

        $this->assertCount(10, $backups);
    }

    public function test_invalid_backup_name_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get('/admin/backups/tidak-ada.zip/download')->assertStatus(404);
        $this->actingAs($admin)->delete('/admin/backups/tidak-ada.zip')->assertRedirect('/admin/dashboard');
        $this->actingAs($admin)->post('/admin/backups/tidak-ada.zip/restore')->assertRedirect('/admin/dashboard');
    }
}
