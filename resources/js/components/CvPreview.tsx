import type { ReactNode } from 'react';
import CvPhotoFrame from '@/components/CvPhotoFrame';
import {
    formatRange,
    formatYearMonth,
    SKILL_CATEGORY_LABELS,
    type CvPreviewData,
    type CvPreviewPersonalData,
} from '@/lib/cv-preview';
import { SKILL_CATEGORY_ORDER } from '@/lib/skill-categories';

type CvPreviewProps = {
    preview: CvPreviewData;
};

// Print-only pagination hint: entries this long cannot fit on a single page
// and must be allowed to continue instead of being pushed whole to a new page.
const LONG_ENTRY_CHARACTERS = 1600;

function isLongEntry(...values: Array<string | null | undefined>): boolean {
    const length = values.reduce(
        (total, value) => total + (value?.length ?? 0),
        0,
    );

    return length > LONG_ENTRY_CHARACTERS;
}

function longClassName(
    base: string,
    ...values: Array<string | null | undefined>
) {
    return isLongEntry(...values) ? `${base} ${base}--long` : base;
}

/**
 * Split a project description into readable blocks without changing its
 * wording. Explicit blank lines are respected. A single long paragraph is
 * grouped in pairs of sentences so print pagination can continue it.
 */
function projectParagraphs(description: string | null): string[] {
    if (!description) {
        return [];
    }

    const normalized = description.replace(/\r\n/g, '\n').trim();

    if (!normalized) {
        return [];
    }

    const explicit = normalized
        .split(/\n{2,}/)
        .map((paragraph) => paragraph.trim())
        .filter(Boolean);

    if (explicit.length > 1) {
        return explicit;
    }

    const singleLine = normalized.replace(/\s*\n\s*/g, ' ').trim();
    const sentences = singleLine.split(/(?<=[.!?])\s+/).filter(Boolean);

    if (sentences.length <= 2) {
        return [singleLine];
    }

    const paragraphs: string[] = [];

    for (let index = 0; index < sentences.length; index += 2) {
        paragraphs.push(
            sentences
                .slice(index, index + 2)
                .join(' ')
                .trim(),
        );
    }

    return paragraphs;
}

function SectionHeading({
    id,
    index,
    children,
}: {
    id: string;
    index: number;
    children: ReactNode;
}) {
    return (
        <div className="cv-section-head">
            <span className="cv-section-index" aria-hidden="true">
                {String(index).padStart(2, '0')}
            </span>
            <h3 id={id} className="cv-section-title">
                {children}
            </h3>
            <span className="cv-section-line" aria-hidden="true" />
        </div>
    );
}

function SidebarHeading({ id, children }: { id: string; children: ReactNode }) {
    return (
        <h3 id={id} className="cv-side-title">
            {children}
        </h3>
    );
}

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
            className="cv-link focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
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
    const rows: Array<{ key: string; label: string; value: ReactNode }> = [];

    if (personalData.phone) {
        rows.push({
            key: 'phone',
            label: 'Teléfono',
            value: (
                <a href={`tel:${personalData.phone}`} className="cv-link">
                    {personalData.phone}
                </a>
            ),
        });
    }

    if (personalData.email) {
        rows.push({
            key: 'email',
            label: 'Email',
            value: (
                <a href={`mailto:${personalData.email}`} className="cv-link">
                    {personalData.email}
                </a>
            ),
        });
    }

    if (personalData.location) {
        rows.push({
            key: 'location',
            label: 'Ubicación',
            value: personalData.location,
        });
    }

    if (personalData.linkedin_url) {
        rows.push({
            key: 'linkedin',
            label: 'LinkedIn',
            value: (
                <ExternalLink href={personalData.linkedin_url}>
                    LinkedIn
                </ExternalLink>
            ),
        });
    }

    if (personalData.github_url) {
        rows.push({
            key: 'github',
            label: 'GitHub',
            value: (
                <ExternalLink href={personalData.github_url}>
                    GitHub
                </ExternalLink>
            ),
        });
    }

    if (personalData.website_url) {
        rows.push({
            key: 'website',
            label: 'Web',
            value: (
                <ExternalLink href={personalData.website_url}>
                    Sitio web
                </ExternalLink>
            ),
        });
    }

    personalData.links.forEach((link) => {
        rows.push({
            key: `link-${link.id}`,
            label: 'Enlace',
            value: <ExternalLink href={link.url}>{link.label}</ExternalLink>,
        });
    });

    if (rows.length === 0) {
        return null;
    }

    return (
        <section
            aria-labelledby="cv-contact-heading"
            className="cv-side-section cv-contact"
        >
            <SidebarHeading id="cv-contact-heading">Contacto</SidebarHeading>
            <div className="cv-contact-list">
                {rows.map((row) => (
                    <div className="cv-contact-row" key={row.key}>
                        <span className="cv-contact-label">{row.label}</span>
                        <span className="cv-contact-value">{row.value}</span>
                    </div>
                ))}
            </div>
        </section>
    );
}

