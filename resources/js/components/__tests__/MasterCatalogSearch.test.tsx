import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { AnchorHTMLAttributes, ReactNode } from 'react';
import { describe, expect, it, vi } from 'vitest';
import CertificationsIndex from '../../pages/Certifications/Index';
import SkillsIndex from '../../pages/Skills/Index';

type MockLinkProps = AnchorHTMLAttributes<HTMLAnchorElement> & {
    href: string;
};

vi.mock('@inertiajs/react', async () => {
    const React = await import('react');

    return {
        Head: () => null,
        Link: React.forwardRef<HTMLAnchorElement, MockLinkProps>(
            ({ href, ...props }, ref) =>
                React.createElement('a', { ...props, href, ref }),
        ),
        router: { delete: vi.fn() },
    };
});

vi.mock('@/layouts/AppLayout', async () => {
    const React = await import('react');

    return {
        default: ({ children }: { children: ReactNode }) =>
            React.createElement('div', null, children),
    };
});

vi.mock('@/components/DeleteConfirmDialog', () => ({ default: () => null }));

const certifications = [
    {
        id: 1,
        name: 'AWS Certified Developer',
        institution: 'Amazon Web Services',
        date_year: 2025,
        date_month: 3,
        category: 'Cloud',
        documents_count: 0,
        skills: [],
    },
    {
        id: 2,
        name: 'Análisis de datos',
        institution: 'Platzi',
        date_year: 2024,
        date_month: 8,
        category: 'Datos',
        documents_count: 0,
        skills: [],
    },
];

const skills = [
    { id: 11, name: 'Python', category: 'tecnica', usage_count: 0 },
    { id: 12, name: 'JavaScript', category: 'tecnica', usage_count: 0 },
];

describe('Perfil Maestro list search', () => {
    it('matches partial certification names and institutions and restores all records when cleared', async () => {
        const user = userEvent.setup();
        render(<CertificationsIndex records={certifications} />);

        const search = screen.getByRole('searchbox', {
            name: 'Buscar cursos y certificaciones',
        });

        await user.type(search, 'Platzi');
        expect(screen.getByText('Análisis de datos')).toBeInTheDocument();
        expect(
            screen.queryByText('AWS Certified Developer'),
        ).not.toBeInTheDocument();

        await user.clear(search);
        expect(screen.getByText('AWS Certified Developer')).toBeInTheDocument();
        expect(screen.getByText('Análisis de datos')).toBeInTheDocument();

        await user.type(search, 'Developer');
        expect(screen.getByText('AWS Certified Developer')).toBeInTheDocument();
        expect(screen.queryByText('Análisis de datos')).not.toBeInTheDocument();

        await user.clear(search);
        await user.type(search, 'no existe');
        expect(
            screen.getByText('No se encontraron resultados'),
        ).toBeInTheDocument();

        await user.clear(search);
        expect(screen.getByText('Análisis de datos')).toBeInTheDocument();
    });

    it('filters skills by name and restores the catalog when cleared', async () => {
        const user = userEvent.setup();
        render(<SkillsIndex records={skills} />);

        const search = screen.getByRole('searchbox', {
            name: 'Buscar habilidades',
        });

        await user.type(search, 'py');
        expect(screen.getByText('Python')).toBeInTheDocument();
        expect(screen.queryByText('JavaScript')).not.toBeInTheDocument();

        await user.clear(search);
        expect(screen.getByText('JavaScript')).toBeInTheDocument();

        await user.type(search, 'no existe');
        expect(
            screen.getByText('No se encontraron resultados'),
        ).toBeInTheDocument();

        await user.clear(search);
        expect(screen.getByText('Python')).toBeInTheDocument();
    });

    it('keeps course/certification and skill searches independent', async () => {
        const user = userEvent.setup();
        render(
            <>
                <CertificationsIndex records={certifications} />
                <SkillsIndex records={skills} />
            </>,
        );

        await user.type(
            screen.getByRole('searchbox', {
                name: 'Buscar cursos y certificaciones',
            }),
            'Platzi',
        );
        expect(screen.getByText('Python')).toBeInTheDocument();
        expect(screen.getByText('JavaScript')).toBeInTheDocument();

        await user.type(
            screen.getByRole('searchbox', { name: 'Buscar habilidades' }),
            'py',
        );
        expect(screen.getByText('Análisis de datos')).toBeInTheDocument();
        expect(
            screen.queryByText('AWS Certified Developer'),
        ).not.toBeInTheDocument();
        expect(screen.queryByText('JavaScript')).not.toBeInTheDocument();
    });
});
