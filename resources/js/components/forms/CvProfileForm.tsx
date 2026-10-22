import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    RELEVANCE_OPTIONS,
    type CvProfileFormData,
    type CvProfileOptions,
    type Relevance,
} from '@/lib/cv-profiles';
import { formatRange, formatYearMonth } from '@/lib/dates';
import { SKILL_CATEGORY_LABELS } from '@/lib/skill-categories';

type ContentField =
    | 'work_experiences'
    | 'education_entries'
    | 'certifications'
    | 'skills'
    | 'projects';

type FormErrors = Record<string, string>;

type SectionOption = { id: number };

const textareaClassName =
    'min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

function SelectionSection<T extends SectionOption>({
    title,
    field,
    options,
    data,
    errors,
    emptyDescription,
    masterHref,
    masterLabel,
    renderLabel,
    renderPeriod,
    onChange,
}: {
    title: string;
    field: ContentField;
    options: T[];
    data: CvProfileFormData;
    errors: FormErrors;
    emptyDescription: string;
    masterHref: string;
    masterLabel: string;
    renderLabel: (option: T) => string;
    renderPeriod?: (option: T) => string;
    onChange: (data: CvProfileFormData) => void;
}) {
    const selected = data[field];

    const toggle = (option: T, checked: boolean) => {
        const next = checked
            ? [...selected, { id: option.id, relevance: 'media' as Relevance }]
            : selected.filter((item) => item.id !== option.id);

        onChange({ ...data, [field]: next });
    };

    const changeRelevance = (id: number, relevance: Relevance) => {
        const next = selected.map((item) =>
            item.id === id ? { ...item, relevance } : item,
        );

        onChange({ ...data, [field]: next });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                {options.length === 0 ? (
                    <div className="rounded-md border border-dashed p-4 text-sm text-muted-foreground">
                        <p>{emptyDescription}</p>
                        <Link
                            href={masterHref}
                            className="mt-1 inline-block text-primary underline-offset-4 hover:underline"
                        >
                            {masterLabel}
                        </Link>
                    </div>
                ) : null}

                {options.map((option) => {
                    const current = selected.find(
                        (item) => item.id === option.id,
                    );
                    const index = selected.findIndex(
                        (item) => item.id === option.id,
                    );
                    const label = renderLabel(option);
                    const period = renderPeriod?.(option);

                    return (
                        <div key={option.id} className="rounded-md border p-3">
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <label className="flex items-start gap-2 text-sm">
                                    <Checkbox
                                        checked={current !== undefined}
                                        onCheckedChange={(checked) =>
                                            toggle(option, checked === true)
                                        }
                                        aria-label={label}
                                    />
                                    <span>
                                        <span className="font-medium">
                                            {label}
                                        </span>
                                        {period ? (
                                            <span className="block text-xs text-muted-foreground">
                                                {period}
                                            </span>
                                        ) : null}
                                    </span>
                                </label>

                                {current ? (
                                    <label className="flex items-center gap-2 text-xs text-muted-foreground">
                                        Relevancia
                                        <select
                                            aria-label={`Relevancia de ${label}`}
                                            className="h-8 rounded-md border border-input bg-transparent px-2 text-sm"
                                            value={current.relevance}
                                            onChange={(event) =>
                                                changeRelevance(
                                                    option.id,
                                                    event.target
                                                        .value as Relevance,
                                                )
                                            }
                                        >
                                            {RELEVANCE_OPTIONS.map((item) => (
                                                <option
                                                    key={item.value}
                                                    value={item.value}
                                                >
                                                    {item.label}
                                                </option>
                                            ))}
                                        </select>
                                    </label>
                                ) : null}
                            </div>

                            {current ? (
                                <>
                                    {errors[`${field}.${index}.id`] ? (
                                        <p className="mt-2 text-sm text-destructive">
                                            {errors[`${field}.${index}.id`]}
                                        </p>
                                    ) : null}
                                    {errors[`${field}.${index}.relevance`] ? (
                                        <p className="mt-2 text-sm text-destructive">
                                            {
                                                errors[
                                                    `${field}.${index}.relevance`
                                                ]
                                            }
                                        </p>
                                    ) : null}
                                </>
                            ) : null}
                        </div>
                    );
                })}

                {errors[field] ? (
                    <p className="text-sm text-destructive">{errors[field]}</p>
                ) : null}
            </CardContent>
        </Card>
    );
}

