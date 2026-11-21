import {
    AlertCircle,
    Check,
    Eye,
    FileText,
    Quote,
    RefreshCw,
    Sparkles,
    Trash2,
} from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import type {
    ImportAnalysis,
    ImportField,
    ImportFieldKey,
    ImportPageOrigin,
    ImportSkill,
} from '@/lib/certification-import';
import {
    fieldStateLabel,
    formatImportDate,
    formatImportPage,
    importFieldLabel,
    importPageOriginLabel,
} from '@/lib/certification-import';

type ReviewValues = {
    name: string;
    institution: string;
    date_year: number | '';
    date_month: number | '';
    duration: string;
    code: string;
    validation_url: string;
    category: string;
    skill_ids: number[];
};

type SimpleFieldKey = Exclude<ImportFieldKey, 'date' | 'category'>;

function stateBadge(field: ImportField) {
    if (field.state === 'found') {
        return (
            <Badge
                variant="outline"
                className="border-emerald-300 bg-emerald-50 text-emerald-800"
            >
                {fieldStateLabel(field.state)}
            </Badge>
        );
    }

    if (field.state === 'suggested') {
        return (
            <Badge
                variant="outline"
                className="border-amber-300 bg-amber-50 text-amber-900"
            >
                {fieldStateLabel(field.state)}
            </Badge>
        );
    }

    if (field.state === 'unverified') {
        return (
            <Badge
                variant="outline"
                className="border-amber-300 bg-amber-50 text-amber-900"
            >
                Sin respaldo verificado
            </Badge>
        );
    }

    return <Badge variant="outline">{fieldStateLabel(field.state)}</Badge>;
}

function SupportLine({
    field,
    origin,
}: {
    field: ImportField;
    origin?: ImportPageOrigin;
}) {
    if (field.support === null) {
        return null;
    }

    return (
        <div className="space-y-1 rounded-md border-l-2 border-muted-foreground/30 bg-muted/20 px-3 py-2 text-xs text-muted-foreground">
            <p className="flex gap-1.5">
                <Quote aria-hidden="true" className="mt-0.5 size-3 shrink-0" />
                <span className="break-words">{field.support}</span>
            </p>
            <p>
                {formatImportPage(field.page)}
                {field.page !== null && origin
                    ? ` · ${importPageOriginLabel(origin)}`
                    : ''}
            </p>
        </div>
    );
}

function SimpleFieldRow({
    fieldKey,
    field,
    currentValue,
    pageOrigin,
    disabled,
    onApply,
}: {
    fieldKey: SimpleFieldKey;
    field: ImportField;
    currentValue: string;
    pageOrigin?: ImportPageOrigin;
    disabled: boolean;
    onApply: (value: string) => void;
}) {
    const applied = field.value !== null && currentValue === field.value;
    const hasCurrent = currentValue.trim() !== '';

    return (
        <div className="space-y-2 rounded-lg border p-3">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="text-sm font-medium">
                    {importFieldLabel(fieldKey)}
                </p>
                {stateBadge(field)}
            </div>

            {field.state === 'absent' || field.value === null ? (
                <p className="text-sm text-muted-foreground">
                    No se encontró este dato en el documento.
                </p>
            ) : (
                <>
                    {hasCurrent ? (
                        <p className="text-xs text-muted-foreground">
                            Valor actual:{' '}
                            <span className="break-words">{currentValue}</span>
                        </p>
                    ) : null}
                    <p className="text-sm font-medium break-words">
                        {field.value}
                    </p>
                    <SupportLine field={field} origin={pageOrigin} />
                    {field.reason ? (
                        <p className="text-xs text-muted-foreground">
                            {field.state === 'unverified'
                                ? 'Aviso'
                                : 'Fundamento'}
                            : {field.reason}
                        </p>
                    ) : null}
                    <Button
                        type="button"
                        size="sm"
                        variant={applied ? 'secondary' : 'outline'}
                        disabled={disabled || applied}
                        aria-label={`${
                            applied
                                ? 'Aplicado'
                                : hasCurrent
                                  ? 'Reemplazar por propuesta'
                                  : 'Aplicar'
                        } ${importFieldLabel(fieldKey)}`}
                        onClick={() => onApply(field.value ?? '')}
                    >
                        <Check />
                        {applied
                            ? 'Aplicado'
                            : hasCurrent
                              ? 'Reemplazar por propuesta'
                              : 'Aplicar'}
                    </Button>
                </>
            )}
        </div>
    );
}

