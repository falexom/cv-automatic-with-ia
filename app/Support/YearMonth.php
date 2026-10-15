<?php

namespace App\Support;

final class YearMonth
{
    /**
     * Determine whether an end date is strictly before a start date.
     *
     * A missing month is treated as January for start dates and December
     * for end dates (FR-034). When either year is missing, the dates
     * cannot be compared and the check passes.
     */
    public static function endIsBeforeStart(?int $startYear, ?int $startMonth, ?int $endYear, ?int $endMonth): bool
    {
        if ($startYear === null || $endYear === null) {
            return false;
        }

        return [$endYear, $endMonth ?? 12] < [$startYear, $startMonth ?? 1];
    }

    /**
     * Chronological sort key; records without a year sort last.
     *
     * @return array{year: int, month: int}
     */
    public static function sortKey(?int $year, ?int $month): array
    {
        return [
            'year' => $year ?? PHP_INT_MIN,
            'month' => $month ?? 1,
        ];
    }

    /**
     * The latest selectable year for date inputs and validation.
     */
    public static function maxYear(): int
    {
        return (int) date('Y') + 10;
    }
}
