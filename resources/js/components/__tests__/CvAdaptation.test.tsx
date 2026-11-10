import { router } from '@inertiajs/react';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { useState } from 'react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
    type MockInstance,
} from 'vitest';
import CvAdaptationObservationForm from '@/components/cv-adaptation/CvAdaptationObservationForm';
import CvAdaptationOfferForm from '@/components/cv-adaptation/CvAdaptationOfferForm';
import CvAdaptationReview from '@/components/cv-adaptation/CvAdaptationReview';
import type {
    CvAdaptationAnalysisResponse,
    CvAdaptationCatalog,
    CvAdaptationDataNotice,
    CvAdaptationObservation,
    CvAdaptationProposal,
    CvAdaptationSummary,
} from '@/lib/cv-adaptation';
import { postJson } from '@/lib/http';

vi.mock('@/lib/http', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@/lib/http')>();

    return {
        ...actual,
        postJson: vi.fn(),
    };
});

const notice: CvAdaptationDataNotice = {
    destination: 'Codex/OpenAI',
    model: 'gpt-6-luna',
    model_label: 'Luna 6',
    reasoning_effort: 'xhigh',
    reasoning_effort_label: 'Extra High',
    sent: ['La oferta completa.', 'El título y el resumen del Perfil Maestro.'],
    excluded: [
        'Nombre personal, teléfono, correo y ubicación.',
        'Fotografía, archivos y documentos.',
    ],
    retention: {
        stored: ['Puesto y empresa.', 'Requisitos y cobertura.'],
        excluded: ['Oferta completa.', 'Prompt y salida técnica.'],
    },
};

const summary: CvAdaptationSummary = {
    work_experiences: 1,
    education_entries: 1,
    certifications: 1,
    skills: 1,
    projects: 1,
    profiles: 1,
    active_profiles: 1,
};

const refs = {
    experience: 'exp_aaaaaaaaaaaa',
    education: 'edu_aaaaaaaaaaaa',
    certification: 'cert_aaaaaaaaaaaa',
    skill: 'skill_aaaaaaaaaaaa',
    project: 'proj_aaaaaaaaaaaa',
    profile: 'cv_aaaaaaaaaaaa',
};

const catalog: CvAdaptationCatalog = {
    professional_title: 'Arquitecta de Soluciones',
    summary: 'Resumen profesional del Maestro.',
    work_experiences: [
        {
            ref: refs.experience,
            position: 'Arquitecta de Software',
            company: 'Acme',
            start_year: 2020,
            start_month: 1,
            end_year: null,
            end_month: null,
            is_current: true,
            description: 'Diseño de servicios.',
            responsibilities: 'Arquitectura.',
            achievements: 'Entrega continua.',
            technologies: ['Laravel', 'AWS'],
        },
    ],
    education_entries: [
        {
            ref: refs.education,
            institution: 'Universidad',
            program: 'Sistemas',
            degree_level: 'Licenciatura',
            start_year: 2015,
            start_month: 2,
            end_year: 2020,
            end_month: 12,
            is_in_progress: false,
            description: 'Formación en sistemas.',
        },
    ],
    certifications: [
        {
            ref: refs.certification,
            name: 'Curso de Laravel',
            institution: 'Academy',
            date_year: 2023,
            date_month: 6,
            duration: '40 horas',
            category: 'Programación',
        },
    ],
    skills: [
        {
            ref: refs.skill,
            name: 'Laravel',
            category: 'tecnologia',
        },
    ],
    projects: [
        {
            ref: refs.project,
            name: 'Portal interno',
            role: 'Arquitecta',
            start_year: 2021,
            start_month: 1,
            end_year: 2021,
            end_month: 9,
            description: 'Migración del portal.',
            results: 'Mejora de tiempos.',
        },
    ],
    profiles: [
        {
            ref: refs.profile,
            name: 'Analista',
            professional_title: 'Analista de Sistemas',
            summary: 'Resumen del perfil de origen.',
            is_active: true,
            selected_content: {
                work_experiences: [{ ref: refs.experience, relevance: 'alta' }],
                education_entries: [
                    { ref: refs.education, relevance: 'media' },
                ],
                certifications: [
                    { ref: refs.certification, relevance: 'media' },
                ],
                skills: [{ ref: refs.skill, relevance: 'alta' }],
                projects: [{ ref: refs.project, relevance: 'baja' }],
            },
        },
    ],
};

