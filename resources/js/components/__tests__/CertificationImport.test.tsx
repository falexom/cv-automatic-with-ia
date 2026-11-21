import {
    act,
    fireEvent,
    render,
    screen,
    waitFor,
} from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import CertificationImportPanel from '@/components/certifications/CertificationImportPanel';
import CertificationImportReview from '@/components/certifications/CertificationImportReview';
import { useCertificationImport } from '@/hooks/use-certification-import';
import type {
    CertificationImportLimits,
    CertificationImportNotice,
    ImportAnalysis,
    ImportPreparation,
    ImportSkill,
} from '@/lib/certification-import';
import { postForm, postJson } from '@/lib/http';

vi.mock('@/lib/http', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@/lib/http')>();

    return {
        ...actual,
        postForm: vi.fn(),
        postJson: vi.fn(),
    };
});

const limits: CertificationImportLimits = {
    max_kilobytes: 20480,
    max_text_chars: 60000,
    max_pages: 30,
    max_page_chars: 20000,
};

const notice: CertificationImportNotice = {
    destination: 'Codex/OpenAI',
    model: 'gpt-6-luna',
    model_label: 'Luna 6',
    reasoning_effort: 'xhigh',
    reasoning_effort_label: 'Extra High',
    sent: ['El texto nativo extraído.', 'Los nombres de los campos.'],
    excluded: ['El PDF completo.', 'Datos personales del titular.'],
    retention: {
        stored: ['Solo el registro confirmado y su PDF.'],
        excluded: ['Texto, prompt y borradores.'],
        note: 'La limpieza local no revierte un envío externo.',
    },
};

const skill: ImportSkill = {
    id: 7,
    name: 'Laravel',
    category: 'tecnologia',
    state: 'suggested',
    support: null,
    page: null,
    reason: 'El temario menciona Laravel.',
};

const preparation: ImportPreparation = {
    preparation_token: 'preparacion-segura',
    checksum: 'a'.repeat(64),
    original_filename: 'certificado.pdf',
    size_bytes: 1200,
    page_count: 1,
    pages: [
        {
            page: 1,
            text: 'Curso de Laravel\nAcademy\nEmitido en 2024\nTemario: Laravel',
            origin: 'native',
        },
    ],
    redactions: 1,
    status: 'ready',
    message: null,
    ocr_used: false,
    ocr_languages: [],
    ocr_warning: null,
};

const analysis: ImportAnalysis = {
    confirmation_token: 'confirmacion-segura',
    proposal: {
        schema_version: 1,
        sufficiency: 'sufficient',
        sufficiency_note: null,
        fields: {
            name: {
                value: 'Curso de Laravel',
                state: 'found',
                support: 'Curso de Laravel',
                page: 1,
                reason: null,
            },
            institution: {
                value: null,
                state: 'absent',
                support: null,
                page: null,
                reason: null,
            },
            date: {
                year: 2024,
                month: null,
                state: 'found',
                support: 'Emitido en 2024',
                page: 1,
                reason: null,
            },
            duration: {
                value: null,
                state: 'absent',
                support: null,
                page: null,
                reason: null,
            },
            code: {
                value: null,
                state: 'absent',
                support: null,
                page: null,
                reason: null,
            },
            validation_url: {
                value: null,
                state: 'absent',
                support: null,
                page: null,
                reason: null,
            },
            category: {
                value: 'Programación',
                state: 'suggested',
                support: null,
                page: null,
                reason: 'El temario trata desarrollo con Laravel.',
            },
        },
        skills: [skill],
        ambiguities: ['La fecha no informa el mes.'],
    },
};

const current = {
    name: 'Mi curso',
    institution: '',
    date_year: '' as const,
    date_month: '' as const,
    duration: '',
    code: '',
    validation_url: '',
    category: '',
    skill_ids: [] as number[],
};

function PanelHarness() {
    const flow = useCertificationImport(limits);

    return (
        <CertificationImportPanel
            notice={notice}
            limits={limits}
            flow={flow}
            existingCategory=""
            existingSkillIds={[]}
        />
    );
}

beforeEach(() => {
    vi.mocked(postForm).mockReset();
    vi.mocked(postJson).mockReset();
});

