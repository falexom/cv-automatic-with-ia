import { Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import YearMonthInput from '@/components/YearMonthInput';

export type EducationFormRecord = {
    id: number;
    institution: string;
    program: string;
    degree_level: string | null;
    start_year: number | null;
    start_month: number | null;
    end_year: number | null;
    end_month: number | null;
    is_in_progress: boolean;
    description: string | null;
};

const textareaClassName =
    'min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

export default function EducationForm({
    record,
}: {
    record?: EducationFormRecord;
}) {
    const isEdit = record !== undefined;
    const maxYear = new Date().getFullYear() + 10;

    const { data, setData, post, put, processing, errors } = useForm({
        institution: record?.institution ?? '',
        program: record?.program ?? '',
        degree_level: record?.degree_level ?? '',
        start_year: record?.start_year ?? '',
        start_month: record?.start_month ?? '',
        end_year: record?.end_year ?? '',
        end_month: record?.end_month ?? '',
        is_in_progress: record?.is_in_progress ?? false,
        description: record?.description ?? '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (isEdit) {
            put(`/education/${record.id}`);
        } else {
            post('/education');
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Formación académica</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-1.5">
                        <Label htmlFor="institution">Institución *</Label>
                        <Input
                            id="institution"
                            value={data.institution}
                            onChange={(event) =>
                                setData('institution', event.target.value)
                            }
                            aria-invalid={Boolean(errors.institution)}
                        />
                        {errors.institution ? (
                            <p className="text-sm text-destructive">
                                {errors.institution}
                            </p>
                        ) : null}
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="program">Programa o carrera *</Label>
                        <Input
                            id="program"
                            value={data.program}
                            onChange={(event) =>
                                setData('program', event.target.value)
                            }
                            aria-invalid={Boolean(errors.program)}
                        />
                        {errors.program ? (
                            <p className="text-sm text-destructive">
                                {errors.program}
                            </p>
                        ) : null}
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="degree_level">Grado o nivel</Label>
                        <Input
                            id="degree_level"
                            value={data.degree_level}
                            onChange={(event) =>
                                setData('degree_level', event.target.value)
                            }
                            placeholder="Licenciatura, Tecnicatura, Posgrado..."
                        />
                    </div>
                    <YearMonthInput
                        label="Fecha de inicio"
                        idPrefix="start"
                        year={data.start_year}
                        month={data.start_month}
                        onChange={(year, month) =>
                            setData((current) => ({
                                ...current,
                                start_year: year,
                                start_month: year === '' ? '' : month,
                            }))
                        }
                        yearError={errors.start_year}
                        monthError={errors.start_month}
                        maxYear={maxYear}
                    />
                    <div className="space-y-4">
                        <YearMonthInput
                            label="Fecha de fin"
                            idPrefix="end"
                            year={data.end_year}
                            month={data.end_month}
                            onChange={(year, month) =>
                                setData((current) => ({
                                    ...current,
                                    end_year: year,
                                    end_month: year === '' ? '' : month,
                                    is_in_progress:
                                        year === ''
                                            ? current.is_in_progress
                                            : false,
                                }))
                            }
                            yearError={errors.end_year}
                            monthError={errors.end_month}
                            maxYear={maxYear}
                            disabled={data.is_in_progress}
                        />
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={data.is_in_progress}
                                onCheckedChange={(checked) =>
                                    setData((current) => ({
                                        ...current,
                                        is_in_progress: checked === true,
                                        end_year:
                                            checked === true
                                                ? ''
                                                : current.end_year,
                                        end_month:
                                            checked === true
                                                ? ''
                                                : current.end_month,
                                    }))
                                }
                            />
                            En curso (sin fecha de fin)
                        </label>
                    </div>
                    <div className="space-y-1.5 sm:col-span-2">
                        <Label htmlFor="description">Descripción</Label>
                        <textarea
                            id="description"
                            className={textareaClassName}
                            value={data.description}
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                        />
                    </div>
                </CardContent>
            </Card>

            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" asChild>
                    <Link href="/education">Cancelar</Link>
                </Button>
                <Button type="submit" disabled={processing}>
                    {isEdit ? 'Guardar cambios' : 'Crear formación'}
                </Button>
            </div>
        </form>
    );
}
