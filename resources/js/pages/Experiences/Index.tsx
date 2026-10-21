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
import type { SkillOption } from '@/components/SkillMultiSelect';

type ExperienceRecord = {
    id: number;
    company: string;
    position: string;
    start_year: number | null;
    start_month: number | null;
    end_year: number | null;
    end_month: number | null;
    is_current: boolean;
    documents_count: number;
    skills: SkillOption[];
};

export default function Index({ records }: { records: ExperienceRecord[] }) {
    const [deleteTarget, setDeleteTarget] = useState<ExperienceRecord | null>(
        null,
    );
    const [processing, setProcessing] = useState(false);

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        setProcessing(true);

        router.delete(`/experiences/${deleteTarget.id}`, {
            onFinish: () => {
                setProcessing(false);
                setDeleteTarget(null);
            },
        });
    };

    return (
        <AppLayout title="Experiencia laboral">
            <Head title="Experiencia" />
            <div className="mb-4 flex justify-end">
                <Button asChild>
                    <Link href="/experiences/create">
                        <Plus /> Nueva experiencia
                    </Link>
                </Button>
            </div>

            {records.length === 0 ? (
                <EmptyState
                    title="Sin experiencias registradas"
                    description="Registrá tu primera experiencia laboral para comenzar a construir tu trayectoria profesional."
                    actionLabel="Nueva experiencia"
                    actionHref="/experiences/create"
                />
            ) : (
                <div className="space-y-3">
                    {records.map((record) => (
                        <Card key={record.id}>
                            <CardContent className="flex flex-col gap-3 pt-6 sm:flex-row sm:items-start sm:justify-between">
                                <div className="space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="font-medium">
                                            {record.position}
                                        </h2>
                                        <span className="text-sm text-muted-foreground">
                                            {record.company}
                                        </span>
                                        {record.is_current ? (
                                            <Badge>Actual</Badge>
                                        ) : null}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {formatRange(
                                            record.start_year,
                                            record.start_month,
                                            record.end_year,
                                            record.end_month,
                                            record.is_current,
                                        )}
                                    </p>
                                    {record.skills.length > 0 ? (
                                        <div className="flex flex-wrap gap-1 pt-1">
                                            {record.skills.map((skill) => (
                                                <Badge
                                                    key={skill.id}
                                                    variant="secondary"
                                                >
                                                    {skill.name}
                                                </Badge>
                                            ))}
                                        </div>
                                    ) : null}
                                </div>
                                <div className="flex gap-1">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        asChild
                                        aria-label={`Editar ${record.position}`}
                                    >
                                        <Link
                                            href={`/experiences/${record.id}/edit`}
                                        >
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`Eliminar ${record.position}`}
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
                title="¿Eliminar esta experiencia?"
                description={
                    deleteTarget
                        ? `Se eliminará "${deleteTarget.position}" en ${deleteTarget.company}.${
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
