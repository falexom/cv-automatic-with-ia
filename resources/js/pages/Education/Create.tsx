import { Head } from '@inertiajs/react';
import EducationForm from '@/components/forms/EducationForm';
import AppLayout from '@/layouts/AppLayout';

export default function Create() {
    return (
        <AppLayout title="Nueva formación">
            <Head title="Nueva formación" />
            <EducationForm />
        </AppLayout>
    );
}
