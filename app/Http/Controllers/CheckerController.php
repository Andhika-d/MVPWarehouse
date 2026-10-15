<?php

namespace App\Http\Controllers;

use App\Models\CoilTag;
use App\Services\KpScanParser;
use App\Services\TaehangTagParser;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;

class CheckerController extends Controller
{
    public function __construct(
        private readonly KpScanParser $kpParser,
        private readonly TaehangTagParser $taehangParser,
    ) {}

    public function index(Request $request): View
    {
        return view('checker.index', [
            'kp' => $request->session()->get('checker.kp'),
            'taehang' => $request->session()->get('checker.taehang'),
        ]);
    }

    public function scanKp(Request $request): RedirectResponse
    {
        $rawKp = $request->input('raw_kp');

        if (! is_string($rawKp) || trim($rawKp) === '') {
            return $this->validationRedirect($request, 'raw_kp', 'Scan KP wajib diisi.', 'raw_kp');
        }

        try {
            $kp = $this->kpParser->parse($rawKp);
        } catch (InvalidArgumentException $exception) {
            $request->session()->forget('checker');

            return $this->validationRedirect($request, 'raw_kp', $exception->getMessage(), 'raw_kp');
        }

        if (CoilTag::query()->where('nomor_coil', $kp['nomor_coil'])->exists()) {
            $request->session()->forget('checker');

            return $this->validationRedirect(
                $request,
                'raw_kp',
                'Nomor coil '.$kp['nomor_coil'].' sudah tersimpan.',
                'raw_kp',
            );
        }

        $request->session()->put('checker.kp', $kp);
        $request->session()->forget('checker.taehang');

        return redirect()->route('checker.index');
    }

    public function scanTaehang(Request $request): RedirectResponse
    {
        if (! is_array($request->session()->get('checker.kp'))) {
            return $this->validationRedirect($request, 'raw_kp', 'Scan KP terlebih dahulu.');
        }

        $rawTaehang = $request->input('raw_taehang');

        if (! is_string($rawTaehang) || trim($rawTaehang) === '') {
            return $this->validationRedirect($request, 'raw_taehang', 'Scan QR Taehang wajib diisi.', 'raw_taehang');
        }

        try {
            $taehang = $this->taehangParser->parse($rawTaehang);
        } catch (InvalidArgumentException $exception) {
            $request->session()->forget('checker.taehang');

            return $this->validationRedirect($request, 'raw_taehang', $exception->getMessage(), 'raw_taehang');
        }

        if (CoilTag::query()->where('tag_taehang', $taehang['tag_taehang'])->exists()) {
            $request->session()->forget('checker.taehang');

            return $this->validationRedirect(
                $request,
                'raw_taehang',
                'Tag Taehang '.$taehang['tag_taehang'].' sudah tersimpan.',
                'raw_taehang',
            );
        }

        $request->session()->put('checker.taehang', $taehang);

        return redirect()->route('checker.index');
    }

    public function save(Request $request): RedirectResponse
    {
        $kpRaw = $request->session()->get('checker.kp.raw_kp');
        $taehangRaw = $request->session()->get('checker.taehang.raw_taehang');

        if (! is_string($kpRaw) || ! is_string($taehangRaw)) {
            return $this->validationRedirect($request, 'raw_kp', 'Scan KP dan Taehang harus lengkap sebelum disimpan.');
        }

        try {
            $kp = $this->kpParser->parse($kpRaw);
            $taehang = $this->taehangParser->parse($taehangRaw);
        } catch (InvalidArgumentException $exception) {
            $request->session()->forget('checker');

            return $this->validationRedirect($request, 'raw_kp', $exception->getMessage());
        }

        try {
            DB::transaction(function () use ($kp, $taehang): void {
                if (CoilTag::query()->where('nomor_coil', $kp['nomor_coil'])->exists()) {
                    throw new DuplicateCoilTagException('raw_kp', 'Nomor coil '.$kp['nomor_coil'].' sudah tersimpan.');
                }

                if (CoilTag::query()->where('tag_taehang', $taehang['tag_taehang'])->exists()) {
                    throw new DuplicateCoilTagException('raw_taehang', 'Tag Taehang '.$taehang['tag_taehang'].' sudah tersimpan.');
                }

                CoilTag::query()->create([
                    ...$kp,
                    'tag_taehang' => $taehang['tag_taehang'],
                    'raw_taehang' => $taehang['raw_taehang'],
                    'lokasi_coil' => null,
                    'user_id' => Auth::id(),
                    'scanned_at' => now(),
                ]);
            });
        } catch (DuplicateCoilTagException $exception) {
            if ($exception->field === 'raw_kp') {
                $request->session()->forget('checker');
            } else {
                $request->session()->forget('checker.taehang');
            }

            return $this->validationRedirect(
                $request,
                $exception->field,
                $exception->getMessage(),
                $exception->field === 'raw_taehang' ? 'raw_taehang' : null,
                $exception->field === 'raw_taehang' ? $taehangRaw : null,
            );
        } catch (QueryException $exception) {
            if (CoilTag::query()->where('nomor_coil', $kp['nomor_coil'])->exists()) {
                $request->session()->forget('checker');

                return $this->validationRedirect(
                    $request,
                    'raw_kp',
                    'Nomor coil '.$kp['nomor_coil'].' sudah tersimpan.',
                    'raw_kp',
                    $kpRaw,
                );
            }

            if (CoilTag::query()->where('tag_taehang', $taehang['tag_taehang'])->exists()) {
                $request->session()->forget('checker.taehang');

                return $this->validationRedirect(
                    $request,
                    'raw_taehang',
                    'Tag Taehang '.$taehang['tag_taehang'].' sudah tersimpan.',
                    'raw_taehang',
                    $taehangRaw,
                );
            }

            throw $exception;
        }

        $request->session()->forget('checker');

        return redirect()->route('checker.index')->with('status', 'Data berhasil disimpan.');
    }

    private function validationRedirect(
        Request $request,
        string $field,
        string $message,
        ?string $oldInputField = null,
        ?string $oldInput = null,
    ): RedirectResponse {
        $request->session()->flash('checker_error', $message);
        $redirect = redirect()->route('checker.index')->withErrors([$field => $message]);

        if ($oldInputField !== null) {
            $oldInput ??= $request->input($oldInputField);

            return $redirect->withInput([$oldInputField => $oldInput]);
        }

        return $redirect;
    }
}

class DuplicateCoilTagException extends \RuntimeException
{
    public function __construct(
        public readonly string $field,
        string $message,
    ) {
        parent::__construct($message);
    }
}
