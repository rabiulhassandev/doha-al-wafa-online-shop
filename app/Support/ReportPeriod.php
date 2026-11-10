<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Throwable;

/**
 * The dates a report covers (whole days in Qatar time) and how its chart groups them: by day, week or month.
 * Read from the query string, ignoring values that are not valid.
 */
final readonly class ReportPeriod
{
    /**
     * Ready-made ranges, in the order the filter lists them.
     */
    public const PRESETS = [
        'today' => 'Today',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'this_year' => 'This year',
    ];

    public const DEFAULT_PRESET = '30d';

    public const GROUPINGS = ['day' => 'By day', 'week' => 'By week', 'month' => 'By month'];

    /**
     * The longest custom range, so a report stays quick to build.
     */
    public const MAX_DAYS = 731;

    /**
     * The most days a "by day" chart shows before switching to weeks, and weeks before months.
     */
    private const MAX_DAY_BUCKETS = 62;

    private const MAX_WEEK_BUCKETS = 53;

    /**
     * Qatar's working week starts on Sunday.
     */
    private const WEEK_STARTS_ON = CarbonImmutable::SUNDAY;

    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public string $grouping,
        public string $preset,
    ) {}

    /**
     * Read the period from "?range=30d" or "?from=2026-10-01&to=2026-10-31", with an optional "&group=week".
     */
    public static function fromRequest(Request $request, ?CarbonImmutable $today = null): self
    {
        $today ??= CarbonImmutable::today();
        $preset = (string) $request->query('range');
        $from = self::date($request->query('from'));
        $to = self::date($request->query('to'));

        if (in_array($preset, ['', 'custom'], true) && $from !== null && $to !== null) {
            [$from, $to] = $from->greaterThan($to) ? [$to, $from] : [$from, $to];
            $from = $from->max($to->subDays(self::MAX_DAYS - 1));

            return self::make($from, $to, 'custom', (string) $request->query('group'));
        }

        $preset = array_key_exists($preset, self::PRESETS) ? $preset : self::DEFAULT_PRESET;
        [$from, $to] = self::presetDates($preset, $today);

        return self::make($from, $to, $preset, (string) $request->query('group'));
    }

    /**
     * Make a period, keeping a requested grouping only when the chart stays readable with it.
     */
    public static function make(CarbonImmutable $from, CarbonImmutable $to, string $preset, string $grouping = ''): self
    {
        $from = $from->startOfDay();
        $to = $to->endOfDay();
        $allowed = self::allowedGroupings((int) $from->diffInDays($to->startOfDay()) + 1);

        if (! in_array($grouping, $allowed, true)) {
            $grouping = $allowed[0] ?? 'day';
        }

        return new self($from, $to, $grouping, $preset);
    }

    /**
     * Get the same number of days just before this period, to compare with.
     */
    public function previous(): self
    {
        $days = $this->days();

        return self::make($this->from->subDays($days), $this->from->subDay(), 'custom', $this->grouping);
    }

    /**
     * Get how many days the period covers.
     */
    public function days(): int
    {
        return (int) $this->from->diffInDays($this->to->startOfDay()) + 1;
    }

    /**
     * Get the groupings that suit this period, for the filter.
     *
     * @return array<string, string>
     */
    public function groupingOptions(): array
    {
        return array_intersect_key(self::GROUPINGS, array_flip(self::allowedGroupings($this->days())));
    }

    /**
     * Describe the period, e.g. "Last 30 days (12 Oct – 10 Nov 2026)".
     */
    public function label(): string
    {
        $dates = $this->from->isSameDay($this->to)
            ? $this->from->format('j M Y')
            : $this->from->format($this->from->isSameYear($this->to) ? 'j M' : 'j M Y').' – '.$this->to->format('j M Y');

        return $this->preset === 'custom' ? $dates : self::PRESETS[$this->preset]." ({$dates})";
    }

    /**
     * Get the first day of the chart bar a date falls in.
     */
    public function bucketStart(CarbonImmutable $date): CarbonImmutable
    {
        return match ($this->grouping) {
            'week' => $date->startOfWeek(self::WEEK_STARTS_ON)->max($this->from),
            'month' => $date->startOfMonth()->max($this->from),
            default => $date->startOfDay(),
        };
    }

    /**
     * Get the first day of every chart bar, in order.
     *
     * @return list<CarbonImmutable>
     */
    public function buckets(): array
    {
        $buckets = [];
        $date = $this->bucketStart($this->from);

        while ($date->lessThanOrEqualTo($this->to)) {
            $buckets[] = $date;
            $date = match ($this->grouping) {
                'week' => $date->startOfWeek(self::WEEK_STARTS_ON)->addWeek(),
                'month' => $date->startOfMonth()->addMonth(),
                default => $date->addDay(),
            };
        }

        return $buckets;
    }

    /**
     * Name a chart bar: "Mon 3 Nov", "Week of 2 Nov" or "Nov 2026".
     */
    public function bucketLabel(CarbonImmutable $bucket): string
    {
        return match ($this->grouping) {
            'week' => 'Week of '.$bucket->format('j M'),
            'month' => $bucket->format('M Y'),
            default => $bucket->format('D j M'),
        };
    }

    /**
     * Name a chart bar briefly for the axis: "3 Nov", or "Nov" for months.
     */
    public function bucketAxisLabel(CarbonImmutable $bucket): string
    {
        return $bucket->format($this->grouping === 'month' ? 'M' : 'j M');
    }

    /**
     * Get the query string that keeps this period, e.g. for export links.
     *
     * @return array<string, string>
     */
    public function query(): array
    {
        $period = $this->preset === 'custom'
            ? ['from' => $this->from->toDateString(), 'to' => $this->to->toDateString()]
            : ['range' => $this->preset];

        return [...$period, 'group' => $this->grouping];
    }

    /**
     * Get the groupings that give a readable chart for this many days, the default first.
     *
     * @return list<string>
     */
    private static function allowedGroupings(int $days): array
    {
        return array_keys(array_filter([
            'day' => $days <= self::MAX_DAY_BUCKETS,
            'week' => $days > 7 && $days <= self::MAX_WEEK_BUCKETS * 7,
            'month' => $days > 31,
        ]));
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private static function presetDates(string $preset, CarbonImmutable $today): array
    {
        return match ($preset) {
            'today' => [$today, $today],
            '7d' => [$today->subDays(6), $today],
            '90d' => [$today->subDays(89), $today],
            'this_month' => [$today->startOfMonth(), $today],
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$today->startOfYear(), $today],
            default => [$today->subDays(29), $today],
        };
    }

    private static function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (Throwable) {
            return null;
        }

        // "2026-02-31" would otherwise roll over into March.
        return $date !== null && $date->format('Y-m-d') === $value ? $date : null;
    }
}
