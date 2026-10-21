import { router } from '@inertiajs/react';
import { useState } from 'react';
import AssociationPicker from '@/components/documents/AssociationPicker';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { DocumentAssociationOptions } from '@/lib/documents';

export default function AssociateDocumentDialog({
    open,
    onOpenChange,
    document,
    associationOptions,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    document: { id: number; display_name: string } | null;
    associationOptions: DocumentAssociationOptions;
}) {
    const [type, setType] = useState('');
    const [entityId, setEntityId] = useState('');
    const [processing, setProcessing] = useState(false);

    const submit = () => {
        if (!document || type === '' || entityId === '') {
            return;
        }

        setProcessing(true);

        router.post(
            `/documents/${document.id}/links`,
            { linkable_type: type, linkable_id: entityId },
            {
                preserveScroll: true,
                onFinish: () => {
                    setProcessing(false);
                    setType('');
                    setEntityId('');
                    onOpenChange(false);
                },
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Asociar documento</DialogTitle>
                    <DialogDescription>
                        {document
                            ? `Elegí la entidad que respalda "${document.display_name}".`
                            : ''}
                    </DialogDescription>
                </DialogHeader>
                <AssociationPicker
                    options={associationOptions}
                    type={type}
                    entityId={entityId}
                    onTypeChange={setType}
                    onEntityChange={setEntityId}
                    idPrefix="associate"
                />
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
                        disabled={processing || type === '' || entityId === ''}
                        onClick={submit}
                    >
                        Asociar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