function skillCategoryLabel(category: string): string {
    return (
        SKILL_CATEGORY_LABELS[category] ??
        category
            .replace(/_/g, ' ')
            .replace(/\b\w/g, (letter) => letter.toUpperCase())
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
    const photo = personalData.photo ?? null;

    const mainSections: Array<{
        id: string;
        title: string;
        content: ReactNode;
    }> = [];

    if (summary) {
        mainSections.push({
            id: 'cv-summary-heading',
            title: 'Resumen',
            content: (
                <p className="cv-summary break-words whitespace-pre-line">
                    {summary}
                </p>
            ),
        });
    }

    if (content.work_experiences.length > 0) {
        mainSections.push({
            id: 'cv-experience-heading',
            title: 'Experiencia laboral',
            content: content.work_experiences.map((item) => {
                const range = formatRange(
                    item.start_year,
                    item.start_month,
                    item.end_year,
                    item.end_month,
                    item.is_current,
                );
                const responsibilities = (item.responsibilities ?? '')
                    .split('\n')
                    .map((line) => line.trim())
                    .filter(Boolean);
                const className = [
                    longClassName(
                        'cv-entry',
                        item.position,
                        item.company,
                        item.description,
                        item.responsibilities,
                        item.achievements,
                    ),
                    range ? '' : 'cv-entry--no-date',
                ]
                    .filter(Boolean)
                    .join(' ');

                return (
                    <div className={className} key={item.id}>
                        {range ? (
                            <div className="cv-entry-date">
                                <span className="cv-entry-range">{range}</span>
                            </div>
                        ) : null}
                        <div className="cv-entry-body">
                            {item.position ? (
                                <h4 className="cv-entry-role">
                                    {item.position}
                                </h4>
                            ) : null}
                            {item.company ? (
                                <p className="cv-entry-company">
                                    {item.company}
                                </p>
                            ) : null}
                            {item.description ? (
                                <p className="cv-entry-desc break-words whitespace-pre-line">
                                    {item.description}
                                </p>
                            ) : null}
                            {responsibilities.length > 0 ? (
                                <>
                                    {item.description ? (
                                        <p className="cv-entry-label">
                                            Responsabilidades
                                        </p>
                                    ) : null}
                                    <ul className="cv-entry-list">
                                        {responsibilities.map(
                                            (responsibility, index) => (
                                                <li key={`${item.id}-${index}`}>
                                                    {responsibility}
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                </>
                            ) : null}
                            {item.achievements ? (
                                <p className="cv-entry-achievements">
                                    <span>Logros</span> {item.achievements}
                                </p>
                            ) : null}
                        </div>
                    </div>
                );
            }),
        });
    }

    if (content.projects.length > 0) {
        mainSections.push({
            id: 'cv-projects-heading',
            title: 'Proyectos',
            content: content.projects.map((item, index) => {
                const range = formatRange(
                    item.start_year,
                    item.start_month,
                    item.end_year,
                    item.end_month,
                );
                const paragraphs = projectParagraphs(item.description);
                const firstParagraph = paragraphs[0] ?? null;
                const remainingParagraphs = paragraphs.slice(1);
                const hasContinuation =
                    remainingParagraphs.length > 0 || Boolean(item.results);
                const className = longClassName(
                    'cv-project',
                    item.name,
                    item.role,
                    item.description,
                    item.results,
                    item.url,
                );

                return (
                    <div className={className} key={item.id}>
                        <div className="cv-project-intro">
                            <div className="cv-project-top">
                                <div className="cv-project-head">
                                    <span className="cv-project-kicker">
                                        Proyecto{' '}
                                        {String(index + 1).padStart(2, '0')}
                                    </span>
                                    {item.role ? (
                                        <span className="cv-project-role">
                                            {item.role}
                                        </span>
                                    ) : null}
                                </div>
                                <h4 className="cv-project-title">
                                    {item.name}
                                </h4>
                                {range ? (
                                    <p className="cv-project-range">{range}</p>
                                ) : null}
                                {item.url ? (
                                    <p className="cv-project-link">
                                        <ExternalLink href={item.url}>
                                            Ver proyecto
                                        </ExternalLink>{' '}
                                        <span aria-hidden="true">↗</span>
                                    </p>
                                ) : null}
                            </div>
                            {firstParagraph ? (
                                <div className="cv-project-desc">
                                    <p>{firstParagraph}</p>
                                </div>
                            ) : null}
                            {!firstParagraph && item.results ? (
                                <div className="cv-project-desc">
                                    <p>{item.results}</p>
                                </div>
                            ) : null}
                        </div>
                        {hasContinuation ? (
                            <div className="cv-project-desc cv-project-continuation">
                                {remainingParagraphs.map(
                                    (paragraph, paragraphIndex) => (
                                        <p key={`${item.id}-${paragraphIndex}`}>
                                            {paragraph}
                                        </p>
                                    ),
                                )}
                                {firstParagraph && item.results ? (
                                    <p>{item.results}</p>
                                ) : null}
                            </div>
                        ) : null}
                    </div>
                );
            }),
        });
    }

    if (content.certifications.length > 0) {
        mainSections.push({
            id: 'cv-certifications-heading',
            title: 'Cursos y certificaciones',
            content: content.certifications.map((item) => {
                const date = formatYearMonth(item.date_year, item.date_month);
                const details = [item.institution, item.duration, item.category]
                    .filter(Boolean)
                    .join(' · ');

                return (
                    <div
                        className={`cv-cert-row${
                            date ? '' : ' cv-cert-row--no-date'
                        }`}
                        key={item.id}
                    >
                        {date ? (
                            <div className="cv-cert-date">{date}</div>
                        ) : null}
                        <div className="cv-cert-main">
                            <h4 className="cv-cert-name">{item.name}</h4>
                            {details ? (
                                <p className="cv-cert-meta">{details}</p>
                            ) : null}
                            {item.code ? (
                                <span className="cv-cert-code">
                                    Código: {item.code}
                                </span>
                            ) : null}
                        </div>
                        <div className="cv-cert-action">
                            {item.validation_url ? (
                                <ExternalLink href={item.validation_url}>
                                    Validar
                                </ExternalLink>
                            ) : null}
                        </div>
                    </div>
                );
            }),
        });
    }

    const skillCategories = [
        ...SKILL_CATEGORY_ORDER,
        ...Array.from(
            new Set(content.skills.map((skill) => skill.category)),
        ).filter((category) => !SKILL_CATEGORY_ORDER.includes(category)),
    ];

    const skillGroups = skillCategories
        .map((category) => ({
            category,
            items: content.skills.filter(
                (skill) => skill.category === category,
            ),
        }))
        .filter((group) => group.items.length > 0);

    return (
        <div className="cv-preview-frame">
            <div className="cv-print-sidebar-bg" aria-hidden="true" />

            <article
                aria-labelledby="cv-preview-title"
                className="cv-preview-sheet mx-auto w-full max-w-[210mm] overflow-hidden rounded-xl border bg-background shadow-sm"
            >
                <table className="cv-layout" role="presentation">
                    <caption className="cv-header-caption">
                        <header
                            className={`cv-header-band${
                                photo ? '' : ' cv-header-band--no-photo'
                            }`}
                        >
                            {photo ? (
                                <div className="cv-header-photo">
                                    <CvPhotoFrame
                                        photo={photo}
                                        className="cv-photo-frame h-[36mm] w-[36mm]"
                                    />
                                </div>
                            ) : null}
                            <div className="cv-header-main">
                                <p className="cv-eyebrow">
                                    Currículum profesional
                                </p>
                                <h2
                                    id="cv-preview-title"
                                    className={
                                        fullName ? 'cv-name' : 'cv-name sr-only'
                                    }
                                >
                                    {fullName ?? 'Currículum'}
                                </h2>
                                {professional_title ? (
                                    <p className="cv-title">
                                        {professional_title}
                                    </p>
                                ) : null}
                                <span
                                    className="cv-header-rule"
                                    aria-hidden="true"
                                />
                            </div>
                        </header>
                    </caption>
                    <colgroup>
                        <col className="cv-col-main" />
                        <col className="cv-col-side" />
                    </colgroup>
                    <thead>
                        <tr>
                            <th />
                            <th />
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <td />
                            <td />
                        </tr>
                    </tfoot>
                    <tbody>
                        <tr>
                            <td className="cv-main" data-testid="cv-main">
                                {mainSections.map((section, index) => (
                                    <section
                                        aria-labelledby={section.id}
                                        className="cv-section"
                                        key={section.id}
                                    >
                                        <SectionHeading
                                            id={section.id}
                                            index={index + 1}
                                        >
                                            {section.title}
                                        </SectionHeading>
                                        {section.content}
                                    </section>
                                ))}
                            </td>
                            <td className="cv-sidebar" data-testid="cv-sidebar">
                                <ContactLinks personalData={personalData} />

                                {content.education_entries.length > 0 ? (
                                    <section
                                        aria-labelledby="cv-education-heading"
                                        className="cv-side-section"
                                    >
                                        <SidebarHeading id="cv-education-heading">
                                            Educación
                                        </SidebarHeading>
                                        {content.education_entries.map(
                                            (item) => {
                                                const range = formatRange(
                                                    item.start_year,
                                                    item.start_month,
                                                    item.end_year,
                                                    item.end_month,
                                                    item.is_in_progress,
                                                    'en curso',
                                                );

                                                return (
                                                    <div
                                                        className="cv-edu"
                                                        key={item.id}
                                                    >
                                                        {item.program ? (
                                                            <p className="cv-edu-program">
                                                                {item.program}
                                                            </p>
                                                        ) : null}
                                                        {item.institution ? (
                                                            <p className="cv-edu-institution">
                                                                {
                                                                    item.institution
                                                                }
                                                            </p>
                                                        ) : null}
                                                        {range ||
                                                        item.degree_level ? (
                                                            <p className="cv-edu-meta">
                                                                {range ? (
                                                                    <span>
                                                                        {range}
                                                                    </span>
                                                                ) : null}
                                                                {range &&
                                                                item.degree_level ? (
                                                                    <span
                                                                        className="cv-edu-sep"
                                                                        aria-hidden="true"
                                                                    >
                                                                        {' · '}
                                                                    </span>
                                                                ) : null}
                                                                {item.degree_level ? (
                                                                    <span>
                                                                        {
                                                                            item.degree_level
                                                                        }
                                                                    </span>
                                                                ) : null}
                                                            </p>
                                                        ) : null}
                                                    </div>
                                                );
                                            },
                                        )}
                                    </section>
                                ) : null}

                                {skillGroups.length > 0 ? (
                                    <section
                                        aria-labelledby="cv-skills-heading"
                                        className="cv-side-section"
                                    >
                                        <SidebarHeading id="cv-skills-heading">
                                            Habilidades
                                        </SidebarHeading>
                                        {skillGroups.map((group) => (
                                            <div
                                                className="cv-skill-group"
                                                key={group.category}
                                            >
                                                <p className="cv-skill-category">
                                                    {skillCategoryLabel(
                                                        group.category,
                                                    )}
                                                </p>
                                                <div className="cv-chips">
                                                    {group.items.map(
                                                        (skill) => (
                                                            <span
                                                                className="cv-chip"
                                                                key={skill.id}
                                                            >
                                                                {skill.name}
                                                            </span>
                                                        ),
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </section>
                                ) : null}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </article>
        </div>
    );
}
