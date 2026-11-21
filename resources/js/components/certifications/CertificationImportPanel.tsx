import {
    AlertCircle,
    CheckCircle2,
    Eye,
    FileText,
    LockKeyhole,
    ShieldCheck,
    Sparkles,
    Trash2,
} from 'lucide-react';
import { useEffect, useRef } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { SkillOption } from '@/components/SkillMultiSelect';
import type { CertificationImportFlow } from '@/hooks/use-certification-import';
import type {
    CertificationImportLimits,
    CertificationImportNotice,
} from '@/lib/certification-import';
import { importPageOriginShortLabel } from '@/lib/certification-import';
import { formatBytes } from '@/lib/documents';
import { SKILL_CATEGORY_LABELS } from '@/lib/skill-categories';

const textareaClassName =
    'min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 font-mono text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50';

export default function CertificationImportPanel({
    notice,
    limits,
    flow,
    existingCategory,
    existingSkillIds,
    skills = [],
    disabled = false,
}: {
    notice: CertificationImportNotice;
    limits: CertificationImportLimits;
    flow: CertificationImportFlow;
    existingCategory: string;
    existingSkillIds: number[];
    skills?: SkillOption[];
    disabled?: boolean;
}) {
    const hasFile = flow.file !== null;
    const analysisReady = flow.analysis !== null;
    const canSend =
        flow.preparation !== null &&
        flow.preparation.preparation_token !== null &&
        flow.preparation.status !== 'insufficient';
    const showConsent = canSend && !analysisReady;
    const overTextLimit = flow.totalChars > limits.max_text_chars;
    const overPageLimit = flow.pages.some(
        (page) => page.text.length > limits.max_page_chars,
    );
    const canRetryOcr =
        flow.file !== null &&
        !flow.fileInvalid &&
        !flow.preparing &&
        !flow.analyzing &&
        [
            'text_insufficient',
            'ocr_unavailable',
            'ocr_language_unavailable',
            'ocr_failed',
            'ocr_render_failed',
            'ocr_timeout',
        ].includes(flow.error?.code ?? '');
    const errorRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        if (flow.error && errorRef.current?.scrollIntoView) {
            errorRef.current.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
        }
    }, [flow.error]);

    return (
        <Card className="overflow-hidden border-primary/20">
            <CardHeader>
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="max-w-2xl space-y-2">
                        <Badge variant="outline" className="gap-1.5">
                            <Sparkles aria-hidden="true" />
                            Asistencia opcional
                        </Badge>
                        <CardTitle>Importar datos desde el PDF</CardTitle>
                        <p className="text-sm text-muted-foreground">
                            Extraé primero el texto nativo, revisá exactamente
                            qué se enviará y autorizá el análisis con{' '}
                            {notice.model_label}. Nada se guarda hasta que
                            confirmes la certificación.
                        </p>
                    </div>
                    <div className="rounded-lg border bg-muted/30 px-4 py-3 text-xs text-muted-foreground">
                        <p className="font-medium text-foreground">
                            {notice.destination}
                        </p>
                        <p>
                            Modelo: {notice.model_label} · Esfuerzo:{' '}
                            {notice.reasoning_effort_label}
                        </p>
                        <p className="mt-1 flex items-center gap-1">
                            <ShieldCheck aria-hidden="true" />
                            El PDF no se envía. OCR local.
                        </p>
                    </div>
                </div>
            </CardHeader>

            <CardContent className="space-y-5">
                <div className="space-y-1.5">
                    <Label htmlFor="import_file">
                        Certificado PDF (opcional)
                    </Label>
                    <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                        <Input
                            key={flow.fileInputKey}
                            id="import_file"
                            type="file"
                            accept="application/pdf,.pdf"
                            onChange={(event) =>
                                flow.selectFile(event.target.files?.[0] ?? null)
                            }
                            disabled={
                                disabled || flow.preparing || flow.analyzing
                            }
                            aria-describedby="import-file-help"
                        />

                        <div className="flex flex-wrap gap-2">
                            {flow.file && flow.objectUrl ? (
                                <Button asChild variant="outline" size="sm">
                                    <a
                                        href={flow.objectUrl}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        <Eye /> Ver PDF original
                                    </a>
                                </Button>
                            ) : null}
                            {flow.file ? (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => flow.selectFile(null)}
                                    disabled={disabled}
                                >
                                    <Trash2 /> Quitar PDF
                                </Button>
                            ) : null}
                            {flow.file &&
                            !flow.preparation &&
                            !analysisReady ? (
                                <Button
                                    type="button"
                                    size="sm"
                                    onClick={() => void flow.prepare()}
                                    disabled={
                                        disabled ||
                                        flow.preparing ||
                                        flow.analyzing ||
                                        flow.fileInvalid
                                    }
                                >
                                    {flow.preparing ? (
                                        <>
                                            <Spinner />{' '}
                                            {flow.preparingMode === 'ocr'
                                                ? 'Procesando OCR local…'
                                                : 'Preparando texto…'}
                                        </>
                                    ) : (
                                        <>
                                            <FileText /> Analizar PDF
                                        </>
                                    )}
                                </Button>
                            ) : null}
                        </div>
                    </div>
                    <p
                        id="import-file-help"
                        className="text-xs text-muted-foreground"
                    >
                        Un único PDF de hasta{' '}
                        {Math.round(limits.max_kilobytes / 1024)} MB. Se
                        conserva solo al confirmar; el PDF completo nunca se
                        envía a la IA.
                    </p>
                </div>

                {flow.file ? (
                    <div className="flex flex-wrap items-center gap-2 rounded-lg border bg-muted/20 px-3 py-2 text-sm">
                        <FileText aria-hidden="true" className="size-4" />
                        <span className="min-w-0 truncate font-medium">
                            {flow.file.name}
                        </span>
                        <span className="text-muted-foreground">
                            {formatBytes(flow.file.size)}
                        </span>
                        {flow.preparingMode === 'ocr' ? (
                            <span className="text-xs text-muted-foreground">
                                El OCR se ejecuta localmente y puede tardar.
                            </span>
                        ) : null}
                    </div>
                ) : null}

                {flow.fileError ? (
                    <Alert variant="destructive">
                        <AlertCircle aria-hidden="true" />
                        <AlertTitle>No se puede usar este archivo</AlertTitle>
                        <AlertDescription>{flow.fileError}</AlertDescription>
                    </Alert>
                ) : null}

                {flow.error ? (
                    <div ref={errorRef}>
                        <Alert variant="destructive">
                            <AlertCircle aria-hidden="true" />
                            <AlertTitle>
                                No se pudo completar la operación
                            </AlertTitle>
                            <AlertDescription className="space-y-2">
                                <p>{flow.error.message}</p>
                                <div className="flex flex-wrap gap-2">
                                    {!flow.fileInvalid ? (
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            onClick={() =>
                                                flow.enterManualMode()
                                            }
                                            disabled={disabled}
                                        >
                                            Seguir manualmente
                                        </Button>
                                    ) : null}
                                    {canRetryOcr ? (
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            onClick={() =>
                                                void flow.prepare(true)
                                            }
                                            disabled={disabled}
                                        >
                                            Intentar OCR local
                                        </Button>
                                    ) : null}
                                    {flow.preparation ? (
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="ghost"
                                            onClick={() =>
                                                flow.discardProposal()
                                            }
                                            disabled={disabled}
                                        >
                                            Descartar texto y propuesta
                                        </Button>
                                    ) : null}
                                </div>
                            </AlertDescription>
                        </Alert>
                    </div>
                ) : null}

                {flow.manualMode ? (
                    <Alert>
                        <FileText aria-hidden="true" />
                        <AlertTitle>Ingreso manual</AlertTitle>
                        <AlertDescription>
                            Los datos se guardan con las validaciones actuales.
                            El PDF válido que sigue seleccionado se asociará al
                            confirmar; si no lo querés asociar, quitá el archivo
                            antes de guardar.
                        </AlertDescription>
                    </Alert>
                ) : null}

                {analysisReady ? (
                    <Alert>
                        <CheckCircle2 aria-hidden="true" />
                        <AlertTitle>Propuesta generada</AlertTitle>
                        <AlertDescription>
                            Los campos vacíos se completaron con la propuesta;
                            tus datos escritos se conservan. Revisá valores,
                            soportes y sugerencias antes de confirmar. Nada se
                            guarda hasta la confirmación final.
                        </AlertDescription>
                    </Alert>
                ) : null}

                {flow.preparation ? (
                    <div className="space-y-5">
                        {flow.preparation.status === 'insufficient' ? (
                            <Alert variant="destructive">
                                <AlertCircle aria-hidden="true" />
                                <AlertTitle>
                                    El texto disponible no es suficiente
                                </AlertTitle>
                                <AlertDescription className="space-y-2">
                                    <p>{flow.preparation.message}</p>
                                    <div className="flex flex-wrap gap-2">
                                        {flow.file && !flow.fileInvalid ? (
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="outline"
                                                onClick={() =>
                                                    void flow.prepare(true)
                                                }
                                                disabled={disabled}
                                            >
                                                Reintentar OCR local
                                            </Button>
                                        ) : null}
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            onClick={() =>
                                                flow.enterManualMode()
                                            }
                                            disabled={disabled}
                                        >
                                            Ingresar manualmente
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="ghost"
                                            onClick={() =>
                                                flow.selectFile(null)
                                            }
                                            disabled={disabled}
                                        >
                                            Quitar PDF
                                        </Button>
                                    </div>
                                </AlertDescription>
                            </Alert>
                        ) : null}

                        {flow.preparation.ocr_warning ? (
                            <Alert>
                                <AlertCircle aria-hidden="true" />
                                <AlertTitle>OCR local</AlertTitle>
                                <AlertDescription>
                                    {flow.preparation.ocr_warning}
                                </AlertDescription>
                            </Alert>
                        ) : null}

                        {flow.preparation.status === 'too_long' ? (
                            <Alert variant="destructive">
                                <AlertCircle aria-hidden="true" />
                                <AlertTitle>
                                    El texto es demasiado extenso
                                </AlertTitle>
                                <AlertDescription className="space-y-2">
                                    <p>{flow.preparation.message}</p>
                                    {canSend ? (
                                        <p>
                                            Caracteres actuales:{' '}
                                            {flow.totalChars} de{' '}
                                            {limits.max_text_chars}. Podés
                                            quitar líneas o páginas completas
                                            del contenido que se enviará.
                                        </p>
                                    ) : (
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            onClick={() =>
                                                flow.enterManualMode()
                                            }
                                            disabled={disabled}
                                        >
                                            Ingresar manualmente
                                        </Button>
                                    )}
                                </AlertDescription>
                            </Alert>
                        ) : null}

                        {canSend ? (
                            <div className="grid gap-4 lg:grid-cols-2">
                                <div className="rounded-lg border border-emerald-200 bg-emerald-50/60 p-4">
                                    <p className="mb-2 text-sm font-medium text-emerald-950">
                                        {analysisReady
                                            ? 'Se envió'
                                            : 'Se enviará'}
                                    </p>
                                    <ul className="space-y-1.5 text-sm text-emerald-900">
                                        {notice.sent.map((item) => (
                                            <li
                                                key={item}
                                                className="flex gap-2"
                                            >
                                                <span aria-hidden="true">
                                                    •
                                                </span>
                                                <span className="break-words">
                                                    {item}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                                <div className="rounded-lg border border-rose-200 bg-rose-50/60 p-4">
                                    <p className="mb-2 text-sm font-medium text-rose-950">
                                        {analysisReady
                                            ? 'No se envió'
                                            : 'No se enviará'}
                                    </p>
                                    <ul className="space-y-1.5 text-sm text-rose-900">
                                        {notice.excluded.map((item) => (
                                            <li
                                                key={item}
                                                className="flex gap-2"
                                            >
                                                <span aria-hidden="true">
                                                    •
                                                </span>
                                                <span className="break-words">
                                                    {item}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                                <details className="rounded-lg border bg-muted/20 p-3 text-sm lg:col-span-2">
                                    <summary className="cursor-pointer font-medium">
                                        {analysisReady
                                            ? 'Habilidades del catálogo que se enviaron'
                                            : 'Habilidades del catálogo que se enviarán'}{' '}
                                        ({skills.length})
                                    </summary>
                                    {skills.length === 0 ? (
                                        <p className="mt-2 text-muted-foreground">
                                            No hay habilidades en el catálogo.
                                            La propuesta dejará la selección
                                            vacía.
                                        </p>
                                    ) : (
                                        <ul className="mt-2 grid gap-1 text-muted-foreground sm:grid-cols-2">
                                            {skills.map((skill) => (
                                                <li key={skill.id}>
                                                    {skill.name} ·{' '}
                                                    {SKILL_CATEGORY_LABELS[
                                                        skill.category
                                                    ] ?? skill.category}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </details>
                            </div>
                        ) : null}

                        <div className="space-y-3">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p className="flex items-center gap-2 text-sm font-medium">
                                        <LockKeyhole aria-hidden="true" />
                                        {analysisReady
                                            ? 'Contenido exacto que se envió'
                                            : canSend
                                              ? 'Contenido exacto que se enviará'
                                              : 'Texto extraído'}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {flow.pages.length} página(s) ·{' '}
                                        {flow.totalChars.toLocaleString(
                                            'es-AR',
                                        )}{' '}
                                        caracteres
                                        {flow.preparation.redactions > 0
                                            ? ` · ${flow.preparation.redactions} coincidencia(s) de datos personales retiradas`
                                            : ''}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {flow.preparation.ocr_used
                                            ? `OCR local: ${flow.preparation.ocr_languages.join('+')}`
                                            : 'Extracción: texto nativo'}
                                    </p>
                                </div>
                                {canSend ? (
                                    <p className="text-xs text-muted-foreground">
                                        Límite:{' '}
                                        {limits.max_text_chars.toLocaleString(
                                            'es-AR',
                                        )}{' '}
                                        caracteres
                                    </p>
                                ) : null}
                            </div>

                            {canSend ? (
                                <div className="space-y-3">
                                    {flow.pages.map((page) => (
                                        <details
                                            key={page.page}
                                            open={flow.pages.length === 1}
                                            className="rounded-lg border p-3"
                                        >
                                            <summary className="cursor-pointer text-sm font-medium">
                                                Página {page.page} ·{' '}
                                                {importPageOriginShortLabel(
                                                    page.origin,
                                                )}{' '}
                                                ·{' '}
                                                {page.text.length.toLocaleString(
                                                    'es-AR',
                                                )}{' '}
                                                caracteres
                                            </summary>
                                            <div className="mt-2 space-y-1">
                                                <Label
                                                    htmlFor={`import-page-${page.page}`}
                                                    className="text-xs text-muted-foreground"
                                                >
                                                    {page.origin === 'ocr' ||
                                                    page.origin === 'mixed'
                                                        ? 'Texto OCR local: revisalo con especial cuidado. '
                                                        : ''}
                                                    Podés quitar líneas con
                                                    datos que no deban enviarse.
                                                </Label>
                                                <textarea
                                                    id={`import-page-${page.page}`}
                                                    className={
                                                        textareaClassName
                                                    }
                                                    value={page.text}
                                                    onChange={(event) =>
                                                        flow.setPageText(
                                                            page.page,
                                                            event.target.value,
                                                        )
                                                    }
                                                    disabled={
                                                        disabled ||
                                                        flow.analyzing
                                                    }
                                                    aria-invalid={
                                                        page.text.length >
                                                        limits.max_page_chars
                                                    }
                                                />
                                                {page.text.length >
                                                limits.max_page_chars ? (
                                                    <p className="text-xs text-destructive">
                                                        Esta página supera el
                                                        límite de{' '}
                                                        {limits.max_page_chars.toLocaleString(
                                                            'es-AR',
                                                        )}{' '}
                                                        caracteres.
                                                    </p>
                                                ) : null}
                                            </div>
                                        </details>
                                    ))}
                                </div>
                            ) : (
                                <div className="space-y-2 rounded-lg border bg-muted/20 p-3">
                                    {flow.pages.map((page) => (
                                        <details key={page.page}>
                                            <summary className="cursor-pointer text-sm font-medium">
                                                Página {page.page} ·{' '}
                                                {importPageOriginShortLabel(
                                                    page.origin,
                                                )}
                                            </summary>
                                            <pre className="mt-2 max-h-48 overflow-auto text-xs whitespace-pre-wrap text-muted-foreground">
                                                {page.text ||
                                                    '(Sin texto en esta página)'}
                                            </pre>
                                        </details>
                                    ))}
                                </div>
                            )}

                            {canSend && (overTextLimit || overPageLimit) ? (
                                <p className="text-sm text-destructive">
                                    Reducí el contenido hasta los límites
                                    indicados para habilitar el análisis.
                                </p>
                            ) : null}
                        </div>

                        {canSend ? (
                            <>
                                <details className="rounded-lg border bg-muted/20 p-3 text-sm">
                                    <summary className="cursor-pointer font-medium">
                                        Retención y limpieza
                                    </summary>
                                    <div className="mt-2 space-y-2 text-muted-foreground">
                                        <p>
                                            <span className="font-medium text-foreground">
                                                Se conserva:
                                            </span>{' '}
                                            {notice.retention.stored.join(' ')}
                                        </p>
                                        <p>
                                            <span className="font-medium text-foreground">
                                                No se conserva:
                                            </span>{' '}
                                            {notice.retention.excluded.join(
                                                ' ',
                                            )}
                                        </p>
                                        <p>{notice.retention.note}</p>
                                    </div>
                                </details>

                                {showConsent ? (
                                    <>
                                        <div className="flex items-start gap-3 rounded-lg border bg-muted/30 p-4">
                                            <Checkbox
                                                id="import_consent"
                                                checked={flow.consent}
                                                onCheckedChange={(checked) =>
                                                    flow.setConsent(
                                                        checked === true,
                                                    )
                                                }
                                                disabled={
                                                    disabled ||
                                                    flow.preparing ||
                                                    flow.analyzing
                                                }
                                            />
                                            <div className="space-y-1">
                                                <Label
                                                    htmlFor="import_consent"
                                                    className="cursor-pointer leading-5"
                                                >
                                                    Entiendo y autorizo el envío
                                                    de este texto a{' '}
                                                    {notice.destination} con{' '}
                                                    {notice.model_label} para
                                                    este análisis. El OCR, si se
                                                    usó, se ejecutó localmente.
                                                </Label>
                                                <p className="text-xs text-muted-foreground">
                                                    Sin esta confirmación no se
                                                    envía nada. El análisis se
                                                    procesa fuera de esta
                                                    aplicación; el texto OCR
                                                    también se envía solo con
                                                    esta autorización. El
                                                    consentimiento no confirma
                                                    el registro.
                                                </p>
                                            </div>
                                        </div>

                                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <p className="text-xs text-muted-foreground">
                                                El análisis puede tardar
                                                alrededor de un minuto. Podés
                                                cancelar en cualquier momento.
                                            </p>
                                            <div className="flex flex-wrap gap-2">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() =>
                                                        flow.cancel()
                                                    }
                                                    disabled={disabled}
                                                >
                                                    Cancelar importación
                                                </Button>
                                                <Button
                                                    type="button"
                                                    onClick={() =>
                                                        void flow.analyze(
                                                            existingCategory,
                                                            existingSkillIds,
                                                        )
                                                    }
                                                    disabled={
                                                        disabled ||
                                                        !flow.canAnalyze
                                                    }
                                                >
                                                    {flow.analyzing ? (
                                                        <>
                                                            <Spinner />{' '}
                                                            Analizando…
                                                        </>
                                                    ) : (
                                                        <>
                                                            <Sparkles />{' '}
                                                            Analizar con{' '}
                                                            {notice.model_label}
                                                        </>
                                                    )}
                                                </Button>
                                            </div>
                                        </div>
                                    </>
                                ) : null}
                            </>
                        ) : null}
                    </div>
                ) : null}

                {!hasFile ? (
                    <p className="text-xs text-muted-foreground">
                        También podés crear la certificación manualmente sin
                        cargar un PDF. La asistencia nunca bloquea el alta
                        manual.
                    </p>
                ) : null}
            </CardContent>
        </Card>
    );
}
