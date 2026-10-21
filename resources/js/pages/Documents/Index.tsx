import { Head, Link, router, usePage } from '@inertiajs/react';
import { Eye, Link2, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import AssociateDocumentDialog from '@/components/documents/AssociateDocumentDialog';
import { selectClassName } from '@/components/documents/AssociationPicker';
import DocumentMetaDialog from '@/components/documents/DocumentMetaDialog';
import VerificationReport from '@/components/documents/VerificationReport';
import DeleteConfirmDialog from '@/components/DeleteConfirmDialog';
import EmptyState from '@/components/EmptyState';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout';
import {
    ASSOCIATION_TYPE_LABELS,
    formatBytes,
    formatDate,
    optionsForType,
    type DocumentAssociationOptions,
    type DocumentRecord,
} from '@/lib/documents';
import { cn } from '@/lib/utils';

type RepositoryFilters = {
    q: string;
    status: 'all' | 'linked' | 'unlinked';
    entity_type: string;
    entity_id: string;
};

export default function Index({
    documents,
    filters,
    associationOptions,
    filterOptions,
}: {
    documents: DocumentRecord[];
    filters: RepositoryFilters;
    associationOptions: DocumentAssociationOptions;
    filterOptions: DocumentAssociationOptions;
}) {
    const { flash } = usePage().props;
    const verification = flash?.verification ?? null;

    const [search, setSearch] = useState(filters.q);
    const [status, setStatus] = useState<RepositoryFilters['status']>(
        filters.status,
    );
    const [entityType, setEntityType] = useState(filters.entity_type);
    const [entityId, setEntityId] = useState(filters.entity_id);

    const [associateTarget, setAssociateTarget] =
        useState<DocumentRecord | null>(null);
    const [editTarget, setEditTarget] = useState<DocumentRecord | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<DocumentRecord | null>(
        null,
    );
    const [processing, setProcessing] = useState(false);

    const hasFilters =
        search !== '' ||
        status !== 'all' ||
        entityType !== '' ||
        entityId !== '';

    const applyFilters = (next: {
        search?: string;
        status?: string;
        entityType?: string;
        entityId?: string;
    }) => {
        const q = next.search ?? search;
        const st = next.status ?? status;
        const et = next.entityType ?? entityType;
        const eid = next.entityId ?? entityId;

        const params: Record<string, string> = {};

        if (q !== '') {
            params.q = q;
        }

        if (st !== 'all') {
            params.status = st;
        }

        if (et !== '') {
            params.entity_type = et;

            if (eid !== '') {
                params.entity_id = eid;
            }
        }

        router.get('/documents', params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearch('');
        setStatus('all');
        setEntityType('');
        setEntityId('');
        router.get('/documents', {}, { preserveScroll: true });
    };

    const verifyIntegrity = () => {
        router.post('/documents/verify', {}, { preserveScroll: true });
    };

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        setProcessing(true);

        router.delete(`/documents/${deleteTarget.id}`, {
            onFinish: () => {
                setProcessing(false);
                setDeleteTarget(null);
            },
        });
    };

    return (
        <AppLayout title="Documentos y evidencias">
            <Head title="Documentos" />

            {verification ? (
                <div className="mb-4">
                    <VerificationReport result={verification} />
                </div>
            ) : null}

            <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
                <form
                    className="flex flex-wrap items-end gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        applyFilters({ search });
                    }}
                >
                    <Input
                        value={search}
                        aria-label="Buscar documentos"
                        placeholder="Buscar por título o archivo"
                        className="sm:max-w-xs"
                        onChange={(event) => setSearch(event.target.value)}
                    />
                    <select
                        value={status}
                        aria-label="Estado de asociación"
                        className={selectClassName}
                        onChange={(event) => {
                            const value = event.target
                                .value as RepositoryFilters['status'];
                            setStatus(value);
                            applyFilters({ status: value });
                        }}
                    >
                        <option value="all">Todos</option>
                        <option value="linked">Asociados</option>
                        <option value="unlinked">Sin asociar</option>
                    </select>
                    <select
                        value={entityType}
                        aria-label="Filtrar por tipo de entidad"
                        className={selectClassName}
                        onChange={(event) => {
                            setEntityType(event.target.value);
                            setEntityId('');
                            applyFilters({
                                entityType: event.target.value,
                                entityId: '',
                            });
                        }}
                    >
                        <option value="">Todas las entidades</option>
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
                    {entityType !== '' ? (
                        <select
                            value={entityId}
                            aria-label="Filtrar por entidad"
                            className={cn(selectClassName, 'max-w-64')}
                            onChange={(event) => {
                                setEntityId(event.target.value);
                                applyFilters({
                                    entityId: event.target.value,
                                });
                            }}
                        >
                            <option value="">Todas</option>
                            {optionsForType(filterOptions, entityType).map(
                                (option) => (
                                    <option key={option.id} value={option.id}>
                                        {option.label}
                                    </option>
                                ),
                            )}
                        </select>
                    ) : null}
                    <Button type="submit" variant="outline">
                        Buscar
                    </Button>
                    {hasFilters ? (
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={clearFilters}
                        >
                            Limpiar
                        </Button>
                    ) : null}
                </form>
                <div className="flex gap-2">
                    <Button variant="outline" onClick={verifyIntegrity}>
                        Verificar integridad
                    </Button>
                    <Button asChild>
                        <Link href="/documents/create">
                            <Plus /> Subir documento
                        </Link>
                    </Button>
                </div>
            </div>

            {documents.length === 0 ? (
                hasFilters ? (
                    <div className="flex flex-col items-center gap-3 rounded-lg border border-dashed bg-background p-10 text-center">
                        <p className="text-sm text-muted-foreground">
                            No hay documentos que coincidan con los filtros
                            aplicados.
                        </p>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={clearFilters}
                        >
                            Limpiar filtros
                        </Button>
                    </div>
                ) : (
                    <EmptyState
                        title="Sin documentos"
                        description="Subí los PDFs originales de tus certificaciones, estudios y experiencias para conservarlos y asociarlos."
                        actionLabel="Subir documento"
                        actionHref="/documents/create"
                    />
                )
            ) : (
                <div className="space-y-3">
                    {documents.map((document) => (
                        <Card key={document.id}>
                            <CardContent className="flex flex-col gap-3 pt-6 sm:flex-row sm:items-start sm:justify-between">
                                <div className="min-w-0 space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="font-medium break-words">
                                            {document.display_name}
                                        </h2>
                                        {document.title ? (
                                            <span className="text-sm text-muted-foreground">
                                                {document.original_filename}
                                            </span>
                                        ) : null}
                                        {!document.is_available ? (
                                            <Badge variant="destructive">
                                                No disponible
                                            </Badge>
                                        ) : null}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {formatBytes(document.size_bytes)}
                                        {document.uploaded_at
                                            ? ` · Subido el ${formatDate(document.uploaded_at)}`
                                            : ''}
                                    </p>
                                    {document.associations.length > 0 ? (
                                        <div className="flex flex-wrap gap-1 pt-1">
                                            {document.associations.map(
                                                (association) => (
                                                    <Badge
                                                        key={`${association.type}-${association.id}`}
                                                        variant="secondary"
                                                    >
                                                        {
                                                            ASSOCIATION_TYPE_LABELS[
                                                                association.type
                                                            ]
                                                        }
                                                        : {association.label}
                                                    </Badge>
                                                ),
                                            )}
                                        </div>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            Sin asociar
                                        </p>
                                    )}
                                </div>
                                <div className="flex gap-1">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`Asociar ${document.display_name}`}
                                        onClick={() =>
                                            setAssociateTarget(document)
                                        }
                                    >
                                        <Link2 />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`Editar ${document.display_name}`}
                                        onClick={() => setEditTarget(document)}
                                    >
                                        <Pencil />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        asChild
                                        aria-label={`Ver ${document.display_name}`}
                                    >
                                        <Link
                                            href={`/documents/${document.id}/viewer`}
                                        >
                                            <Eye />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`Eliminar ${document.display_name}`}
                                        onClick={() =>
                                            setDeleteTarget(document)
                                        }
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}

            <AssociateDocumentDialog
                open={associateTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setAssociateTarget(null);
                    }
                }}
                document={associateTarget}
                associationOptions={associationOptions}
            />

            <DocumentMetaDialog
                key={editTarget?.id ?? 'meta-none'}
                open={editTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditTarget(null);
                    }
                }}
                document={editTarget}
            />

            <DeleteConfirmDialog
                open={deleteTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleteTarget(null);
                    }
                }}
                title="¿Eliminar este documento?"
                description={
                    deleteTarget
                        ? `Se eliminará "${deleteTarget.display_name}" y su archivo original.${
                              deleteTarget.associations.length > 0
                                  ? ` Se quitarán ${deleteTarget.associations.length} asociación(es); las entidades no se ven afectadas.`
                                  : ''
                          } Esta acción es definitiva.`
                        : undefined
                }
                processing={processing}
                onConfirm={confirmDelete}
            />
        </AppLayout>
    );
}
