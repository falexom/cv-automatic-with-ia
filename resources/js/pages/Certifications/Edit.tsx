import { Head } from '@inertiajs/react';
import CertificationForm, {
    type CertificationFormRecord,
} from '@/components/forms/CertificationForm';
import type { SkillOption } from '@/components/SkillMultiSelect';
import AppLayout from '@/layouts/AppLayout';

export default function Edit({
    record,
    skills,
}: {
    record: CertificationFormRecord;
    skills: SkillOption[];
}) {
    return (
        <AppLayout title={`Editar ${record.name}`}>
            <Head title={`Editar ${record.name}`} />
            <CertificationForm record={record} skills={skills} />
        </AppLayout>
    );
}
