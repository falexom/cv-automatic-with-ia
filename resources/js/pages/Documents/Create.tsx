import { Head, Link } from '@inertiajs/react';
import DocumentForm from '@/components/forms/DocumentForm';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout';
import type { DocumentAssociationOptions } from '@/lib/documents';

export default function Create({
    associationOptions,
}: {
    associationOptions: DocumentAssociationOptions;
}) {
    return (
        <AppLayout title="Subir documento">
            <Head title="Subir documento" />
            <DocumentForm associationOptions={associationOptions} />
            <div className="mt-4 flex justify-start">
                <Button variant="outline" asChild>
                    <Link href="/documents">Cancelar</Link>
                </Button>
            </div>
        </AppLayout>
    );
}
