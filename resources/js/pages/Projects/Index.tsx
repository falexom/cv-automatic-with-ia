import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import DeleteConfirmDialog from '@/components/DeleteConfirmDialog';
import EmptyState from '@/components/EmptyState';
import type { SkillOption } from '@/components/SkillMultiSelect';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout';
import { formatRange } from '@/lib/dates';

type ProjectRecord = {
    id: number;
    name: string;
    role: string | null;
    start_year: number | null;
    start_month: number | null;
    end_year: number | null;
    end_month: number | null;
    skills: SkillOption[];
};

export default function Index({ records }: { records: ProjectRecord[] }) {
    const [deleteTarget, setDeleteTarget] = useState<ProjectRecord | null>(
        null,
    );
    const [processing, setProcessing] = useState(false);

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        setProcessing(true);

        router.delete(`/projects/${deleteTarget.id}`, {
            onFinish: () => {
                setProcessing(false);
                setDeleteTarget(null);
            },
        });
    };

    return (
        <AppLayout title="Proyectos">
            <Head title="Proyectos" />
            <div className="mb-4 flex justify-end">
                <Button asChild>
                    <Link href="/projects/create">
                        <Plus /> Nuevo proyecto
                    </Link>
                </Button>
            </div>

            {records.length === 0 ? (
                <EmptyState
                    title="Sin proyectos registrados"
                    description="Los proyectos complementan tu experiencia y demuestran tu trabajo práctico."
                    actionLabel="Nuevo proyecto"
                    actionHref="/projects/create"
                />
            ) : (
                <div className="space-y-3">
                    {records.map((record) => (
                        <Card key={record.id}>
                            <CardContent className="flex flex-col gap-3 pt-6 sm:flex-row sm:items-start sm:justify-between">
                                <div className="space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="font-medium">
                                            {record.name}
                                        </h2>
                                        {record.role ? (
                                            <span className="text-sm text-muted-foreground">
                                                {record.role}
                                            </span>
                                        ) : null}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {formatRange(
                                            record.start_year,
                                            record.start_month,
                                            record.end_year,
                                            record.end_month,
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
                                        aria-label={`Editar ${record.name}`}
                                    >
                                        <Link
                                            href={`/projects/${record.id}/edit`}
                                        >
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`Eliminar ${record.name}`}
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
                title="¿Eliminar este proyecto?"
                description={
                    deleteTarget
                        ? `Se eliminará "${deleteTarget.name}". Esta acción es definitiva.`
                        : undefined
                }
                processing={processing}
                onConfirm={confirmDelete}
            />
        </AppLayout>
    );
}
