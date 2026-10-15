import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import EmptyState from '../EmptyState';

describe('EmptyState', () => {
    it('renders guidance text', () => {
        render(
            <EmptyState
                title="Sin experiencias registradas"
                description="Agregá tu primera experiencia laboral para comenzar."
            />,
        );

        expect(screen.getByTestId('empty-state')).toBeInTheDocument();
        expect(
            screen.getByText('Sin experiencias registradas'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Agregá tu primera experiencia laboral para comenzar.',
            ),
        ).toBeInTheDocument();
    });

    it('renders the create action when provided', () => {
        render(
            <EmptyState
                title="Sin proyectos"
                description="Los proyectos complementan tu experiencia."
                actionLabel="Nuevo proyecto"
                actionHref="/projects/create"
            />,
        );

        const action = screen.getByRole('link', { name: 'Nuevo proyecto' });

        expect(action).toHaveAttribute('href', '/projects/create');
    });
});
