<?php

namespace App\Http\Controllers\AdminCoil;

use App\Http\Controllers\Controller;
use App\Models\CoilLocation;
use App\Models\CoilTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CoilLocationController extends Controller
{
    public function index(): View
    {
        $locations = CoilLocation::query()
            ->orderBy('urutan')
            ->orderBy('kode')
            ->get();

        return view('admin-coil.locations.index', [
            'locations' => $locations,
            'usage' => $this->usageCounts(),
            'unknown' => $this->unknownCodes($locations->pluck('kode')->all()),
            'nextUrutan' => (int) CoilLocation::max('urutan') + 1,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['kode' => CoilLocation::normalize((string) $request->input('kode'))]);

        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:50', Rule::unique('coil_locations', 'kode')],
            'nama' => ['nullable', 'string', 'max:100'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [], [
            'kode' => __('Kode Lokasi'),
            'nama' => __('Nama Lokasi'),
            'urutan' => __('Urutan'),
        ]);

        CoilLocation::create([
            'kode' => $validated['kode'],
            'nama' => $validated['nama'] ?? null,
            'urutan' => $validated['urutan'] ?? ((int) CoilLocation::max('urutan') + 1),
            'is_active' => true,
        ]);

        return back()->with('status', __('Lokasi berhasil ditambahkan.'));
    }

    public function update(Request $request, CoilLocation $coilLocation): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['nullable', 'string', 'max:100'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $coilLocation->update([
            'nama' => $validated['nama'] ?? null,
            'urutan' => $validated['urutan'] ?? $coilLocation->urutan,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', __('Lokasi berhasil diperbarui.'));
    }

    public function destroy(CoilLocation $coilLocation): RedirectResponse
    {
        if (CoilTag::query()->where('lokasi_coil', $coilLocation->kode)->exists()) {
            return back()->with('error', __('Lokasi masih dipakai coil. Nonaktifkan saja agar tetap tersimpan di riwayat.'));
        }

        $coilLocation->delete();

        return back()->with('status', __('Lokasi berhasil dihapus.'));
    }

    /**
     * Pull every location value already stored on coils into the master list.
     */
    public function sync(): RedirectResponse
    {
        $known = CoilLocation::query()->pluck('kode')->all();
        $added = 0;

        foreach ($this->unknownCodes($known) as $kode) {
            CoilLocation::ensure($kode);
            $added++;
        }

        if ($added === 0) {
            return back()->with('status', __('Tidak ada lokasi baru dari data coil.'));
        }

        return back()->with('status', __(':count lokasi baru ditambahkan dari data coil.', ['count' => $added]));
    }

    /**
     * @return Collection<string, int>
     */
    private function usageCounts(): Collection
    {
        return CoilTag::query()
            ->whereNotNull('lokasi_coil')
            ->selectRaw('lokasi_coil, COUNT(*) as total')
            ->groupBy('lokasi_coil')
            ->pluck('total', 'lokasi_coil');
    }

    /**
     * Values stored on coils that are missing from the given master codes.
     *
     * @param  array<int, string>  $known
     * @return Collection<int, string>
     */
    private function unknownCodes(array $known): Collection
    {
        return CoilTag::query()
            ->whereNotNull('lokasi_coil')
            ->distinct()
            ->orderBy('lokasi_coil')
            ->pluck('lokasi_coil')
            ->reject(fn (string $kode) => in_array($kode, $known, true))
            ->values();
    }
}
