import { router } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    CheckCircle2,
    CircleAlert,
    Lightbulb,
    MessageCircleQuestion,
    ShieldCheck,
    Sparkles,
    UserRound,
} from 'lucide-react';
import { useRef, useState } from 'react';
import CvAdaptationSelectionSection from '@/components/cv-adaptation/CvAdaptationSelectionSection';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    CONTENT_FIELD_LABELS,
    COVERAGE_LABELS,
    EVIDENCE_STATE_LABELS,
    IMPORTANCE_LABELS,
    OPERATION_DESCRIPTIONS,
    OPERATION_LABELS,
    RECOMMENDATION_KIND_LABELS,
    selectedContentFromProposal,
    type CvAdaptationAnalysisResponse,
    type CvAdaptationApplyResponse,
    type CvAdaptationCatalog,
    type CvAdaptationCatalogProfile,
    type CvAdaptationContentField,
    type CvAdaptationDataNotice,
    type CvAdaptationQuestion,
    type CvAdaptationQuestionReview,
    type CvAdaptationRequirement,
    type CvAdaptationSelection,
    type CvAdaptationSelectedContent,
} from '@/lib/cv-adaptation';
import { RELEVANCE_LABELS, type Relevance } from '@/lib/cv-profiles';
import { formatRange, formatYearMonth } from '@/lib/dates';
import { postJson, RequestError } from '@/lib/http';
import { SKILL_CATEGORY_LABELS } from '@/lib/skill-categories';

const textareaClassName =
    'min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

const CONTENT_FIELDS: CvAdaptationContentField[] = [
    'work_experiences',
    'education_entries',
    'certifications',
    'skills',
    'projects',
];

function questionKey(question: CvAdaptationQuestion, index: number): string {
    return `${question.requirement_ref}::${question.question}::${index}`;
}

function coverageVariant(
    coverage: CvAdaptationRequirement['coverage'],
): 'default' | 'secondary' | 'destructive' | 'outline' {
    return coverage === 'supported'
        ? 'default'
        : coverage === 'gap'
          ? 'destructive'
          : coverage === 'partial'
            ? 'secondary'
            : 'outline';
}

function importanceVariant(
    importance: CvAdaptationRequirement['importance'],
): 'default' | 'secondary' | 'outline' {
    return importance === 'required'
        ? 'default'
        : importance === 'preferred'
          ? 'secondary'
          : 'outline';
}

function catalogLabels(
    catalog: CvAdaptationCatalog,
    selected: CvAdaptationSelectedContent,
): Record<
    CvAdaptationContentField,
    { ref: string; label: string; relevance: Relevance }[]
> {
    const workExperiences = new Map(
        catalog.work_experiences.map((item) => [
            item.ref,
            `${item.position} — ${item.company}`,
        ]),
    );
    const educationEntries = new Map(
        catalog.education_entries.map((item) => [
            item.ref,
            `${item.program} — ${item.institution}`,
        ]),
    );
    const certifications = new Map(
        catalog.certifications.map((item) => [item.ref, item.name]),
    );
    const skills = new Map(catalog.skills.map((item) => [item.ref, item.name]));
    const projects = new Map(
        catalog.projects.map((item) => [item.ref, item.name]),
    );

    return {
        work_experiences: selected.work_experiences.map((selection) => ({
            ref: selection.ref,
            label:
                workExperiences.get(selection.ref) ?? 'Elemento no disponible',
            relevance: selection.relevance,
        })),
        education_entries: selected.education_entries.map((selection) => ({
            ref: selection.ref,
            label:
                educationEntries.get(selection.ref) ?? 'Elemento no disponible',
            relevance: selection.relevance,
        })),
        certifications: selected.certifications.map((selection) => ({
            ref: selection.ref,
            label:
                certifications.get(selection.ref) ?? 'Elemento no disponible',
            relevance: selection.relevance,
        })),
        skills: selected.skills.map((selection) => ({
            ref: selection.ref,
            label: skills.get(selection.ref) ?? 'Elemento no disponible',
            relevance: selection.relevance,
        })),
        projects: selected.projects.map((selection) => ({
            ref: selection.ref,
            label: projects.get(selection.ref) ?? 'Elemento no disponible',
            relevance: selection.relevance,
        })),
    };
}

