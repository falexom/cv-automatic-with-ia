import { fireEvent, render, screen } from '@testing-library/react';
import { router } from '@inertiajs/react';
import { describe, expect, it, vi } from 'vitest';
import EducationForm from '../forms/EducationForm';

describe('EducationForm', () => {
    it('keeps the written institution in the submitted data when dates change', () => {
        const postSpy = vi.spyOn(router, 'post').mockImplementation(() => {});

        render(<EducationForm />);

        fireEvent.change(screen.getByLabelText('Institución *'), {
            target: { value: 'Universidad Nacional' },
        });

        fireEvent.change(screen.getByLabelText('Fecha de inicio'), {
            target: { value: '2018' },
        });
        fireEvent.change(screen.getByLabelText('Fecha de fin'), {
            target: { value: '2022' },
        });

        expect(screen.getByLabelText('Institución *')).toHaveValue(
            'Universidad Nacional',
        );

        fireEvent.click(
            screen.getByRole('checkbox', {
                name: 'En curso (sin fecha de fin)',
            }),
        );

        fireEvent.click(
            screen.getByRole('button', { name: 'Crear formación' }),
        );

        expect(postSpy).toHaveBeenCalledTimes(1);
        expect(postSpy.mock.calls[0]?.[1]).toMatchObject({
            institution: 'Universidad Nacional',
            start_year: '2018',
            is_in_progress: true,
        });

        postSpy.mockRestore();
    });
});
