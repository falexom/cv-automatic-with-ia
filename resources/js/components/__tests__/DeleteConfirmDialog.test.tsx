import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import DeleteConfirmDialog from '../DeleteConfirmDialog';

describe('DeleteConfirmDialog', () => {
    it('shows the confirmation content when open', () => {
        render(
            <DeleteConfirmDialog
                open
                onOpenChange={() => {}}
                description="Se eliminará la experiencia en Acme."
                onConfirm={() => {}}
            />,
        );

        expect(
            screen.getByText('¿Eliminar este registro?'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Se eliminará la experiencia en Acme.'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Eliminar' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Cancelar' }),
        ).toBeInTheDocument();
    });

    it('calls onConfirm when the user confirms', async () => {
        const user = userEvent.setup();
        const onConfirm = vi.fn();

        render(
            <DeleteConfirmDialog
                open
                onOpenChange={() => {}}
                onConfirm={onConfirm}
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Eliminar' }));

        expect(onConfirm).toHaveBeenCalledTimes(1);
    });

    it('does not confirm when the user cancels', async () => {
        const user = userEvent.setup();
        const onConfirm = vi.fn();
        const onOpenChange = vi.fn();

        render(
            <DeleteConfirmDialog
                open
                onOpenChange={onOpenChange}
                onConfirm={onConfirm}
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Cancelar' }));

        expect(onConfirm).not.toHaveBeenCalled();
        expect(onOpenChange).toHaveBeenCalledWith(false);
    });
});