const proposal: CvAdaptationProposal = {
    schema_version: 1,
    operation: 'duplicate',
    source_profile_ref: refs.profile,
    job_title: 'Analista de Sistemas',
    company: 'Acme',
    rationale: ['La oferta pide Laravel y el perfil lo registra.'],
    profile_details: {
        name: 'Analista adaptado',
        professional_title: 'Analista de Sistemas',
        summary: 'Resumen revisable.',
    },
    requirements: [
        {
            ref: 'req_01',
            text: 'Experiencia con Laravel',
            importance: 'required',
            coverage: 'supported',
            exclusive: false,
            exclusive_support: null,
        },
        {
            ref: 'req_02',
            text: 'Experiencia con AWS',
            importance: 'required',
            coverage: 'gap',
            exclusive: true,
            exclusive_support: 'Se requiere AWS excluyente.',
        },
    ],
    gaps: [
        {
            requirement_ref: 'req_02',
            reason: 'No hay evidencia de AWS registrada.',
            evidence_state: 'not_recorded',
        },
    ],
    selected_content: {
        work_experiences: [{ ref: refs.experience, relevance: 'alta' }],
        education_entries: [{ ref: refs.education, relevance: 'media' }],
        certifications: [{ ref: refs.certification, relevance: 'media' }],
        skills: [{ ref: refs.skill, relevance: 'alta' }],
        projects: [{ ref: refs.project, relevance: 'baja' }],
    },
    development_recommendations: [
        {
            kind: 'skill',
            label: 'AWS',
            reason: 'La oferta lo pide y no está registrado.',
            evidence_state: 'not_recorded',
        },
    ],
    clarifying_questions: [
        {
            requirement_ref: 'req_02',
            question: '¿Tenés experiencia con AWS?',
        },
    ],
};

const analysis: CvAdaptationAnalysisResponse = {
    proposal,
    catalog,
    ref_nonce: '0123456789abcdef0123456789abcdef',
    confirmation_token: 'token-de-confirmacion',
};

let visitSpy: MockInstance;

beforeEach(() => {
    vi.mocked(postJson).mockReset();
    visitSpy = vi.spyOn(router, 'visit').mockImplementation(() => {});
});

afterEach(() => {
    vi.restoreAllMocks();
});

describe('CvAdaptationOfferForm', () => {
    it('keeps the analysis disabled until offer and explicit consent exist', () => {
        const onAnalyze = vi.fn();

        render(
            <CvAdaptationOfferForm
                offer=""
                onOfferChange={() => {}}
                strategy="auto"
                onStrategyChange={() => {}}
                dataNotice={notice}
                summary={summary}
                processing={false}
                error={null}
                onAnalyze={onAnalyze}
            />,
        );

        const button = screen.getByRole('button', {
            name: /Analizar oferta/,
        });

        expect(button).toBeDisabled();
        expect(screen.getByText('No se enviará')).toBeInTheDocument();
        expect(
            screen.getByText(/Sin esta confirmación no se envía información/),
        ).toBeInTheDocument();
        expect(onAnalyze).not.toHaveBeenCalled();
    });

    it('calls the analysis only with an offer and consent', () => {
        const onAnalyze = vi.fn();

        render(<OfferHarness onAnalyze={onAnalyze} />);

        fireEvent.change(screen.getByLabelText(/Texto de la oferta/), {
            target: { value: 'Buscamos Laravel.' },
        });

        const button = screen.getByRole('button', {
            name: /Analizar oferta/,
        });

        expect(button).toBeDisabled();

        fireEvent.click(
            screen.getByRole('checkbox', { name: /autorizo el envío/ }),
        );

        expect(button).toBeEnabled();
        fireEvent.click(button);

        expect(onAnalyze).toHaveBeenCalledWith(true);
    });

    it('shows the configured model and effort next to the analysis action', () => {
        render(<OfferHarness onAnalyze={vi.fn()} />);

        const line = screen.getByText('Luna 6').closest('p');

        expect(line).not.toBeNull();
        expect(line).toHaveTextContent(
            'Modelo configurado: Luna 6 · Esfuerzo: Extra High',
        );
        expect(
            line
                ?.closest('form')
                ?.contains(
                    screen.getByRole('button', { name: /Analizar oferta/ }),
                ),
        ).toBe(true);
    });

    it('renders configured labels without fixed model or effort values', () => {
        render(
            <OfferHarness
                onAnalyze={vi.fn()}
                dataNotice={{
                    ...notice,
                    model_label: 'custom-model',
                    reasoning_effort_label: 'High',
                }}
            />,
        );

        expect(screen.getByText('custom-model')).toBeInTheDocument();
        expect(screen.getByText('High')).toBeInTheDocument();
        expect(screen.queryByText('Luna 6')).not.toBeInTheDocument();
        expect(screen.queryByText('Extra High')).not.toBeInTheDocument();
    });
});

