<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class PeriodRange
{
    private function __construct(
        public readonly string $mode,
        public readonly ?CarbonImmutable $start,
        public readonly ?CarbonImmutable $end,
    ) {}

    public static function fromRequest(Request $request): ?self
    {
        if ($request->filled('period_mode')) {
            $data = $request->validate([
                'period_mode' => ['required', 'in:weekly,monthly,flexible'],
                'period_start' => ['required', 'date_format:Y-m-d'],
                'period_end' => ['nullable', 'required_if:period_mode,flexible', 'date_format:Y-m-d', 'after_or_equal:period_start'],
            ]);

            return self::fromValues($data['period_mode'], $data['period_start'], $data['period_end'] ?? null);
        }

        if ($date = self::date($request->query('date'))) {
            return new self('legacy', $date, $date);
        }

        if ($month = self::month($request->query('month'))) {
            return new self('legacy', $month->startOfMonth(), $month->endOfMonth());
        }

        $start = self::date($request->query('date_from'));
        $end = self::date($request->query('date_to'));

        return $start || $end ? new self('legacy', $start, $end) : null;
    }

    public static function fromValues(string $mode, string $start, ?string $end = null): self
    {
        $startDate = self::date($start) ?? throw new \InvalidArgumentException('Tanggal awal tidak valid.');

        return match ($mode) {
            'weekly' => new self($mode, $startDate, $startDate->addDays(7)),
            'monthly' => new self($mode, $startDate, $startDate->addMonthNoOverflow()),
            'flexible' => self::flexible($startDate, $end),
            default => throw new \InvalidArgumentException('Mode periode tidak valid.'),
        };
    }

    public function apply($query, string $column): void
    {
        if ($this->start) {
            $query->where($column, '>=', $this->start->startOfDay());
        }

        if ($this->end) {
            $query->where($column, '<', $this->end->addDay()->startOfDay());
        }
    }

    public function applyWithFallback($query, string $primaryColumn, string $fallbackColumn): void
    {
        $query->where(function ($rangeQuery) use ($primaryColumn, $fallbackColumn) {
            $rangeQuery->where(function ($primary) use ($primaryColumn) {
                $primary->whereNotNull($primaryColumn);
                $this->apply($primary, $primaryColumn);
            })->orWhere(function ($fallback) use ($primaryColumn, $fallbackColumn) {
                $fallback->whereNull($primaryColumn);
                $this->apply($fallback, $fallbackColumn);
            });
        });
    }

    public function startDate(): ?string
    {
        return $this->start?->toDateString();
    }

    public function endDate(): ?string
    {
        return $this->end?->toDateString();
    }

    public function endExclusive(): ?CarbonImmutable
    {
        return $this->end?->addDay()->startOfDay();
    }

    public function label(): string
    {
        if ($this->start && $this->end) {
            if ($this->start->isSameDay($this->end)) {
                return $this->start->translatedFormat('d M Y');
            }

            return $this->start->translatedFormat('d M Y').' - '.$this->end->translatedFormat('d M Y');
        }

        if ($this->start) {
            return 'Mulai '.$this->start->translatedFormat('d M Y');
        }

        return 'Sampai '.$this->end?->translatedFormat('d M Y');
    }

    private static function flexible(CarbonImmutable $start, ?string $end): self
    {
        $endDate = self::date($end) ?? throw new \InvalidArgumentException('Tanggal akhir tidak valid.');

        if ($endDate->isBefore($start)) {
            throw new \InvalidArgumentException('Tanggal akhir harus setelah tanggal awal.');
        }

        return new self('flexible', $start, $endDate);
    }

    private static function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }

    private static function month(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m', $value, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }
}
