import { AlertCircle, ArrowRight, LockKeyhole, Sparkles } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type {
    CvAdaptationDataNotice,
    CvAdaptationStrategy,
    CvAdaptationSummary,
} from '@/lib/cv-adaptation';

const textareaClassName =
    'min-h-44 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50';

export default function CvAdaptationOfferForm({
    offer,
    onOfferChange,
    strategy,
    onStrategyChange,
    dataNotice,
    summary,
    processing,
    error,
    onAnalyze,
}: {
    offer: string;
    onOfferChange: (value: string) => void;
    strategy: CvAdaptationStrategy;
    onStrategyChange: (value: CvAdaptationStrategy) => void;
    dataNotice: CvAdaptationDataNotice;
    summary: CvAdaptationSummary;
    processing: boolean;
    error: string | null;
    onAnalyze: (consent: boolean) => void;
}) {
    const [consent, setConsent] = useState(false);

    useEffect(() => {
        setConsent(false);
    }, [offer, strategy]);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (!consent || offer.trim() === '' || processing) {
            return;
        }

        onAnalyze(consent);
    };

    return (
        <div className="space-y-6">
            <Card className="overflow-hidden border-primary/20">
                <div className="border-l-4 border-primary bg-gradient-to-br from-primary/5 via-background to-background p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="max-w-2xl space-y-2">
                            <Badge variant="outline" className="gap-1.5">
                                <Sparkles aria-hidden="true" />
                                Codex · Revisión humana
                            </Badge>
                            <h2 className="text-xl font-semibold tracking-tight">
                                Adaptá tu CV a una oferta con revisión humana
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Pegá la oferta, revisá exactamente qué datos se
                                enviarán y autorizá el análisis. Codex propondrá
                                una estrategia; nada se guarda hasta que vos
                                confirmes.
                            </p>
                        </div>
                        <div className="rounded-lg border bg-background px-4 py-3 text-sm">
                            <p className="font-medium">Perfil Maestro</p>
                            <dl className="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-muted-foreground">
                                <div className="flex justify-between gap-2">
                                    <dt>Experiencias</dt>
                                    <dd>{summary.work_experiences}</dd>
                                </div>
                                <div className="flex justify-between gap-2">
                                    <dt>Estudios</dt>
                                    <dd>{summary.education_entries}</dd>
                                </div>
                                <div className="flex justify-between gap-2">
                                    <dt>Cursos/cert.</dt>
                                    <dd>{summary.certifications}</dd>
                                </div>
                                <div className="flex justify-between gap-2">
                                    <dt>Habilidades</dt>
                                    <dd>{summary.skills}</dd>
                                </div>
                                <div className="flex justify-between gap-2">
                                    <dt>Proyectos</dt>
                                    <dd>{summary.projects}</dd>
                                </div>
                                <div className="flex justify-between gap-2">
                                    <dt>Perfiles</dt>
                                    <dd>
                                        {summary.profiles} (
                                        {summary.active_profiles} activos)
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </Card>

            <form onSubmit={submit} className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Oferta laboral</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="offer">Texto de la oferta *</Label>
                            <textarea
                                id="offer"
                                className={textareaClassName}
                                value={offer}
                                onChange={(event) =>
                                    onOfferChange(event.target.value)
                                }
                                maxLength={30000}
                                placeholder="Pegá aquí la descripción completa del puesto, requisitos y responsabilidades."
                                disabled={processing}
                                aria-describedby="offer-help"
                            />
                            <p
                                id="offer-help"
                                className="text-xs text-muted-foreground"
                            >
                                {offer.length.toLocaleString('es-AR')} / 30.000
                                caracteres. La oferta se trata como contenido no
                                confiable y no se guarda.
                            </p>
                        </div>

                        <div className="max-w-md space-y-1.5">
                            <Label htmlFor="strategy">Estrategia</Label>
                            <select
                                id="strategy"
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                                value={strategy}
                                onChange={(event) =>
                                    onStrategyChange(
                                        event.target
                                            .value as CvAdaptationStrategy,
                                    )
                                }
                                disabled={processing}
                            >
                                <option value="auto">
                                    Recomendada por Codex
                                </option>
                                <option value="reuse">
                                    Reutilizar un perfil activo
                                </option>
                                <option value="duplicate">
                                    Duplicar y adaptar un perfil
                                </option>
                                <option value="create">
                                    Crear un perfil nuevo
                                </option>
                            </select>
                            <p className="text-xs text-muted-foreground">
                                Si elegís una estrategia, Codex debe respetarla.
                                Si dejás la recomendación automática, elegirá
                                entre las tres operaciones.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <LockKeyhole aria-hidden="true" />
                            Datos que se enviarán a {dataNotice.destination}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-5">
                        <div className="grid gap-4 lg:grid-cols-2">
                            <div className="rounded-lg border border-emerald-200 bg-emerald-50/60 p-4">
                                <p className="mb-2 text-sm font-medium text-emerald-950">
                                    Se enviará
                                </p>
                                <ul className="space-y-1.5 text-sm text-emerald-900">
                                    {dataNotice.sent.map((item) => (
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
                                    No se enviará
                                </p>
                                <ul className="space-y-1.5 text-sm text-rose-900">
                                    {dataNotice.excluded.map((item) => (
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
                                id="consent"
                                checked={consent}
                                onCheckedChange={(checked) =>
                                    setConsent(checked === true)
                                }
                                disabled={processing}
                            />
                            <div className="space-y-1">
                                <Label
                                    htmlFor="consent"
                                    className="cursor-pointer leading-5"
                                >
                                    Entiendo y autorizo el envío de estos datos
                                    a {dataNotice.destination} para este
                                    análisis.
                                </Label>
                                <p className="text-xs text-muted-foreground">
                                    Sin esta confirmación no se envía
                                    información. El análisis no persiste la
                                    oferta ni el prompt. Al confirmar, se
                                    guardará una evaluación histórica revisada;
                                    no se guarda la oferta completa.
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {error ? (
                    <Alert variant="destructive">
                        <AlertCircle aria-hidden="true" />
                        <div className="col-start-2 space-y-1">
                            <p className="font-medium">
                                No se pudo completar el análisis
                            </p>
                            <p className="text-sm break-words">{error}</p>
                        </div>
                    </Alert>
                ) : null}

                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <p className="text-xs text-muted-foreground">
                        El análisis puede tardar alrededor de un minuto. No se
                        guarda ningún cambio hasta la confirmación final.
                    </p>
                    <div className="flex flex-col gap-2 sm:items-end">
                        <p className="text-xs text-muted-foreground">
                            Modelo configurado:{' '}
                            <span className="font-medium text-foreground">
                                {dataNotice.model_label}
                            </span>{' '}
                            · Esfuerzo:{' '}
                            <span className="font-medium text-foreground">
                                {dataNotice.reasoning_effort_label}
                            </span>
                        </p>
                        <Button
                            type="submit"
                            className="w-full sm:w-auto"
                            disabled={
                                !consent || offer.trim() === '' || processing
                            }
                        >
                            {processing ? (
                                <>
                                    <Spinner /> Analizando oferta…
                                </>
                            ) : (
                                <>
                                    Analizar oferta <ArrowRight />
                                </>
                            )}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    );
}
