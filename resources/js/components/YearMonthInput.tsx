import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

const selectClassName =
    'h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50';

const MONTHS = [
    'enero',
    'febrero',
    'marzo',
    'abril',
    'mayo',
    'junio',
    'julio',
    'agosto',
    'septiembre',
    'octubre',
    'noviembre',
    'diciembre',
];

type YearMonthInputProps = {
    label: string;
    idPrefix: string;
    year: number | string | null | undefined;
    month: number | string | null | undefined;
    onChange: (year: string, month: string) => void;
    yearError?: string;
    monthError?: string;
    minYear?: number;
    maxYear: number;
    disabled?: boolean;
};

export default function YearMonthInput({
    label,
    idPrefix,
    year,
    month,
    onChange,
    yearError,
    monthError,
    minYear = 1900,
    maxYear,
    disabled = false,
}: YearMonthInputProps) {
    const yearValue =
        year === null || year === undefined || year === '' ? '' : String(year);
    const monthValue =
        month === null || month === undefined || month === ''
            ? ''
            : String(month);

    const years: number[] = [];
    for (let y = maxYear; y >= minYear; y -= 1) {
        years.push(y);
    }

    return (
        <div className="space-y-1.5">
            <Label htmlFor={`${idPrefix}-year`}>{label}</Label>
            <div className="flex gap-2">
                <select
                    id={`${idPrefix}-year`}
                    value={yearValue}
                    disabled={disabled}
                    className={cn(selectClassName, 'w-28')}
                    onChange={(event) => {
                        const nextYear = event.target.value;

                        onChange(nextYear, nextYear === '' ? '' : monthValue);
                    }}
                >
                    <option value="">Año</option>
                    {years.map((y) => (
                        <option key={y} value={y}>
                            {y}
                        </option>
                    ))}
                </select>
                <select
                    id={`${idPrefix}-month`}
                    value={monthValue}
                    disabled={disabled || yearValue === ''}
                    className={cn(selectClassName, 'w-36')}
                    onChange={(event) => {
                        onChange(yearValue, event.target.value);
                    }}
                >
                    <option value="">Mes (opcional)</option>
                    {MONTHS.map((name, index) => (
                        <option key={name} value={index + 1}>
                            {name}
                        </option>
                    ))}
                </select>
            </div>
            {yearError ? (
                <p className="text-sm text-destructive">{yearError}</p>
            ) : null}
            {monthError ? (
                <p className="text-sm text-destructive">{monthError}</p>
            ) : null}
        </div>
    );
}
