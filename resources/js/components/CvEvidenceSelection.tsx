import { useState } from 'react';
import EmptyState from '@/components/EmptyState';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import type { CvEvidence } from '@/lib/cv-preview';
import { ASSOCIATION_TYPE_LABELS, formatDateTime } from '@/lib/documents';

type CvEvidenceSelectionProps = {
    evidences: CvEvidence[];
    profileId: number;
};

export default function CvEvidenceSelection({
    evidences,
    profileId,
}: CvEvidenceSelectionProps) {
    const [excludedIds, setExcludedIds] = useState<Set<number>>(
        () => new Set(),
    );

    const selectedIds = evidences
        .filter((evidence) => !excludedIds.has(evidence.id))
        .map((evidence) => evidence.id);
    const selectedCount = selectedIds.length;

    const toggle = (id: number, checked: boolean) => {
        setExcludedIds((current) => {
            const next = new Set(current);

            if (checked) {
                next.delete(id);
            } else {
                next.add(id);
            }

            return next;
        });
    };

    return (
        <section
            aria-labelledby="cv-evidences-heading"
            className="rounded-xl border bg-background p-4 sm:p-6"
        >
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2
                        id="cv-evidences-heading"
                        className="text-base font-semibold"
                    >
                        Evidencias del CV
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Documentos asociados a los registros incluidos en el
                        perfil. Marcá los que quieras incluir en el CV
                        documentado; la selección es temporal y no se guarda.
                    </p>
                </div>
                {evidences.length > 0 ? (
                    <p
                        role="status"
                        aria-live="polite"
                        className="text-sm font-medium"
                    >
                        Seleccionadas: {selectedCount} de {evidences.length}
                    </p>
                ) : null}
            </div>

            {evidences.length === 0 ? (
                <div className="mt-4">
                    <EmptyState
                        title="Sin evidencias elegibles"
                        description="Los registros incluidos en este perfil no tienen documentos asociados."
                    />
                </div>
            ) : (
                <ul className="mt-4 divide-y">
                    {evidences.map((evidence) => {
                        const checkboxId = `cv-evidence-${evidence.id}`;
                        const selected = !excludedIds.has(evidence.id);
                        const verifiedAt = formatDateTime(
                            evidence.last_verified_at,
                        );

                        return (
                            <li
                                key={evidence.id}
                                className="flex items-start gap-3 py-3"
                            >
                                <Checkbox
                                    id={checkboxId}
                                    className="mt-0.5"
                                    checked={selected}
                                    aria-label={evidence.display_name}
                                    onCheckedChange={(checked) =>
                                        toggle(evidence.id, checked === true)
                                    }
                                />
                                <div className="min-w-0 flex-1 space-y-1.5">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Label
                                            htmlFor={checkboxId}
                                            className="text-sm font-medium break-words"
                                        >
                                            {evidence.display_name}
                                        </Label>
                                        <Badge
                                            variant={
                                                evidence.is_available
                                                    ? 'secondary'
                                                    : 'destructive'
                                            }
                                        >
                                            {evidence.is_available
                                                ? 'Disponible'
                                                : 'No disponible'}
                                        </Badge>
                                    </div>
                                    {verifiedAt ? (
                                        <p className="text-xs text-muted-foreground">
                                            Última verificación: {verifiedAt}
                                        </p>
                                    ) : null}
                                    <ul
                                        className="flex flex-wrap gap-1"
                                        aria-label={`Registros asociados a ${evidence.display_name}`}
                                    >
                                        {evidence.associations.map(
                                            (association) => (
                                                <li
                                                    key={`${association.type}-${association.id}`}
                                                >
                                                    <Badge variant="outline">
                                                        {
                                                            ASSOCIATION_TYPE_LABELS[
                                                                association.type
                                                            ]
                                                        }
                                                        : {association.label}
                                                    </Badge>
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}

            {evidences.length > 0 ? (
                <form
                    method="get"
                    action="/cv/documentado"
                    className="mt-4 flex flex-col gap-3 border-t pt-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <input type="hidden" name="profile" value={profileId} />
                    {selectedIds.map((id) => (
                        <input
                            key={id}
                            type="hidden"
                            name="documents[]"
                            value={id}
                        />
                    ))}
                    <p className="text-sm text-muted-foreground">
                        {selectedCount === 0
                            ? 'Seleccioná al menos una evidencia para generar el CV documentado.'
                            : 'El CV se incluirá primero y luego las evidencias marcadas.'}
                    </p>
                    <Button
                        type="submit"
                        disabled={selectedCount === 0}
                        className="w-full sm:w-auto"
                    >
                        Descargar CV documentado
                    </Button>
                </form>
            ) : null}
        </section>
    );
}
