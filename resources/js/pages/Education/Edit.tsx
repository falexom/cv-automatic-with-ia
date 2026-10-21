import { Head } from '@inertiajs/react';
import LinkedDocumentsSection, {
    type LinkedDocument,
} from '@/components/documents/LinkedDocumentsSection';
import EducationForm, {
    type EducationFormRecord,
} from '@/components/forms/EducationForm';
import AppLayout from '@/layouts/AppLayout';

export default function Edit({
    record,
    documents,
}: {
    record: EducationFormRecord;
    documents: LinkedDocument[];
}) {
    return (
        <AppLayout title={`Editar ${record.program}`}>
            <Head title={`Editar ${record.program}`} />
            <div className="space-y-6">
                <EducationForm record={record} />
                <LinkedDocumentsSection
                    entityType="education"
                    entityId={record.id}
                    documents={documents}
                />
            </div>
        </AppLayout>
    );
}
