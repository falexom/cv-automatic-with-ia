import { Head, useForm } from '@inertiajs/react';
import CvProfileForm from '@/components/forms/CvProfileForm';
import AppLayout from '@/layouts/AppLayout';
import {
    emptyCvProfileFormData,
    type CvProfileFormData,
    type CvProfileOptions,
} from '@/lib/cv-profiles';

export default function Create({ options }: { options: CvProfileOptions }) {
    const { data, setData, post, processing, errors } =
        useForm<CvProfileFormData>(emptyCvProfileFormData());

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post('/profiles');
    };

    return (
        <AppLayout title="Nuevo perfil de CV">
            <Head title="Nuevo perfil de CV" />
            <CvProfileForm
                data={data}
                options={options}
                errors={errors}
                processing={processing}
                submitLabel="Crear perfil"
                cancelHref="/profiles"
                onChange={(next) => setData(() => next)}
                onSubmit={submit}
            />
        </AppLayout>
    );
}
