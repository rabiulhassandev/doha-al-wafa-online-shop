<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Works out delivery dates in working days, skipping the store's days off (Friday by default).
 */
final class DeliveryEstimate
{
    /**
     * Get the earliest and latest delivery dates for an order placed on the given day.
     *
     * @param  list<int>  $daysOff  ISO weekdays with no delivery (1 = Monday … 7 = Sunday)
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    public static function between(CarbonImmutable $orderedOn, int $minDays, int $maxDays, array $daysOff): array
    {
        return [
            self::addWorkingDays($orderedOn, $minDays, $daysOff),
            self::addWorkingDays($orderedOn, max($minDays, $maxDays), $daysOff),
        ];
    }

    /**
     * Get the delivery days a shopper may pick: the earliest possible day and the delivery days after it.
     *
     * @param  list<int>  $daysOff  ISO weekdays with no delivery
     * @return list<CarbonImmutable>
     */
    public static function choosableDays(CarbonImmutable $orderedOn, int $minDays, int $count, array $daysOff): array
    {
        $days = [];
        $day = self::addWorkingDays($orderedOn, $minDays, $daysOff);

        // A same-day area ordered on a day off can first deliver on the next working day.
        if ($minDays === 0 && count(array_unique($daysOff)) < 7 && in_array($day->dayOfWeekIso, $daysOff, true)) {
            $day = self::addWorkingDays($day, 1, $daysOff);
        }

        while (count($days) < $count) {
            $days[] = $day;
            $day = self::addWorkingDays($day, 1, $daysOff);
        }

        return $days;
    }

    /**
     * @param  list<int>  $daysOff
     */
    private static function addWorkingDays(CarbonImmutable $date, int $days, array $daysOff): CarbonImmutable
    {
        // Seven days off would never end; treat that as no days off.
        $daysOff = count(array_unique($daysOff)) >= 7 ? [] : $daysOff;
        $date = $date->startOfDay();

        while ($days > 0) {
            $date = $date->addDay();

            if (! in_array($date->dayOfWeekIso, $daysOff, true)) {
                $days--;
            }
        }

        return $date;
    }
}
