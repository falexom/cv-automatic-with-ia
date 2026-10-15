import { Head, Link } from '@inertiajs/react';
import SkillForm, { type SkillFormRecord } from '@/components/forms/SkillForm';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout';

type UsageItem = {
    id: number;
    label: string;
};

type Usage = {
    experiences: UsageItem[];
    certifications: UsageItem[];
    projects: UsageItem[];
};

function UsageSection({
    title,
    items,
    hrefBase,
}: {
    title: string;
    items: UsageItem[];
    hrefBase: string;
}) {
    return (
        <div>
            <h3 className="text-sm font-medium">{title}</h3>
            {items.length === 0 ? (
                <p className="text-sm text-muted-foreground">Sin usos.</p>
            ) : (
                <ul className="mt-1 space-y-0.5">
                    {items.map((item) => (
                        <li key={item.id} className="text-sm">
                            <Link
                                href={`${hrefBase}/${item.id}/edit`}
                                className="text-primary underline-offset-4 hover:underline"
                            >
                                {item.label}
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

export default function Edit({
    record,
    usage,
}: {
    record: SkillFormRecord;
    usage: Usage;
}) {
    const hasUsage =
        usage.experiences.length > 0 ||
        usage.certifications.length > 0 ||
        usage.projects.length > 0;

    return (
        <AppLayout title={`Editar ${record.name}`}>
            <Head title={`Editar ${record.name}`} />
            <div className="space-y-6">
                <SkillForm record={record} />

                <Card>
                    <CardHeader>
                        <CardTitle>Se usa en</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {hasUsage ? (
                            <>
                                <UsageSection
                                    title="Experiencias"
                                    items={usage.experiences}
                                    hrefBase="/experiences"
                                />
                                <UsageSection
                                    title="Cursos y certificaciones"
                                    items={usage.certifications}
                                    hrefBase="/certifications"
                                />
                                <UsageSection
                                    title="Proyectos"
                                    items={usage.projects}
                                    hrefBase="/projects"
                                />
                            </>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Todavía no se usa en ningún registro.
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
