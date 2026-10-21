import { Label } from '@/components/ui/label';
import {
    ASSOCIATION_TYPE_LABELS,
    optionsForType,
    type DocumentAssociationOptions,
} from '@/lib/documents';
import { cn } from '@/lib/utils';

export const selectClassName =
    'h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50';

type AssociationPickerProps = {
    options: DocumentAssociationOptions;
    type: string;
    entityId: string;
    onTypeChange: (value: string) => void;
    onEntityChange: (value: string) => void;
    idPrefix?: string;
    typeError?: string;
    entityError?: string;
};

export default function AssociationPicker({
    options,
    type,
    entityId,
    onTypeChange,
    onEntityChange,
    idPrefix = 'association',
    typeError,
    entityError,
}: AssociationPickerProps) {
    const entities = optionsForType(options, type);
    const typeLabel =
        type === ''
            ? ''
            : ASSOCIATION_TYPE_LABELS[
                  type as keyof typeof ASSOCIATION_TYPE_LABELS
              ];

    return (
        <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
                <Label htmlFor={`${idPrefix}-type`}>Tipo de entidad</Label>
                <select
                    id={`${idPrefix}-type`}
                    value={type}
                    className={cn(selectClassName, 'w-full')}
                    aria-invalid={Boolean(typeError)}
                    onChange={(event) => {
                        onTypeChange(event.target.value);
                        onEntityChange('');
                    }}
                >
                    <option value="">Sin asociar</option>
                    {(
                        Object.keys(ASSOCIATION_TYPE_LABELS) as Array<
                            keyof typeof ASSOCIATION_TYPE_LABELS
                        >
                    ).map((key) => (
                        <option key={key} value={key}>
                            {ASSOCIATION_TYPE_LABELS[key]}
                        </option>
                    ))}
                </select>
                {typeError ? (
                    <p className="text-sm text-destructive">{typeError}</p>
                ) : null}
            </div>
            <div className="space-y-1.5">
                <Label htmlFor={`${idPrefix}-entity`}>Entidad</Label>
                <select
                    id={`${idPrefix}-entity`}
                    value={entityId}
                    disabled={type === ''}
                    className={cn(selectClassName, 'w-full')}
                    aria-invalid={Boolean(entityError)}
                    onChange={(event) => onEntityChange(event.target.value)}
                >
                    <option value="">
                        {type === '' ? 'Elegí un tipo primero' : 'Seleccionar…'}
                    </option>
                    {entities.map((entity) => (
                        <option key={entity.id} value={entity.id}>
                            {entity.label}
                        </option>
                    ))}
                </select>
                {type !== '' && entities.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No hay {typeLabel.toLowerCase()}es registradas todavía.
                    </p>
                ) : null}
                {entityError ? (
                    <p className="text-sm text-destructive">{entityError}</p>
                ) : null}
            </div>
        </div>
    );
}
