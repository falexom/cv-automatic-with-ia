import { Head, Link, router } from '@inertiajs/react';
import { History, Pencil, Undo2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout';
import {
    RELEVANCE_LABELS,
    type CvProfileContentItemBase,
    type CvProfileShowProps,
} from '@/lib/cv-profiles';
import { formatRange, formatYearMonth } from '@/lib/dates';
import { SKILL_CATEGORY_LABELS } from '@/lib/skill-categories';

function RelevanceBadge({
    relevance,
}: {
    relevance: 'alta' | 'media' | 'baja';
}) {
    return (
        <Badge variant={relevance === 'alta' ? 'default' : 'secondary'}>
            {RELEVANCE_LABELS[relevance]}
        </Badge>
    );
}

function MasterLink({ item }: { item: CvProfileContentItemBase }) {
    return (
        <Link
            href={item.master_path}
            className="text-xs text-primary underline-offset-4 hover:underline"
        >
            Ver en el Perfil Maestro
        </Link>
    );
}

export default function Show({
    profile,
    personal_data,
    content,
}: CvProfileShowProps) {
    const personalFields = [
        { label: 'Nombre completo', value: personal_data.full_name },
        { label: 'Teléfono', value: personal_data.phone },
        { label: 'Correo electrónico', value: personal_data.email },
        { label: 'Ubicación', value: personal_data.location },
        { label: 'LinkedIn', value: personal_data.linkedin_url },
        { label: 'GitHub', value: personal_data.github_url },
        { label: 'Sitio web', value: personal_data.website_url },
    ].filter((field) => field.value);

    const reactivate = () => {
        router.post(`/profiles/${profile.id}/activate`);
    };

    return (
        <AppLayout title={profile.name}>
            <Head title={profile.name} />

            <div className="mb-4 flex flex-wrap justify-end gap-2">
                <Button variant="outline" asChild>
                    <Link href="/profiles">Volver</Link>
                </Button>
                <Button variant="outline" asChild>
                    <Link href={`/profiles/${profile.id}/adaptations`}>
                        <History /> Ver adaptación
                    </Link>
                </Button>
                <Button asChild>
                    <Link href={`/profiles/${profile.id}/edit`}>
                        <Pencil /> Editar
                    </Link>
                </Button>
            </div>

            <div className="space-y-4">
                <Card>
                    <CardContent className="space-y-2 pt-6">
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 className="text-xl font-semibold">
                                {profile.name}
                            </h2>
                            <Badge
                                variant={
                                    profile.is_active ? 'default' : 'secondary'
                                }
                            >
                                {profile.is_active ? 'Activo' : 'Inactivo'}
                            </Badge>
                        </div>
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
                            <div className="mt-3 flex flex-col gap-2 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 sm:flex-row sm:items-center sm:justify-between">
                                <span>
                                    Este perfil está inactivo y no se ofrece
                                    para la futura generación de CV.
                                </span>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={reactivate}
                                >
                                    <Undo2 /> Reactivar
                                </Button>
                            </div>
                        ) : null}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Datos personales heredados</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {!personal_data.has_data ? (
                            <p className="text-sm text-muted-foreground">
                                El Perfil Maestro todavía no tiene datos
                                personales. Podés completarlos en la sección
                                Perfil.
                            </p>
                        ) : (
                            <>
                                <dl className="grid gap-3 sm:grid-cols-2">
                                    {personalFields.map((field) => (
                                        <div key={field.label}>
                                            <dt className="text-xs text-muted-foreground">
                                                {field.label}
                                            </dt>
                                            <dd className="text-sm">
                                                {field.value}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                                {personal_data.links.length > 0 ? (
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Enlaces adicionales
                                        </p>
                                        <ul className="list-inside list-disc text-sm">
                                            {personal_data.links.map((link) => (
                                                <li key={link.id}>
                                                    <a
                                                        href={link.url}
                                                        className="text-primary underline-offset-4 hover:underline"
                                                        target="_blank"
                                                        rel="noreferrer"
                                                    >
                                                        {link.label}
                                                    </a>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                ) : null}
                            </>
                        )}
                        <p className="text-xs text-muted-foreground">
                            Estos datos se leen del Perfil Maestro y no se
                            editan desde el perfil.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Experiencia laboral</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {content.work_experiences.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin experiencias incluidas. Podés agregarlas
                                desde Editar.
                            </p>
                        ) : (
                            content.work_experiences.map((item) => (
                                <div
                                    key={item.id}
                                    className="space-y-1 border-b pb-3 last:border-0 last:pb-0"
                                >
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="font-medium">
                                            {item.position} — {item.company}
                                        </span>
                                        <RelevanceBadge
                                            relevance={item.relevance}
                                        />
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        {formatRange(
                                            item.start_year,
                                            item.start_month,
                                            item.end_year,
                                            item.end_month,
                                            item.is_current,
                                        )}
                                    </p>
                                    {item.description ? (
                                        <p className="text-sm">
                                            {item.description}
                                        </p>
                                    ) : null}
                                    <MasterLink item={item} />
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Educación</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {content.education_entries.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin formación incluida. Podés agregarla desde
                                Editar.
                            </p>
                        ) : (
                            content.education_entries.map((item) => (
                                <div
                                    key={item.id}
                                    className="space-y-1 border-b pb-3 last:border-0 last:pb-0"
                                >
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="font-medium">
                                            {item.program} — {item.institution}
                                        </span>
                                        <RelevanceBadge
                                            relevance={item.relevance}
                                        />
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        {formatRange(
                                            item.start_year,
                                            item.start_month,
                                            item.end_year,
                                            item.end_month,
                                            item.is_in_progress,
                                            'en curso',
                                        )}
                                    </p>
                                    {item.degree_level ? (
                                        <p className="text-sm">
                                            {item.degree_level}
                                        </p>
                                    ) : null}
                                    {item.description ? (
                                        <p className="text-sm">
                                            {item.description}
                                        </p>
                                    ) : null}
                                    <MasterLink item={item} />
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Cursos y certificaciones</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {content.certifications.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin cursos ni certificaciones incluidos. Podés
                                agregarlos desde Editar.
                            </p>
                        ) : (
                            content.certifications.map((item) => (
                                <div
                                    key={item.id}
                                    className="space-y-1 border-b pb-3 last:border-0 last:pb-0"
                                >
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="font-medium">
                                            {item.name}
                                            {item.institution
                                                ? ` — ${item.institution}`
                                                : ''}
                                        </span>
                                        <RelevanceBadge
                                            relevance={item.relevance}
                                        />
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        {formatYearMonth(
                                            item.date_year,
                                            item.date_month,
                                        ) ?? ''}
                                    </p>
                                    {item.duration ? (
                                        <p className="text-sm">
                                            {item.duration}
                                        </p>
                                    ) : null}
                                    {item.category ? (
                                        <p className="text-sm">
                                            {item.category}
                                        </p>
                                    ) : null}
                                    {item.validation_url ? (
                                        <a
                                            href={item.validation_url}
                                            className="text-xs text-primary underline-offset-4 hover:underline"
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            Validación
                                        </a>
                                    ) : null}
                                    <MasterLink item={item} />
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Habilidades</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {content.skills.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin habilidades incluidas. Podés agregarlas
                                desde Editar.
                            </p>
                        ) : (
                            content.skills.map((item) => (
                                <div
                                    key={item.id}
                                    className="flex flex-wrap items-center gap-2 border-b pb-3 last:border-0 last:pb-0"
                                >
                                    <span className="font-medium">
                                        {item.name}
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {SKILL_CATEGORY_LABELS[item.category] ??
                                            item.category}
                                    </span>
                                    <RelevanceBadge
                                        relevance={item.relevance}
                                    />
                                    <MasterLink item={item} />
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Proyectos</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {content.projects.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin proyectos incluidos. Podés agregarlos desde
                                Editar.
                            </p>
                        ) : (
                            content.projects.map((item) => (
                                <div
                                    key={item.id}
                                    className="space-y-1 border-b pb-3 last:border-0 last:pb-0"
                                >
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="font-medium">
                                            {item.name}
                                            {item.role ? ` — ${item.role}` : ''}
                                        </span>
                                        <RelevanceBadge
                                            relevance={item.relevance}
                                        />
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        {formatRange(
                                            item.start_year,
                                            item.start_month,
                                            item.end_year,
                                            item.end_month,
                                        )}
                                    </p>
                                    {item.description ? (
                                        <p className="text-sm">
                                            {item.description}
                                        </p>
                                    ) : null}
                                    {item.url ? (
                                        <a
                                            href={item.url}
                                            className="text-xs text-primary underline-offset-4 hover:underline"
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            Ver proyecto
                                        </a>
                                    ) : null}
                                    <MasterLink item={item} />
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
