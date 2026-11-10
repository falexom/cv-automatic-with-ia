import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    ClipboardList,
    FileText,
    History,
    Lightbulb,
    MessageCircleQuestion,
    Pencil,
    ShieldCheck,
} from 'lucide-react';
import CvAdaptationObservationForm from '@/components/cv-adaptation/CvAdaptationObservationForm';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout';
import {
    COVERAGE_LABELS,
    EVIDENCE_STATE_LABELS,
    IMPORTANCE_LABELS,
    OPERATION_LABELS,
    OBSERVATION_TYPE_LABELS,
    QUESTION_STATUS_LABELS,
    RECOMMENDATION_KIND_LABELS,
    type CvAdaptationEvaluationContext,
    type CvAdaptationObservation,
    type CvAdaptationObservationType,
    type CvAdaptationRequirement,
    type CvAdaptationShowProps,
} from '@/lib/cv-adaptation';
import { formatDateTime } from '@/lib/dates';

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

function observationLinked(
    context: CvAdaptationEvaluationContext,
    observation: CvAdaptationObservation,
): { title: string; detail: string | null } {
    if (observation.type === 'gap') {
        const ref = observation.reference.replace(/^gap:/, '');
        const requirement = context.requirements.find(
            (item) => item.ref === ref,
        );
        const gap = context.gaps.find((item) => item.requirement_ref === ref);

        return {
            title: requirement?.text ?? 'Brecha sin requisito',
            detail: gap?.reason ?? null,
        };
    }

    if (observation.type === 'recommendation') {
        const index = Number(
            observation.reference.replace(/^recommendation:/, ''),
        );
        const recommendation = context.development_recommendations[index];

        return {
            title: recommendation?.label ?? 'Recomendación',
            detail: recommendation
                ? `${RECOMMENDATION_KIND_LABELS[recommendation.kind]} · ${recommendation.reason}`
                : null,
        };
    }

    const index = Number(observation.reference.replace(/^question:/, ''));
    const question = context.clarifying_questions[index];
    const requirement = question
        ? context.requirements.find(
              (item) => item.ref === question.requirement_ref,
          )
        : null;

    return {
        title: question?.question ?? 'Pregunta',
        detail: question
            ? [
                  requirement?.text,
                  question.answer
                      ? `Respuesta: ${question.answer}`
                      : 'Sin respuesta.',
                  question.user_confirmed_absent
                      ? 'Carencia confirmada por el usuario.'
                      : null,
              ]
                  .filter(Boolean)
                  .join('\n')
            : null,
    };
}

function Count({
    label,
    value,
    detail,
}: {
    label: string;
    value: number;
    detail?: string;
}) {
    return (
        <div className="rounded-md border p-3">
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="text-2xl font-semibold">{value}</p>
            {detail ? (
                <p className="text-xs text-muted-foreground">{detail}</p>
            ) : null}
        </div>
    );
}

const OBSERVATION_TYPES: CvAdaptationObservationType[] = [
    'gap',
    'recommendation',
    'question',
];

