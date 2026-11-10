import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, FileText, History, Pencil, Plus } from 'lucide-react';
import EmptyState from '@/components/EmptyState';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout';
import {
    OPERATION_LABELS,
    type CvAdaptationIndexProps,
} from '@/lib/cv-adaptation';
import { formatDateTime } from '@/lib/dates';

export default function Index({
    profile,
    adaptations,
}: CvAdaptationIndexProps) {
    return (
        <AppLayout title={`Adaptaciones de ${profile.name}`}>
            <Head title={`Adaptaciones de ${profile.name}`} />

            <div className="mb-4 flex flex-wrap justify-end gap-2">
                <Button variant="outline" asChild>
                    <Link href={`/profiles/${profile.id}`}>
                        <ArrowLeft /> Volver al perfil
                    </Link>
                </Button>
                <Button variant="outline" asChild>
                    <Link href={`/profiles/${profile.id}/edit`}>
                        <Pencil /> Editar perfil
                    </Link>
                </Button>
                {profile.is_active ? (
                    <Button asChild>
                        <Link href={`/cv?profile=${profile.id}`}>
                            <FileText /> Generar CV
                        </Link>
                    </Button>
                ) : null}
            </div>

            <Card className="mb-4">
                <CardHeader>
                    <CardTitle className="flex flex-wrap items-center gap-2">
                        <History aria-hidden="true" />
                        {profile.name}
                        <Badge
                            variant={
                                profile.is_active ? 'default' : 'secondary'
                            }
                        >
                            {profile.is_active ? 'Activo' : 'Inactivo'}
                        </Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-2">
                    {profile.professional_title ? (
                        <p className="text-sm font-medium">
                            {profile.professional_title}
                        </p>
                    ) : null}
                    {profile.summary ? (
                        <p className="text-sm whitespace-pre-line text-muted-foreground">
                            {profile.summary}
                        </p>
                    ) : null}
                    {!profile.is_active ? (
                        <p className="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
                            Este perfil está inactivo. Podés consultar sus
                            evaluaciones, pero no reutilizarlo para nuevas
                            adaptaciones.
                        </p>
                    ) : null}
                </CardContent>
            </Card>

            {adaptations.length === 0 ? (
                <EmptyState
                    title="Sin adaptación guardada"
                    description="Este perfil todavía no tiene evaluaciones confirmadas. Podés crear una adaptación desde el generador de CV."
                    actionLabel="Adaptar CV a una oferta"
                    actionHref="/cv/adapt"
                />
            ) : (
                <div className="space-y-3">
                    {adaptations.map((adaptation) => (
                        <Card key={adaptation.id}>
                            <CardContent className="flex flex-col gap-3 pt-6 sm:flex-row sm:items-center sm:justify-between">
                                <div className="space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="font-medium">
                                            {adaptation.job_title}
                                        </p>
                                        <Badge variant="outline">
                                            {
                                                OPERATION_LABELS[
                                                    adaptation.operation
                                                ]
                                            }
                                        </Badge>
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {adaptation.company ??
                                            'Empresa no informada'}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Análisis:{' '}
                                        {formatDateTime(adaptation.analyzed_at)}{' '}
                                        · Confirmación:{' '}
                                        {formatDateTime(
                                            adaptation.confirmed_at,
                                        )}
                                    </p>
                                </div>
                                <Button asChild className="w-full sm:w-auto">
                                    <Link
                                        href={`/profiles/${profile.id}/adaptations/${adaptation.id}`}
                                    >
                                        Ver evaluación
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}

            {adaptations.length > 0 ? (
                <div className="mt-4 flex justify-center">
                    <Button variant="outline" asChild>
                        <Link href="/cv/adapt">
                            <Plus /> Nueva adaptación
                        </Link>
                    </Button>
                </div>
            ) : null}
        </AppLayout>
    );
}
