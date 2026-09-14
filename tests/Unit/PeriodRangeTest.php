<?php

namespace Tests\Unit;

use App\Support\PeriodRange;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PeriodRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_period_uses_the_selected_date_and_seven_days_later(): void
    {
        $period = PeriodRange::fromValues('weekly', '2026-08-07');

        $this->assertSame('2026-08-07', $period->start->toDateString());
        $this->assertSame('2026-08-14', $period->end->toDateString());
    }

    public function test_monthly_period_rolls_to_the_same_valid_day_next_month(): void
    {
        $regular = PeriodRange::fromValues('monthly', '2026-08-07');
        $clamped = PeriodRange::fromValues('monthly', '2026-01-31');

        $this->assertSame('2026-09-07', $regular->end->toDateString());
        $this->assertSame('2026-02-28', $clamped->end->toDateString());
    }

    public function test_flexible_period_rejects_an_end_before_the_start(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PeriodRange::fromValues('flexible', '2026-08-14', '2026-08-07');
    }

    public function test_query_boundaries_include_the_full_end_date(): void
    {
        Schema::create('period_range_records', function (Blueprint $table) {
            $table->id();
            $table->timestamp('occurred_at');
        });

        $model = new class extends Model {
            public $timestamps = false;
            protected $table = 'period_range_records';
            protected $guarded = [];
        };

        foreach (['2026-08-06 23:59:59', '2026-08-07 00:00:00', '2026-08-14 23:59:59', '2026-08-15 00:00:00'] as $time) {
            $model->newQuery()->create(['occurred_at' => $time]);
        }

        $query = $model->newQuery();
        PeriodRange::fromValues('weekly', '2026-08-07')->apply($query, 'occurred_at');

        $this->assertSame(2, $query->count());
    }

    public function test_legacy_month_keeps_calendar_month_semantics(): void
    {
        $request = Request::create('/history', 'GET', ['month' => '2026-08']);
        $period = PeriodRange::fromRequest($request);

        $this->assertSame('2026-08-01', $period->startDate());
        $this->assertSame('2026-08-31', $period->endDate());
        $this->assertSame('2026-09-01', $period->endExclusive()->toDateString());
    }
}