export default function Show({ profile, adaptation }: CvAdaptationShowProps) {
    const { context, counts } = adaptation;
    const gapsByRef = new Map(
        context.gaps.map((gap) => [gap.requirement_ref, gap]),
    );

    return (
        <AppLayout title={`Adaptación — ${adaptation.job_title}`}>
            <Head title={`Adaptación — ${adaptation.job_title}`} />

            <div className="mb-4 flex flex-wrap justify-end gap-2">
                <Button variant="outline" asChild>
                    <Link href={`/profiles/${profile.id}`}>
                        <ArrowLeft /> Volver al perfil
                    </Link>
                </Button>
                <Button variant="outline" asChild>
                    <Link href={`/profiles/${profile.id}/edit`}>
                        <Pencil /> Editar perfil
                    </Link>
                </Button>
                {profile.is_active ? (
                    <Button asChild>
                        <Link href={`/cv?profile=${profile.id}`}>
                            <FileText /> Generar CV
                        </Link>
                    </Button>
                ) : null}
            </div>

            <div className="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex flex-wrap items-center gap-2">
                            <History aria-hidden="true" />
                            {adaptation.job_title}
                            <Badge variant="outline">
                                {OPERATION_LABELS[adaptation.operation]}
                            </Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <p className="text-sm text-muted-foreground">
                            {adaptation.company ?? 'Empresa no informada'}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            Perfil: {profile.name} ·{' '}
                            {profile.is_active ? 'Activo' : 'Inactivo'}
                        </p>
                        <p className="rounded-md border border-primary/20 bg-primary/5 p-3 text-sm">
                            Evaluación histórica del{' '}
                            {formatDateTime(adaptation.analyzed_at)}; no se
                            actualiza con cambios del Maestro o perfil.
                        </p>
                        <p className="text-xs text-muted-foreground">
                            Confirmada el{' '}
                            {formatDateTime(adaptation.confirmed_at)}.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Resumen de cobertura</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <Count label="Cubiertos" value={counts.supported} />
                            <Count label="Parciales" value={counts.partial} />
                            <Count
                                label="Pendientes de cobertura"
                                value={counts.pending_coverage}
                                detail={`Brecha: ${counts.gap} · Por confirmar: ${counts.needs_confirmation}`}
                            />
                            <Count label="Total" value={counts.total} />
                        </div>
                        <div className="flex flex-wrap items-center gap-2 rounded-md border border-dashed p-3 text-sm">
                            <Badge variant="outline">
                                Observaciones pendientes
                            </Badge>
                            <span className="font-medium">
                                {counts.pending_observations}
                            </span>
                            <span className="text-muted-foreground">
                                Se gestionan aparte de la cobertura.
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Lightbulb aria-hidden="true" />
                            Justificación de la operación
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {context.rationale.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin justificación registrada.
                            </p>
                        ) : (
                            <ul className="space-y-2 text-sm">
                                {context.rationale.map((reason, index) => (
                                    <li
                                        key={`${index}-${reason}`}
                                        className="flex gap-2"
                                    >
                                        <span aria-hidden="true">•</span>
                                        <span className="break-words">
                                            {reason}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Requisitos de la oferta</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {context.requirements.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No hay requisitos registrados.
                            </p>
                        ) : (
                            context.requirements.map((requirement) => {
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
                                            {requirement.exclusive ? (
                                                <Badge variant="destructive">
                                                    Excluyente
                                                </Badge>
                                            ) : null}
                                        </div>
                                        <p className="text-sm break-words whitespace-pre-line">
                                            {requirement.text}
                                        </p>
                                        {requirement.exclusive &&
                                        requirement.exclusive_support ? (
                                            <blockquote className="border-l-2 border-destructive/40 pl-3 text-sm text-muted-foreground">
                                                Soporte en la oferta: «
                                                {requirement.exclusive_support}»
                                            </blockquote>
                                        ) : null}
                                        {gap ? (
                                            <div className="flex flex-wrap items-start gap-2 rounded-md bg-muted/50 p-2 text-sm text-muted-foreground">
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
                            <ShieldCheck aria-hidden="true" />
                            Recomendaciones de desarrollo
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <p className="text-sm text-amber-950">
                            Estas recomendaciones no acreditan competencias y no
                            se incorporan automáticamente al perfil ni al CV.
                        </p>
                        {context.development_recommendations.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No hay recomendaciones registradas.
                            </p>
                        ) : (
                            context.development_recommendations.map(
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
                            <MessageCircleQuestion aria-hidden="true" />
                            Preguntas y respuestas
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {context.clarifying_questions.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No hay preguntas registradas.
                            </p>
                        ) : (
                            context.clarifying_questions.map(
                                (question, index) => {
                                    const requirement =
                                        context.requirements.find(
                                            (item) =>
                                                item.ref ===
                                                question.requirement_ref,
                                        );
                                    const gap = context.gaps.find(
                                        (item) =>
                                            item.requirement_ref ===
                                            question.requirement_ref,
                                    );

                                    return (
                                        <div
                                            key={`${index}-${question.requirement_ref}-${question.question}`}
                                            className="space-y-2 rounded-md border p-3"
                                        >
                                            {requirement ? (
                                                <p className="text-xs text-muted-foreground">
                                                    {requirement.text}
                                                </p>
                                            ) : null}
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="text-sm font-medium break-words">
                                                    {question.question}
                                                </p>
                                                <Badge variant="outline">
                                                    {
                                                        QUESTION_STATUS_LABELS[
                                                            question.status
                                                        ]
                                                    }
                                                </Badge>
                                                {gap ? (
                                                    <Badge variant="secondary">
                                                        Evidencia IA:{' '}
                                                        {
                                                            EVIDENCE_STATE_LABELS[
                                                                gap
                                                                    .evidence_state
                                                            ]
                                                        }
                                                    </Badge>
                                                ) : null}
                                                {question.user_confirmed_absent ? (
                                                    <Badge variant="destructive">
                                                        Carencia confirmada por
                                                        el usuario
                                                    </Badge>
                                                ) : null}
                                            </div>
                                            {question.status === 'answered' &&
                                            question.answer ? (
                                                <div className="rounded-md bg-muted/50 p-3 text-sm">
                                                    <p className="mb-1 text-xs text-muted-foreground">
                                                        Declaración del usuario
                                                    </p>
                                                    <p className="break-words whitespace-pre-line">
                                                        {question.answer}
                                                    </p>
                                                </div>
                                            ) : (
                                                <p className="text-sm text-muted-foreground">
                                                    {question.status ===
                                                    'discarded'
                                                        ? 'Pregunta descartada.'
                                                        : 'Sin respuesta.'}
                                                </p>
                                            )}
                                        </div>
                                    );
                                },
                            )
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <ClipboardList aria-hidden="true" />
                            Observaciones
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-sm text-muted-foreground">
                            Las observaciones son independientes de la cobertura
                            y de la respuesta original. No modifican el perfil,
                            el Maestro ni el CV.
                        </p>

                        {adaptation.observations.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No hay observaciones para gestionar.
                            </p>
                        ) : (
                            OBSERVATION_TYPES.map((type) => {
                                const observations =
                                    adaptation.observations.filter(
                                        (observation) =>
                                            observation.type === type,
                                    );

                                if (observations.length === 0) {
                                    return null;
                                }

                                return (
                                    <section key={type} className="space-y-3">
                                        <h3 className="text-sm font-semibold">
                                            {OBSERVATION_TYPE_LABELS[type]} (
                                            {observations.length})
                                        </h3>
                                        {observations.map((observation) => {
                                            const linked = observationLinked(
                                                context,
                                                observation,
                                            );

                                            return (
                                                <CvAdaptationObservationForm
                                                    key={`${observation.id}-${observation.status}-${observation.note ?? ''}`}
                                                    profileId={profile.id}
                                                    adaptationId={adaptation.id}
                                                    observation={observation}
                                                    linkedTitle={linked.title}
                                                    linkedDetail={linked.detail}
                                                />
                                            );
                                        })}
                                    </section>
                                );
                            })
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
