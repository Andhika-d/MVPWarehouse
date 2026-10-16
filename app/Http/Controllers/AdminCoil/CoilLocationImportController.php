<?php

namespace App\Http\Controllers\AdminCoil;

use App\Exports\CoilLocationImportTemplate;
use App\Http\Controllers\Controller;
use App\Models\CoilLocation;
use App\Models\CoilTag;
use App\Models\CoilTagLocationHistory;
use App\Support\XlsxParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class CoilLocationImportController extends Controller
{
    private const SESSION_KEY = 'coil_location_import';

    private const MAX_ROWS = 5000;

    public function form(): View
    {
        return view('admin-coil.locations.import', [
            'locations' => CoilLocation::active()
                ->orderBy('urutan')
                ->orderBy('kode')
                ->pluck('kode'),
        ]);
    }

    public function template(): Response
    {
        $export = new CoilLocationImportTemplate([
            ['HKG0268301', 'AL'],
            ['HKG0268302', 'BL'],
            ['', ''],
        ]);

        return response($export->toXlsx(), 200)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="template-import-lokasi.xlsx"');
    }

    public function preview(Request $request): RedirectResponse|View
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:2048'],
        ]);

        try {
            $rows = XlsxParser::parse($request->file('file')->getRealPath());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        [$headers, $dataRows, $headerIndex] = $this->normalizeRows($rows);
        $columns = $this->mapHeaders($headers);

        if ($columns['nomor_coil'] === null || $columns['lokasi'] === null) {
            return back()->with('error', __('Kolom "Nomor Coil" dan "Lokasi" harus tersedia di file Excel.'));
        }

        $preview = [];
        $skipped = 0;
        $skippedRows = [];
        $counts = ['update' => 0, 'unchanged' => 0, 'not_found' => 0];

        foreach ($dataRows as $offset => $row) {
            $rowNumber = $headerIndex + 2 + $offset;
            $nomorCoil = trim((string) ($row[$columns['nomor_coil']] ?? ''));
            $lokasi = trim((string) ($row[$columns['lokasi']] ?? ''));

            if ($nomorCoil === '' || $lokasi === '') {
                $skipped++;
                $skippedRows[] = [
                    'row' => $rowNumber,
                    'nomor_coil' => $nomorCoil,
                    'reason' => $this->skipReason($nomorCoil, $lokasi),
                ];

                continue;
            }

            $coilTag = CoilTag::query()->where('nomor_coil', $nomorCoil)->first();
            $target = CoilLocation::normalize($lokasi);

            $status = match (true) {
                $coilTag === null => 'not_found',
                $coilTag->lokasi_coil === $target => 'unchanged',
                default => 'update',
            };

            $counts[$status]++;

            $preview[] = [
                'nomor_coil' => $nomorCoil,
                'lokasi' => $target,
                'coil_tag_id' => $coilTag?->id,
                'current_location' => $coilTag?->lokasi_coil,
                'status' => $status,
            ];
        }

        if (count($preview) > self::MAX_ROWS) {
            return back()->with('error', __('Terlalu banyak baris untuk diimpor sekaligus (maksimal :max).', ['max' => self::MAX_ROWS]));
        }

        session([self::SESSION_KEY => ['rows' => $preview]]);

        return view('admin-coil.locations.import-preview', [
            'preview' => $preview,
            'skipped' => $skipped,
            'skippedRows' => array_slice($skippedRows, 0, 50),
            'counts' => $counts,
        ]);
    }

    public function execute(Request $request): RedirectResponse
    {
        $stored = session(self::SESSION_KEY);

        if (! is_array($stored) || ! isset($stored['rows']) || ! is_array($stored['rows'])) {
            return redirect()
                ->route('admin-coil.locations.import')
                ->with('error', __('Sesi import kedaluwarsa. Silakan upload ulang.'));
        }

        session()->forget(self::SESSION_KEY);

        $rows = $stored['rows'];
        $updated = 0;
        $unchanged = 0;
        $notFound = 0;

        DB::transaction(function () use ($rows, &$updated, &$unchanged, &$notFound): void {
            foreach ($rows as $row) {
                $coilTag = isset($row['coil_tag_id']) && $row['coil_tag_id'] !== null
                    ? CoilTag::query()->find($row['coil_tag_id'])
                    : null;

                if ($coilTag === null) {
                    $notFound++;

                    continue;
                }

                if ($coilTag->lokasi_coil === ($row['lokasi'] ?? null)) {
                    $unchanged++;

                    continue;
                }

                $coilTag->changeLocation(
                    $row['lokasi'] ?? null,
                    CoilTagLocationHistory::SOURCE_ADMIN_IMPORT,
                    Auth::id(),
                );

                $updated++;
            }
        });

        return redirect()->route('admin-coil.coil-tags.index')->with('status', __('Import lokasi selesai: :updated diperbarui, :unchanged tanpa perubahan, :notFound tidak ditemukan.', [
            'updated' => $updated,
            'unchanged' => $unchanged,
            'notFound' => $notFound,
        ]));
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array{0: array<int, string>, 1: array<int, array<int, string>>, 2: int}
     */
    private function normalizeRows(array $rows): array
    {
        foreach ($rows as $index => $row) {
            $hasContent = collect($row)->contains(fn ($value): bool => trim((string) $value) !== '');

            if ($hasContent) {
                return [$row, array_slice($rows, $index + 1), $index];
            }
        }

        return [[], [], -1];
    }

    private function skipReason(string $nomorCoil, string $lokasi): string
    {
        return match (true) {
            $nomorCoil === '' && $lokasi === '' => __('Nomor Coil dan Lokasi kosong'),
            $nomorCoil === '' => __('Nomor Coil kosong'),
            default => __('Lokasi kosong'),
        };
    }

    /**
     * @param  array<int, string>  $headers
     * @return array{nomor_coil: int|null, lokasi: int|null}
     */
    private function mapHeaders(array $headers): array
    {
        $map = ['nomor_coil' => null, 'lokasi' => null];

        foreach ($headers as $index => $header) {
            $key = preg_replace('/[^a-z0-9]+/', ' ', strtolower(trim((string) $header)));

            if (in_array($key, ['nomor coil', 'coil number', 'no coil', 'kode coil', 'coil'], true)) {
                $map['nomor_coil'] ??= $index;
            } elseif (in_array($key, ['lokasi', 'location', 'lokasi coil', 'coil location'], true)) {
                $map['lokasi'] ??= $index;
            }
        }

        return $map;
    }
}
