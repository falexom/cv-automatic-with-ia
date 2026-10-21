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
import { formatYearMonth } from '@/lib/dates';

type CertificationRecord = {
    id: number;
    name: string;
    institution: string | null;
    date_year: number | null;
    date_month: number | null;
    category: string | null;
    documents_count: number;
    skills: SkillOption[];
};

export default function Index({ records }: { records: CertificationRecord[] }) {
    const [deleteTarget, setDeleteTarget] =
        useState<CertificationRecord | null>(null);
    const [processing, setProcessing] = useState(false);

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        setProcessing(true);

        router.delete(`/certifications/${deleteTarget.id}`, {
            onFinish: () => {
                setProcessing(false);
                setDeleteTarget(null);
            },
        });
    };

    return (
        <AppLayout title="Cursos y certificaciones">
            <Head title="Cursos y certificaciones" />
            <div className="mb-4 flex justify-end">
                <Button asChild>
                    <Link href="/certifications/create">
                        <Plus /> Nueva certificación
                    </Link>
                </Button>
            </div>

            {records.length === 0 ? (
                <EmptyState
                    title="Sin cursos ni certificaciones"
                    description="Registrá los cursos y certificaciones que respaldan tus conocimientos."
                    actionLabel="Nueva certificación"
                    actionHref="/certifications/create"
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
                                        {record.institution ? (
                                            <span className="text-sm text-muted-foreground">
                                                {record.institution}
                                            </span>
                                        ) : null}
                                        {record.category ? (
                                            <Badge variant="outline">
                                                {record.category}
                                            </Badge>
                                        ) : null}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {formatYearMonth(
                                            record.date_year,
                                            record.date_month,
                                        ) ?? 'Sin fecha'}
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
                                            href={`/certifications/${record.id}/edit`}
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
                title="¿Eliminar esta certificación?"
                description={
                    deleteTarget
                        ? `Se eliminará "${deleteTarget.name}".${
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
