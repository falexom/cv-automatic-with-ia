import { router, useForm } from '@inertiajs/react';
import { Trash2, Upload } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import CvPhotoFrame from '@/components/CvPhotoFrame';
import DeleteConfirmDialog from '@/components/DeleteConfirmDialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import type { CvPhotoData } from '@/lib/cv-preview';

const MAX_BYTES = 5 * 1024 * 1024;
const ALLOWED_TYPES = ['image/jpeg', 'image/png'];

type PhotoFormData = {
    photo: File | null;
    photo_focus_x: number;
    photo_focus_y: number;
    photo_zoom: number;
};

export default function ProfilePhotoManager({
    photo,
}: {
    photo: CvPhotoData | null;
}) {
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [localError, setLocalError] = useState<string | null>(null);
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const fileInput = useRef<HTMLInputElement>(null);

    const { data, setData, post, processing, errors, reset } =
        useForm<PhotoFormData>({
            photo: null,
            photo_focus_x: photo?.focus_x ?? 50,
            photo_focus_y: photo?.focus_y ?? 50,
            photo_zoom: photo?.zoom ?? 100,
        });

    useEffect(() => {
        return () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
        };
    }, [previewUrl]);

    const currentImage = previewUrl ?? photo?.data_uri ?? null;
    const previewPhoto: CvPhotoData | null = currentImage
        ? {
              data_uri: currentImage,
              focus_x: data.photo_focus_x,
              focus_y: data.photo_focus_y,
              zoom: data.photo_zoom,
          }
        : null;
    const fieldErrors = errors as Record<string, string>;
    const hasPhoto = photo !== null;

    const handleFileChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;

        setLocalError(null);

        if (!file) {
            setData('photo', null);
            setPreviewUrl(null);

            return;
        }

        if (!ALLOWED_TYPES.includes(file.type)) {
            setLocalError('La fotografía debe ser JPG, JPEG o PNG.');
            event.target.value = '';
            setData('photo', null);
            setPreviewUrl(null);

            return;
        }

        if (file.size > MAX_BYTES) {
            setLocalError('La fotografía supera el límite de 5 MB.');
            event.target.value = '';
            setData('photo', null);
            setPreviewUrl(null);

            return;
        }

        setData('photo', file);
        setPreviewUrl(URL.createObjectURL(file));
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        setLocalError(null);

        if (!hasPhoto && !data.photo) {
            setLocalError('Seleccioná una fotografía JPG, JPEG o PNG.');

            return;
        }

        post('/profile/photo', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setPreviewUrl(null);
                reset('photo');

                if (fileInput.current) {
                    fileInput.current.value = '';
                }
            },
        });
    };

    const remove = () => {
        router.delete('/profile/photo', {
            preserveScroll: true,
            onStart: () => setDeleting(true),
            onFinish: () => setDeleting(false),
            onSuccess: () => {
                setConfirmOpen(false);
                setLocalError(null);
                setPreviewUrl(null);
                reset('photo');

                if (fileInput.current) {
                    fileInput.current.value = '';
                }
            },
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Fotografía profesional</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                <p className="text-sm text-muted-foreground">
                    Se incluye automáticamente en la vista previa, el PDF y el
                    CV documentado. Ajustá el encuadre cuadrado 1:1 que se verá
                    en un marco de 30 × 30 mm.
                </p>

                <div className="flex flex-col gap-6 sm:flex-row sm:items-start">
                    <div className="space-y-2">
                        {previewPhoto ? (
                            <CvPhotoFrame
                                photo={previewPhoto}
                                className="h-44 w-44"
                            />
                        ) : (
                            <div
                                data-testid="photo-placeholder"
                                className="flex h-44 w-44 items-center justify-center rounded-sm border border-dashed bg-muted/40 px-4 text-center text-xs text-muted-foreground"
                            >
                                Sin fotografía
                            </div>
                        )}
                        <p className="text-xs text-muted-foreground">
                            {hasPhoto
                                ? 'Fotografía guardada'
                                : 'Sin fotografía'}
                        </p>
                    </div>

                    <div className="min-w-0 flex-1 space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="photo">
                                {hasPhoto
                                    ? 'Reemplazar fotografía'
                                    : 'Fotografía'}
                            </Label>
                            <input
                                ref={fileInput}
                                id="photo"
                                type="file"
                                accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                                onChange={handleFileChange}
                                aria-invalid={Boolean(
                                    localError || fieldErrors.photo,
                                )}
                                className="flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                            <p className="text-xs text-muted-foreground">
                                JPG, JPEG o PNG. Tamaño máximo: 5 MB.
                            </p>
                            {localError ? (
                                <p className="text-sm text-destructive">
                                    {localError}
                                </p>
                            ) : null}
                            {fieldErrors.photo ? (
                                <p className="text-sm text-destructive">
                                    {fieldErrors.photo}
                                </p>
                            ) : null}
                        </div>

                        <div className="grid gap-4 sm:grid-cols-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="photo_focus_x">
                                    Horizontal
                                </Label>
                                <input
                                    id="photo_focus_x"
                                    type="range"
                                    min={0}
                                    max={100}
                                    value={data.photo_focus_x}
                                    disabled={!currentImage}
                                    onChange={(event) =>
                                        setData(
                                            'photo_focus_x',
                                            Number(event.target.value),
                                        )
                                    }
                                    className="h-9 w-full accent-primary"
                                />
                                {fieldErrors.photo_focus_x ? (
                                    <p className="text-sm text-destructive">
                                        {fieldErrors.photo_focus_x}
                                    </p>
                                ) : null}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="photo_focus_y">Vertical</Label>
                                <input
                                    id="photo_focus_y"
                                    type="range"
                                    min={0}
                                    max={100}
                                    value={data.photo_focus_y}
                                    disabled={!currentImage}
                                    onChange={(event) =>
                                        setData(
                                            'photo_focus_y',
                                            Number(event.target.value),
                                        )
                                    }
                                    className="h-9 w-full accent-primary"
                                />
                                {fieldErrors.photo_focus_y ? (
                                    <p className="text-sm text-destructive">
                                        {fieldErrors.photo_focus_y}
                                    </p>
                                ) : null}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="photo_zoom">
                                    Zoom ({data.photo_zoom}%)
                                </Label>
                                <input
                                    id="photo_zoom"
                                    type="range"
                                    min={100}
                                    max={200}
                                    step={5}
                                    value={data.photo_zoom}
                                    disabled={!currentImage}
                                    onChange={(event) =>
                                        setData(
                                            'photo_zoom',
                                            Number(event.target.value),
                                        )
                                    }
                                    className="h-9 w-full accent-primary"
                                />
                                {fieldErrors.photo_zoom ? (
                                    <p className="text-sm text-destructive">
                                        {fieldErrors.photo_zoom}
                                    </p>
                                ) : null}
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            <Button
                                type="button"
                                onClick={submit}
                                disabled={
                                    processing || (!hasPhoto && !data.photo)
                                }
                            >
                                <Upload /> Guardar fotografía
                            </Button>
                            {hasPhoto ? (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setConfirmOpen(true)}
                                    disabled={deleting}
                                >
                                    <Trash2 /> Eliminar fotografía
                                </Button>
                            ) : null}
                        </div>
                    </div>
                </div>

                <DeleteConfirmDialog
                    open={confirmOpen}
                    onOpenChange={setConfirmOpen}
                    title="¿Eliminar la fotografía?"
                    description="La fotografía se quitará del Perfil Maestro y de los CV que generes en adelante."
                    processing={deleting}
                    onConfirm={remove}
                />
            </CardContent>
        </Card>
    );
}
