const MONTHS_SHORT = [
    'ene',
    'feb',
    'mar',
    'abr',
    'may',
    'jun',
    'jul',
    'ago',
    'sep',
    'oct',
    'nov',
    'dic',
];

export function formatYearMonth(
    year: number | null | undefined,
    month: number | null | undefined,
): string | null {
    if (year === null || year === undefined) {
        return null;
    }

    if (month === null || month === undefined) {
        return String(year);
    }

    return `${MONTHS_SHORT[month - 1]} ${year}`;
}

export function formatRange(
    startYear: number | null | undefined,
    startMonth: number | null | undefined,
    endYear: number | null | undefined,
    endMonth: number | null | undefined,
    isCurrent = false,
    currentLabel = 'actual',
): string {
    const start = formatYearMonth(startYear, startMonth);

    if (isCurrent) {
        return start ? `${start} – ${currentLabel}` : currentLabel;
    }

    const end = formatYearMonth(endYear, endMonth);

    if (start && end) {
        return `${start} – ${end}`;
    }

    return start ?? end ?? '';
}
