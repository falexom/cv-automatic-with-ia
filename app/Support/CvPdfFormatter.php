<?php

namespace App\Support;

final class CvPdfFormatter
{
    private const MONTHS = [
        1 => 'ene',
        2 => 'feb',
        3 => 'mar',
        4 => 'abr',
        5 => 'may',
        6 => 'jun',
        7 => 'jul',
        8 => 'ago',
        9 => 'sep',
        10 => 'oct',
        11 => 'nov',
        12 => 'dic',
    ];

    private const SKILL_CATEGORY_LABELS = [
        'tecnica' => 'Técnicas',
        'funcional' => 'Funcionales',
        'metodologica' => 'Metodológicas',
        'herramienta' => 'Herramientas',
        'tecnologia' => 'Tecnologías',
    ];

    public static function formatYearMonth(?int $year, ?int $month): ?string
    {
        if ($year === null) {
            return null;
        }

        if ($month === null || ! isset(self::MONTHS[$month])) {
            return (string) $year;
        }

        return self::MONTHS[$month].' '.$year;
    }

    public static function formatRange(
        ?int $startYear,
        ?int $startMonth,
        ?int $endYear,
        ?int $endMonth,
        bool $isCurrent = false,
        string $currentLabel = 'actual',
    ): string {
        $start = self::formatYearMonth($startYear, $startMonth);

        if ($isCurrent) {
            return $start === null ? $currentLabel : "{$start} – {$currentLabel}";
        }

        $end = self::formatYearMonth($endYear, $endMonth);

        if ($start !== null && $end !== null) {
            return "{$start} – {$end}";
        }

        return $start ?? $end ?? '';
    }

    public static function skillCategoryLabel(string $category): string
    {
        return self::SKILL_CATEGORY_LABELS[$category] ?? $category;
    }
}
