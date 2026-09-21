<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProcurementNote extends Model
{
    public const STATUS_ACTIVE = 'Aktif';

    public const STATUS_COMPLETED = 'Selesai';

    public const TERMINAL_REQUEST_STATUSES = [
        'Ditolak',
        'Diterima Penuh',
        'Ditutup Sebagian',
        'Dibatalkan',
    ];

    protected $fillable = [
        'number',
        'request_date',
        'last_printed_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'last_printed_at' => 'datetime',
    ];

    public function requests()
    {
        return $this->hasMany(StockRequest::class)->orderBy('created_at')->orderBy('id');
    }

    public static function findOrCreateForDate(CarbonInterface|string $date): self
    {
        $requestDate = $date instanceof CarbonInterface ? $date->toDateString() : $date;

        DB::table('procurement_notes')->insertOrIgnore([
            'number' => 'NOTA-'.str_replace('-', '', $requestDate),
            'request_date' => $requestDate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return self::where('request_date', $requestDate)->firstOrFail();
    }

    public function statusLabel(): string
    {
        $requests = $this->relationLoaded('requests') ? $this->requests : $this->requests()->get();

        return $requests->isNotEmpty()
            && $requests->every(fn (StockRequest $request) => in_array($request->status, self::TERMINAL_REQUEST_STATUSES, true))
                ? self::STATUS_COMPLETED
                : self::STATUS_ACTIVE;
    }

    public function statusCounts(): array
    {
        $requests = $this->relationLoaded('requests') ? $this->requests : $this->requests()->get();

        return $requests->countBy('status')->all();
    }
}
