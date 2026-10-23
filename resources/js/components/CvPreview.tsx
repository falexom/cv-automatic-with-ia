import type { ReactNode } from 'react';
import {
    formatRange,
    formatYearMonth,
    SKILL_CATEGORY_LABELS,
    type CvPreviewData,
    type CvPreviewPersonalData,
} from '@/lib/cv-preview';

type CvPreviewProps = {
    preview: CvPreviewData;
};

function ExternalLink({
    href,
    children,
}: {
    href: string;
    children: ReactNode;
}) {
    return (
        <a
            href={href}
            target="_blank"
            rel="noreferrer"
            className="break-words text-primary underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
        >
            {children}
        </a>
    );
}

function ContactLinks({
    personalData,
}: {
    personalData: CvPreviewPersonalData;
}) {
    const hasContact =
        personalData.phone ||
        personalData.email ||
        personalData.location ||
        personalData.linkedin_url ||
        personalData.github_url ||
        personalData.website_url ||
        personalData.links.length > 0;

    if (!hasContact) {
        return null;
    }

    return (
        <ul className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
            {personalData.phone ? (
                <li>
                    <a
                        href={`tel:${personalData.phone}`}
                        className="underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        {personalData.phone}
                    </a>
                </li>
            ) : null}
            {personalData.email ? (
                <li>
                    <a
                        href={`mailto:${personalData.email}`}
                        className="underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        {personalData.email}
                    </a>
                </li>
            ) : null}
            {personalData.location ? <li>{personalData.location}</li> : null}
            {personalData.linkedin_url ? (
                <li>
                    <ExternalLink href={personalData.linkedin_url}>
                        LinkedIn
                    </ExternalLink>
                </li>
            ) : null}
            {personalData.github_url ? (
                <li>
                    <ExternalLink href={personalData.github_url}>
                        GitHub
                    </ExternalLink>
                </li>
            ) : null}
            {personalData.website_url ? (
                <li>
                    <ExternalLink href={personalData.website_url}>
                        Sitio web
                    </ExternalLink>
                </li>
            ) : null}
            {personalData.links.map((link) => (
                <li key={link.id}>
                    <ExternalLink href={link.url}>{link.label}</ExternalLink>
                </li>
            ))}
        </ul>
    );
}

