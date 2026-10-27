import { Link, router } from '@inertiajs/react';
import { AlertCircle } from 'lucide-react';
import CvPreview from '@/components/CvPreview';
import EmptyState from '@/components/EmptyState';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { CvGeneratorPageProps } from '@/lib/cv-preview';

export default function CvGeneratorPanel({
    profiles,
    status,
    selected_profile_id,
    preview,
}: CvGeneratorPageProps) {
    return (
        <div className="space-y-4">
            {status === 'no_active_profiles' ? (
                <EmptyState
                    title="Sin perfiles activos"
                    description="Creá o activá un perfil de CV para generar una vista previa."
                    actionLabel="Gestionar perfiles"
                    actionHref="/profiles"
                />
            ) : null}

            {status === 'selection_required' ? (
                <p role="status" className="text-sm text-muted-foreground">
                    Seleccioná un perfil para ver su vista previa.
                </p>
            ) : null}

            {status === 'unavailable' ? (
                <Alert>
                    <AlertCircle aria-hidden="true" />
                    <div className="col-start-2 space-y-1">
                        <p className="font-medium break-words">
                            El perfil seleccionado no está disponible
                        </p>
                        <p className="text-sm break-words text-muted-foreground">
                            Elegí uno de los perfiles activos para ver su vista
                            previa.
                        </p>
                    </div>
                </Alert>
            ) : null}

            {status === 'error' ? (
                <Alert variant="destructive">
                    <AlertCircle aria-hidden="true" />
                    <div className="col-start-2 space-y-2">
                        <p className="font-medium break-words">
                            No se pudo generar la vista previa
                        </p>
                        <p className="text-sm break-words">
                            Ocurrió un problema al cargar el perfil o componer
                            el CV. Podés volver a intentarlo.
                        </p>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => router.reload()}
                        >
                            Reintentar
                        </Button>
                    </div>
                </Alert>
            ) : null}

            {profiles.length > 0 ? (
                <div className="rounded-lg border bg-background p-4">
                    <form
                        method="get"
                        action="/cv"
                        className="flex flex-col gap-3 sm:flex-row sm:items-end"
                    >
                        <div className="min-w-0 flex-1 space-y-1.5">
                            <Label htmlFor="cv-profile">Perfil de CV</Label>
                            <select
                                key={selected_profile_id ?? 'none'}
                                id="cv-profile"
                                name="profile"
                                defaultValue={
                                    selected_profile_id !== null
                                        ? String(selected_profile_id)
                                        : ''
                                }
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="">Seleccioná un perfil</option>
                                {profiles.map((profile) => (
                                    <option key={profile.id} value={profile.id}>
                                        {profile.professional_title
                                            ? `${profile.name} — ${profile.professional_title}`
                                            : profile.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <Button type="submit" className="w-full sm:w-auto">
                                Ver vista previa
                            </Button>
                            {status === 'ready' &&
                            preview?.has_data &&
                            selected_profile_id !== null ? (
                                <Button
                                    asChild
                                    variant="outline"
                                    className="w-full sm:w-auto"
                                >
                                    <a
                                        href={`/cv/pdf?profile=${selected_profile_id}`}
                                    >
                                        Exportar PDF
                                    </a>
                                </Button>
                            ) : null}
                        </div>
                    </form>
                </div>
            ) : null}

            {status === 'ready' && preview ? (
                <div className="mx-auto w-full max-w-3xl space-y-3">
                    {preview.has_data ? (
                        <CvPreview preview={preview} />
                    ) : (
                        <EmptyState
                            title="Sin datos para mostrar"
                            description="Este perfil todavía no tiene contenido. Completá el perfil de CV o el Perfil Maestro y volvé a consultar."
                            actionLabel={
                                selected_profile_id !== null
                                    ? 'Editar perfil de CV'
                                    : undefined
                            }
                            actionHref={
                                selected_profile_id !== null
                                    ? `/profiles/${selected_profile_id}/edit`
                                    : undefined
                            }
                        />
                    )}

                    <section
                        aria-labelledby="cv-source-links-heading"
                        className="rounded-xl border bg-background p-4 sm:p-6"
                    >
                        <h2
                            id="cv-source-links-heading"
                            className="text-base font-semibold"
                        >
                            Editar información del CV
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Modifica los datos de origen y vuelve a generar la
                            vista previa.
                        </p>
                        <ul className="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-sm">
                            {[
                                ['Perfil de CV', preview.source_links.profile],
                                [
                                    'Datos personales',
                                    preview.source_links.personal_data,
                                ],
                                [
                                    'Experiencia',
                                    preview.source_links.work_experiences,
                                ],
                                [
                                    'Educación',
                                    preview.source_links.education_entries,
                                ],
                                [
                                    'Cursos y certificaciones',
                                    preview.source_links.certifications,
                                ],
                                ['Habilidades', preview.source_links.skills],
                                ['Proyectos', preview.source_links.projects],
                            ].map(([label, href]) => (
                                <li key={href}>
                                    <Link
                                        href={href}
                                        className="text-primary underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                    >
                                        {label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                </div>
            ) : null}
        </div>
    );
}