function SourceProfileCard({
    profile,
    catalog,
    title,
}: {
    profile: CvAdaptationCatalogProfile;
    catalog: CvAdaptationCatalog;
    title: string;
}) {
    const labels = catalogLabels(catalog, profile.selected_content);
    const counts = CONTENT_FIELDS.map((field) => ({
        field,
        count: profile.selected_content[field].length,
    }));

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex flex-wrap items-center gap-2">
                    <UserRound aria-hidden="true" />
                    {title}
                    <Badge
                        variant={profile.is_active ? 'default' : 'secondary'}
                    >
                        {profile.is_active ? 'Activo' : 'Inactivo'}
                    </Badge>
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="space-y-1">
                    <p className="text-lg font-semibold break-words">
                        {profile.name}
                    </p>
                    {profile.professional_title ? (
                        <p className="text-sm font-medium break-words">
                            {profile.professional_title}
                        </p>
                    ) : null}
                    {profile.summary ? (
                        <p className="text-sm break-words whitespace-pre-line text-muted-foreground">
                            {profile.summary}
                        </p>
                    ) : null}
                </div>

                <div className="flex flex-wrap gap-2">
                    {counts.map(({ field, count }) => (
                        <Badge key={field} variant="outline">
                            {CONTENT_FIELD_LABELS[field]}: {count}
                        </Badge>
                    ))}
                </div>

                <details className="rounded-md border p-3">
                    <summary className="cursor-pointer text-sm font-medium">
                        Ver contenido seleccionado
                    </summary>
                    <div className="mt-3 space-y-4">
                        {CONTENT_FIELDS.map((field) => (
                            <div key={field}>
                                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    {CONTENT_FIELD_LABELS[field]}
                                </p>
                                {labels[field].length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Sin elementos.
                                    </p>
                                ) : (
                                    <ul className="mt-1 space-y-1 text-sm">
                                        {labels[field].map((item) => (
                                            <li
                                                key={item.ref}
                                                className="flex flex-wrap items-center gap-2"
                                            >
                                                <span className="break-words">
                                                    {item.label}
                                                </span>
                                                <Badge variant="outline">
                                                    {
                                                        RELEVANCE_LABELS[
                                                            item.relevance
                                                        ]
                                                    }
                                                </Badge>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        ))}
                    </div>
                </details>
            </CardContent>
        </Card>
    );
}

export default function CvAdaptationReview({
    analysis,
    dataNotice,
    onCancel,
}: {
    analysis: CvAdaptationAnalysisResponse;
    dataNotice: CvAdaptationDataNotice;
    onCancel: () => void;
}) {
    const { proposal, catalog, ref_nonce: refNonce } = analysis;
    const [details, setDetails] = useState({
        name: proposal.profile_details?.name ?? '',
        professional_title: proposal.profile_details?.professional_title ?? '',
        summary: proposal.profile_details?.summary ?? '',
    });
    const [jobTitle, setJobTitle] = useState(proposal.job_title);
    const [company, setCompany] = useState(proposal.company ?? '');
    const [selected, setSelected] = useState<CvAdaptationSelectedContent>(
        selectedContentFromProposal(proposal),
    );
    const [answers, setAnswers] = useState<Record<string, string>>({});
    const [discardedQuestions, setDiscardedQuestions] = useState<Set<string>>(
        new Set(),
    );
    const [confirmedAbsent, setConfirmedAbsent] = useState<Set<string>>(
        new Set(),
    );
    const [retentionAccepted, setRetentionAccepted] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<{
        message: string;
        code?: string;
    } | null>(null);
    const applyingRef = useRef(false);

    const isReuse = proposal.operation === 'reuse';
    const requirementsByRef = new Map(
        proposal.requirements.map((requirement) => [
            requirement.ref,
            requirement,
        ]),
    );
    const gapsByRef = new Map(
        proposal.gaps.map((gap) => [gap.requirement_ref, gap]),
    );
    const sourceProfile = proposal.source_profile_ref
        ? (catalog.profiles.find(
              (profile) => profile.ref === proposal.source_profile_ref,
          ) ?? null)
        : null;
    const visibleQuestions = proposal.clarifying_questions
        .map((question, index) => ({
            question,
            key: questionKey(question, index),
        }))
        .filter(({ key }) => !discardedQuestions.has(key));
    const canApply =
        jobTitle.trim() !== '' &&
        retentionAccepted &&
        (isReuse || details.name.trim() !== '');

    const apply = async () => {
        if (!canApply || applyingRef.current) {
            return;
        }

        applyingRef.current = true;
        setProcessing(true);
        setError(null);

        const questions: CvAdaptationQuestionReview[] =
            proposal.clarifying_questions.map((question, index) => {
                const key = questionKey(question, index);
                const discarded = discardedQuestions.has(key);
                const answer = answers[key] ?? '';
                const answered = !discarded && answer.trim() !== '';

                return {
                    index,
                    status: discarded
                        ? 'discarded'
                        : answered
                          ? 'answered'
                          : 'unanswered',
                    answer: answered ? answer : null,
                    user_confirmed_absent: answered && confirmedAbsent.has(key),
                };
            });

        let redirect: string;

        try {
            const response = await postJson<CvAdaptationApplyResponse>(
                '/cv/adapt/apply',
                {
                    operation: proposal.operation,
                    ref_nonce: refNonce,
                    confirmation_token: analysis.confirmation_token,
                    retention_accepted: true,
                    job_title: jobTitle.trim(),
                    company: company.trim() === '' ? null : company,
                    questions,
                    source_profile_ref: proposal.source_profile_ref,
                    profile_details: isReuse
                        ? null
                        : {
                              name: details.name.trim(),
                              professional_title:
                                  details.professional_title.trim() === ''
                                      ? null
                                      : details.professional_title,
                              summary:
                                  details.summary.trim() === ''
                                      ? null
                                      : details.summary,
                          },
                    selected_content: isReuse ? null : selected,
                },
            );

            redirect = response.redirect;
        } catch (caught) {
            setError({
                message:
                    caught instanceof RequestError
                        ? caught.message
                        : 'No se pudo guardar el perfil. No se guardó ningún cambio.',
                code: caught instanceof RequestError ? caught.code : undefined,
            });
            setProcessing(false);
            applyingRef.current = false;

            return;
        }

        applyingRef.current = false;
        router.visit(redirect);
    };

    const changeField = (
        field: CvAdaptationContentField,
        next: CvAdaptationSelection[],
    ) => {
        setSelected((current) => ({ ...current, [field]: next }));
    };

    const buttonLabel = isReuse
        ? 'Usar perfil y guardar evaluación'
        : 'Crear perfil y guardar evaluación';

    return (
        <div className="space-y-6">
            <Card className="overflow-hidden border-primary/20">
                <div className="border-l-4 border-primary bg-gradient-to-br from-primary/5 via-background to-background p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="max-w-3xl space-y-2">
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge className="gap-1.5">
                                    <Sparkles aria-hidden="true" />
                                    {OPERATION_LABELS[proposal.operation]}
                                </Badge>
                                <Badge variant="outline">
                                    Propuesta no guardada
                                </Badge>
                            </div>
                            <h2 className="text-xl font-semibold tracking-tight">
                                Revisá la propuesta antes de confirmar
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {OPERATION_DESCRIPTIONS[proposal.operation]}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Para cambiar la operación, cancelá y volvé a
                                analizar con otra estrategia.
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onCancel}
                            disabled={processing}
                        >
                            <ArrowLeft /> Cancelar
                        </Button>
                    </div>
                </div>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Lightbulb aria-hidden="true" /> Por qué esta
                        recomendación
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <ul className="space-y-2 text-sm">
                        {proposal.rationale.map((reason, index) => (
                            <li
                                key={`${index}-${reason}`}
                                className="flex gap-2"
                            >
                                <CheckCircle2
                                    aria-hidden="true"
                                    className="mt-0.5 size-4 shrink-0 text-primary"
                                />
                                <span className="break-words">{reason}</span>
                            </li>
                        ))}
                    </ul>
                </CardContent>
            </Card>

            {sourceProfile ? (
                <SourceProfileCard
                    profile={sourceProfile}
                    catalog={catalog}
                    title={
                        isReuse
                            ? 'Perfil que se reutilizará'
                            : 'Perfil de origen'
                    }
                />
            ) : null}

            <Card>
                <CardHeader>
                    <CardTitle>Datos de la evaluación</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-1.5 sm:col-span-2">
                        <Label htmlFor="job-title">Puesto de la oferta *</Label>
                        <Input
                            id="job-title"
                            value={jobTitle}
                            onChange={(event) =>
                                setJobTitle(event.target.value)
                            }
                            maxLength={255}
                            disabled={processing}
                        />
                        <p className="text-xs text-muted-foreground">
                            Si no se identifica, usá «Puesto no identificado».
                        </p>
                    </div>
                    <div className="space-y-1.5 sm:col-span-2">
                        <Label htmlFor="company">Empresa</Label>
                        <Input
                            id="company"
                            value={company}
                            onChange={(event) => setCompany(event.target.value)}
                            maxLength={255}
                            disabled={processing}
                        />
                        <p className="text-xs text-muted-foreground">
                            Dejala vacía si no se conoce. No se inventa.
                        </p>
                    </div>
                </CardContent>
            </Card>

            {!isReuse ? (
                <Card>
                    <CardHeader>
                        <CardTitle>Datos del perfil nuevo</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="profile-name">
                                Nombre del perfil *
                            </Label>
                            <Input
                                id="profile-name"
                                value={details.name}
                                onChange={(event) =>
                                    setDetails((current) => ({
                                        ...current,
                                        name: event.target.value,
                                    }))
                                }
                                maxLength={100}
                                disabled={processing}
                            />
                            <p className="text-xs text-muted-foreground">
                                Debe ser único. El perfil de origen, si existe,
                                no se modifica.
                            </p>
                        </div>
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="profile-title">
                                Título profesional
                            </Label>
                            <Input
                                id="profile-title"
                                value={details.professional_title}
                                onChange={(event) =>
                                    setDetails((current) => ({
                                        ...current,
                                        professional_title: event.target.value,
                                    }))
                                }
                                maxLength={255}
                                disabled={processing}
                            />
                        </div>
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="profile-summary">Resumen</Label>
                            <textarea
                                id="profile-summary"
                                className={textareaClassName}
                                value={details.summary}
                                onChange={(event) =>
                                    setDetails((current) => ({
                                        ...current,
                                        summary: event.target.value,
                                    }))
                                }
                                disabled={processing}
                            />
                        </div>
                    </CardContent>
                </Card>
            ) : null}

            {!isReuse ? (
                <Card>
                    <CardHeader>
                        <CardTitle>Contenido seleccionado para el CV</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <p className="text-sm text-muted-foreground">
                            Podés quitar elementos, agregar otros del Perfil
                            Maestro y cambiar su relevancia. El orden final lo
                            aplica Laravel al generar el CV.
                        </p>
                        <CvAdaptationSelectionSection
                            title={CONTENT_FIELD_LABELS.work_experiences}
                            field="work_experiences"
                            items={catalog.work_experiences}
                            selected={selected.work_experiences}
                            disabled={processing}
                            defaultOpen
                            onChange={(next) =>
                                changeField('work_experiences', next)
                            }
                            renderLabel={(item) =>
                                `${item.position} — ${item.company}`
                            }
                            renderMeta={(item) => {
                                const period = formatRange(
                                    item.start_year,
                                    item.start_month,
                                    item.end_year,
                                    item.end_month,
                                    item.is_current,
                                );
                                const technologies =
                                    item.technologies.length > 0
                                        ? ` · ${item.technologies.slice(0, 3).join(', ')}`
                                        : '';

                                return `${period}${technologies}`;
                            }}
                        />

                        <CvAdaptationSelectionSection
                            title={CONTENT_FIELD_LABELS.education_entries}
                            field="education_entries"
                            items={catalog.education_entries}
                            selected={selected.education_entries}
                            disabled={processing}
                            onChange={(next) =>
                                changeField('education_entries', next)
                            }
                            renderLabel={(item) =>
                                `${item.program} — ${item.institution}`
                            }
                            renderMeta={(item) => {
                                const period = formatRange(
                                    item.start_year,
                                    item.start_month,
                                    item.end_year,
                                    item.end_month,
                                    item.is_in_progress,
                                    'en curso',
                                );

                                return [period, item.degree_level]
                                    .filter(Boolean)
                                    .join(' · ');
                            }}
                        />

                        <CvAdaptationSelectionSection
                            title={CONTENT_FIELD_LABELS.certifications}
                            field="certifications"
                            items={catalog.certifications}
                            selected={selected.certifications}
                            disabled={processing}
                            onChange={(next) =>
                                changeField('certifications', next)
                            }
                            renderLabel={(item) => item.name}
                            renderMeta={(item) => {
                                const period =
                                    formatYearMonth(
                                        item.date_year,
                                        item.date_month,
                                    ) ?? '';

                                return [
                                    item.institution,
                                    period,
                                    item.duration,
                                    item.category,
                                ]
                                    .filter(Boolean)
                                    .join(' · ');
                            }}
                        />

                        <CvAdaptationSelectionSection
                            title={CONTENT_FIELD_LABELS.skills}
                            field="skills"
                            items={catalog.skills}
                            selected={selected.skills}
                            disabled={processing}
                            onChange={(next) => changeField('skills', next)}
                            renderLabel={(item) => item.name}
                            renderMeta={(item) =>
                                SKILL_CATEGORY_LABELS[item.category] ??
                                item.category
                            }
                        />

                        <CvAdaptationSelectionSection
                            title={CONTENT_FIELD_LABELS.projects}
                            field="projects"
                            items={catalog.projects}
                            selected={selected.projects}
                            disabled={processing}
                            onChange={(next) => changeField('projects', next)}
                            renderLabel={(item) => item.name}
                            renderMeta={(item) => {
                                const period = formatRange(
                                    item.start_year,
                                    item.start_month,
                                    item.end_year,
                                    item.end_month,
                                );

                                return [item.role, period]
                                    .filter(Boolean)
                                    .join(' · ');
                            }}
                        />
                    </CardContent>
                </Card>
            ) : null}

            <Card>
                <CardHeader>
                    <CardTitle>Requisitos de la oferta</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    {proposal.requirements.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Codex no identificó requisitos verificables.
                        </p>
                    ) : (
                        proposal.requirements.map((requirement) => {
                            const gap = gapsByRef.get(requirement.ref);

                            return (
                                <div
                                    key={requirement.ref}
                                    className="space-y-2 rounded-md border p-3"
                                >
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Badge
                                            variant={importanceVariant(
                                                requirement.importance,
                                            )}
                                        >
                                            {
                                                IMPORTANCE_LABELS[
                                                    requirement.importance
                                                ]
                                            }
                                        </Badge>
                                        <Badge
                                            variant={coverageVariant(
                                                requirement.coverage,
                                            )}
                                        >
                                            {
                                                COVERAGE_LABELS[
                                                    requirement.coverage
                                                ]
                                            }
                                        </Badge>
                                        <span className="text-xs text-muted-foreground">
                                            {requirement.ref}
                                        </span>
                                    </div>
                                    <p className="text-sm break-words whitespace-pre-line">
                                        {requirement.text}
                                    </p>
                                    {gap ? (
                                        <div className="flex flex-wrap items-start gap-2 rounded-md bg-muted/50 p-2 text-sm text-muted-foreground">
                                            <CircleAlert
                                                aria-hidden="true"
                                                className="mt-0.5 size-4 shrink-0"
                                            />
                                            <Badge variant="outline">
                                                {
                                                    EVIDENCE_STATE_LABELS[
                                                        gap.evidence_state
                                                    ]
                                                }
                                            </Badge>
                                            <span className="break-words">
                                                {gap.reason}
                                            </span>
                                        </div>
                                    ) : null}
                                </div>
                            );
                        })
                    )}
                </CardContent>
            </Card>

            <Card className="border-amber-200 bg-amber-50/40">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <ShieldCheck aria-hidden="true" /> Recomendaciones de
                        desarrollo
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <p className="text-sm text-amber-950">
                        Estas sugerencias no acreditan que ya tengas las
                        capacidades, no se agregan al Perfil Maestro ni al
                        perfil de CV, y no aparecen en el CV por esta propuesta.
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Si querés incorporarlas, usá las secciones existentes
                        del Perfil Maestro y volvé a analizar la oferta.
                    </p>
                    {proposal.development_recommendations.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Codex no agregó recomendaciones de desarrollo.
                        </p>
                    ) : (
                        proposal.development_recommendations.map(
                            (recommendation, index) => (
                                <div
                                    key={`${index}-${recommendation.kind}-${recommendation.label}`}
                                    className="space-y-1 rounded-md border border-amber-200 bg-background p-3"
                                >
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Badge variant="secondary">
                                            {
                                                RECOMMENDATION_KIND_LABELS[
                                                    recommendation.kind
                                                ]
                                            }
                                        </Badge>
                                        <span className="font-medium break-words">
                                            {recommendation.label}
                                        </span>
                                        <Badge variant="outline">
                                            {
                                                EVIDENCE_STATE_LABELS[
                                                    recommendation
                                                        .evidence_state
                                                ]
                                            }
                                        </Badge>
                                    </div>
                                    <p className="text-sm break-words text-muted-foreground">
                                        {recommendation.reason}
                                    </p>
                                </div>
                            ),
                        )
                    )}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <MessageCircleQuestion aria-hidden="true" /> Preguntas
                        aclaratorias (opcionales)
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <p className="text-sm text-muted-foreground">
                        Las respuestas se guardarán con la evaluación histórica.
                        No modifican la cobertura ni acreditan competencias.
                    </p>
                    {visibleQuestions.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            {proposal.clarifying_questions.length === 0
                                ? 'No hay preguntas aclaratorias para revisar.'
                                : 'No quedan preguntas por revisar.'}
                        </p>
                    ) : (
                        visibleQuestions.map(({ question, key }) => {
                            const requirement = requirementsByRef.get(
                                question.requirement_ref,
                            );
                            const hasAnswer =
                                (answers[key] ?? '').trim() !== '';

                            return (
                                <div
                                    key={key}
                                    className="space-y-2 rounded-md border p-3"
                                >
                                    {requirement ? (
                                        <p className="text-xs text-muted-foreground">
                                            {requirement.text}
                                        </p>
                                    ) : null}
                                    <p className="text-sm font-medium break-words">
                                        {question.question}
                                    </p>
                                    <textarea
                                        className="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        value={answers[key] ?? ''}
                                        onChange={(event) => {
                                            const value = event.target.value;

                                            setAnswers((current) => ({
                                                ...current,
                                                [key]: value,
                                            }));

                                            if (value.trim() === '') {
                                                setConfirmedAbsent(
                                                    (current) => {
                                                        const next = new Set(
                                                            current,
                                                        );
                                                        next.delete(key);

                                                        return next;
                                                    },
                                                );
                                            }
                                        }}
                                        placeholder="Respuesta opcional…"
                                        aria-label={`Respuesta a: ${question.question}`}
                                        maxLength={5000}
                                        disabled={processing}
                                    />
                                    <label className="flex items-start gap-2 text-xs text-muted-foreground">
                                        <Checkbox
                                            checked={confirmedAbsent.has(key)}
                                            disabled={processing || !hasAnswer}
                                            onCheckedChange={(checked) =>
                                                setConfirmedAbsent(
                                                    (current) => {
                                                        const next = new Set(
                                                            current,
                                                        );

                                                        if (checked === true) {
                                                            next.add(key);
                                                        } else {
                                                            next.delete(key);
                                                        }

                                                        return next;
                                                    },
                                                )
                                            }
                                        />
                                        <span>
                                            Confirmo que no dispongo de este
                                            requisito.
                                        </span>
                                    </label>
                                    <p className="text-xs text-muted-foreground">
                                        La confirmación de ausencia es una
                                        declaración humana: no se infiere del
                                        texto ni modifica la cobertura.
                                    </p>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => {
                                            setDiscardedQuestions((current) =>
                                                new Set(current).add(key),
                                            );
                                            setConfirmedAbsent((current) => {
                                                const next = new Set(current);
                                                next.delete(key);

                                                return next;
                                            });
                                        }}
                                        disabled={processing}
                                    >
                                        Descartar pregunta
                                    </Button>
                                </div>
                            );
                        })
                    )}
                </CardContent>
            </Card>

            <Card className="border-primary/20">
                <CardHeader>
                    <CardTitle>Qué se guardará al confirmar</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <p className="text-sm text-muted-foreground">
                        Esta evaluación quedará guardada localmente y vinculada
                        al perfil. No se actualiza con cambios posteriores del
                        Maestro o del perfil.
                    </p>
                    <div className="grid gap-4 lg:grid-cols-2">
                        <div className="rounded-lg border border-emerald-200 bg-emerald-50/60 p-4">
                            <p className="mb-2 text-sm font-medium text-emerald-950">
                                Se guardará
                            </p>
                            <ul className="space-y-1.5 text-sm text-emerald-900">
                                {dataNotice.retention.stored.map((item) => (
                                    <li key={item} className="flex gap-2">
                                        <span aria-hidden="true">•</span>
                                        <span className="break-words">
                                            {item}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div className="rounded-lg border border-rose-200 bg-rose-50/60 p-4">
                            <p className="mb-2 text-sm font-medium text-rose-950">
                                No se guardará
                            </p>
                            <ul className="space-y-1.5 text-sm text-rose-900">
                                {dataNotice.retention.excluded.map((item) => (
                                    <li key={item} className="flex gap-2">
                                        <span aria-hidden="true">•</span>
                                        <span className="break-words">
                                            {item}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                    <div className="flex items-start gap-3 rounded-lg border bg-muted/30 p-4">
                        <Checkbox
                            id="retention-accepted"
                            checked={retentionAccepted}
                            onCheckedChange={(checked) =>
                                setRetentionAccepted(checked === true)
                            }
                            disabled={processing}
                        />
                        <div className="space-y-1">
                            <Label
                                htmlFor="retention-accepted"
                                className="cursor-pointer leading-5"
                            >
                                Entiendo y acepto que se guarde esta evaluación
                                histórica con los datos revisados.
                            </Label>
                            <p className="text-xs text-muted-foreground">
                                Sin esta aceptación no se crea ni reutiliza el
                                perfil ni se guarda la evaluación.
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {error ? (
                <Alert variant="destructive">
                    <AlertCircle aria-hidden="true" />
                    <div className="col-start-2 space-y-2">
                        <p className="font-medium">
                            No se guardó ningún cambio
                        </p>
                        <p className="text-sm break-words">{error.message}</p>
                        {error.code === 'stale_reference' ? (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={onCancel}
                            >
                                Volver a analizar
                            </Button>
                        ) : null}
                    </div>
                </Alert>
            ) : null}

            <Card>
                <CardContent className="flex flex-col gap-3 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <div className="space-y-1 text-sm">
                        <p className="font-medium">
                            {isReuse
                                ? 'Se usará el perfil activo sin modificar sus datos.'
                                : 'Se creará un perfil nuevo y activo.'}
                        </p>
                        <p className="text-muted-foreground">
                            {isReuse
                                ? 'Se guardará una evaluación histórica vinculada al perfil.'
                                : `Relevancias elegidas: ${CONTENT_FIELDS.map(
                                      (field) =>
                                          `${CONTENT_FIELD_LABELS[field]} ${
                                              selected[field].length
                                          }`,
                                  ).join(
                                      ' · ',
                                  )} · Se guardará una evaluación histórica.`}
                        </p>
                    </div>
                    <div className="flex flex-col-reverse gap-2 sm:flex-row">
                        <Button
                            type="button"
                            variant="outline"
                            className="w-full sm:w-auto"
                            onClick={onCancel}
                            disabled={processing}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            className="w-full sm:w-auto"
                            onClick={apply}
                            disabled={!canApply || processing}
                        >
                            {processing ? (
                                <>
                                    <Spinner /> Guardando…
                                </>
                            ) : (
                                buttonLabel
                            )}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
