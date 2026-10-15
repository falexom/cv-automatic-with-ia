import { Head, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout';

type ProfileProp = {
    id: number;
    full_name: string | null;
    professional_title: string | null;
    phone: string | null;
    email: string | null;
    location: string | null;
    linkedin_url: string | null;
    github_url: string | null;
    website_url: string | null;
    summary: string | null;
};

type ProfileLinkProp = {
    id: number;
    label: string;
    url: string;
};

type FormLink = {
    label: string;
    url: string;
};

const textareaClassName =
    'min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

export default function Edit({
    profile,
    links,
}: {
    profile: ProfileProp;
    links: ProfileLinkProp[];
}) {
    const { data, setData, put, processing, errors } = useForm<{
        full_name: string;
        professional_title: string;
        phone: string;
        email: string;
        location: string;
        linkedin_url: string;
        github_url: string;
        website_url: string;
        summary: string;
        links: FormLink[];
    }>({
        full_name: profile.full_name ?? '',
        professional_title: profile.professional_title ?? '',
        phone: profile.phone ?? '',
        email: profile.email ?? '',
        location: profile.location ?? '',
        linkedin_url: profile.linkedin_url ?? '',
        github_url: profile.github_url ?? '',
        website_url: profile.website_url ?? '',
        summary: profile.summary ?? '',
        links: links.map((link) => ({ label: link.label, url: link.url })),
    });

    const fieldErrors = errors as Record<string, string>;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        put('/profile');
    };

    const updateLink = (
        index: number,
        field: keyof FormLink,
        value: string,
    ) => {
        setData(
            'links',
            data.links.map((link, linkIndex) =>
                linkIndex === index ? { ...link, [field]: value } : link,
            ),
        );
    };

    const addLink = () => {
        setData('links', [...data.links, { label: '', url: '' }]);
    };

    const removeLink = (index: number) => {
        setData(
            'links',
            data.links.filter((_, linkIndex) => linkIndex !== index),
        );
    };

    return (
        <AppLayout title="Perfil Profesional">
            <Head title="Perfil" />
            <form onSubmit={submit} className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Datos personales</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="full_name">Nombre completo *</Label>
                            <Input
                                id="full_name"
                                value={data.full_name}
                                onChange={(event) =>
                                    setData('full_name', event.target.value)
                                }
                                aria-invalid={Boolean(errors.full_name)}
                            />
                            {errors.full_name ? (
                                <p className="text-sm text-destructive">
                                    {errors.full_name}
                                </p>
                            ) : null}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="professional_title">
                                Título profesional
                            </Label>
                            <Input
                                id="professional_title"
                                value={data.professional_title}
                                onChange={(event) =>
                                    setData(
                                        'professional_title',
                                        event.target.value,
                                    )
                                }
                            />
                            {errors.professional_title ? (
                                <p className="text-sm text-destructive">
                                    {errors.professional_title}
                                </p>
                            ) : null}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="phone">Teléfono</Label>
                            <Input
                                id="phone"
                                value={data.phone}
                                onChange={(event) =>
                                    setData('phone', event.target.value)
                                }
                            />
                            {errors.phone ? (
                                <p className="text-sm text-destructive">
                                    {errors.phone}
                                </p>
                            ) : null}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="email">Correo electrónico</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(event) =>
                                    setData('email', event.target.value)
                                }
                                aria-invalid={Boolean(errors.email)}
                            />
                            {errors.email ? (
                                <p className="text-sm text-destructive">
                                    {errors.email}
                                </p>
                            ) : null}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="location">Ubicación</Label>
                            <Input
                                id="location"
                                value={data.location}
                                onChange={(event) =>
                                    setData('location', event.target.value)
                                }
                            />
                            {errors.location ? (
                                <p className="text-sm text-destructive">
                                    {errors.location}
                                </p>
                            ) : null}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="linkedin_url">LinkedIn</Label>
                            <Input
                                id="linkedin_url"
                                value={data.linkedin_url}
                                onChange={(event) =>
                                    setData('linkedin_url', event.target.value)
                                }
                                aria-invalid={Boolean(errors.linkedin_url)}
                            />
                            {errors.linkedin_url ? (
                                <p className="text-sm text-destructive">
                                    {errors.linkedin_url}
                                </p>
                            ) : null}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="github_url">GitHub</Label>
                            <Input
                                id="github_url"
                                value={data.github_url}
                                onChange={(event) =>
                                    setData('github_url', event.target.value)
                                }
                                aria-invalid={Boolean(errors.github_url)}
                            />
                            {errors.github_url ? (
                                <p className="text-sm text-destructive">
                                    {errors.github_url}
                                </p>
                            ) : null}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="website_url">Sitio web</Label>
                            <Input
                                id="website_url"
                                value={data.website_url}
                                onChange={(event) =>
                                    setData('website_url', event.target.value)
                                }
                                aria-invalid={Boolean(errors.website_url)}
                            />
                            {errors.website_url ? (
                                <p className="text-sm text-destructive">
                                    {errors.website_url}
                                </p>
                            ) : null}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Resumen profesional</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <textarea
                            id="summary"
                            className={textareaClassName}
                            value={data.summary}
                            onChange={(event) =>
                                setData('summary', event.target.value)
                            }
                            placeholder="Texto base reutilizable para tus currículums."
                        />
                        {errors.summary ? (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.summary}
                            </p>
                        ) : null}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Enlaces adicionales</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {data.links.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin enlaces adicionales. Podés agregar tu
                                portafolio, blog u otros perfiles.
                            </p>
                        ) : null}
                        {data.links.map((link, index) => (
                            <div
                                key={index}
                                className="flex flex-col gap-2 sm:flex-row sm:items-start"
                            >
                                <div className="flex-1 space-y-1.5">
                                    <Input
                                        aria-label={`Etiqueta del enlace ${index + 1}`}
                                        placeholder="Etiqueta (p. ej., Portafolio)"
                                        value={link.label}
                                        onChange={(event) =>
                                            updateLink(
                                                index,
                                                'label',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    {fieldErrors[`links.${index}.label`] ? (
                                        <p className="text-sm text-destructive">
                                            {
                                                fieldErrors[
                                                    `links.${index}.label`
                                                ]
                                            }
                                        </p>
                                    ) : null}
                                </div>
                                <div className="flex-[2] space-y-1.5">
                                    <Input
                                        aria-label={`Dirección del enlace ${index + 1}`}
                                        placeholder="https://..."
                                        value={link.url}
                                        onChange={(event) =>
                                            updateLink(
                                                index,
                                                'url',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    {fieldErrors[`links.${index}.url`] ? (
                                        <p className="text-sm text-destructive">
                                            {fieldErrors[`links.${index}.url`]}
                                        </p>
                                    ) : null}
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`Quitar enlace ${index + 1}`}
                                    onClick={() => removeLink(index)}
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        ))}
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={addLink}
                        >
                            <Plus /> Agregar enlace
                        </Button>
                    </CardContent>
                </Card>

                <div className="flex justify-end">
                    <Button type="submit" disabled={processing}>
                        Guardar perfil
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
