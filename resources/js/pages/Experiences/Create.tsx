import { Head } from '@inertiajs/react';
import WorkExperienceForm from '@/components/forms/WorkExperienceForm';
import type { SkillOption } from '@/components/SkillMultiSelect';
import AppLayout from '@/layouts/AppLayout';

export default function Create({ skills }: { skills: SkillOption[] }) {
    return (
        <AppLayout title="Nueva experiencia">
            <Head title="Nueva experiencia" />
            <WorkExperienceForm skills={skills} />
        </AppLayout>
    );
}