export default function CertificationImportReview({
    analysis,
    current,
    originalUrl,
    acceptedCategory,
    pageOrigins = {},
    onApplyField,
    onApplyDate,
    onApplyCategory,
    onAcceptCategory,
    onToggleSkill,
    onRetryOcr,
    onDiscard,
    onCancel,
    disabled = false,
}: {
    analysis: ImportAnalysis;
    current: ReviewValues;
    originalUrl: string | null;
    acceptedCategory: boolean;
    pageOrigins?: Record<number, ImportPageOrigin>;
    onApplyField: (field: SimpleFieldKey, value: string) => void;
    onApplyDate: (year: number | null, month: number | null) => void;
    onApplyCategory: (value: string) => void;
    onAcceptCategory: (accepted: boolean) => void;
    onToggleSkill: (skill: ImportSkill, checked: boolean) => void;
    onRetryOcr?: () => void;
    onDiscard: () => void;
    onCancel: () => void;
    disabled?: boolean;
}) {
    const { proposal } = analysis;
    const fields = proposal.fields;
    const category = fields.category;
    const date = fields.date;
    const simpleFields: SimpleFieldKey[] = [
        'name',
        'institution',
        'duration',
        'code',
        'validation_url',
    ];

    return (
        <Card className="border-primary/30">
            <CardHeader>
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="space-y-1">
                        <Badge variant="outline" className="gap-1.5">
                            <Sparkles aria-hidden="true" />
                            Revisión humana obligatoria
                        </Badge>
                        <CardTitle>Propuesta de importación</CardTitle>
                        <p className="text-sm text-muted-foreground">
                            Los campos vacíos se completaron automáticamente con
                            la propuesta; tus valores escritos se conservaron.
                            Podés editar o quitar cualquier valor. Las citas o
                            páginas no verificadas quedan como sugerencias sin
                            respaldo. Nada se guarda hasta la confirmación
                            final.
                        </p>
                    </div>
                    {originalUrl ? (
                        <Button asChild variant="outline" size="sm">
                            <a
                                href={originalUrl}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <Eye /> Consultar PDF original
                            </a>
                        </Button>
                    ) : null}
                </div>
            </CardHeader>

            <CardContent className="space-y-5">
                {proposal.sufficiency_note ? (
                    <Alert>
                        <FileText aria-hidden="true" />
                        <AlertTitle>Nota del análisis</AlertTitle>
                        <AlertDescription>
                            {proposal.sufficiency_note}
                        </AlertDescription>
                    </Alert>
                ) : null}

                {proposal.ambiguities.length > 0 ? (
                    <Alert>
                        <AlertCircle aria-hidden="true" />
                        <AlertTitle>Datos ambiguos</AlertTitle>
                        <AlertDescription>
                            <ul className="list-disc space-y-1 pl-4">
                                {proposal.ambiguities.map((item, index) => (
                                    <li key={`${index}-${item}`}>{item}</li>
                                ))}
                            </ul>
                            <p className="mt-2">
                                No se eligió ningún valor por vos. Completá esos
                                datos manualmente si corresponde.
                            </p>
                        </AlertDescription>
                    </Alert>
                ) : null}

                <div className="grid gap-3 md:grid-cols-2">
                    {simpleFields.map((fieldKey) => (
                        <SimpleFieldRow
                            key={fieldKey}
                            fieldKey={fieldKey}
                            field={fields[fieldKey]}
                            currentValue={String(current[fieldKey] ?? '')}
                            pageOrigin={
                                fields[fieldKey].page === null
                                    ? undefined
                                    : pageOrigins[fields[fieldKey].page]
                            }
                            disabled={disabled}
                            onApply={(value) => onApplyField(fieldKey, value)}
                        />
                    ))}

                    <div className="space-y-2 rounded-lg border p-3">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <p className="text-sm font-medium">Fecha</p>
                            {date.state === 'found' ? (
                                <Badge
                                    variant="outline"
                                    className="border-emerald-300 bg-emerald-50 text-emerald-800"
                                >
                                    Encontrada
                                </Badge>
                            ) : date.state === 'unverified' ? (
                                <Badge
                                    variant="outline"
                                    className="border-amber-300 bg-amber-50 text-amber-900"
                                >
                                    Sin respaldo verificado
                                </Badge>
                            ) : (
                                <Badge variant="outline">No encontrada</Badge>
                            )}
                        </div>

                        {date.state === 'absent' ? (
                            <p className="text-sm text-muted-foreground">
                                No se encontró una fecha explícita. No se
                                completó ningún componente por inferencia.
                            </p>
                        ) : (
                            <>
                                <p className="text-sm font-medium">
                                    {formatImportDate(date)}
                                </p>
                                {date.month === null ? (
                                    <p className="text-xs text-muted-foreground">
                                        El mes no está informado; quedará vacío.
                                    </p>
                                ) : null}
                                {date.support ? (
                                    <div className="space-y-1 rounded-md border-l-2 border-muted-foreground/30 bg-muted/20 px-3 py-2 text-xs text-muted-foreground">
                                        <p className="flex gap-1.5">
                                            <Quote
                                                aria-hidden="true"
                                                className="mt-0.5 size-3 shrink-0"
                                            />
                                            <span className="break-words">
                                                {date.support}
                                            </span>
                                        </p>
                                        <p>
                                            {formatImportPage(date.page)}
                                            {date.page !== null &&
                                            pageOrigins[date.page]
                                                ? ` · ${importPageOriginLabel(pageOrigins[date.page])}`
                                                : ''}
                                        </p>
                                    </div>
                                ) : null}
                                {date.reason ? (
                                    <p className="text-xs text-muted-foreground">
                                        Aviso: {date.reason}
                                    </p>
                                ) : null}
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    disabled={
                                        disabled ||
                                        (current.date_year === date.year &&
                                            current.date_month ===
                                                (date.month ?? ''))
                                    }
                                    onClick={() =>
                                        onApplyDate(date.year, date.month)
                                    }
                                >
                                    <Check /> Aplicar fecha
                                </Button>
                            </>
                        )}
                    </div>

                    <div className="space-y-2 rounded-lg border p-3">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <p className="text-sm font-medium">Categoría</p>
                            {stateBadge(category)}
                        </div>

                        {category.state === 'absent' ||
                        category.value === null ? (
                            <p className="text-sm text-muted-foreground">
                                No se encontró una categoría. Si se hubiera
                                inferido, requeriría tu aceptación.
                            </p>
                        ) : (
                            <>
                                <p className="text-sm font-medium break-words">
                                    {category.value}
                                </p>
                                {category.support ? (
                                    <SupportLine
                                        field={category}
                                        origin={
                                            category.page === null
                                                ? undefined
                                                : pageOrigins[category.page]
                                        }
                                    />
                                ) : null}
                                {category.reason ? (
                                    <p className="text-xs text-muted-foreground">
                                        {category.state === 'unverified'
                                            ? 'Aviso'
                                            : 'Fundamento'}
                                        : {category.reason}
                                    </p>
                                ) : null}

                                {category.state === 'suggested' ||
                                category.state === 'unverified' ? (
                                    <div className="flex items-start gap-2 rounded-md border bg-amber-50/50 p-2">
                                        <Checkbox
                                            id="accept-import-category"
                                            checked={acceptedCategory}
                                            onCheckedChange={(checked) =>
                                                onAcceptCategory(
                                                    checked === true,
                                                )
                                            }
                                            disabled={disabled}
                                        />
                                        <Label
                                            htmlFor="accept-import-category"
                                            className="cursor-pointer text-xs leading-5"
                                        >
                                            {category.state === 'unverified'
                                                ? 'Acepto esta categoría como sugerencia sin respaldo verificado.'
                                                : 'Acepto esta categoría sugerida. Es una inferencia, no un dato acreditado.'}
                                        </Label>
                                    </div>
                                ) : category.state === 'found' ? (
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        aria-label="Aplicar categoría"
                                        disabled={
                                            disabled ||
                                            current.category === category.value
                                        }
                                        onClick={() =>
                                            onApplyCategory(
                                                category.value ?? '',
                                            )
                                        }
                                    >
                                        <Check />{' '}
                                        {current.category === category.value
                                            ? 'Aplicada'
                                            : current.category.trim() !== ''
                                              ? 'Reemplazar por propuesta'
                                              : 'Aplicar'}
                                    </Button>
                                ) : null}
                            </>
                        )}
                    </div>
                </div>

                <div className="space-y-3">
                    <div>
                        <p className="text-sm font-medium">
                            Habilidades propuestas
                        </p>
                        <p className="text-xs text-muted-foreground">
                            Solo se asocian habilidades existentes del catálogo.
                            Las sugeridas requieren aceptación individual y
                            ninguna acredita dominio profesional.
                        </p>
                    </div>

                    {proposal.skills.length === 0 ? (
                        <p className="rounded-lg border bg-muted/20 p-3 text-sm text-muted-foreground">
                            No se propusieron habilidades del catálogo.
                        </p>
                    ) : (
                        <div className="grid gap-2 md:grid-cols-2">
                            {proposal.skills.map((skill) => {
                                const checked = current.skill_ids.includes(
                                    skill.id,
                                );

                                return (
                                    <div
                                        key={skill.id}
                                        className="space-y-2 rounded-lg border p-3"
                                    >
                                        <div className="flex items-start gap-2">
                                            <Checkbox
                                                id={`import-skill-${skill.id}`}
                                                checked={checked}
                                                onCheckedChange={(value) =>
                                                    onToggleSkill(
                                                        skill,
                                                        value === true,
                                                    )
                                                }
                                                disabled={disabled}
                                            />
                                            <div className="min-w-0 flex-1 space-y-1">
                                                <Label
                                                    htmlFor={`import-skill-${skill.id}`}
                                                    className="cursor-pointer text-sm leading-5"
                                                >
                                                    {skill.state === 'suggested'
                                                        ? `Aceptar sugerencia: ${skill.name}`
                                                        : `Incluir ${skill.name}`}
                                                </Label>
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        skill.state ===
                                                        'suggested'
                                                            ? 'border-amber-300 bg-amber-50 text-amber-900'
                                                            : 'border-emerald-300 bg-emerald-50 text-emerald-800'
                                                    }
                                                >
                                                    {skill.state === 'suggested'
                                                        ? 'Sugerencia'
                                                        : 'Mencionada'}
                                                </Badge>
                                                {skill.support ? (
                                                    <SupportLine
                                                        field={{
                                                            value: skill.name,
                                                            state: skill.state,
                                                            support:
                                                                skill.support,
                                                            page: skill.page,
                                                            reason: skill.reason,
                                                        }}
                                                        origin={
                                                            skill.page === null
                                                                ? undefined
                                                                : pageOrigins[
                                                                      skill.page
                                                                  ]
                                                        }
                                                    />
                                                ) : null}
                                                {skill.reason ? (
                                                    <p className="text-xs text-muted-foreground">
                                                        Fundamento:{' '}
                                                        {skill.reason}
                                                    </p>
                                                ) : null}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>

                {onRetryOcr ? (
                    <div className="flex flex-col gap-2 rounded-lg border bg-muted/20 p-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="max-w-xl text-xs text-muted-foreground">
                            Si el documento tiene partes escaneadas que no
                            aparecen en esta propuesta, podés volver a extraerlo
                            con OCR local. Se descartará esta propuesta y
                            deberás autorizar el nuevo envío.
                        </p>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={onRetryOcr}
                            disabled={disabled}
                        >
                            <RefreshCw /> Reintentar con OCR local
                        </Button>
                    </div>
                ) : null}

                <div className="flex flex-col gap-3 border-t pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p className="max-w-xl text-xs text-muted-foreground">
                        La confirmación final se realiza con «Crear
                        certificación y asociar PDF» al final del formulario.
                        Consentir el envío o aplicar un campo no guarda el
                        registro.
                    </p>
                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={onDiscard}
                            disabled={disabled}
                        >
                            <Trash2 /> Descartar propuesta y texto
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onCancel}
                            disabled={disabled}
                        >
                            Cancelar importación
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
