import { Head } from '@inertiajs/react';
import ProjectForm, {
    type ProjectFormRecord,
} from '@/components/forms/ProjectForm';
import type { SkillOption } from '@/components/SkillMultiSelect';
import AppLayout from '@/layouts/AppLayout';

export default function Edit({
    record,
    skills,
}: {
    record: ProjectFormRecord;
    skills: SkillOption[];
}) {
    return (
        <AppLayout title={`Editar ${record.name}`}>
            <Head title={`Editar ${record.name}`} />
            <ProjectForm record={record} skills={skills} />
        </AppLayout>
    );
}
