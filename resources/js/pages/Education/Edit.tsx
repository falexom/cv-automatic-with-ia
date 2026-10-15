import { Head } from '@inertiajs/react';
import EducationForm, {
    type EducationFormRecord,
} from '@/components/forms/EducationForm';
import AppLayout from '@/layouts/AppLayout';

export default function Edit({ record }: { record: EducationFormRecord }) {
    return (
        <AppLayout title={`Editar ${record.program}`}>
            <Head title={`Editar ${record.program}`} />
            <EducationForm record={record} />
        </AppLayout>
    );
}
