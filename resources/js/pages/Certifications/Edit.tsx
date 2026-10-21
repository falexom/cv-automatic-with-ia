import { Head } from '@inertiajs/react';
import LinkedDocumentsSection, {
    type LinkedDocument,
} from '@/components/documents/LinkedDocumentsSection';
import CertificationForm, {
    type CertificationFormRecord,
} from '@/components/forms/CertificationForm';
import type { SkillOption } from '@/components/SkillMultiSelect';
import AppLayout from '@/layouts/AppLayout';

export default function Edit({
    record,
    skills,
    documents,
}: {
    record: CertificationFormRecord;
    skills: SkillOption[];
    documents: LinkedDocument[];
}) {
    return (
        <AppLayout title={`Editar ${record.name}`}>
            <Head title={`Editar ${record.name}`} />
            <div className="space-y-6">
                <CertificationForm record={record} skills={skills} />
                <LinkedDocumentsSection
                    entityType="certification"
                    entityId={record.id}
                    documents={documents}
                />
            </div>
        </AppLayout>
    );
}