export default function CvProfileForm({
    data,
    options,
    errors,
    processing,
    submitLabel,
    cancelHref,
    onChange,
    onSubmit,
}: {
    data: CvProfileFormData;
    options: CvProfileOptions;
    errors: FormErrors;
    processing: boolean;
    submitLabel: string;
    cancelHref: string;
    onChange: (data: CvProfileFormData) => void;
    onSubmit: (event: React.FormEvent) => void;
}) {
    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Datos propios del perfil</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-1.5 sm:col-span-2">
                        <Label htmlFor="name">Nombre del perfil *</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(event) =>
                                onChange({
                                    ...data,
                                    name: event.target.value,
                                })
                            }
                            aria-invalid={Boolean(errors.name)}
                        />
                        {errors.name ? (
                            <p className="text-sm text-destructive">
                                {errors.name}
                            </p>
                        ) : null}
                    </div>
                    <div className="space-y-1.5 sm:col-span-2">
                        <Label htmlFor="professional_title">
                            Título profesional
                        </Label>
                        <Input
                            id="professional_title"
                            value={data.professional_title}
                            onChange={(event) =>
                                onChange({
                                    ...data,
                                    professional_title: event.target.value,
                                })
                            }
                            aria-invalid={Boolean(errors.professional_title)}
                        />
                        {errors.professional_title ? (
                            <p className="text-sm text-destructive">
                                {errors.professional_title}
                            </p>
                        ) : null}
                    </div>
                    <div className="space-y-1.5 sm:col-span-2">
                        <Label htmlFor="summary">Resumen</Label>
                        <textarea
                            id="summary"
                            className={textareaClassName}
                            value={data.summary}
                            onChange={(event) =>
                                onChange({
                                    ...data,
                                    summary: event.target.value,
                                })
                            }
                            aria-invalid={Boolean(errors.summary)}
                        />
                        {errors.summary ? (
                            <p className="text-sm text-destructive">
                                {errors.summary}
                            </p>
                        ) : null}
                    </div>
                </CardContent>
            </Card>

            <SelectionSection
                title="Experiencia laboral"
                field="work_experiences"
                options={options.work_experiences}
                data={data}
                errors={errors}
                emptyDescription="No hay experiencias en el Perfil Maestro."
                masterHref="/experiences"
                masterLabel="Ir a Experiencia"
                renderLabel={(option) =>
                    `${option.position} — ${option.company}`
                }
                renderPeriod={(option) =>
                    formatRange(
                        option.start_year,
                        option.start_month,
                        option.end_year,
                        option.end_month,
                        option.is_current,
                    )
                }
                onChange={onChange}
            />

            <SelectionSection
                title="Educación"
                field="education_entries"
                options={options.education_entries}
                data={data}
                errors={errors}
                emptyDescription="No hay formación en el Perfil Maestro."
                masterHref="/education"
                masterLabel="Ir a Educación"
                renderLabel={(option) =>
                    `${option.program} — ${option.institution}`
                }
                renderPeriod={(option) =>
                    formatRange(
                        option.start_year,
                        option.start_month,
                        option.end_year,
                        option.end_month,
                        option.is_in_progress,
                        'en curso',
                    )
                }
                onChange={onChange}
            />

            <SelectionSection
                title="Cursos y certificaciones"
                field="certifications"
                options={options.certifications}
                data={data}
                errors={errors}
                emptyDescription="No hay cursos ni certificaciones en el Perfil Maestro."
                masterHref="/certifications"
                masterLabel="Ir a Cursos y certificaciones"
                renderLabel={(option) =>
                    option.institution
                        ? `${option.name} — ${option.institution}`
                        : option.name
                }
                renderPeriod={(option) =>
                    formatYearMonth(option.date_year, option.date_month) ?? ''
                }
                onChange={onChange}
            />

            <SelectionSection
                title="Habilidades"
                field="skills"
                options={options.skills}
                data={data}
                errors={errors}
                emptyDescription="No hay habilidades en el Perfil Maestro."
                masterHref="/skills"
                masterLabel="Ir a Habilidades"
                renderLabel={(option) =>
                    `${option.name} — ${SKILL_CATEGORY_LABELS[option.category] ?? option.category}`
                }
                onChange={onChange}
            />

            <SelectionSection
                title="Proyectos"
                field="projects"
                options={options.projects}
                data={data}
                errors={errors}
                emptyDescription="No hay proyectos en el Perfil Maestro."
                masterHref="/projects"
                masterLabel="Ir a Proyectos"
                renderLabel={(option) =>
                    option.role
                        ? `${option.name} — ${option.role}`
                        : option.name
                }
                renderPeriod={(option) =>
                    formatRange(
                        option.start_year,
                        option.start_month,
                        option.end_year,
                        option.end_month,
                    )
                }
                onChange={onChange}
            />

            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" asChild>
                    <Link href={cancelHref}>Cancelar</Link>
                </Button>
                <Button type="submit" disabled={processing}>
                    {submitLabel}
                </Button>
            </div>
        </form>
    );
}
