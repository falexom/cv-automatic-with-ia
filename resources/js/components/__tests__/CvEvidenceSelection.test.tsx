import { fireEvent, render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import CvEvidenceSelection from '@/components/CvEvidenceSelection';
import CvGeneratorPanel from '@/components/CvGeneratorPanel';
import type {
    CvEvidence,
    CvGeneratorPageProps,
    CvPreviewData,
} from '@/lib/cv-preview';

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

const evidences: CvEvidence[] = [
    {
        id: 10,
        display_name: 'Curso de Laravel',
        is_available: true,
        last_verified_at: '2026-10-20T12:00:00+00:00',
        associations: [
            {
                type: 'certification',
                id: 1,
                label: 'Curso de Laravel',
            },
        ],
    },
    {
        id: 11,
        display_name: 'certificado.pdf',
        is_available: false,
        last_verified_at: null,
        associations: [
            {
                type: 'education',
                id: 2,
                label: 'Universidad — Sistemas',
            },
            {
                type: 'experience',
                id: 3,
                label: 'Acme — Analista',
            },
        ],
    },
];

describe('CvEvidenceSelection', () => {
    it('preselects every eligible evidence with its associations, availability and verification date', () => {
        render(<CvEvidenceSelection profileId={1} evidences={evidences} />);

        expect(
            screen.getByRole('heading', { name: 'Evidencias del CV' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Seleccionadas: 2 de 2')).toBeInTheDocument();

        expect(
            screen.getByRole('checkbox', { name: 'Curso de Laravel' }),
        ).toBeChecked();
        expect(
            screen.getByRole('checkbox', { name: 'certificado.pdf' }),
        ).toBeChecked();

        expect(screen.getByText('Disponible')).toBeInTheDocument();
        expect(screen.getByText('No disponible')).toBeInTheDocument();
        expect(screen.getByText(/Última verificación:/)).toBeInTheDocument();

        const associations = screen.getByRole('list', {
            name: 'Registros asociados a certificado.pdf',
        });

        expect(
            within(associations).getByText('Educación: Universidad — Sistemas'),
        ).toBeInTheDocument();
        expect(
            within(associations).getByText('Experiencia: Acme — Analista'),
        ).toBeInTheDocument();
    });

    it('updates the selection count immediately when an evidence is excluded or included', () => {
        render(<CvEvidenceSelection profileId={1} evidences={evidences} />);

        const checkbox = screen.getByRole('checkbox', {
            name: 'certificado.pdf',
        });

        fireEvent.click(checkbox);

        expect(checkbox).not.toBeChecked();
        expect(screen.getByText('Seleccionadas: 1 de 2')).toBeInTheDocument();

        fireEvent.click(checkbox);

        expect(checkbox).toBeChecked();
        expect(screen.getByText('Seleccionadas: 2 de 2')).toBeInTheDocument();
    });

    it('submits the profile and selected document ids through a GET form', () => {
        render(<CvEvidenceSelection profileId={7} evidences={evidences} />);

        const button = screen.getByRole('button', {
            name: 'Descargar CV documentado',
        });
        const form = button.closest('form');

        expect(form).not.toBeNull();
        expect(form).toHaveAttribute('method', 'get');
        expect(form).toHaveAttribute('action', '/cv/documentado');
        expect(button).not.toBeDisabled();

        const profile = form?.querySelector<HTMLInputElement>(
            'input[name="profile"]',
        );
        const documents = Array.from(
            form?.querySelectorAll<HTMLInputElement>(
                'input[name="documents[]"]',
            ) ?? [],
        ).map((input) => input.value);

        expect(profile?.value).toBe('7');
        expect(documents).toEqual(['10', '11']);
    });

    it('disables the documented download when no evidence is selected', () => {
        render(<CvEvidenceSelection profileId={1} evidences={evidences} />);

        const button = screen.getByRole('button', {
            name: 'Descargar CV documentado',
        });

        fireEvent.click(
            screen.getByRole('checkbox', { name: 'Curso de Laravel' }),
        );
        fireEvent.click(
            screen.getByRole('checkbox', { name: 'certificado.pdf' }),
        );

        expect(button).toBeDisabled();
        expect(
            screen.getByText(
                'Seleccioná al menos una evidencia para generar el CV documentado.',
            ),
        ).toBeInTheDocument();

        const form = button.closest('form');
        const documents = Array.from(
            form?.querySelectorAll<HTMLInputElement>(
                'input[name="documents[]"]',
            ) ?? [],
        );

        expect(documents).toHaveLength(0);

        fireEvent.click(
            screen.getByRole('checkbox', { name: 'Curso de Laravel' }),
        );

        expect(button).not.toBeDisabled();
        expect(
            form?.querySelectorAll<HTMLInputElement>(
                'input[name="documents[]"]',
            ),
        ).toHaveLength(1);
    });

    it('shows an empty state when there are no eligible documents', () => {
        render(<CvEvidenceSelection profileId={1} evidences={[]} />);

        expect(screen.getByTestId('empty-state')).toBeInTheDocument();
        expect(
            screen.getByText('Sin evidencias elegibles'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Los registros incluidos en este perfil no tienen documentos asociados.',
            ),
        ).toBeInTheDocument();
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Descargar CV documentado' }),
        ).not.toBeInTheDocument();
    });

    it('resets the selection when the profile changes', () => {
        const { rerender } = render(
            <CvGeneratorPanel
                profiles={profiles}
                status="ready"
                selected_profile_id={1}
                preview={preview}
                evidences={[
                    {
                        id: 21,
                        display_name: 'Evidencia A',
                        is_available: true,
                        last_verified_at: null,
                        associations: [],
                    },
                ]}
            />,
        );

        const evidenceA = screen.getByRole('checkbox', {
            name: 'Evidencia A',
        });

        fireEvent.click(evidenceA);

        expect(evidenceA).not.toBeChecked();
        expect(screen.getByText('Seleccionadas: 0 de 1')).toBeInTheDocument();

        rerender(
            <CvGeneratorPanel
                profiles={profiles}
                status="ready"
                selected_profile_id={2}
                preview={preview}
                evidences={[
                    {
                        id: 22,
                        display_name: 'Evidencia B',
                        is_available: true,
                        last_verified_at: null,
                        associations: [],
                    },
                ]}
            />,
        );

        expect(screen.queryByText('Evidencia A')).not.toBeInTheDocument();
        expect(
            screen.getByRole('checkbox', { name: 'Evidencia B' }),
        ).toBeChecked();
        expect(screen.getByText('Seleccionadas: 1 de 1')).toBeInTheDocument();

        const form = screen
            .getByRole('button', { name: 'Descargar CV documentado' })
            .closest('form');

        expect(
            form?.querySelector<HTMLInputElement>('input[name="profile"]')
                ?.value,
        ).toBe('2');
    });

    it('only offers the evidence selection in the ready state with a selected profile', () => {
        const withoutProfile: CvGeneratorPageProps[] = [
            {
                profiles: [],
                status: 'no_active_profiles',
                selected_profile_id: null,
                preview: null,
                evidences,
            },
            {
                profiles,
                status: 'selection_required',
                selected_profile_id: null,
                preview: null,
                evidences,
            },
            {
                profiles,
                status: 'unavailable',
                selected_profile_id: null,
                preview: null,
                evidences,
            },
            {
                profiles,
                status: 'error',
                selected_profile_id: 1,
                preview: null,
                evidences,
            },
        ];

        for (const props of withoutProfile) {
            const { unmount } = render(<CvGeneratorPanel {...props} />);

            expect(
                screen.queryByRole('heading', { name: 'Evidencias del CV' }),
            ).not.toBeInTheDocument();

            unmount();
        }

        render(
            <CvGeneratorPanel
                profiles={profiles}
                status="ready"
                selected_profile_id={1}
                preview={preview}
                evidences={evidences}
            />,
        );

        expect(
            screen.getByRole('heading', { name: 'Evidencias del CV' }),
        ).toBeInTheDocument();
    });
});
