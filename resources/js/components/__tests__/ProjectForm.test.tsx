import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import type { SkillOption } from '../SkillMultiSelect';
import ProjectForm from '../forms/ProjectForm';

const skills: SkillOption[] = [{ id: 1, name: 'PHP', category: 'tecnica' }];

describe('ProjectForm', () => {
    it('keeps the written name when the start and end years change', () => {
        render(<ProjectForm skills={skills} />);

        fireEvent.change(screen.getByLabelText('Nombre *'), {
            target: { value: 'Migración de plataforma' },
        });

        fireEvent.change(screen.getByLabelText('Fecha de inicio'), {
            target: { value: '2024' },
        });
        fireEvent.change(screen.getByLabelText('Fecha de fin'), {
            target: { value: '2025' },
        });

        expect(screen.getByLabelText('Nombre *')).toHaveValue(
            'Migración de plataforma',
        );
        expect(screen.getByText('Habilidades asociadas')).toBeInTheDocument();
        expect(
            screen.getByRole('checkbox', { name: 'PHP' }),
        ).toBeInTheDocument();
    });
});
