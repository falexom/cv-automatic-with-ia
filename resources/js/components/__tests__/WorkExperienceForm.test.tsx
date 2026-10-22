import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import type { SkillOption } from '../SkillMultiSelect';
import WorkExperienceForm from '../forms/WorkExperienceForm';

const skills: SkillOption[] = [{ id: 1, name: 'PHP', category: 'tecnica' }];

describe('WorkExperienceForm', () => {
    it('keeps the written company when dates change and current job toggles', () => {
        render(<WorkExperienceForm skills={skills} />);

        fireEvent.change(screen.getByLabelText('Empresa *'), {
            target: { value: 'Acme Corp' },
        });

        fireEvent.change(screen.getByLabelText('Fecha de inicio'), {
            target: { value: '2020' },
        });
        fireEvent.change(screen.getByLabelText('Fecha de fin'), {
            target: { value: '2022' },
        });

        expect(screen.getByLabelText('Empresa *')).toHaveValue('Acme Corp');
        expect(
            screen.getByRole('checkbox', { name: 'PHP' }),
        ).toBeInTheDocument();

        fireEvent.click(
            screen.getByRole('checkbox', {
                name: 'Trabajo actual (sin fecha de fin)',
            }),
        );

        expect(screen.getByLabelText('Empresa *')).toHaveValue('Acme Corp');
    });
});
