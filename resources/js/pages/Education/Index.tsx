import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import DeleteConfirmDialog from '@/components/DeleteConfirmDialog';
import EmptyState from '@/components/EmptyState';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout';
import { formatRange } from '@/lib/dates';

type EducationRecord = {
    id: number;
    institution: string;
    program: string;
    degree_level: string | null;
    start_year: number | null;
    start_month: number | null;
    end_year: number | null;
    end_month: number | null;
    is_in_progress: boolean;
    documents_count: number;
};

export default function Index({ records }: { records: EducationRecord[] }) {
    const [deleteTarget, setDeleteTarget] = useState<EducationRecord | null>(
        null,
    );
    const [processing, setProcessing] = useState(false);

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        setProcessing(true);

        router.delete(`/education/${deleteTarget.id}`, {
            onFinish: () => {
                setProcessing(false);
                setDeleteTarget(null);
            },
        });
    };

    return (
        <AppLayout title="Educación">
            <Head title="Educación" />
            <div className="mb-4 flex justify-end">
                <Button asChild>
                    <Link href="/education/create">
                        <Plus /> Nueva formación
                    </Link>
                </Button>
            </div>

            {records.length === 0 ? (
                <EmptyState
                    title="Sin formación registrada"
                    description="Registrá tus estudios para respaldar tu trayectoria profesional."
                    actionLabel="Nueva formación"
                    actionHref="/education/create"
                />
            ) : (
                <div className="space-y-3">
                    {records.map((record) => (
                        <Card key={record.id}>
                            <CardContent className="flex flex-col gap-3 pt-6 sm:flex-row sm:items-start sm:justify-between">
                                <div className="space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="font-medium">
                                            {record.program}
                                        </h2>
                                        <span className="text-sm text-muted-foreground">
                                            {record.institution}
                                        </span>
                                        {record.is_in_progress ? (
                                            <Badge>En curso</Badge>
                                        ) : null}
                                        {record.degree_level ? (
                                            <Badge variant="outline">
                                                {record.degree_level}
                                            </Badge>
                                        ) : null}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {formatRange(
                                            record.start_year,
                                            record.start_month,
                                            record.end_year,
                                            record.end_month,
                                            record.is_in_progress,
                                            'en curso',
                                        )}
                                    </p>
                                </div>
                                <div className="flex gap-1">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        asChild
                                        aria-label={`Editar ${record.program}`}
                                    >
                                        <Link
                                            href={`/education/${record.id}/edit`}
                                        >
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`Eliminar ${record.program}`}
                                        onClick={() => setDeleteTarget(record)}
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}

            <DeleteConfirmDialog
                open={deleteTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleteTarget(null);
                    }
                }}
                title="¿Eliminar esta formación?"
                description={
                    deleteTarget
                        ? `Se eliminará "${deleteTarget.program}" de ${deleteTarget.institution}.${
                              deleteTarget.documents_count > 0
                                  ? ` Sus ${deleteTarget.documents_count} documento(s) quedarán sin asociar y se conservarán en el repositorio.`
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
