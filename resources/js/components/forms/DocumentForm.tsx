import { useForm } from '@inertiajs/react';
import AssociationPicker from '@/components/documents/AssociationPicker';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { DocumentAssociationOptions } from '@/lib/documents';

type DocumentFormData = {
    file: File | null;
    title: string;
    description: string;
    linkable_type: string;
    linkable_id: string;
};

const textareaClassName =
    'flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

export default function DocumentForm({
    associationOptions,
    linkableType,
    linkableId,
    submitLabel = 'Subir documento',
    onSuccess,
}: {
    associationOptions?: DocumentAssociationOptions;
    linkableType?: string;
    linkableId?: number;
    submitLabel?: string;
    onSuccess?: () => void;
}) {
    const fixedLink = linkableType !== undefined && linkableId !== undefined;

    const { data, setData, post, processing, progress, errors, reset } =
        useForm<DocumentFormData>({
            file: null,
            title: '',
            description: '',
            linkable_type: fixedLink ? linkableType : '',
            linkable_id: fixedLink ? String(linkableId) : '',
        });

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post('/documents', {
            forceFormData: true,
            onSuccess: () => {
                reset();
                onSuccess?.();
            },
        });
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Archivo PDF original</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="file">Archivo *</Label>
                        <Input
                            id="file"
                            type="file"
                            accept="application/pdf,.pdf"
                            aria-invalid={Boolean(errors.file)}
                            onChange={(event) =>
                                setData('file', event.target.files?.[0] ?? null)
                            }
                        />
                        {errors.file ? (
                            <p className="text-sm text-destructive">
                                {errors.file}
                            </p>
                        ) : null}
                        <p className="text-sm text-muted-foreground">
                            Se conserva sin alteración. Tamaño máximo: 20 MB. Un
                            archivo por operación.
                        </p>
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="title">Título (opcional)</Label>
                        <Input
                            id="title"
                            value={data.title}
                            aria-invalid={Boolean(errors.title)}
                            onChange={(event) =>
                                setData('title', event.target.value)
                            }
                            placeholder="Certificado de API REST con PHP"
                        />
                        {errors.title ? (
                            <p className="text-sm text-destructive">
                                {errors.title}
                            </p>
                        ) : null}
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="description">
                            Descripción (opcional)
                        </Label>
                        <textarea
                            id="description"
                            value={data.description}
                            className={textareaClassName}
                            aria-invalid={Boolean(errors.description)}
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                        />
                        {errors.description ? (
                            <p className="text-sm text-destructive">
                                {errors.description}
                            </p>
                        ) : null}
                    </div>
                </CardContent>
            </Card>

            {!fixedLink && associationOptions ? (
                <Card>
                    <CardHeader>
                        <CardTitle>Asociación (opcional)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <AssociationPicker
                            options={associationOptions}
                            type={data.linkable_type}
                            entityId={data.linkable_id}
                            onTypeChange={(value) =>
                                setData('linkable_type', value)
                            }
                            onEntityChange={(value) =>
                                setData('linkable_id', value)
                            }
                            typeError={errors.linkable_type}
                            entityError={errors.linkable_id}
                        />
                    </CardContent>
                </Card>
            ) : null}

            <div className="flex items-center justify-end gap-3">
                {progress ? (
                    <p className="text-sm text-muted-foreground">
                        Subiendo… {progress.percentage}%
                    </p>
                ) : null}
                <Button type="submit" disabled={processing}>
                    {submitLabel}
                </Button>
            </div>
        </form>
    );
}
