<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'details',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function labelForAction(): string
    {
        return static::actionLabels()[$this->action] ?? $this->action;
    }

    public function labelForTarget(): string
    {
        $type = $this->target_type;

        if (! $type) {
            return '—';
        }

        $name = static::targetLabels()[$type] ?? class_basename($type);

        return $name . ($this->target_id ? ' #'.$this->target_id : '');
    }

    public static function actionLabels(): array
    {
        return [
            'created_request' => 'Membuat request',
            'approved_request' => 'Menyetujui request',
            'rejected_request' => 'Menolak request',
            'delayed_request' => 'Menandai request terlambat',
            'closed_request' => 'Menutup request',
            'user_login' => 'Login pengguna',
            'user_logout' => 'Logout pengguna',
            'changed_password' => 'Mengubah password',
            'created_item' => 'Menambah barang',
            'updated_item' => 'Memperbarui barang',
            'adjusted_stock' => 'Menyesuaikan stok',
            'deleted_item' => 'Menghapus barang',
            'imported_items' => 'Mengimpor barang',
            'approved_location_change' => 'Menyetujui perubahan lokasi',
            'rejected_location_change' => 'Menolak perubahan lokasi',
            'created_user' => 'Menambah pengguna',
            'reset_password' => 'Reset password',
            'toggle_user_status' => 'Mengubah status pengguna',
            'updated_user_role' => 'Mengubah peran pengguna',
            'deleted_user' => 'Menghapus pengguna',
            'impersonated_user' => 'Login sebagai pengguna',
            'impersonation_stopped' => 'Menghentikan login sebagai',
            'dev_mode_enabled' => 'Mengaktifkan Developer Mode',
            'dev_mode_disabled' => 'Menonaktifkan Developer Mode',
            'backup_created' => 'Membuat backup',
            'backup_deleted' => 'Menghapus backup',
            'backup_restored' => 'Memulihkan backup',
            'reset_master_items' => 'Mereset master barang',
            'reset_stock_movements' => 'Mereset riwayat stok',
            'reset_stock_requests' => 'Mereset request',
            'reset_location_changes' => 'Mereset perubahan lokasi',
            'help_guide_published' => 'Menerbitkan panduan',
            'help_guide_archived' => 'Mengarsipkan panduan',
            'help_guide_deleted' => 'Menghapus panduan',
            'procurement_note_created' => 'Membuat nota pengadaan',
            'procurement_note_updated' => 'Memperbarui nota pengadaan',
            'procurement_note_issued' => 'Menerbitkan nota pengadaan',
            'procurement_note_cancelled' => 'Membatalkan nota pengadaan',
            'procurement_note_printed' => 'Mencetak nota pengadaan',
        ];
    }

    public static function targetLabels(): array
    {
        return [
            StockRequest::class => 'Request',
            Item::class => 'Barang',
            User::class => 'Pengguna',
            LocationChangeRequest::class => 'Perubahan Lokasi',
            ProcurementNote::class => 'Nota Pengadaan',
            HelpGuide::class => 'Panduan',
            Setting::class => 'Pengaturan',
        ];
    }
}