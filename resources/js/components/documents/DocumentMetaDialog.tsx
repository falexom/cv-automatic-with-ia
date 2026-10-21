import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type EditableDocument = {
    id: number;
    display_name: string;
    title: string | null;
    description: string | null;
};

const textareaClassName =
    'flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

export default function DocumentMetaDialog({
    open,
    onOpenChange,
    document,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    document: EditableDocument | null;
}) {
    const { data, setData, put, processing, errors } = useForm({
        title: document?.title ?? '',
        description: document?.description ?? '',
    });

    const submit = () => {
        if (!document) {
            return;
        }

        put(`/documents/${document.id}`, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Editar documento</DialogTitle>
                    <DialogDescription>
                        {document
                            ? `Metadatos de "${document.display_name}". El archivo original no se modifica.`
                            : ''}
                    </DialogDescription>
                </DialogHeader>
                <div className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="meta-title">Título</Label>
                        <Input
                            id="meta-title"
                            value={data.title}
                            aria-invalid={Boolean(errors.title)}
                            onChange={(event) =>
                                setData('title', event.target.value)
                            }
                        />
                        {errors.title ? (
                            <p className="text-sm text-destructive">
                                {errors.title}
                            </p>
                        ) : null}
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="meta-description">Descripción</Label>
                        <textarea
                            id="meta-description"
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
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        disabled={processing}
                        onClick={submit}
                    >
                        Guardar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