describe('CvAdaptationReview', () => {
    it('shows development recommendations apart and never as selected content', () => {
        render(
            <CvAdaptationReview
                analysis={analysis}
                dataNotice={notice}
                onCancel={() => {}}
            />,
        );

        expect(
            screen.getByText(/Recomendaciones de desarrollo/),
        ).toBeInTheDocument();
        expect(screen.getByText('AWS')).toBeInTheDocument();
        expect(
            screen.getByText(/no se agregan al Perfil Maestro ni al perfil/),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('checkbox', { name: /^AWS$/ }),
        ).not.toBeInTheDocument();
    });

    it('allows discarding an optional question without blocking the review', () => {
        render(
            <CvAdaptationReview
                analysis={analysis}
                dataNotice={notice}
                onCancel={() => {}}
            />,
        );

        expect(
            screen.getByText('¿Tenés experiencia con AWS?'),
        ).toBeInTheDocument();

        fireEvent.click(
            screen.getByRole('button', { name: 'Descartar pregunta' }),
        );

        expect(
            screen.queryByText('¿Tenés experiencia con AWS?'),
        ).not.toBeInTheDocument();
        expect(
            screen.getByText('No quedan preguntas por revisar.'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', {
                name: 'Crear perfil y guardar evaluación',
            }),
        ).toBeInTheDocument();
    });

    it('submits reviewed profile data, evaluation metadata and answers', async () => {
        vi.mocked(postJson).mockResolvedValue({
            redirect: '/cv?profile=9',
        });

        render(
            <CvAdaptationReview
                analysis={analysis}
                dataNotice={notice}
                onCancel={() => {}}
            />,
        );

        const apply = screen.getByRole('button', {
            name: 'Crear perfil y guardar evaluación',
        });

        expect(apply).toBeDisabled();

        fireEvent.change(
            screen.getByLabelText(/Respuesta a: ¿Tenés experiencia con AWS\?/),
            { target: { value: 'No tengo esa certificación.' } },
        );

        fireEvent.click(
            screen.getByRole('checkbox', {
                name: /Entiendo y acepto que se guarde/,
            }),
        );

        expect(apply).toBeEnabled();
        fireEvent.click(apply);

        await waitFor(() =>
            expect(vi.mocked(postJson)).toHaveBeenCalledTimes(1),
        );

        const payload = vi.mocked(postJson).mock.calls[0][1] as Record<
            string,
            unknown
        >;

        expect(payload).toMatchObject({
            operation: 'duplicate',
            ref_nonce: analysis.ref_nonce,
            confirmation_token: analysis.confirmation_token,
            retention_accepted: true,
            job_title: 'Analista de Sistemas',
            company: 'Acme',
            source_profile_ref: refs.profile,
            profile_details: {
                name: 'Analista adaptado',
                professional_title: 'Analista de Sistemas',
                summary: 'Resumen revisable.',
            },
            questions: [
                {
                    index: 0,
                    status: 'answered',
                    answer: 'No tengo esa certificación.',
                    user_confirmed_absent: false,
                },
            ],
        });
        expect(payload.selected_content).toMatchObject({
            work_experiences: [{ ref: refs.experience, relevance: 'alta' }],
            skills: [{ ref: refs.skill, relevance: 'alta' }],
        });
        expect(payload).not.toHaveProperty('development_recommendations');
        expect(payload).not.toHaveProperty('clarifying_questions');
        expect(payload).not.toHaveProperty('answers');

        await waitFor(() =>
            expect(visitSpy).toHaveBeenCalledWith('/cv?profile=9'),
        );
    });

    it('stores an explicit absence declaration only when the user confirms it', async () => {
        vi.mocked(postJson).mockResolvedValue({
            redirect: '/cv?profile=9',
        });

        render(
            <CvAdaptationReview
                analysis={analysis}
                dataNotice={notice}
                onCancel={() => {}}
            />,
        );

        const absence = screen.getByRole('checkbox', {
            name: /Confirmo que no dispongo de este requisito/,
        });

        expect(absence).toBeDisabled();

        fireEvent.change(
            screen.getByLabelText(/Respuesta a: ¿Tenés experiencia con AWS\?/),
            { target: { value: 'No tengo esa certificación.' } },
        );

        expect(absence).toBeEnabled();
        fireEvent.click(absence);

        fireEvent.click(
            screen.getByRole('checkbox', {
                name: /Entiendo y acepto que se guarde/,
            }),
        );

        fireEvent.click(
            screen.getByRole('button', {
                name: 'Crear perfil y guardar evaluación',
            }),
        );

        await waitFor(() =>
            expect(vi.mocked(postJson)).toHaveBeenCalledTimes(1),
        );

        const payload = vi.mocked(postJson).mock.calls[0][1] as {
            questions: Array<Record<string, unknown>>;
        };

        expect(payload.questions[0]).toMatchObject({
            index: 0,
            status: 'answered',
            answer: 'No tengo esa certificación.',
            user_confirmed_absent: true,
        });
    });

    it('uses reuse without sending profile changes', async () => {
        vi.mocked(postJson).mockResolvedValue({
            redirect: '/cv?profile=1',
        });

        const reuseAnalysis: CvAdaptationAnalysisResponse = {
            ...analysis,
            proposal: {
                ...proposal,
                operation: 'reuse',
                profile_details: null,
                selected_content: null,
                development_recommendations: [],
                clarifying_questions: [],
            },
        };

        render(
            <CvAdaptationReview
                analysis={reuseAnalysis}
                dataNotice={notice}
                onCancel={() => {}}
            />,
        );

        expect(
            screen.queryByLabelText('Nombre del perfil *'),
        ).not.toBeInTheDocument();

        fireEvent.click(
            screen.getByRole('checkbox', {
                name: /Entiendo y acepto que se guarde/,
            }),
        );

        fireEvent.click(
            screen.getByRole('button', {
                name: 'Usar perfil y guardar evaluación',
            }),
        );

        await waitFor(() =>
            expect(vi.mocked(postJson)).toHaveBeenCalledTimes(1),
        );

        const payload = vi.mocked(postJson).mock.calls[0][1] as Record<
            string,
            unknown
        >;

        expect(payload).toMatchObject({
            operation: 'reuse',
            ref_nonce: analysis.ref_nonce,
            confirmation_token: analysis.confirmation_token,
            retention_accepted: true,
            job_title: 'Analista de Sistemas',
            company: 'Acme',
            source_profile_ref: refs.profile,
            profile_details: null,
            selected_content: null,
            questions: [],
        });
    });
});

