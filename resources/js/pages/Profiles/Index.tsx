import { Head, Link, router } from '@inertiajs/react';
import { History, Pencil, Plus, UserRound } from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/components/EmptyState';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout';
import type { CvProfileSummary } from '@/lib/cv-profiles';

export default function Index({
    profiles,
    can_create_initial_profiles,
}: {
    profiles: CvProfileSummary[];
    can_create_initial_profiles: boolean;
}) {
    const [processingId, setProcessingId] = useState<number | null>(null);

    const toggleActive = (profile: CvProfileSummary) => {
        setProcessingId(profile.id);

        router.post(
            `/profiles/${profile.id}/${profile.is_active ? 'deactivate' : 'activate'}`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessingId(null),
            },
        );
    };

    const createInitial = () => {
        router.post('/profiles/initial');
    };

    return (
        <AppLayout title="Perfiles de CV">
            <Head title="Perfiles de CV" />
            <div className="mb-4 flex flex-wrap justify-end gap-2">
                {can_create_initial_profiles ? (
                    <Button variant="outline" onClick={createInitial}>
                        Crear perfiles iniciales
                    </Button>
                ) : null}
                <Button asChild>
                    <Link href="/profiles/create">
                        <Plus /> Crear perfil
                    </Link>
                </Button>
            </div>

            {profiles.length === 0 ? (
                <div className="space-y-3">
                    <EmptyState
                        title="Sin perfiles de CV"
                        description="Creá un perfil para seleccionar qué parte de tu trayectoria querés destacar en cada CV."
                        actionLabel="Crear perfil"
                        actionHref="/profiles/create"
                    />
                    {can_create_initial_profiles ? (
                        <div className="text-center">
                            <Button variant="outline" onClick={createInitial}>
                                Crear perfiles iniciales Analista y Programador
                            </Button>
                        </div>
                    ) : null}
                </div>
            ) : (
                <div className="space-y-3">
                    {profiles.map((profile) => (
                        <Card key={profile.id}>
                            <CardContent className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div className="flex items-start gap-3">
                                    <UserRound
                                        aria-hidden="true"
                                        className="mt-0.5 size-5 text-muted-foreground"
                                    />
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="font-medium">
                                                {profile.name}
                                            </span>
                                            <Badge
                                                variant={
                                                    profile.is_active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {profile.is_active
                                                    ? 'Activo'
                                                    : 'Inactivo'}
                                            </Badge>
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            {profile.professional_title ??
                                                'Sin título propio'}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex flex-wrap gap-1">
                                    <Button variant="ghost" asChild>
                                        <Link href={`/profiles/${profile.id}`}>
                                            Ver
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <Link
                                            href={`/profiles/${profile.id}/adaptations`}
                                        >
                                            <History /> Ver adaptación
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <Link
                                            href={`/profiles/${profile.id}/edit`}
                                        >
                                            <Pencil /> Editar
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="outline"
                                        disabled={processingId === profile.id}
                                        onClick={() => toggleActive(profile)}
                                    >
                                        {profile.is_active
                                            ? 'Desactivar'
                                            : 'Activar'}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
