import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout';
import { formatBytes, formatDate } from '@/lib/documents';

type ViewerDocument = {
    id: number;
    display_name: string;
    original_filename: string;
    size_bytes: number;
    is_available: boolean;
    uploaded_at: string | null;
};

export default function Viewer({
    document,
    available,
    fileUrl,
    backUrl,
}: {
    document: ViewerDocument;
    available: boolean;
    fileUrl: string | null;
    backUrl: string;
}) {
    return (
        <AppLayout title={document.display_name}>
            <Head title={document.display_name} />
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-muted-foreground">
                    {document.original_filename} ·{' '}
                    {formatBytes(document.size_bytes)}
                    {document.uploaded_at
                        ? ` · Subido el ${formatDate(document.uploaded_at)}`
                        : ''}
                </p>
                <Button variant="outline" asChild>
                    <Link href={backUrl}>
                        <ArrowLeft /> Volver
                    </Link>
                </Button>
            </div>

            {available && fileUrl ? (
                <iframe
                    title={document.display_name}
                    src={fileUrl}
                    className="h-[75vh] w-full rounded-lg border bg-background"
                />
            ) : (
                <Alert variant="destructive">
                    <AlertTriangle />
                    <AlertTitle>Documento no disponible</AlertTitle>
                    <AlertDescription>
                        El archivo ya no está en el almacenamiento del
                        repositorio o su contenido fue alterado externamente. No
                        se regenera ni se sustituye automáticamente.
                    </AlertDescription>
                </Alert>
            )}
        </AppLayout>
    );
}
