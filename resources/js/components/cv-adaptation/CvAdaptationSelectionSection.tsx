import { ChevronDown, ChevronUp } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { RELEVANCE_OPTIONS, type Relevance } from '@/lib/cv-profiles';
import type {
    CvAdaptationContentField,
    CvAdaptationSelection,
} from '@/lib/cv-adaptation';

const RELEVANCE_WEIGHT: Record<Relevance, number> = {
    alta: 3,
    media: 2,
    baja: 1,
};

export default function CvAdaptationSelectionSection<
    T extends { ref: string },
>({
    title,
    field,
    items,
    selected,
    renderLabel,
    renderMeta,
    defaultOpen = false,
    disabled = false,
    onChange,
}: {
    title: string;
    field: CvAdaptationContentField;
    items: T[];
    selected: CvAdaptationSelection[];
    renderLabel: (item: T) => string;
    renderMeta?: (item: T) => string;
    defaultOpen?: boolean;
    disabled?: boolean;
    onChange: (selected: CvAdaptationSelection[]) => void;
}) {
    const [open, setOpen] = useState(defaultOpen);
    const selectedByRef = new Map(
        selected.map((selection) => [selection.ref, selection]),
    );

    const orderedItems = items
        .map((item, index) => ({
            item,
            index,
            selection: selectedByRef.get(item.ref),
        }))
        .sort((left, right) => {
            if (left.selection && right.selection) {
                const relevance =
                    RELEVANCE_WEIGHT[right.selection.relevance] -
                    RELEVANCE_WEIGHT[left.selection.relevance];

                return relevance !== 0 ? relevance : left.index - right.index;
            }

            if (left.selection) {
                return -1;
            }

            if (right.selection) {
                return 1;
            }

            return left.index - right.index;
        });

    const toggle = (ref: string, checked: boolean) => {
        onChange(
            checked
                ? [...selected, { ref, relevance: 'media' }]
                : selected.filter((selection) => selection.ref !== ref),
        );
    };

    const changeRelevance = (ref: string, relevance: Relevance) => {
        onChange(
            selected.map((selection) =>
                selection.ref === ref ? { ...selection, relevance } : selection,
            ),
        );
    };

    return (
        <section className="rounded-lg border bg-background">
            <button
                type="button"
                className="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition-colors hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                onClick={() => setOpen((current) => !current)}
                aria-expanded={open}
                aria-controls={`${field}-selection`}
            >
                <span className="flex min-w-0 flex-wrap items-center gap-2">
                    <span className="font-medium">{title}</span>
                    <Badge
                        variant={selected.length > 0 ? 'secondary' : 'outline'}
                    >
                        {selected.length} de {items.length} seleccionados
                    </Badge>
                </span>
                {open ? (
                    <ChevronUp aria-hidden="true" className="size-4 shrink-0" />
                ) : (
                    <ChevronDown
                        aria-hidden="true"
                        className="size-4 shrink-0"
                    />
                )}
            </button>

            {open ? (
                <div
                    id={`${field}-selection`}
                    className="space-y-2 border-t px-4 py-3"
                >
                    {items.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No hay registros disponibles en el Perfil Maestro.
                        </p>
                    ) : (
                        <div className="max-h-96 space-y-2 overflow-y-auto pr-1">
                            {orderedItems.map(({ item, selection }) => {
                                const inputId = `${field}-${item.ref}`;
                                const meta = renderMeta?.(item);

                                return (
                                    <div
                                        key={item.ref}
                                        className={
                                            selection
                                                ? 'rounded-md border border-primary/30 bg-primary/5 p-3'
                                                : 'rounded-md border p-3'
                                        }
                                    >
                                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                            <label
                                                htmlFor={inputId}
                                                className="flex items-start gap-2 text-sm"
                                            >
                                                <Checkbox
                                                    id={inputId}
                                                    checked={
                                                        selection !== undefined
                                                    }
                                                    disabled={disabled}
                                                    onCheckedChange={(
                                                        checked,
                                                    ) =>
                                                        toggle(
                                                            item.ref,
                                                            checked === true,
                                                        )
                                                    }
                                                />
                                                <span>
                                                    <span className="font-medium">
                                                        {renderLabel(item)}
                                                    </span>
                                                    {meta ? (
                                                        <span className="block text-xs text-muted-foreground">
                                                            {meta}
                                                        </span>
                                                    ) : null}
                                                </span>
                                            </label>

                                            {selection ? (
                                                <label className="flex items-center gap-2 text-xs text-muted-foreground">
                                                    Relevancia
                                                    <select
                                                        aria-label={`Relevancia de ${renderLabel(item)}`}
                                                        className="h-8 rounded-md border border-input bg-transparent px-2 text-sm"
                                                        value={
                                                            selection.relevance
                                                        }
                                                        disabled={disabled}
                                                        onChange={(event) =>
                                                            changeRelevance(
                                                                item.ref,
                                                                event.target
                                                                    .value as Relevance,
                                                            )
                                                        }
                                                    >
                                                        {RELEVANCE_OPTIONS.map(
                                                            (option) => (
                                                                <option
                                                                    key={
                                                                        option.value
                                                                    }
                                                                    value={
                                                                        option.value
                                                                    }
                                                                >
                                                                    {
                                                                        option.label
                                                                    }
                                                                </option>
                                                            ),
                                                        )}
                                                    </select>
                                                </label>
                                            ) : null}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            ) : null}
        </section>
    );
}
