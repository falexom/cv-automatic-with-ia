import { router } from '@inertiajs/react';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import {
    beforeEach,
    describe,
    expect,
    it,
    vi,
    type MockInstance,
} from 'vitest';
import CertificationForm from '@/components/forms/CertificationForm';
import type { SkillOption } from '@/components/SkillMultiSelect';
import type {
    CertificationImportLimits,
    CertificationImportNotice,
    ImportAnalysis,
    ImportPreparation,
} from '@/lib/certification-import';
import { postForm, postJson } from '@/lib/http';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@inertiajs/react')>();

    return {
        ...actual,
        usePage: () => ({ props: { flash: {} } }),
    };
});

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
    sent: ['El texto nativo extraído.'],
    excluded: ['El PDF completo.'],
    retention: {
        stored: ['Solo el registro confirmado y su PDF.'],
        excluded: ['Texto, prompt y borradores.'],
        note: 'La limpieza local no revierte un envío externo.',
    },
};

const skills: SkillOption[] = [
    { id: 7, name: 'Laravel', category: 'tecnologia' },
];

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
        },
    ],
    redactions: 0,
    status: 'ready',
    message: null,
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
                value: 'Academy',
                state: 'found',
                support: 'Academy',
                page: 1,
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
        skills: [
            {
                id: 7,
                name: 'Laravel',
                category: 'tecnologia',
                state: 'suggested',
                support: null,
                page: null,
                reason: 'El temario menciona Laravel.',
            },
        ],
        ambiguities: [],
    },
};

let visitSpy: MockInstance;

beforeEach(() => {
    vi.mocked(postForm).mockReset();
    vi.mocked(postJson).mockReset();
    visitSpy = vi.spyOn(router, 'visit').mockImplementation(() => {});
});

describe('CertificationForm PDF import', () => {
    it('confirms exactly the reviewed values, accepted suggestions and original', async () => {
        vi.mocked(postForm).mockImplementation(async (url) => {
            if (url === '/certifications/import/prepare') {
                return preparation;
            }

            return { redirect: '/certifications' };
        });
        vi.mocked(postJson).mockResolvedValue(analysis);

        render(
            <CertificationForm
                skills={skills}
                importNotice={notice}
                importLimits={limits}
            />,
        );

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
        await screen.findByText('Contenido exacto que se enviará');

        fireEvent.click(
            screen.getByRole('checkbox', { name: /autorizo el envío/ }),
        );
        fireEvent.click(
            screen.getByRole('button', { name: /Analizar con Luna 6/ }),
        );

        await screen.findByText('Propuesta de importación');
        expect(postForm).toHaveBeenCalledTimes(1);

        fireEvent.click(screen.getByRole('button', { name: 'Aplicar Nombre' }));
        expect(screen.getByLabelText('Nombre *')).toHaveValue(
            'Curso de Laravel',
        );

        fireEvent.click(
            screen.getByRole('button', { name: 'Aplicar Institución' }),
        );
        expect(screen.getByLabelText('Institución')).toHaveValue('Academy');

        fireEvent.click(screen.getByRole('button', { name: 'Aplicar fecha' }));
        expect(screen.getByLabelText('Fecha')).toHaveValue('2024');

        fireEvent.click(
            screen.getByRole('checkbox', {
                name: /Acepto esta categoría sugerida/,
            }),
        );
        expect(screen.getByLabelText('Categoría')).toHaveValue('Programación');

        fireEvent.click(
            screen.getByRole('checkbox', {
                name: /Aceptar sugerencia: Laravel/,
            }),
        );

        fireEvent.click(
            screen.getByRole('button', {
                name: 'Crear certificación y asociar PDF',
            }),
        );

        await waitFor(() => expect(postForm).toHaveBeenCalledTimes(2));
        await waitFor(() =>
            expect(visitSpy).toHaveBeenCalledWith('/certifications'),
        );

        const body = vi.mocked(postForm).mock.calls[1][1] as FormData;

        expect(body.get('name')).toBe('Curso de Laravel');
        expect(body.get('institution')).toBe('Academy');
        expect(body.get('date_year')).toBe('2024');
        expect(body.get('category')).toBe('Programación');
        expect(body.get('proposal_token')).toBe(analysis.confirmation_token);
        expect(body.get('accepted_category')).toBe('1');
        expect(body.getAll('skill_ids[]')).toEqual(['7']);
        expect(body.getAll('accepted_skill_ids[]')).toEqual(['7']);
        expect(body.get('confirmation_id')).toEqual(expect.any(String));
        expect((body.get('file') as File).name).toBe('certificado.pdf');
        expect(body.get('confirm_duplicate')).toBeNull();
    });
});
