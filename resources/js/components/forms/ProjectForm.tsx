import { Link, useForm } from '@inertiajs/react';
import SkillMultiSelect, {
    type SkillOption,
} from '@/components/SkillMultiSelect';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import YearMonthInput from '@/components/YearMonthInput';

export type ProjectFormRecord = {
    id: number;
    name: string;
    description: string | null;
    role: string | null;
    results: string | null;
    url: string | null;
    start_year: number | null;
    start_month: number | null;
    end_year: number | null;
    end_month: number | null;
    skill_ids: number[];
};

const textareaClassName =
    'min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

export default function ProjectForm({
    record,
    skills,
}: {
    record?: ProjectFormRecord;
    skills: SkillOption[];
}) {
    const isEdit = record !== undefined;
    const maxYear = new Date().getFullYear() + 10;

    const { data, setData, post, put, processing, errors } = useForm({
        name: record?.name ?? '',
        description: record?.description ?? '',
        role: record?.role ?? '',
        results: record?.results ?? '',
        url: record?.url ?? '',
        start_year: record?.start_year ?? '',
        start_month: record?.start_month ?? '',
        end_year: record?.end_year ?? '',
        end_month: record?.end_month ?? '',
        skill_ids: record?.skill_ids ?? ([] as number[]),
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (isEdit) {
            put(`/projects/${record.id}`);
        } else {
            post('/projects');
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Datos del proyecto</CardTitle>
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
                        <Label htmlFor="role">Rol</Label>
                        <Input
                            id="role"
                            value={data.role}
                            onChange={(event) =>
                                setData('role', event.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="url">URL</Label>
                        <Input
                            id="url"
                            value={data.url}
                            onChange={(event) =>
                                setData('url', event.target.value)
                            }
                            aria-invalid={Boolean(errors.url)}
                        />
                        {errors.url ? (
                            <p className="text-sm text-destructive">
                                {errors.url}
                            </p>
                        ) : null}
                    </div>
                    <YearMonthInput
                        label="Fecha de inicio"
                        idPrefix="start"
                        year={data.start_year}
                        month={data.start_month}
                        onChange={(year, month) =>
                            setData({
                                start_year: year,
                                start_month: year === '' ? '' : month,
                            })
                        }
                        yearError={errors.start_year}
                        monthError={errors.start_month}
                        maxYear={maxYear}
                    />
                    <YearMonthInput
                        label="Fecha de fin"
                        idPrefix="end"
                        year={data.end_year}
                        month={data.end_month}
                        onChange={(year, month) =>
                            setData({
                                end_year: year,
                                end_month: year === '' ? '' : month,
                            })
                        }
                        yearError={errors.end_year}
                        monthError={errors.end_month}
                        maxYear={maxYear}
                    />
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
                    <div className="space-y-1.5 sm:col-span-2">
                        <Label htmlFor="results">Resultados</Label>
                        <textarea
                            id="results"
                            className={textareaClassName}
                            value={data.results}
                            onChange={(event) =>
                                setData('results', event.target.value)
                            }
                        />
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
                        error={(errors as Record<string, string>)['skill_ids']}
                    />
                </CardContent>
            </Card>

            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" asChild>
                    <Link href="/projects">Cancelar</Link>
                </Button>
                <Button type="submit" disabled={processing}>
                    {isEdit ? 'Guardar cambios' : 'Crear proyecto'}
                </Button>
            </div>
        </form>
    );
}
