import { Link, router, useForm, usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import CertificationImportPanel from '@/components/certifications/CertificationImportPanel';
import CertificationImportReview from '@/components/certifications/CertificationImportReview';
import DuplicateWarningDialog, {
    type DuplicateWarning,
} from '@/components/DuplicateWarningDialog';
import SkillMultiSelect, {
    type SkillOption,
} from '@/components/SkillMultiSelect';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import YearMonthInput from '@/components/YearMonthInput';
import {
    DEFAULT_IMPORT_LIMITS,
    useCertificationImport,
} from '@/hooks/use-certification-import';
import type {
    CertificationImportLimits,
    CertificationImportNotice,
    ImportSkill,
} from '@/lib/certification-import';
import { postForm, RequestError } from '@/lib/http';

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

type ProposalField =
    | 'name'
    | 'institution'
    | 'duration'
    | 'code'
    | 'validation_url';

type ImportConfirmResponse = {
    redirect?: string;
    duplicate_warning?: DuplicateWarning;
    requires_confirmation?: boolean;
};

export default function CertificationForm({
    record,
    skills,
    importNotice,
    importLimits,
}: {
    record?: CertificationFormRecord;
    skills: SkillOption[];
    importNotice?: CertificationImportNotice;
    importLimits?: CertificationImportLimits;
}) {
    const isEdit = record !== undefined;
    const maxYear = new Date().getFullYear() + 10;
    const { flash } = usePage().props;
    const flashWarning = (flash?.duplicate_warning ??
        null) as DuplicateWarning | null;
    const limits = importLimits ?? DEFAULT_IMPORT_LIMITS;
    const importFlow = useCertificationImport(limits);

    const [warningOpen, setWarningOpen] = useState(false);
    const [retrying, setRetrying] = useState(false);
    const [localWarning, setLocalWarning] = useState<DuplicateWarning | null>(
        null,
    );
    const [saving, setSaving] = useState(false);
    const [importFailure, setImportFailure] = useState<string | null>(null);
    const [importErrors, setImportErrors] = useState<Record<string, string>>(
        {},
    );
    const savingRef = useRef(false);
    const importFailureRef = useRef<HTMLDivElement | null>(null);

    const warning = localWarning ?? flashWarning;

    const { data, setData, post, put, processing, errors, clearErrors } =
        useForm({
            name: record?.name ?? '',
            institution: record?.institution ?? '',
            date_year: record?.date_year ?? ('' as number | ''),
            date_month: record?.date_month ?? ('' as number | ''),
            duration: record?.duration ?? '',
            code: record?.code ?? '',
            validation_url: record?.validation_url ?? '',
            category: record?.category ?? '',
            skill_ids: record?.skill_ids ?? ([] as number[]),
        });
    const fieldErrors = { ...errors, ...importErrors } as Record<
        string,
        string | undefined
    >;
    const skillError =
        fieldErrors['skill_ids'] ??
        Object.entries(fieldErrors).find(([field]) =>
            field.startsWith('skill_ids.'),
        )?.[1];

    const importEnabled = !isEdit && importNotice !== undefined;

    useEffect(() => {
        if (warning) {
            setWarningOpen(true);
        }
    }, [warning]);

    useEffect(() => {
        setImportFailure(null);
        setImportErrors({});
    }, [importFlow.analysis, importFlow.file]);

    useEffect(() => {
        if (importFailure && importFailureRef.current?.scrollIntoView) {
            importFailureRef.current.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
        }
    }, [importFailure]);

    const clearImportError = (field: string) => {
        setImportErrors((current) => {
            if (!(field in current)) {
                return current;
            }

            const next = { ...current };
            delete next[field];

            return next;
        });
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (importEnabled && importFlow.file !== null) {
            if (!importFlow.fileInvalid) {
                void submitImport(false);
            }

            return;
        }

        setLocalWarning(null);
        setImportFailure(null);
        setImportErrors({});
        clearErrors();

        if (isEdit) {
            put(`/certifications/${record.id}`);
        } else {
            post('/certifications');
        }
    };

    const submitImport = async (confirmDuplicate: boolean) => {
        if (
            savingRef.current ||
            importFlow.file === null ||
            importFlow.fileInvalid
        ) {
            return;
        }

        savingRef.current = true;
        setSaving(true);
        setImportFailure(null);
        setImportErrors({});
        clearErrors();

        try {
            const formData = new FormData();

            if (data.name !== '') {
                formData.append('name', data.name);
            }

            if (data.institution !== '') {
                formData.append('institution', data.institution);
            }

            if (data.date_year !== '') {
                formData.append('date_year', String(data.date_year));
            }

            if (data.date_year !== '' && data.date_month !== '') {
                formData.append('date_month', String(data.date_month));
            }

            if (data.duration !== '') {
                formData.append('duration', data.duration);
            }

            if (data.code !== '') {
                formData.append('code', data.code);
            }

            if (data.validation_url !== '') {
                formData.append('validation_url', data.validation_url);
            }

            if (data.category !== '') {
                formData.append('category', data.category);
            }

            data.skill_ids
                .map(Number)
                .forEach((id) => formData.append('skill_ids[]', String(id)));

            formData.append('file', importFlow.file);
            formData.append('confirmation_id', importFlow.confirmationId);

            if (importFlow.analysis) {
                formData.append(
                    'proposal_token',
                    importFlow.analysis.confirmation_token,
                );
            }

            if (importFlow.acceptedCategory) {
                formData.append('accepted_category', '1');
            }

            importFlow.acceptedSkillIds.forEach((id) =>
                formData.append('accepted_skill_ids[]', String(id)),
            );

            if (confirmDuplicate) {
                formData.append('confirm_duplicate', '1');
            }

            const response = await postForm<ImportConfirmResponse>(
                '/certifications/import/confirm',
                formData,
            );

            if (response.duplicate_warning) {
                setLocalWarning(response.duplicate_warning);
                setWarningOpen(true);

                return;
            }

            if (response.redirect) {
                router.visit(response.redirect);

                return;
            }

            setImportFailure(
                'No se recibió una confirmación válida del servidor. No se guardó nada.',
            );
        } catch (caught) {
            if (caught instanceof RequestError) {
                setImportFailure(caught.message);

                if (caught.errors?.file?.[0]) {
                    importFlow.markFileInvalid(caught.errors.file[0]);
                }

                if (caught.errors) {
                    setImportErrors(
                        Object.fromEntries(
                            Object.entries(caught.errors).map(
                                ([field, messages]) => [
                                    field,
                                    messages[0] ?? '',
                                ],
                            ),
                        ),
                    );
                }
            } else {
                setImportFailure(
                    'No se pudo guardar la certificación. No se creó ningún registro parcial.',
                );
            }
        } finally {
            savingRef.current = false;
            setSaving(false);
        }
    };

    const confirmAnyway = () => {
        setWarningOpen(false);

        if (importEnabled && importFlow.file !== null) {
            void submitImport(true);

            return;
        }

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

    const applyField = (field: ProposalField, value: string) => {
        setData(field, value);
        clearErrors(field);
        clearImportError(field);
    };

    const applyDate = (year: number | null, month: number | null) => {
        setData('date_year', year ?? '');
        setData('date_month', year === null ? '' : (month ?? ''));
        clearErrors('date_year', 'date_month');
        clearImportError('date_year');
        clearImportError('date_month');
    };

    const applyCategory = (value: string) => {
        setData('category', value);
        clearErrors('category');
        clearImportError('category');
    };

    const acceptCategory = (accepted: boolean) => {
        importFlow.acceptCategory(accepted);
        const suggestionValue =
            importFlow.analysis?.proposal.fields.category.value;

        if (suggestionValue === undefined || suggestionValue === null) {
            return;
        }

        if (accepted) {
            applyCategory(suggestionValue);
        } else if (data.category === suggestionValue) {
            applyCategory('');
        }
    };

    const toggleSkill = (skill: ImportSkill, checked: boolean) => {
        if (checked) {
            setData('skill_ids', [
                ...new Set([...data.skill_ids.map(Number), skill.id]),
            ]);

            if (skill.state === 'suggested') {
                importFlow.acceptSkill(skill.id, true);
            }

            return;
        }

        setData(
            'skill_ids',
            data.skill_ids.map(Number).filter((id) => id !== skill.id),
        );

        if (skill.state === 'suggested') {
            importFlow.acceptSkill(skill.id, false);
        }
    };

    const changeSkills = (ids: number[]) => {
        const next = ids.map(Number);
        const previous = data.skill_ids.map(Number);
        const suggested = new Set(
            (importFlow.analysis?.proposal.skills ?? [])
                .filter((skill) => skill.state === 'suggested')
                .map((skill) => skill.id),
        );

        setData('skill_ids', next);

        next.filter(
            (id) => !previous.includes(id) && suggested.has(id),
        ).forEach((id) => importFlow.acceptSkill(id, true));
        previous
            .filter((id) => !next.includes(id) && suggested.has(id))
            .forEach((id) => importFlow.acceptSkill(id, false));
    };

    const submitDisabled =
        processing ||
        saving ||
        importFlow.preparing ||
        importFlow.analyzing ||
        (importEnabled && importFlow.fileInvalid);
    const hasImportFile = importEnabled && importFlow.file !== null;

    return (
        <>
            {importEnabled && importNotice ? (
                <>
                    <CertificationImportPanel
                        notice={importNotice}
                        limits={limits}
                        flow={importFlow}
                        existingCategory={data.category}
                        existingSkillIds={data.skill_ids.map(Number)}
                        skills={skills}
                        disabled={saving}
                    />
                    {importFlow.analysis ? (
                        <CertificationImportReview
                            analysis={importFlow.analysis}
                            current={{
                                name: data.name,
                                institution: data.institution,
                                date_year: data.date_year,
                                date_month: data.date_month,
                                duration: data.duration,
                                code: data.code,
                                validation_url: data.validation_url,
                                category: data.category,
                                skill_ids: data.skill_ids.map(Number),
                            }}
                            originalUrl={importFlow.objectUrl}
                            acceptedCategory={importFlow.acceptedCategory}
                            onApplyField={applyField}
                            onApplyDate={applyDate}
                            onApplyCategory={applyCategory}
                            onAcceptCategory={acceptCategory}
                            onToggleSkill={toggleSkill}
                            onDiscard={importFlow.discardProposal}
                            onCancel={() => {
                                importFlow.cancel();
                                setLocalWarning(null);
                                setWarningOpen(false);
                            }}
                            disabled={saving}
                        />
                    ) : null}
                </>
            ) : null}

            <form onSubmit={submit} className="space-y-6">
                {importFailure ? (
                    <div ref={importFailureRef}>
                        <Alert variant="destructive">
                            <AlertCircle aria-hidden="true" />
                            <AlertTitle>No se pudo guardar</AlertTitle>
                            <AlertDescription>{importFailure}</AlertDescription>
                        </Alert>
                    </div>
                ) : null}

                {hasImportFile && importFlow.fileInvalid ? (
                    <Alert variant="destructive">
                        <AlertCircle aria-hidden="true" />
                        <AlertTitle>El PDF no es válido</AlertTitle>
                        <AlertDescription>
                            Quitá o reemplazá el archivo antes de guardar. Un
                            archivo inválido no se asocia a ningún registro.
                        </AlertDescription>
                    </Alert>
                ) : null}

                {hasImportFile && !importFlow.fileInvalid ? (
                    <Alert>
                        <CheckCircle2 aria-hidden="true" />
                        <AlertTitle>PDF seleccionado</AlertTitle>
                        <AlertDescription>
                            Se validará, conservará y asociará junto con el
                            registro solo al confirmar. Si preferís no
                            asociarlo, quitá el archivo o cancelá la
                            importación.
                        </AlertDescription>
                    </Alert>
                ) : null}

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
                                onChange={(event) => {
                                    setData('name', event.target.value);
                                    clearImportError('name');
                                }}
                                aria-invalid={Boolean(fieldErrors.name)}
                            />
                            {fieldErrors.name ? (
                                <p className="text-sm text-destructive">
                                    {fieldErrors.name}
                                </p>
                            ) : null}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="institution">Institución</Label>
                            <Input
                                id="institution"
                                value={data.institution}
                                onChange={(event) => {
                                    setData('institution', event.target.value);
                                    clearImportError('institution');
                                }}
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="category">Categoría</Label>
                            <Input
                                id="category"
                                value={data.category}
                                onChange={(event) => {
                                    setData('category', event.target.value);

                                    if (
                                        event.target.value !==
                                        importFlow.analysis?.proposal.fields
                                            .category.value
                                    ) {
                                        importFlow.acceptCategory(false);
                                    }

                                    clearImportError('category');
                                }}
                                placeholder="Programación, Análisis, Gestión..."
                            />
                        </div>
                        <YearMonthInput
                            label="Fecha"
                            idPrefix="date"
                            year={data.date_year}
                            month={data.date_month}
                            onChange={(year, month) => {
                                setData(
                                    'date_year',
                                    year === '' ? '' : Number(year),
                                );
                                setData(
                                    'date_month',
                                    year === '' || month === ''
                                        ? ''
                                        : Number(month),
                                );
                                clearImportError('date_year');
                                clearImportError('date_month');
                            }}
                            yearError={fieldErrors.date_year}
                            monthError={fieldErrors.date_month}
                            maxYear={maxYear}
                        />
                        <div className="space-y-1.5">
                            <Label htmlFor="duration">Duración</Label>
                            <Input
                                id="duration"
                                value={data.duration}
                                onChange={(event) => {
                                    setData('duration', event.target.value);
                                    clearImportError('duration');
                                }}
                                placeholder="40 horas, 6 semanas..."
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="code">Código o identificador</Label>
                            <Input
                                id="code"
                                value={data.code}
                                onChange={(event) => {
                                    setData('code', event.target.value);
                                    clearImportError('code');
                                }}
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="validation_url">
                                URL de validación
                            </Label>
                            <Input
                                id="validation_url"
                                value={data.validation_url}
                                onChange={(event) => {
                                    setData(
                                        'validation_url',
                                        event.target.value,
                                    );
                                    clearImportError('validation_url');
                                }}
                                aria-invalid={Boolean(
                                    fieldErrors.validation_url,
                                )}
                            />
                            {fieldErrors.validation_url ? (
                                <p className="text-sm text-destructive">
                                    {fieldErrors.validation_url}
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
                            onChange={changeSkills}
                            error={skillError}
                        />
                    </CardContent>
                </Card>

                {hasImportFile && importFlow.file ? (
                    <p className="text-xs text-muted-foreground">
                        Al guardar se crea la certificación y se asocia el PDF
                        original «{importFlow.file.name}» en una sola operación.
                        El título y la descripción documental no se generan
                        automáticamente.
                    </p>
                ) : null}

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" asChild>
                        <Link href="/certifications">Cancelar</Link>
                    </Button>
                    <Button type="submit" disabled={submitDisabled}>
                        {saving ? (
                            <>
                                <Spinner /> Guardando…
                            </>
                        ) : hasImportFile ? (
                            'Crear certificación y asociar PDF'
                        ) : isEdit ? (
                            'Guardar cambios'
                        ) : (
                            'Crear certificación'
                        )}
                    </Button>
                </div>
            </form>

            {warning ? (
                <DuplicateWarningDialog
                    open={warningOpen}
                    warning={warning}
                    onOpenChange={setWarningOpen}
                    processing={retrying || saving}
                    onConfirm={confirmAnyway}
                />
            ) : null}
        </>
    );
}
