import { Link, router } from '@inertiajs/react';
import { Eye, Trash2, Upload } from 'lucide-react';
import { useState } from 'react';
import DocumentForm from '@/components/forms/DocumentForm';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatBytes } from '@/lib/documents';

export type LinkedDocument = {
    id: number;
    display_name: string;
    size_bytes: number;
    is_available: boolean;
    uploaded_at: string | null;
};

export default function LinkedDocumentsSection({
    entityType,
    entityId,
    documents,
}: {
    entityType: 'certification' | 'education' | 'experience';
    entityId: number;
    documents: LinkedDocument[];
}) {
    const [uploadOpen, setUploadOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const unlink = (document: LinkedDocument) => {
        setProcessing(true);

        router.delete(`/documents/${document.id}/links`, {
            data: { linkable_type: entityType, linkable_id: entityId },
            preserveScroll: true,
            onFinish: () => setProcessing(false),
        });
    };

    const backUrl =
        typeof window !== 'undefined' ? window.location.pathname : '';

    return (
        <Card>
            <CardContent className="space-y-4 pt-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 className="font-medium">Documentos</h2>
                        <p className="text-sm text-muted-foreground">
                            PDFs originales conservados como evidencia de este
                            registro.
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => setUploadOpen(true)}
                    >
                        <Upload /> Subir PDF
                    </Button>
                </div>

                {documents.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Todavía no hay documentos asociados. Subí el PDF
                        original para respaldar este registro.
                    </p>
                ) : (
                    <ul className="divide-y">
                        {documents.map((document) => (
                            <li
                                key={document.id}
                                className="flex items-center justify-between gap-3 py-2"
                            >
                                <div className="min-w-0 space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="truncate text-sm font-medium">
                                            {document.display_name}
                                        </span>
                                        {!document.is_available ? (
                                            <Badge variant="destructive">
                                                No disponible
                                            </Badge>
                                        ) : null}
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        {formatBytes(document.size_bytes)}
                                    </p>
                                </div>
                                <div className="flex gap-1">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        asChild
                                        aria-label={`Ver ${document.display_name}`}
                                    >
                                        <Link
                                            href={`/documents/${document.id}/viewer?back=${encodeURIComponent(backUrl)}`}
                                        >
                                            <Eye />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        disabled={processing}
                                        aria-label={`Quitar ${document.display_name}`}
                                        onClick={() => unlink(document)}
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>

            <Dialog open={uploadOpen} onOpenChange={setUploadOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Subir y asociar PDF</DialogTitle>
                    </DialogHeader>
                    <DocumentForm
                        linkableType={entityType}
                        linkableId={entityId}
                        submitLabel="Subir y asociar"
                        onSuccess={() => setUploadOpen(false)}
                    />
                </DialogContent>
            </Dialog>
        </Card>
    );
}
