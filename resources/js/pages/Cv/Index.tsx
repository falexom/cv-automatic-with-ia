import { Head } from '@inertiajs/react';
import CvGeneratorPanel from '@/components/CvGeneratorPanel';
import AppLayout from '@/layouts/AppLayout';
import type { CvGeneratorPageProps } from '@/lib/cv-preview';

export default function Index(props: CvGeneratorPageProps) {
    return (
        <AppLayout title="Generador de CV">
            <Head title="Generador de CV" />
            <CvGeneratorPanel
                key={`${props.status}-${props.selected_profile_id ?? 'none'}`}
                {...props}
            />
        </AppLayout>
    );
}
