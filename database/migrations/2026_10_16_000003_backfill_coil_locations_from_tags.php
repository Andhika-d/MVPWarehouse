<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Make sure every location value already stored on coils exists in the master
     * list, so the location filters and pills never hide existing data.
     */
    public function up(): void
    {
        $codes = DB::table('coil_tags')
            ->whereNotNull('lokasi_coil')
            ->where('lokasi_coil', '!=', '')
            ->distinct()
            ->pluck('lokasi_coil');

        if ($codes->isEmpty()) {
            return;
        }

        $now = now();
        $urutan = (int) DB::table('coil_locations')->max('urutan');

        foreach ($codes as $code) {
            $kode = mb_strtoupper(trim((string) $code));

            if ($kode === '') {
                continue;
            }

            // Normalize the value already saved on the coil.
            DB::table('coil_tags')
                ->where('lokasi_coil', $code)
                ->update(['lokasi_coil' => $kode]);

            if (DB::table('coil_locations')->where('kode', $kode)->exists()) {
                continue;
            }

            $urutan++;

            DB::table('coil_locations')->insert([
                'kode' => $kode,
                'nama' => null,
                'urutan' => $urutan,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Data backfill cannot be safely reverted.
    }
};
