import { Head } from '@inertiajs/react';
import WorkExperienceForm, {
    type WorkExperienceFormRecord,
} from '@/components/forms/WorkExperienceForm';
import type { SkillOption } from '@/components/SkillMultiSelect';
import AppLayout from '@/layouts/AppLayout';
import { formatRange } from '@/lib/dates';

export default function Edit({
    record,
    skills,
}: {
    record: WorkExperienceFormRecord;
    skills: SkillOption[];
}) {
    const range = formatRange(
        record.start_year,
        record.start_month,
        record.end_year,
        record.end_month,
        record.is_current,
    );

    return (
        <AppLayout
            title={
                range
                    ? `Editar ${record.position} (${range})`
                    : `Editar ${record.position}`
            }
        >
            <Head title={`Editar ${record.position}`} />
            <WorkExperienceForm record={record} skills={skills} />
        </AppLayout>
    );
}
