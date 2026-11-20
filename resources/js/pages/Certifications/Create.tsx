import { Head } from '@inertiajs/react';
import CertificationForm from '@/components/forms/CertificationForm';
import type { SkillOption } from '@/components/SkillMultiSelect';
import type {
    CertificationImportLimits,
    CertificationImportNotice,
} from '@/lib/certification-import';
import AppLayout from '@/layouts/AppLayout';

export default function Create({
    skills,
    import_notice,
    import_limits,
}: {
    skills: SkillOption[];
    import_notice: CertificationImportNotice;
    import_limits: CertificationImportLimits;
}) {
    return (
        <AppLayout title="Nueva certificación">
            <Head title="Nueva certificación" />
            <CertificationForm
                skills={skills}
                importNotice={import_notice}
                importLimits={import_limits}
            />
        </AppLayout>
    );
}
