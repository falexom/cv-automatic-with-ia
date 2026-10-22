import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import type { SkillOption } from '../SkillMultiSelect';
import CertificationForm from '../forms/CertificationForm';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@inertiajs/react')>();

    return {
        ...actual,
        usePage: () => ({ props: { flash: {} } }),
    };
});

const skills: SkillOption[] = [{ id: 1, name: 'PHP', category: 'tecnica' }];

describe('CertificationForm', () => {
    it('keeps the written name when the date year changes', () => {
        render(<CertificationForm skills={skills} />);

        fireEvent.change(screen.getByLabelText('Nombre *'), {
            target: { value: 'Scrum Master' },
        });

        fireEvent.change(screen.getByLabelText('Fecha'), {
            target: { value: '2023' },
        });

        expect(screen.getByLabelText('Nombre *')).toHaveValue('Scrum Master');
        expect(screen.getByText('Habilidades asociadas')).toBeInTheDocument();
        expect(
            screen.getByRole('checkbox', { name: 'PHP' }),
        ).toBeInTheDocument();
    });
});
