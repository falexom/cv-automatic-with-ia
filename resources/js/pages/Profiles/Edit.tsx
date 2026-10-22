import { Head, useForm } from '@inertiajs/react';
import CvProfileForm from '@/components/forms/CvProfileForm';
import AppLayout from '@/layouts/AppLayout';
import {
    type CvProfileOptions,
    type CvProfileOwnData,
    type CvProfileSelections,
    type CvProfileFormData,
} from '@/lib/cv-profiles';

export default function Edit({
    profile,
    options,
    selections,
}: {
    profile: CvProfileOwnData;
    options: CvProfileOptions;
    selections: CvProfileSelections;
}) {
    const { data, setData, put, processing, errors } =
        useForm<CvProfileFormData>({
            name: profile.name,
            professional_title: profile.professional_title ?? '',
            summary: profile.summary ?? '',
            ...selections,
        });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        put(`/profiles/${profile.id}`);
    };

    return (
        <AppLayout title="Editar perfil de CV">
            <Head title={`Editar ${profile.name}`} />
            <CvProfileForm
                data={data}
                options={options}
                errors={errors}
                processing={processing}
                submitLabel="Guardar cambios"
                cancelHref={`/profiles/${profile.id}`}
                onChange={(next) => setData(() => next)}
                onSubmit={submit}
            />
        </AppLayout>
    );
}
