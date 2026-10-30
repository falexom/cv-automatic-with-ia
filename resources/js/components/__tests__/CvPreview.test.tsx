import { render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import CvPreview from '@/components/CvPreview';
import type { CvPreviewData } from '@/lib/cv-preview';

function makePreview(overrides: Partial<CvPreviewData> = {}): CvPreviewData {
    return {
        personal_data: {
            full_name: 'Ada Lovelace',
            phone: '+54 11 5555 1234',
            email: 'ada@example.com',
            location: 'Buenos Aires, Argentina',
            linkedin_url: 'https://www.linkedin.com/in/ada',
            github_url: 'https://github.com/ada',
            website_url: 'https://ada.example.com',
            links: [
                {
                    id: 1,
                    label: 'Blog',
                    url: 'https://blog.example.com',
                },
            ],
            has_data: true,
            photo: null,
        },
        professional_title: 'Analista de Sistemas',
        summary: 'Resumen propio con acentos: estadística.',
        content: {
            work_experiences: [
                {
                    id: 12,
                    position: 'Analista funcional',
                    company: 'Acme',
                    start_year: 2022,
                    start_month: 3,
                    end_year: null,
                    end_month: null,
                    is_current: true,
                    description: 'Relevamiento y documentación de procesos.',
                    responsibilities:
                        'Relevamiento de requerimientos.\nDocumentación funcional.',
                    achievements: 'Reducción de tiempos de proceso en un 30%.',
                },
            ],
            education_entries: [
                {
                    id: 3,
                    institution: 'Universidad',
                    program: 'Sistemas',
                    degree_level: 'Licenciatura',
                    start_year: 2015,
                    start_month: 2,
                    end_year: 2020,
                    end_month: 12,
                    is_in_progress: false,
                    description: 'Formación en análisis y diseño de sistemas.',
                },
            ],
            certifications: [
                {
                    id: 4,
                    name: 'Curso de Laravel',
                    institution: 'Academy',
                    date_year: 2023,
                    date_month: 6,
                    duration: '40 horas',
                    category: 'Desarrollo web',
                    code: 'LAR-2023-001',
                    validation_url:
                        'https://academy.example.com/validar/LAR-2023-001',
                },
            ],
            skills: [{ id: 7, name: 'PHP', category: 'tecnologia' }],
            projects: [
                {
                    id: 9,
                    name: 'Portal interno',
                    role: 'Desarrolladora',
                    start_year: 2021,
                    start_month: 1,
                    end_year: 2021,
                    end_month: 9,
                    description: 'Migración del portal corporativo.',
                    results: 'Reducción del tiempo de carga en un 40%.',
                    url: 'https://example.com/proyecto',
                },
            ],
        },
        source_links: {
            profile: '/profiles/1/edit',
            personal_data: '/profile',
            work_experiences: '/experiences',
            education_entries: '/education',
            certifications: '/certifications',
            skills: '/skills',
            projects: '/projects',
        },
        has_data: true,
        ...overrides,
    };
}

describe('CvPreview', () => {
    it('renders one read-only article with a semantic heading hierarchy', () => {
        const { container } = render(<CvPreview preview={makePreview()} />);

        const article = screen.getByRole('article', { name: 'Ada Lovelace' });

        expect(
            within(article).getByRole('heading', {
                level: 2,
                name: 'Ada Lovelace',
            }),
        ).toBeInTheDocument();
        expect(
            within(article).getByRole('heading', {
                level: 3,
                name: 'Experiencia laboral',
            }),
        ).toBeInTheDocument();
        expect(
            within(article).getByRole('heading', {
                level: 3,
                name: 'Educación',
            }),
        ).toBeInTheDocument();
        expect(
            within(article).getByRole('heading', {
                level: 3,
                name: 'Cursos y certificaciones',
            }),
        ).toBeInTheDocument();
        expect(
            within(article).getByRole('heading', {
                level: 3,
                name: 'Habilidades',
            }),
        ).toBeInTheDocument();
        expect(
            within(article).getByRole('heading', {
                level: 3,
                name: 'Proyectos',
            }),
        ).toBeInTheDocument();

        expect(container.querySelector('form')).toBeNull();
        expect(screen.queryByRole('button')).not.toBeInTheDocument();
        expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
        expect(screen.queryByRole('combobox')).not.toBeInTheDocument();
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
        expect(
            screen.queryByText(/plantilla|exportar|imprimir|descargar|PDF/i),
        ).not.toBeInTheDocument();
    });

    it('renders the saved photo in a 30 by 30 mm frame with the stored crop', () => {
        const base = makePreview();

        render(
            <CvPreview
                preview={makePreview({
                    personal_data: {
                        ...base.personal_data,
                        photo: {
                            data_uri: 'data:image/jpeg;base64,Zm90bw==',
                            focus_x: 40,
                            focus_y: 60,
                            zoom: 150,
                        },
                    },
                })}
            />,
        );

        const image = screen.getByRole('img', {
            name: 'Fotografía profesional',
        });
        const frame = screen.getByTestId('cv-photo-frame');

        expect(frame).toContainElement(image);
        expect(frame).toHaveClass('h-[30mm]', 'w-[30mm]');
        expect(image).toHaveStyle({
            objectPosition: '40% 60%',
            transform: 'scale(1.5)',
            transformOrigin: '40% 60%',
        });
        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });

    it('does not reserve photo space when the master profile has no photo', () => {
        render(<CvPreview preview={makePreview()} />);

        expect(screen.queryByTestId('cv-photo-frame')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('img', { name: 'Fotografía profesional' }),
        ).not.toBeInTheDocument();
    });

    it('renders contact and external links with safe attributes', () => {
        render(<CvPreview preview={makePreview()} />);

        const phone = screen.getByRole('link', {
            name: '+54 11 5555 1234',
        });
        expect(phone).toHaveAttribute('href', 'tel:+54 11 5555 1234');
        expect(phone).not.toHaveAttribute('target');

        const email = screen.getByRole('link', { name: 'ada@example.com' });
        expect(email).toHaveAttribute('href', 'mailto:ada@example.com');
        expect(email).not.toHaveAttribute('target');

        const externalLinks = [
            ['LinkedIn', 'https://www.linkedin.com/in/ada'],
            ['GitHub', 'https://github.com/ada'],
            ['Sitio web', 'https://ada.example.com'],
            ['Blog', 'https://blog.example.com'],
        ] as const;

        for (const [name, href] of externalLinks) {
            const link = screen.getByRole('link', { name });
            expect(link).toHaveAttribute('href', href);
            expect(link).toHaveAttribute('target', '_blank');
            expect(link).toHaveAttribute('rel', 'noreferrer');
        }

        expect(screen.getByText('Buenos Aires, Argentina')).toBeInTheDocument();
    });

    it('renders FR-011 fields, dates, categories and validation links', () => {
        render(<CvPreview preview={makePreview()} />);

        expect(screen.getByText('Analista de Sistemas')).toBeInTheDocument();
        expect(
            screen.getByText('Resumen propio con acentos: estadística.'),
        ).toHaveClass('whitespace-pre-line');
        expect(screen.getByText('mar 2022 – actual')).toBeInTheDocument();
        expect(screen.getByText('feb 2015 – dic 2020')).toBeInTheDocument();
        expect(screen.getByText('jun 2023')).toBeInTheDocument();
        expect(screen.getByText('ene 2021 – sep 2021')).toBeInTheDocument();

        expect(screen.getByText(/Analista funcional/)).toBeInTheDocument();
        expect(screen.getByText(/Acme/)).toBeInTheDocument();
        expect(
            screen.getByText('Relevamiento y documentación de procesos.'),
        ).toBeInTheDocument();
        const responsibilities = screen.getByText(
            /Relevamiento de requerimientos/,
        );
        expect(responsibilities).toHaveClass('whitespace-pre-line');
        expect(
            screen.getByText(/Reducción de tiempos de proceso/),
        ).toBeInTheDocument();

        expect(screen.getByText(/^Sistemas/)).toBeInTheDocument();
        expect(screen.getByText(/Universidad/)).toBeInTheDocument();
        expect(screen.getByText('Licenciatura')).toBeInTheDocument();
        expect(
            screen.getByText('Formación en análisis y diseño de sistemas.'),
        ).toBeInTheDocument();

        expect(screen.getByText(/Curso de Laravel/)).toBeInTheDocument();
        expect(screen.getByText(/Academy/)).toBeInTheDocument();
        expect(screen.getByText(/40 horas/)).toBeInTheDocument();
        expect(screen.getByText(/Desarrollo web/)).toBeInTheDocument();
        expect(screen.getByText('Código: LAR-2023-001')).toBeInTheDocument();

        const validation = screen.getByRole('link', {
            name: 'Validar certificación',
        });
        expect(validation).toHaveAttribute(
            'href',
            'https://academy.example.com/validar/LAR-2023-001',
        );
        expect(validation).toHaveAttribute('target', '_blank');
        expect(validation).toHaveAttribute('rel', 'noreferrer');

        expect(screen.getByText('PHP')).toBeInTheDocument();
        expect(screen.getByText('Tecnologías')).toBeInTheDocument();

        expect(screen.getByText(/Portal interno/)).toBeInTheDocument();
        expect(screen.getByText(/Desarrolladora/)).toBeInTheDocument();
        expect(
            screen.getByText('Migración del portal corporativo.'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(/Reducción del tiempo de carga/),
        ).toBeInTheDocument();

        const projectLink = screen.getByRole('link', {
            name: 'Ver proyecto',
        });
        expect(projectLink).toHaveAttribute(
            'href',
            'https://example.com/proyecto',
        );
        expect(projectLink).toHaveAttribute('target', '_blank');
        expect(projectLink).toHaveAttribute('rel', 'noreferrer');
    });

    it('omits empty sections and preserves the received order', () => {
        const base = makePreview();
        const first = base.content.work_experiences[0];

        render(
            <CvPreview
                preview={makePreview({
                    content: {
                        work_experiences: [
                            {
                                ...first,
                                id: 2,
                                position: 'Puesto B',
                                company: 'Empresa B',
                            },
                            {
                                ...first,
                                id: 1,
                                position: 'Puesto A',
                                company: 'Empresa A',
                            },
                        ],
                        education_entries: [],
                        certifications: [],
                        skills: [],
                        projects: [],
                    },
                })}
            />,
        );

        expect(
            screen.getByRole('heading', {
                level: 3,
                name: 'Experiencia laboral',
            }),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('heading', { level: 3, name: 'Educación' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('heading', {
                level: 3,
                name: 'Cursos y certificaciones',
            }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('heading', { level: 3, name: 'Habilidades' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('heading', { level: 3, name: 'Proyectos' }),
        ).not.toBeInTheDocument();

        const section = screen.getByRole('region', {
            name: 'Experiencia laboral',
        });
        const text = section.textContent ?? '';

        expect(text.indexOf('Puesto B')).toBeLessThan(text.indexOf('Puesto A'));
    });

    it.each([null, '', '   '])(
        'uses a hidden structural heading when full_name is %j',
        (fullName) => {
            const base = makePreview();

            render(
                <CvPreview
                    preview={makePreview({
                        personal_data: {
                            ...base.personal_data,
                            full_name: fullName,
                            phone: null,
                            email: null,
                            location: null,
                            linkedin_url: null,
                            github_url: null,
                            website_url: null,
                            links: [],
                            has_data: false,
                        },
                        professional_title: null,
                        summary: null,
                        content: {
                            work_experiences: [],
                            education_entries: [],
                            certifications: [],
                            skills: [],
                            projects: [],
                        },
                        has_data: false,
                    })}
                />,
            );

            const heading = screen.getByRole('heading', {
                level: 2,
                name: 'Currículum',
            });
            expect(heading).toHaveClass('sr-only');
            expect(screen.queryByText('Ada Lovelace')).not.toBeInTheDocument();
            expect(screen.queryByText('N/D')).not.toBeInTheDocument();
            expect(screen.queryByText('Sin datos')).not.toBeInTheDocument();
        },
    );

    it('omits empty optional fields and their labels', () => {
        const base = makePreview();

        render(
            <CvPreview
                preview={makePreview({
                    personal_data: {
                        ...base.personal_data,
                        phone: null,
                        email: null,
                        location: null,
                        linkedin_url: null,
                        github_url: null,
                        website_url: null,
                        links: [],
                        has_data: true,
                    },
                    professional_title: null,
                    summary: null,
                    content: {
                        work_experiences: [
                            {
                                ...base.content.work_experiences[0],
                                description: null,
                                responsibilities: null,
                                achievements: null,
                                is_current: false,
                            },
                        ],
                        education_entries: [
                            {
                                ...base.content.education_entries[0],
                                degree_level: null,
                                description: null,
                            },
                        ],
                        certifications: [
                            {
                                ...base.content.certifications[0],
                                institution: null,
                                duration: null,
                                category: null,
                                code: null,
                                validation_url: null,
                            },
                        ],
                        skills: base.content.skills,
                        projects: [
                            {
                                ...base.content.projects[0],
                                role: null,
                                description: null,
                                results: null,
                                url: null,
                            },
                        ],
                    },
                })}
            />,
        );

        expect(screen.queryByText(/Código:/)).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Validar certificación' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Ver proyecto' }),
        ).not.toBeInTheDocument();
        expect(screen.queryByText('Licenciatura')).not.toBeInTheDocument();
        expect(screen.queryByText(/tel:/)).not.toBeInTheDocument();
        expect(screen.queryByText(/mailto:/)).not.toBeInTheDocument();
    });

    it('keeps long accented text complete and exposes visible focus styles', () => {
        const longText = `${'Áéíóú ñandú — ✓ '.repeat(20)}fin`;
        const base = makePreview();

        render(
            <CvPreview
                preview={makePreview({
                    summary: longText,
                    content: {
                        ...base.content,
                        work_experiences: [
                            {
                                ...base.content.work_experiences[0],
                                responsibilities: `${longText}\n${longText}`,
                            },
                        ],
                    },
                })}
            />,
        );

        const summary = screen.getByText(longText);

        expect(summary).toBeInTheDocument();
        expect(summary).toHaveClass('whitespace-pre-line');
        expect(summary).toHaveClass('break-words');
        expect(summary.className).not.toMatch(/truncate|line-clamp/);

        const responsibilities = screen.getByText(`${longText} ${longText}`);

        expect(responsibilities).toBeInTheDocument();
        expect(responsibilities.className).not.toMatch(/truncate|line-clamp/);

        const linkedin = screen.getByRole('link', { name: 'LinkedIn' });

        expect(linkedin.className).toMatch(/focus-visible/);
    });
});
