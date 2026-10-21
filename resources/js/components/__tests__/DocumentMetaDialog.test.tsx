import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import DocumentMetaDialog from '../documents/DocumentMetaDialog';

const { putMock, setDataMock } = vi.hoisted(() => ({
    putMock: vi.fn(),
    setDataMock: vi.fn(),
}));

vi.mock('@inertiajs/react', () => ({
    useForm: () => ({
        data: { title: 'Certificado AWS', description: 'Notas' },
        setData: setDataMock,
        put: putMock,
        processing: false,
        errors: {},
    }),
}));

describe('DocumentMetaDialog', () => {
    it('prefills the current metadata', () => {
        render(
            <DocumentMetaDialog
                open
                onOpenChange={() => {}}
                document={{
                    id: 9,
                    display_name: 'certificado.pdf',
                    title: 'Certificado AWS',
                    description: 'Notas',
                }}
            />,
        );

        expect(screen.getByLabelText('Título')).toHaveValue('Certificado AWS');
        expect(screen.getByLabelText('Descripción')).toHaveValue('Notas');
    });

    it('submits the metadata update', async () => {
        const user = userEvent.setup();

        render(
            <DocumentMetaDialog
                open
                onOpenChange={() => {}}
                document={{
                    id: 9,
                    display_name: 'certificado.pdf',
                    title: 'Certificado AWS',
                    description: 'Notas',
                }}
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Guardar' }));

        expect(putMock).toHaveBeenCalledTimes(1);
        expect(putMock.mock.calls[0]?.[0]).toBe('/documents/9');
    });
});
