import { Head } from '@inertiajs/react';
import ProjectForm from '@/components/forms/ProjectForm';
import type { SkillOption } from '@/components/SkillMultiSelect';
import AppLayout from '@/layouts/AppLayout';

export default function Create({ skills }: { skills: SkillOption[] }) {
    return (
        <AppLayout title="Nuevo proyecto">
            <Head title="Nuevo proyecto" />
            <ProjectForm skills={skills} />
        </AppLayout>
    );
}