describe('CvAdaptationObservationForm', () => {
    it('saves status and note and can clear the note', () => {
        const observation: CvAdaptationObservation = {
            id: 7,
            type: 'gap',
            reference: 'gap:req_02',
            status: 'pending',
            note: null,
        };
        const patch = vi
            .spyOn(router, 'patch')
            .mockImplementation((_url, _data, options) => {
                options?.onFinish?.({} as never);
            });

        render(
            <CvAdaptationObservationForm
                profileId={1}
                adaptationId={2}
                observation={observation}
                linkedTitle="Brecha AWS"
                linkedDetail="Falta evidencia de AWS."
            />,
        );

        fireEvent.change(screen.getByLabelText('Estado'), {
            target: { value: 'resolved' },
        });
        fireEvent.change(screen.getByLabelText('Nota opcional'), {
            target: { value: 'Revisar AWS.' },
        });
        fireEvent.click(
            screen.getByRole('button', { name: 'Guardar observación' }),
        );

        expect(patch).toHaveBeenCalledWith(
            '/profiles/1/adaptations/2/observations/7',
            { status: 'resolved', note: 'Revisar AWS.' },
            expect.objectContaining({ preserveScroll: true }),
        );

        fireEvent.click(screen.getByRole('button', { name: 'Borrar nota' }));

        expect(patch).toHaveBeenLastCalledWith(
            '/profiles/1/adaptations/2/observations/7',
            { status: 'resolved', note: '' },
            expect.objectContaining({ preserveScroll: true }),
        );
    });
});

function OfferHarness({
    onAnalyze,
    dataNotice = notice,
}: {
    onAnalyze: (consent: boolean) => void;
    dataNotice?: CvAdaptationDataNotice;
}) {
    const [offer, setOffer] = useState('');

    return (
        <CvAdaptationOfferForm
            offer={offer}
            onOfferChange={setOffer}
            strategy="auto"
            onStrategyChange={() => {}}
            dataNotice={dataNotice}
            summary={summary}
            processing={false}
            error={null}
            onAnalyze={onAnalyze}
        />
    );
}
