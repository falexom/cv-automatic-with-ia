import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import DeleteConfirmDialog from '@/components/DeleteConfirmDialog';
import EmptyState from '@/components/EmptyState';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout';
import {
    SKILL_CATEGORY_LABELS,
    SKILL_CATEGORY_ORDER,
} from '@/lib/skill-categories';

type SkillRecord = {
    id: number;
    name: string;
    category: string;
    usage_count: number;
};

function deleteDescription(name: string, usageCount: number): string {
    if (usageCount === 0) {
        return `No se usa en ningún elemento. Se eliminará "${name}" del catálogo.`;
    }

    const usage = usageCount === 1 ? '1 elemento' : `${usageCount} elementos`;

    return `Se usa en ${usage}. Se eliminará "${name}" del catálogo y sus vínculos con experiencias, certificaciones y proyectos. Los registros que la usan permanecerán intactos.`;
}

export default function Index({ records }: { records: SkillRecord[] }) {
    const [deleteTarget, setDeleteTarget] = useState<SkillRecord | null>(null);
    const [processing, setProcessing] = useState(false);

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        setProcessing(true);

        router.delete(`/skills/${deleteTarget.id}`, {
            onFinish: () => {
                setProcessing(false);
                setDeleteTarget(null);
            },
        });
    };

    const groups = SKILL_CATEGORY_ORDER.map((category) => ({
        category,
        items: records.filter((record) => record.category === category),
    })).filter((group) => group.items.length > 0);

    return (
        <AppLayout title="Habilidades">
            <Head title="Habilidades" />
            <div className="mb-4 flex justify-end">
                <Button asChild>
                    <Link href="/skills/create">
                        <Plus /> Nueva habilidad
                    </Link>
                </Button>
            </div>

            {records.length === 0 ? (
                <EmptyState
                    title="Sin habilidades en el catálogo"
                    description="Creá tu catálogo de habilidades para reutilizarlas en experiencias, certificaciones y proyectos."
                    actionLabel="Nueva habilidad"
                    actionHref="/skills/create"
                />
            ) : (
                <div className="space-y-4">
                    {groups.map((group) => (
                        <Card key={group.category}>
                            <CardHeader>
                                <CardTitle>
                                    {SKILL_CATEGORY_LABELS[group.category] ??
                                        group.category}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="divide-y">
                                {group.items.map((record) => (
                                    <div
                                        key={record.id}
                                        className="flex items-center justify-between py-2 first:pt-0 last:pb-0"
                                    >
                                        <span className="text-sm font-medium">
                                            {record.name}
                                        </span>
                                        <div className="flex gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                asChild
                                                aria-label={`Editar ${record.name}`}
                                            >
                                                <Link
                                                    href={`/skills/${record.id}/edit`}
                                                >
                                                    <Pencil />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Eliminar ${record.name}`}
                                                onClick={() =>
                                                    setDeleteTarget(record)
                                                }
                                            >
                                                <Trash2 />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
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
                title="¿Eliminar esta habilidad?"
                description={
                    deleteTarget
                        ? deleteDescription(
                              deleteTarget.name,
                              deleteTarget.usage_count,
                          )
                        : undefined
                }
                processing={processing}
                onConfirm={confirmDelete}
            />
        </AppLayout>
    );
}
