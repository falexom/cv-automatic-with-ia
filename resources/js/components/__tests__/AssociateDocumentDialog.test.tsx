import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import AssociateDocumentDialog from '../documents/AssociateDocumentDialog';
import type { DocumentAssociationOptions } from '@/lib/documents';

const { postMock } = vi.hoisted(() => ({ postMock: vi.fn() }));

vi.mock('@inertiajs/react', () => ({
    router: { post: postMock },
}));

const options: DocumentAssociationOptions = {
    certifications: [{ id: 3, label: 'Curso de Laravel' }],
    education: [{ id: 7, label: 'Universidad Nacional — Ingeniería' }],
    experiences: [],
};

const document = { id: 5, display_name: 'certificado.pdf' };

describe('AssociateDocumentDialog', () => {
    beforeEach(() => {
        postMock.mockClear();
    });

    it('keeps the entity select disabled until a type is chosen', async () => {
        const user = userEvent.setup();

        render(
            <AssociateDocumentDialog
                open
                onOpenChange={() => {}}
                document={document}
                associationOptions={options}
            />,
        );

        const entitySelect = screen.getByLabelText('Entidad');

        expect(entitySelect).toBeDisabled();

        await user.selectOptions(
            screen.getByLabelText('Tipo de entidad'),
            'certification',
        );

        expect(entitySelect).toBeEnabled();
        expect(
            screen.getByRole('option', { name: 'Curso de Laravel' }),
        ).toBeInTheDocument();
    });

    it('submits the selected link', async () => {
        const user = userEvent.setup();

        render(
            <AssociateDocumentDialog
                open
                onOpenChange={() => {}}
                document={document}
                associationOptions={options}
            />,
        );

        await user.selectOptions(
            screen.getByLabelText('Tipo de entidad'),
            'education',
        );
        await user.selectOptions(
            screen.getByLabelText('Entidad'),
            'Universidad Nacional — Ingeniería',
        );
        await user.click(screen.getByRole('button', { name: 'Asociar' }));

        expect(postMock).toHaveBeenCalledTimes(1);
        expect(postMock.mock.calls[0]?.[0]).toBe('/documents/5/links');
        expect(postMock.mock.calls[0]?.[1]).toEqual({
            linkable_type: 'education',
            linkable_id: '7',
        });
    });
});
