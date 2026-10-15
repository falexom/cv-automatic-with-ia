import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import DuplicateWarningDialog from '../DuplicateWarningDialog';

const warning = {
    existing_id: 7,
    name: 'Curso de Laravel',
    institution: 'Laravel Academy',
};

describe('DuplicateWarningDialog', () => {
    it('shows the existing record', () => {
        render(
            <DuplicateWarningDialog
                open
                warning={warning}
                onOpenChange={() => {}}
                onConfirm={() => {}}
            />,
        );

        expect(screen.getByText('Registro duplicado')).toBeInTheDocument();
        expect(
            screen.getByText('Curso de Laravel — Laravel Academy'),
        ).toBeInTheDocument();
    });

    it('confirms saving anyway', async () => {
        const user = userEvent.setup();
        const onConfirm = vi.fn();

        render(
            <DuplicateWarningDialog
                open
                warning={warning}
                onOpenChange={() => {}}
                onConfirm={onConfirm}
            />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Guardar de todos modos' }),
        );

        expect(onConfirm).toHaveBeenCalledTimes(1);
    });

    it('closes without confirming when cancelled', async () => {
        const user = userEvent.setup();
        const onConfirm = vi.fn();
        const onOpenChange = vi.fn();

        render(
            <DuplicateWarningDialog
                open
                warning={warning}
                onOpenChange={onOpenChange}
                onConfirm={onConfirm}
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Cancelar' }));

        expect(onConfirm).not.toHaveBeenCalled();
        expect(onOpenChange).toHaveBeenCalledWith(false);
    });
});
