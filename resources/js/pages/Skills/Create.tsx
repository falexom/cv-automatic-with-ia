import { Head } from '@inertiajs/react';
import SkillForm from '@/components/forms/SkillForm';
import AppLayout from '@/layouts/AppLayout';

export default function Create() {
    return (
        <AppLayout title="Nueva habilidad">
            <Head title="Nueva habilidad" />
            <SkillForm />
        </AppLayout>
    );
}
