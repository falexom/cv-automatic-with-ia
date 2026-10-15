import { Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SkillMultiSelect, {
    type SkillOption,
} from '@/components/SkillMultiSelect';
import YearMonthInput from '@/components/YearMonthInput';

export type WorkExperienceFormRecord = {
    id: number;
    company: string;
    position: string;
    start_year: number | null;
    start_month: number | null;
    end_year: number | null;
    end_month: number | null;
    is_current: boolean;
    description: string | null;
    responsibilities: string | null;
    achievements: string | null;
    skill_ids: number[];
};

const textareaClassName =
    'min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

export default function WorkExperienceForm({
    record,
    skills,
}: {
    record?: WorkExperienceFormRecord;
    skills: SkillOption[];
}) {
    const isEdit = record !== undefined;
    const maxYear = new Date().getFullYear() + 10;

    const { data, setData, post, put, processing, errors } = useForm({
        company: record?.company ?? '',
        position: record?.position ?? '',
        start_year: record?.start_year ?? '',
        start_month: record?.start_month ?? '',
        end_year: record?.end_year ?? '',
        end_month: record?.end_month ?? '',
        is_current: record?.is_current ?? false,
        description: record?.description ?? '',
        responsibilities: record?.responsibilities ?? '',
        achievements: record?.achievements ?? '',
        skill_ids: record?.skill_ids ?? ([] as number[]),
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (isEdit) {
            put(`/experiences/${record.id}`);
        } else {
            post('/experiences');
        }
    };

    const toggleCurrent = (checked: boolean) => {
        setData({
            is_current: checked,
            end_year: checked ? '' : data.end_year,
            end_month: checked ? '' : data.end_month,
        });
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Datos del puesto</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-1.5">
                        <Label htmlFor="company">Empresa *</Label>
                        <Input
                            id="company"
                            value={data.company}
                            onChange={(event) =>
                                setData('company', event.target.value)
                            }
                            aria-invalid={Boolean(errors.company)}
                        />
                        {errors.company ? (
                            <p className="text-sm text-destructive">
                                {errors.company}
                            </p>
                        ) : null}
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="position">Cargo *</Label>
                        <Input
                            id="position"
                            value={data.position}
                            onChange={(event) =>
                                setData('position', event.target.value)
                            }
                            aria-invalid={Boolean(errors.position)}
                        />
                        {errors.position ? (
                            <p className="text-sm text-destructive">
                                {errors.position}
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
                    <div className="space-y-4">
                        <YearMonthInput
                            label="Fecha de fin"
                            idPrefix="end"
                            year={data.end_year}
                            month={data.end_month}
                            onChange={(year, month) =>
                                setData({
                                    end_year: year,
                                    end_month: year === '' ? '' : month,
                                    is_current:
                                        year === '' ? data.is_current : false,
                                })
                            }
                            yearError={errors.end_year}
                            monthError={errors.end_month}
                            maxYear={maxYear}
                            disabled={data.is_current}
                        />
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={data.is_current}
                                onCheckedChange={(checked) =>
                                    toggleCurrent(checked === true)
                                }
                            />
                            Trabajo actual (sin fecha de fin)
                        </label>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Descripción del rol</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="space-y-1.5">
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
                    <div className="space-y-1.5">
                        <Label htmlFor="responsibilities">
                            Responsabilidades
                        </Label>
                        <textarea
                            id="responsibilities"
                            className={textareaClassName}
                            value={data.responsibilities}
                            onChange={(event) =>
                                setData('responsibilities', event.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="achievements">Logros</Label>
                        <textarea
                            id="achievements"
                            className={textareaClassName}
                            value={data.achievements}
                            onChange={(event) =>
                                setData('achievements', event.target.value)
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
                    <Link href="/experiences">Cancelar</Link>
                </Button>
                <Button type="submit" disabled={processing}>
                    {isEdit ? 'Guardar cambios' : 'Crear experiencia'}
                </Button>
            </div>
        </form>
    );
}