export default function CvPreview({ preview }: CvPreviewProps) {
    const {
        personal_data: personalData,
        professional_title,
        summary,
        content,
    } = preview;

    const fullName = personalData.full_name?.trim()
        ? personalData.full_name
        : null;

    return (
        <article
            aria-labelledby="cv-preview-title"
            className="mx-auto w-full max-w-3xl rounded-xl border bg-background p-6 shadow-sm sm:p-10"
        >
            <header className="space-y-3 border-b pb-6">
                <h2
                    id="cv-preview-title"
                    className={
                        fullName
                            ? 'text-2xl font-semibold tracking-tight break-words'
                            : 'sr-only'
                    }
                >
                    {fullName ?? 'Currículum'}
                </h2>

                <ContactLinks personalData={personalData} />

                {professional_title ? (
                    <p className="text-lg font-medium break-words">
                        {professional_title}
                    </p>
                ) : null}

                {summary ? (
                    <p className="text-sm break-words whitespace-pre-line text-muted-foreground">
                        {summary}
                    </p>
                ) : null}
            </header>

            <div className="space-y-6">
                {content.work_experiences.length > 0 ? (
                    <section
                        aria-labelledby="cv-experience-heading"
                        className="space-y-3 border-b pb-6 last:border-0 last:pb-0"
                    >
                        <h3
                            id="cv-experience-heading"
                            className="text-lg font-semibold"
                        >
                            Experiencia laboral
                        </h3>
                        <div className="space-y-4">
                            {content.work_experiences.map((item) => {
                                const range = formatRange(
                                    item.start_year,
                                    item.start_month,
                                    item.end_year,
                                    item.end_month,
                                    item.is_current,
                                );

                                return (
                                    <div key={item.id} className="space-y-1">
                                        <p className="font-medium break-words">
                                            {item.position} — {item.company}
                                        </p>
                                        {range ? (
                                            <p className="text-sm text-muted-foreground">
                                                {range}
                                            </p>
                                        ) : null}
                                        {item.description ? (
                                            <p className="text-sm break-words whitespace-pre-line">
                                                {item.description}
                                            </p>
                                        ) : null}
                                        {item.responsibilities ? (
                                            <p className="text-sm break-words whitespace-pre-line">
                                                {item.responsibilities}
                                            </p>
                                        ) : null}
                                        {item.achievements ? (
                                            <p className="text-sm break-words whitespace-pre-line">
                                                {item.achievements}
                                            </p>
                                        ) : null}
                                    </div>
                                );
                            })}
                        </div>
                    </section>
                ) : null}

                {content.education_entries.length > 0 ? (
                    <section
                        aria-labelledby="cv-education-heading"
                        className="space-y-3 border-b pb-6 last:border-0 last:pb-0"
                    >
                        <h3
                            id="cv-education-heading"
                            className="text-lg font-semibold"
                        >
                            Educación
                        </h3>
                        <div className="space-y-4">
                            {content.education_entries.map((item) => {
                                const range = formatRange(
                                    item.start_year,
                                    item.start_month,
                                    item.end_year,
                                    item.end_month,
                                    item.is_in_progress,
                                    'en curso',
                                );

                                return (
                                    <div key={item.id} className="space-y-1">
                                        <p className="font-medium break-words">
                                            {item.program} — {item.institution}
                                        </p>
                                        {range ? (
                                            <p className="text-sm text-muted-foreground">
                                                {range}
                                            </p>
                                        ) : null}
                                        {item.degree_level ? (
                                            <p className="text-sm">
                                                {item.degree_level}
                                            </p>
                                        ) : null}
                                        {item.description ? (
                                            <p className="text-sm break-words whitespace-pre-line">
                                                {item.description}
                                            </p>
                                        ) : null}
                                    </div>
                                );
                            })}
                        </div>
                    </section>
                ) : null}

                {content.certifications.length > 0 ? (
                    <section
                        aria-labelledby="cv-certifications-heading"
                        className="space-y-3 border-b pb-6 last:border-0 last:pb-0"
                    >
                        <h3
                            id="cv-certifications-heading"
                            className="text-lg font-semibold"
                        >
                            Cursos y certificaciones
                        </h3>
                        <div className="space-y-4">
                            {content.certifications.map((item) => {
                                const date = formatYearMonth(
                                    item.date_year,
                                    item.date_month,
                                );

                                return (
                                    <div key={item.id} className="space-y-1">
                                        <p className="font-medium break-words">
                                            {item.name}
                                            {item.institution
                                                ? ` — ${item.institution}`
                                                : null}
                                        </p>
                                        {date ? (
                                            <p className="text-sm text-muted-foreground">
                                                {date}
                                            </p>
                                        ) : null}
                                        {item.duration ? (
                                            <p className="text-sm">
                                                Duración: {item.duration}
                                            </p>
                                        ) : null}
                                        {item.category ? (
                                            <p className="text-sm">
                                                Categoría: {item.category}
                                            </p>
                                        ) : null}
                                        {item.code ? (
                                            <p className="text-sm">
                                                Código: {item.code}
                                            </p>
                                        ) : null}
                                        {item.validation_url ? (
                                            <p className="text-sm">
                                                <ExternalLink
                                                    href={item.validation_url}
                                                >
                                                    Validar certificación
                                                </ExternalLink>
                                            </p>
                                        ) : null}
                                    </div>
                                );
                            })}
                        </div>
                    </section>
                ) : null}

                {content.skills.length > 0 ? (
                    <section
                        aria-labelledby="cv-skills-heading"
                        className="space-y-3 border-b pb-6 last:border-0 last:pb-0"
                    >
                        <h3
                            id="cv-skills-heading"
                            className="text-lg font-semibold"
                        >
                            Habilidades
                        </h3>
                        <ul className="list-inside list-disc space-y-1 text-sm">
                            {content.skills.map((item) => (
                                <li key={item.id} className="break-words">
                                    <span className="font-medium">
                                        {item.name}
                                    </span>{' '}
                                    <span className="text-muted-foreground">
                                        {SKILL_CATEGORY_LABELS[item.category] ??
                                            item.category}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </section>
                ) : null}

                {content.projects.length > 0 ? (
                    <section
                        aria-labelledby="cv-projects-heading"
                        className="space-y-3"
                    >
                        <h3
                            id="cv-projects-heading"
                            className="text-lg font-semibold"
                        >
                            Proyectos
                        </h3>
                        <div className="space-y-4">
                            {content.projects.map((item) => {
                                const range = formatRange(
                                    item.start_year,
                                    item.start_month,
                                    item.end_year,
                                    item.end_month,
                                );

                                return (
                                    <div key={item.id} className="space-y-1">
                                        <p className="font-medium break-words">
                                            {item.name}
                                            {item.role
                                                ? ` — ${item.role}`
                                                : null}
                                        </p>
                                        {range ? (
                                            <p className="text-sm text-muted-foreground">
                                                {range}
                                            </p>
                                        ) : null}
                                        {item.description ? (
                                            <p className="text-sm break-words whitespace-pre-line">
                                                {item.description}
                                            </p>
                                        ) : null}
                                        {item.results ? (
                                            <p className="text-sm break-words whitespace-pre-line">
                                                {item.results}
                                            </p>
                                        ) : null}
                                        {item.url ? (
                                            <p className="text-sm">
                                                <ExternalLink href={item.url}>
                                                    Ver proyecto
                                                </ExternalLink>
                                            </p>
                                        ) : null}
                                    </div>
                                );
                            })}
                        </div>
                    </section>
                ) : null}
            </div>
        </article>
    );
}
