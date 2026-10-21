import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import VerificationReport from '../documents/VerificationReport';

describe('VerificationReport', () => {
    it('renders nothing without a result', () => {
        const { container } = render(<VerificationReport result={null} />);

        expect(container).toBeEmptyDOMElement();
    });

    it('summarizes a clean verification', () => {
        render(
            <VerificationReport
                result={{ checked: 4, ok: 4, unavailable: 0, problems: [] }}
            />,
        );

        expect(
            screen.getByText(/4 documento\(s\) verificados/),
        ).toBeInTheDocument();
        expect(
            screen.getByText(/4 correctos, 0 con problemas/),
        ).toBeInTheDocument();
    });

    it('lists problems with their reason', () => {
        render(
            <VerificationReport
                result={{
                    checked: 2,
                    ok: 0,
                    unavailable: 2,
                    problems: [
                        {
                            id: 1,
                            display_name: 'certificado.pdf',
                            reason: 'missing',
                        },
                        {
                            id: 2,
                            display_name: 'titulo.pdf',
                            reason: 'altered',
                        },
                    ],
                }}
            />,
        );

        expect(
            screen.getByText(/certificado\.pdf — Archivo ausente/),
        ).toBeInTheDocument();
        expect(
            screen.getByText(/titulo\.pdf — Archivo alterado/),
        ).toBeInTheDocument();
    });
});
