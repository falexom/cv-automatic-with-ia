import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import DuplicateWarningDialog, {
    type DuplicateWarning,
} from '@/components/DuplicateWarningDialog';
import SkillMultiSelect, {
    type SkillOption,
} from '@/components/SkillMultiSelect';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import YearMonthInput from '@/components/YearMonthInput';

export type CertificationFormRecord = {
    id: number;
    name: string;
    institution: string | null;
    date_year: number | null;
    date_month: number | null;
    duration: string | null;
    code: string | null;
    validation_url: string | null;
    category: string | null;
    skill_ids: number[];
};

export default function CertificationForm({
    record,
    skills,
}: {
    record?: CertificationFormRecord;
    skills: SkillOption[];
}) {
    const isEdit = record !== undefined;
    const maxYear = new Date().getFullYear() + 10;
    const { flash } = usePage().props;
    const warning = (flash?.duplicate_warning ??
        null) as DuplicateWarning | null;

    const [warningOpen, setWarningOpen] = useState(false);
    const [retrying, setRetrying] = useState(false);

    const { data, setData, post, put, processing, errors } = useForm({
        name: record?.name ?? '',
        institution: record?.institution ?? '',
        date_year: record?.date_year ?? '',
        date_month: record?.date_month ?? '',
        duration: record?.duration ?? '',
        code: record?.code ?? '',
        validation_url: record?.validation_url ?? '',
        category: record?.category ?? '',
        skill_ids: record?.skill_ids ?? ([] as number[]),
    });

    useEffect(() => {
        if (warning) {
            setWarningOpen(true);
        }
    }, [warning]);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (isEdit) {
            put(`/certifications/${record.id}`);
        } else {
            post('/certifications');
        }
    };

    const confirmAnyway = () => {
        setRetrying(true);

        const payload = { ...data, confirm_duplicate: true };
        const options = {
            onFinish: () => {
                setRetrying(false);
                setWarningOpen(false);
            },
        };

        if (isEdit) {
            router.put(`/certifications/${record.id}`, payload, options);
        } else {
            router.post('/certifications', payload, options);
        }
    };

    return (
        <>
            <form onSubmit={submit} className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Datos del curso o certificación</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="name">Nombre *</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                aria-invalid={Boolean(errors.name)}
                            />
                            {errors.name ? (
                                <p className="text-sm text-destructive">
                                    {errors.name}
                                </p>
                            ) : null}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="institution">Institución</Label>
                            <Input
                                id="institution"
                                value={data.institution}
                                onChange={(event) =>
                                    setData('institution', event.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="category">Categoría</Label>
                            <Input
                                id="category"
                                value={data.category}
                                onChange={(event) =>
                                    setData('category', event.target.value)
                                }
                                placeholder="Programación, Análisis, Gestión..."
                            />
                        </div>
                        <YearMonthInput
                            label="Fecha"
                            idPrefix="date"
                            year={data.date_year}
                            month={data.date_month}
                            onChange={(year, month) =>
                                setData((current) => ({
                                    ...current,
                                    date_year: year,
                                    date_month: year === '' ? '' : month,
                                }))
                            }
                            yearError={errors.date_year}
                            monthError={errors.date_month}
                            maxYear={maxYear}
                        />
                        <div className="space-y-1.5">
                            <Label htmlFor="duration">Duración</Label>
                            <Input
                                id="duration"
                                value={data.duration}
                                onChange={(event) =>
                                    setData('duration', event.target.value)
                                }
                                placeholder="40 horas, 6 semanas..."
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="code">Código o identificador</Label>
                            <Input
                                id="code"
                                value={data.code}
                                onChange={(event) =>
                                    setData('code', event.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="validation_url">
                                URL de validación
                            </Label>
                            <Input
                                id="validation_url"
                                value={data.validation_url}
                                onChange={(event) =>
                                    setData(
                                        'validation_url',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={Boolean(errors.validation_url)}
                            />
                            {errors.validation_url ? (
                                <p className="text-sm text-destructive">
                                    {errors.validation_url}
                                </p>
                            ) : null}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Habilidades asociadas</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <SkillMultiSelect
                            skills={skills}
                            selected={data.skill_ids.map(Number)}
                            onChange={(ids) => setData('skill_ids', ids)}
                            error={
                                (errors as Record<string, string>)['skill_ids']
                            }
                        />
                    </CardContent>
                </Card>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" asChild>
                        <Link href="/certifications">Cancelar</Link>
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {isEdit ? 'Guardar cambios' : 'Crear certificación'}
                    </Button>
                </div>
            </form>

            {warning ? (
                <DuplicateWarningDialog
                    open={warningOpen}
                    warning={warning}
                    onOpenChange={setWarningOpen}
                    processing={retrying}
                    onConfirm={confirmAnyway}
                />
            ) : null}
        </>
    );
}
