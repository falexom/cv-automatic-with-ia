import { fireEvent, render, screen, within } from '@testing-library/react';
import { router } from '@inertiajs/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import CvGeneratorPanel from '@/components/CvGeneratorPanel';
import type { CvGeneratorPageProps, CvPreviewData } from '@/lib/cv-preview';

afterEach(() => {
    vi.restoreAllMocks();
});

const profiles: CvGeneratorPageProps['profiles'] = [
    {
        id: 1,
        name: 'Analista',
        professional_title: 'Analista de Sistemas',
    },
    {
        id: 2,
        name: 'Programador',
        professional_title: null,
    },
];

const preview: CvPreviewData = {
    personal_data: {
        full_name: 'Ada Lovelace',
        phone: null,
        email: null,
        location: null,
        linkedin_url: null,
        github_url: null,
        website_url: null,
        links: [],
        has_data: true,
    },
    professional_title: 'Analista de Sistemas',
    summary: null,
    content: {
        work_experiences: [],
        education_entries: [],
        certifications: [],
        skills: [],
        projects: [],
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
};

function makePanelPreview(
    overrides: Partial<CvPreviewData> = {},
): CvPreviewData {
    return {
        ...preview,
        ...overrides,
        personal_data: {
            ...preview.personal_data,
            ...overrides.personal_data,
        },
        content: {
            ...preview.content,
            ...overrides.content,
        },
        source_links: {
            ...preview.source_links,
            ...overrides.source_links,
        },
    };
}

function experience(position: string, id: number) {
    return {
        id,
        position,
        company: `Empresa ${position}`,
        start_year: 2021,
        start_month: 1,
        end_year: null,
        end_month: null,
        is_current: false,
        description: null,
        responsibilities: null,
        achievements: null,
    };
}

const previewA = makePanelPreview({
    professional_title: 'Título A',
    summary: 'Resumen A',
    content: {
        ...preview.content,
        work_experiences: [experience('Experiencia A', 10)],
    },
});

const previewB = makePanelPreview({
    professional_title: 'Título B',
    summary: 'Resumen B',
    content: {
        ...preview.content,
        work_experiences: [experience('Experiencia B', 20)],
    },
});

describe('CvGeneratorPanel', () => {
    it('renders an accessible GET form with an empty option and no preselection', () => {
        const { container } = render(
            <CvGeneratorPanel
                profiles={profiles}
                status="selection_required"
                selected_profile_id={null}
                preview={null}
            />,
        );

        const select = screen.getByRole('combobox', { name: /perfil/i });

        expect(select).toHaveValue('');
        expect(select).toHaveAttribute('name', 'profile');
        expect(
            within(select).getByRole('option', {
                name: 'Seleccioná un perfil',
            }),
        ).toBeInTheDocument();
        expect(
            within(select).getByRole('option', {
                name: 'Analista — Analista de Sistemas',
            }),
        ).toBeInTheDocument();
        expect(
            within(select).getByRole('option', {
                name: 'Programador',
            }),
        ).toBeInTheDocument();

        const form = container.querySelector('form');

        expect(form).not.toBeNull();
        expect(form).toHaveAttribute('method', 'get');
        expect(form).toHaveAttribute('action', '/cv');

        expect(
            screen.getByRole('button', { name: 'Ver vista previa' }),
        ).toHaveAttribute('type', 'submit');
        expect(screen.queryByRole('article')).not.toBeInTheDocument();
    });

    it('derives the selected value and renders the preview when ready with data', () => {
        render(
            <CvGeneratorPanel
                profiles={profiles}
                status="ready"
                selected_profile_id={2}
                preview={preview}
            />,
        );

        expect(screen.getByRole('combobox', { name: /perfil/i })).toHaveValue(
            '2',
        );
        expect(
            screen.getByRole('article', { name: 'Ada Lovelace' }),
        ).toBeInTheDocument();
    });

    it('does not render a blank CV when ready has no data', () => {
        render(
            <CvGeneratorPanel
                profiles={profiles}
                status="ready"
                selected_profile_id={1}
                preview={{ ...preview, has_data: false }}
            />,
        );

        expect(screen.queryByRole('article')).not.toBeInTheDocument();
    });

    it('updates the selector and preview across A, B and A without residues', () => {
        const { rerender } = render(
            <CvGeneratorPanel
                profiles={profiles}
                status="ready"
                selected_profile_id={1}
                preview={previewA}
            />,
        );

        expect(screen.getByRole('combobox', { name: /perfil/i })).toHaveValue(
            '1',
        );
        expect(screen.getByText('Título A')).toBeInTheDocument();
        expect(screen.getByText(/Experiencia A/)).toBeInTheDocument();
        expect(screen.queryByText('Título B')).not.toBeInTheDocument();
        expect(screen.queryByText(/Experiencia B/)).not.toBeInTheDocument();

        rerender(
            <CvGeneratorPanel
                profiles={profiles}
                status="ready"
                selected_profile_id={2}
                preview={previewB}
            />,
        );

        expect(screen.getByRole('combobox', { name: /perfil/i })).toHaveValue(
            '2',
        );
        expect(screen.getByText('Título B')).toBeInTheDocument();
        expect(screen.getByText(/Experiencia B/)).toBeInTheDocument();
        expect(screen.queryByText('Título A')).not.toBeInTheDocument();
        expect(screen.queryByText(/Experiencia A/)).not.toBeInTheDocument();

        rerender(
            <CvGeneratorPanel
                profiles={profiles}
                status="ready"
                selected_profile_id={1}
                preview={previewA}
            />,
        );

        expect(screen.getByRole('combobox', { name: /perfil/i })).toHaveValue(
            '1',
        );
        expect(screen.getByText('Título A')).toBeInTheDocument();
        expect(screen.queryByText('Título B')).not.toBeInTheDocument();
        expect(screen.queryByText(/Experiencia B/)).not.toBeInTheDocument();
    });

    it('clears the selector and preview when the selection becomes unavailable', () => {
        const { rerender } = render(
            <CvGeneratorPanel
                profiles={profiles}
                status="ready"
                selected_profile_id={2}
                preview={previewB}
            />,
        );

        expect(screen.getByRole('combobox', { name: /perfil/i })).toHaveValue(
            '2',
        );
        expect(screen.getByRole('article')).toBeInTheDocument();

        rerender(
            <CvGeneratorPanel
                profiles={profiles}
                status="unavailable"
                selected_profile_id={null}
                preview={null}
            />,
        );

        expect(screen.getByRole('combobox', { name: /perfil/i })).toHaveValue(
            '',
        );
        expect(screen.queryByRole('article')).not.toBeInTheDocument();
    });

    it('renders the correction links outside the CV sheet', () => {
        render(
            <CvGeneratorPanel
                profiles={profiles}
                status="ready"
                selected_profile_id={1}
                preview={preview}
            />,
        );

        const article = screen.getByRole('article', { name: 'Ada Lovelace' });
        const block = screen.getByRole('region', {
            name: 'Corregir en las fuentes',
        });

        expect(article.contains(block)).toBe(false);
        expect(
            within(block).getByRole('heading', {
                name: 'Corregir en las fuentes',
            }),
        ).toBeInTheDocument();

        const expectedLinks = [
            ['Perfil de CV', preview.source_links.profile],
            ['Datos personales', preview.source_links.personal_data],
            ['Experiencia', preview.source_links.work_experiences],
            ['Educación', preview.source_links.education_entries],
            ['Cursos y certificaciones', preview.source_links.certifications],
            ['Habilidades', preview.source_links.skills],
            ['Proyectos', preview.source_links.projects],
        ] as const;

        expect(within(block).getAllByRole('link')).toHaveLength(7);

        for (const [name, href] of expectedLinks) {
            const link = within(block).getByRole('link', { name });

            expect(link).toHaveAttribute('href', href);
            expect(link.className).toMatch(/focus-visible/);
            expect(article.contains(link)).toBe(false);
        }

        for (const link of within(article).queryAllByRole('link')) {
            expect(link.getAttribute('href') ?? '').not.toMatch(
                /^\/(profiles|profile|experiences|education|certifications|skills|projects)/,
            );
        }
    });

    it('shows an empty state with a profiles link when there are no active profiles', () => {
        render(
            <CvGeneratorPanel
                profiles={[]}
                status="no_active_profiles"
                selected_profile_id={null}
                preview={null}
            />,
        );

        expect(screen.getByTestId('empty-state')).toBeInTheDocument();
        expect(screen.getByText('Sin perfiles activos')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Gestionar perfiles' }),
        ).toHaveAttribute('href', '/profiles');
        expect(screen.queryByRole('article')).not.toBeInTheDocument();
        expect(screen.queryByRole('combobox')).not.toBeInTheDocument();
    });

    it('asks for a selection without preselecting a profile', () => {
        render(
            <CvGeneratorPanel
                profiles={profiles}
                status="selection_required"
                selected_profile_id={null}
                preview={null}
            />,
        );

        expect(
            screen.getByText('Seleccioná un perfil para ver su vista previa.'),
        ).toBeInTheDocument();
        expect(screen.getByRole('combobox', { name: /perfil/i })).toHaveValue(
            '',
        );
        expect(screen.queryByRole('article')).not.toBeInTheDocument();
    });

    it('warns when the selected profile is unavailable and offers active alternatives', () => {
        render(
            <CvGeneratorPanel
                profiles={profiles}
                status="unavailable"
                selected_profile_id={null}
                preview={null}
            />,
        );

        expect(screen.getByRole('alert')).toHaveTextContent(
            /no está disponible/i,
        );

        const select = screen.getByRole('combobox', { name: /perfil/i });

        expect(select).toHaveValue('');
        expect(
            within(select).getByRole('option', {
                name: 'Analista — Analista de Sistemas',
            }),
        ).toBeInTheDocument();
        expect(
            within(select).getByRole('option', {
                name: 'Programador',
            }),
        ).toBeInTheDocument();
        expect(screen.queryByRole('article')).not.toBeInTheDocument();
    });

    it('shows an empty state instead of a blank sheet when ready has no data', () => {
        render(
            <CvGeneratorPanel
                profiles={profiles}
                status="ready"
                selected_profile_id={1}
                preview={{ ...preview, has_data: false }}
            />,
        );

        expect(screen.getByTestId('empty-state')).toBeInTheDocument();
        expect(screen.queryByRole('article')).not.toBeInTheDocument();
    });

    it('shows a controlled error and retries the current URL', () => {
        const reload = vi.spyOn(router, 'reload').mockImplementation(() => {});

        render(
            <CvGeneratorPanel
                profiles={profiles}
                status="error"
                selected_profile_id={1}
                preview={preview}
            />,
        );

        expect(screen.getByRole('alert')).toHaveTextContent(
            /no se pudo generar/i,
        );
        expect(screen.queryByRole('article')).not.toBeInTheDocument();
        expect(screen.queryByText('Ada Lovelace')).not.toBeInTheDocument();

        const retry = screen.getByRole('button', { name: 'Reintentar' });

        expect(retry.className).toMatch(/focus-visible/);

        fireEvent.click(retry);

        expect(reload).toHaveBeenCalledTimes(1);
    });
});