describe('CertificationImportPanel', () => {
    it('does not analyze without explicit consent and sends the reviewed pages', async () => {
        vi.mocked(postForm).mockResolvedValue(preparation);
        vi.mocked(postJson).mockResolvedValue(analysis);

        render(<PanelHarness />);

        fireEvent.change(screen.getByLabelText(/Certificado PDF/), {
            target: {
                files: [
                    new File(['%PDF-1.4'], 'certificado.pdf', {
                        type: 'application/pdf',
                    }),
                ],
            },
        });

        fireEvent.click(screen.getByRole('button', { name: /Preparar texto/ }));

        await waitFor(() => expect(postForm).toHaveBeenCalledTimes(1));
        await screen.findByText('Contenido exacto que se enviará');

        const analyzeButton = screen.getByRole('button', {
            name: /Analizar con Luna 6/,
        });

        expect(analyzeButton).toBeDisabled();
        expect(screen.getByText(/El PDF no se envía/)).toBeInTheDocument();
        expect(
            screen.getByText(/coincidencia\(s\) de datos personales retiradas/),
        ).toBeInTheDocument();

        fireEvent.click(
            screen.getByRole('checkbox', { name: /autorizo el envío/ }),
        );

        expect(analyzeButton).toBeEnabled();
        fireEvent.click(analyzeButton);

        await waitFor(() => expect(postJson).toHaveBeenCalledTimes(1));

        const payload = vi.mocked(postJson).mock.calls[0][1] as Record<
            string,
            unknown
        >;

        expect(payload).toMatchObject({
            preparation_token: preparation.preparation_token,
            consent: true,
            existing_category: null,
            existing_skill_ids: [],
        });
        expect(payload.pages).toEqual(preparation.pages);
        await screen.findByText('Propuesta generada');
    });

    it('cancels during the analysis and discards a late response', async () => {
        vi.mocked(postForm).mockResolvedValue(preparation);
        let resolveAnalysis: (value: ImportAnalysis) => void = () => {};
        const pending = new Promise<ImportAnalysis>((resolve) => {
            resolveAnalysis = resolve;
        });
        vi.mocked(postJson).mockImplementation(() => pending);

        render(<PanelHarness />);

        fireEvent.change(screen.getByLabelText(/Certificado PDF/), {
            target: {
                files: [
                    new File(['%PDF-1.4'], 'certificado.pdf', {
                        type: 'application/pdf',
                    }),
                ],
            },
        });
        fireEvent.click(screen.getByRole('button', { name: /Preparar texto/ }));
        await waitFor(() => expect(postForm).toHaveBeenCalledTimes(1));
        await screen.findByText('Contenido exacto que se enviará');

        fireEvent.click(
            screen.getByRole('checkbox', { name: /autorizo el envío/ }),
        );
        fireEvent.click(
            screen.getByRole('button', { name: /Analizar con Luna 6/ }),
        );
        await screen.findByRole('button', { name: /Analizando…/ });

        fireEvent.click(
            screen.getByRole('button', { name: 'Cancelar importación' }),
        );

        expect(
            screen.queryByText('Contenido exacto que se enviará'),
        ).not.toBeInTheDocument();

        await act(async () => {
            resolveAnalysis(analysis);
            await pending;
        });

        expect(
            screen.queryByText('Propuesta generada'),
        ).not.toBeInTheDocument();
        expect(
            screen.getByText(
                /También podés crear la certificación manualmente/,
            ),
        ).toBeInTheDocument();
    });
});

describe('CertificationImportReview', () => {
    it('shows provenance and applies a value only after an explicit action', () => {
        const onApplyField = vi.fn();
        const onApplyDate = vi.fn();
        const onAcceptCategory = vi.fn();
        const onToggleSkill = vi.fn();

        render(
            <CertificationImportReview
                analysis={analysis}
                current={current}
                originalUrl="blob:original"
                acceptedCategory={false}
                onApplyField={onApplyField}
                onApplyDate={onApplyDate}
                onApplyCategory={vi.fn()}
                onAcceptCategory={onAcceptCategory}
                onToggleSkill={onToggleSkill}
                onDiscard={vi.fn()}
                onCancel={vi.fn()}
            />,
        );

        expect(screen.getAllByText('Encontrado').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Sugerencia').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Página 1').length).toBeGreaterThan(0);
        expect(screen.getByText('Datos ambiguos')).toBeInTheDocument();
        expect(
            screen.getByText('La fecha no informa el mes.'),
        ).toBeInTheDocument();
        expect(
            screen.getAllByText('No se encontró este dato en el documento.')
                .length,
        ).toBeGreaterThan(0);
        expect(
            screen.getByText(/El mes no está informado/),
        ).toBeInTheDocument();

        expect(onApplyField).not.toHaveBeenCalled();

        fireEvent.click(
            screen.getByRole('button', {
                name: 'Reemplazar por propuesta Nombre',
            }),
        );

        expect(onApplyField).toHaveBeenCalledWith('name', 'Curso de Laravel');

        fireEvent.click(
            screen.getByRole('checkbox', {
                name: /Acepto esta categoría sugerida/,
            }),
        );
        expect(onAcceptCategory).toHaveBeenCalledWith(true);

        fireEvent.click(
            screen.getByRole('checkbox', {
                name: /Aceptar sugerencia: Laravel/,
            }),
        );
        expect(onToggleSkill).toHaveBeenCalledWith(skill, true);

        fireEvent.click(screen.getByRole('button', { name: 'Aplicar fecha' }));
        expect(onApplyDate).toHaveBeenCalledWith(2024, null);
    });

    it('offers discard and cancel without saving anything', () => {
        const onDiscard = vi.fn();
        const onCancel = vi.fn();

        render(
            <CertificationImportReview
                analysis={analysis}
                current={current}
                originalUrl={null}
                acceptedCategory={false}
                onApplyField={vi.fn()}
                onApplyDate={vi.fn()}
                onApplyCategory={vi.fn()}
                onAcceptCategory={vi.fn()}
                onToggleSkill={vi.fn()}
                onDiscard={onDiscard}
                onCancel={onCancel}
            />,
        );

        fireEvent.click(
            screen.getByRole('button', {
                name: /Descartar propuesta y texto/,
            }),
        );
        expect(onDiscard).toHaveBeenCalledTimes(1);

        fireEvent.click(
            screen.getByRole('button', { name: 'Cancelar importación' }),
        );
        expect(onCancel).toHaveBeenCalledTimes(1);
    });
});
