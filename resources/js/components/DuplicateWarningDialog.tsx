import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

export type DuplicateWarning = {
    existing_id: number;
    name: string;
    institution: string | null;
};

type DuplicateWarningDialogProps = {
    open: boolean;
    warning: DuplicateWarning;
    onOpenChange: (open: boolean) => void;
    processing?: boolean;
    onConfirm: () => void;
};

export default function DuplicateWarningDialog({
    open,
    warning,
    onOpenChange,
    processing = false,
    onConfirm,
}: DuplicateWarningDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Registro duplicado</DialogTitle>
                    <DialogDescription>
                        Ya existe un curso o certificación con el mismo nombre e
                        institución:
                        <strong className="mt-2 block">
                            {warning.name}
                            {warning.institution
                                ? ` — ${warning.institution}`
                                : ''}
                        </strong>
                        Podés corregir los datos o guardarlo de todos modos si
                        son dos registros distintos.
                    </DialogDescription>
                </DialogHeader>
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
                        onClick={onConfirm}
                    >
                        Guardar de todos modos
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
