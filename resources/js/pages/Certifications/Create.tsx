import { Head } from '@inertiajs/react';
import CertificationForm from '@/components/forms/CertificationForm';
import type { SkillOption } from '@/components/SkillMultiSelect';
import AppLayout from '@/layouts/AppLayout';

export default function Create({ skills }: { skills: SkillOption[] }) {
    return (
        <AppLayout title="Nueva certificación">
            <Head title="Nueva certificación" />
            <CertificationForm skills={skills} />
        </AppLayout>
    );
}
