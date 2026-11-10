import type { Relevance } from '@/lib/cv-profiles';

export type CvAdaptationOperation = 'reuse' | 'duplicate' | 'create';

export type CvAdaptationStrategy = 'auto' | CvAdaptationOperation;

export type CvAdaptationContentField =
    | 'work_experiences'
    | 'education_entries'
    | 'certifications'
    | 'skills'
    | 'projects';

export type CvAdaptationSelection = {
    ref: string;
    relevance: Relevance;
};

export type CvAdaptationSelectedContent = Record<
    CvAdaptationContentField,
    CvAdaptationSelection[]
>;

export type CvAdaptationProfileDetails = {
    name: string;
    professional_title: string | null;
    summary: string | null;
};

export type CvAdaptationRequirement = {
    ref: string;
    text: string;
    importance: 'required' | 'preferred' | 'unspecified';
    coverage: 'supported' | 'partial' | 'gap' | 'needs_confirmation';
    exclusive: boolean;
    exclusive_support: string | null;
};

export type CvAdaptationGap = {
    requirement_ref: string;
    reason: string;
    evidence_state: 'not_recorded' | 'needs_confirmation';
};

export type CvAdaptationRecommendation = {
    kind: 'skill' | 'course' | 'certification' | 'education';
    label: string;
    reason: string;
    evidence_state: 'not_recorded' | 'needs_confirmation';
};

export type CvAdaptationQuestion = {
    requirement_ref: string;
    question: string;
};

export type CvAdaptationProposal = {
    schema_version: 1;
    operation: CvAdaptationOperation;
    source_profile_ref: string | null;
    job_title: string;
    company: string | null;
    rationale: string[];
    profile_details: CvAdaptationProfileDetails | null;
    requirements: CvAdaptationRequirement[];
    gaps: CvAdaptationGap[];
    selected_content: CvAdaptationSelectedContent | null;
    development_recommendations: CvAdaptationRecommendation[];
    clarifying_questions: CvAdaptationQuestion[];
};

export type CvAdaptationCatalogWorkExperience = {
    ref: string;
    position: string;
    company: string;
    start_year: number | null;
    start_month: number | null;
    end_year: number | null;
    end_month: number | null;
    is_current: boolean;
    description: string | null;
    responsibilities: string | null;
    achievements: string | null;
    technologies: string[];
};

export type CvAdaptationCatalogEducation = {
    ref: string;
    institution: string;
    program: string;
    degree_level: string | null;
    start_year: number | null;
    start_month: number | null;
    end_year: number | null;
    end_month: number | null;
    is_in_progress: boolean;
    description: string | null;
};

export type CvAdaptationCatalogCertification = {
    ref: string;
    name: string;
    institution: string | null;
    date_year: number | null;
    date_month: number | null;
    duration: string | null;
    category: string | null;
};

export type CvAdaptationCatalogSkill = {
    ref: string;
    name: string;
    category: string;
};

export type CvAdaptationCatalogProject = {
    ref: string;
    name: string;
    role: string | null;
    start_year: number | null;
    start_month: number | null;
    end_year: number | null;
    end_month: number | null;
    description: string | null;
    results: string | null;
};

export type CvAdaptationCatalogProfile = {
    ref: string;
    name: string;
    professional_title: string | null;
    summary: string | null;
    is_active: boolean;
    selected_content: CvAdaptationSelectedContent;
};

export type CvAdaptationCatalog = {
    professional_title: string | null;
    summary: string | null;
    work_experiences: CvAdaptationCatalogWorkExperience[];
    education_entries: CvAdaptationCatalogEducation[];
    certifications: CvAdaptationCatalogCertification[];
    skills: CvAdaptationCatalogSkill[];
    projects: CvAdaptationCatalogProject[];
    profiles: CvAdaptationCatalogProfile[];
};

export type CvAdaptationDataNotice = {
    destination: string;
    model: string;
    model_label: string;
    reasoning_effort: string;
    reasoning_effort_label: string;
    sent: string[];
    excluded: string[];
    retention: {
        stored: string[];
        excluded: string[];
    };
};

export type CvAdaptationSummary = {
    work_experiences: number;
    education_entries: number;
    certifications: number;
    skills: number;
    projects: number;
    profiles: number;
    active_profiles: number;
};

export type CvAdaptationPageProps = {
    data_notice: CvAdaptationDataNotice;
    summary: CvAdaptationSummary;
};

export type CvAdaptationAnalysisResponse = {
    proposal: CvAdaptationProposal;
    catalog: CvAdaptationCatalog;
    ref_nonce: string;
    confirmation_token: string;
};

export type CvAdaptationApplyResponse = {
    redirect: string;
};

export type CvAdaptationQuestionStatus =
    | 'answered'
    | 'unanswered'
    | 'discarded';

export type CvAdaptationQuestionReview = {
    index: number;
    status: CvAdaptationQuestionStatus;
    answer: string | null;
    user_confirmed_absent: boolean;
};

export type CvAdaptationQuestionRecord = CvAdaptationQuestion & {
    status: CvAdaptationQuestionStatus;
    answer: string | null;
    answer_source: 'user_statement' | null;
    user_confirmed_absent: boolean;
};

export type CvAdaptationObservationType = 'gap' | 'recommendation' | 'question';

export type CvAdaptationObservationStatus =
    | 'pending'
    | 'resolved'
    | 'discarded';

export type CvAdaptationObservation = {
    id: number;
    type: CvAdaptationObservationType;
    reference: string;
    status: CvAdaptationObservationStatus;
    note: string | null;
};

export type CvAdaptationEvaluationContext = {
    schema_version: 1;
    job_title: string;
    company: string | null;
    operation: CvAdaptationOperation;
    rationale: string[];
    requirements: CvAdaptationRequirement[];
    gaps: CvAdaptationGap[];
    development_recommendations: CvAdaptationRecommendation[];
    clarifying_questions: CvAdaptationQuestionRecord[];
};

export type CvAdaptationEvaluationCounts = {
    supported: number;
    partial: number;
    gap: number;
    needs_confirmation: number;
    pending_coverage: number;
    total: number;
    pending_observations: number;
};

export type CvAdaptationEvaluationSummary = {
    id: number;
    operation: CvAdaptationOperation;
    job_title: string;
    company: string | null;
    analyzed_at: string;
    confirmed_at: string;
};

export type CvAdaptationEvaluation = CvAdaptationEvaluationSummary & {
    context: CvAdaptationEvaluationContext;
    counts: CvAdaptationEvaluationCounts;
    observations: CvAdaptationObservation[];
};

export type CvAdaptationProfile = {
    id: number;
    name: string;
    professional_title: string | null;
    summary: string | null;
    is_active: boolean;
};

export type CvAdaptationIndexProps = {
    profile: CvAdaptationProfile;
    adaptations: CvAdaptationEvaluationSummary[];
};

export type CvAdaptationShowProps = {
    profile: CvAdaptationProfile;
    adaptation: CvAdaptationEvaluation;
};

export const OPERATION_LABELS: Record<CvAdaptationOperation, string> = {
    reuse: 'Reutilizar perfil',
    duplicate: 'Duplicar y adaptar',
    create: 'Crear perfil nuevo',
};

export const OPERATION_DESCRIPTIONS: Record<CvAdaptationOperation, string> = {
    reuse: 'Se usará un perfil activo exactamente como está. No se guardará ni modificará el perfil; se guardará una evaluación histórica vinculada.',
    duplicate:
        'Se creará un perfil nuevo a partir de un perfil existente. El perfil de origen no se modificará y se guardará una evaluación histórica vinculada al nuevo.',
    create: 'Se creará un perfil nuevo con los registros del Perfil Maestro seleccionados y se guardará una evaluación histórica vinculada.',
};

export const IMPORTANCE_LABELS: Record<
    CvAdaptationRequirement['importance'],
    string
> = {
    required: 'Obligatorio',
    preferred: 'Deseable',
    unspecified: 'Sin especificar',
};

export const COVERAGE_LABELS: Record<
    CvAdaptationRequirement['coverage'],
    string
> = {
    supported: 'Con evidencia',
    partial: 'Cobertura parcial',
    gap: 'Brecha',
    needs_confirmation: 'Requiere confirmación',
};

export const RECOMMENDATION_KIND_LABELS: Record<
    CvAdaptationRecommendation['kind'],
    string
> = {
    skill: 'Habilidad',
    course: 'Curso',
    certification: 'Certificación',
    education: 'Formación',
};

export const EVIDENCE_STATE_LABELS: Record<
    CvAdaptationRecommendation['evidence_state'],
    string
> = {
    not_recorded: 'Sin información registrada',
    needs_confirmation: 'Requiere confirmación',
};

export const QUESTION_STATUS_LABELS: Record<
    CvAdaptationQuestionStatus,
    string
> = {
    answered: 'Respondida',
    unanswered: 'Sin respuesta',
    discarded: 'Descartada',
};

export const OBSERVATION_TYPE_LABELS: Record<
    CvAdaptationObservationType,
    string
> = {
    gap: 'Brecha',
    recommendation: 'Recomendación',
    question: 'Pregunta',
};

export const OBSERVATION_STATUS_LABELS: Record<
    CvAdaptationObservationStatus,
    string
> = {
    pending: 'Pendiente',
    resolved: 'Resuelto',
    discarded: 'Descartado',
};

export const CONTENT_FIELD_LABELS: Record<CvAdaptationContentField, string> = {
    work_experiences: 'Experiencia laboral',
    education_entries: 'Educación',
    certifications: 'Cursos y certificaciones',
    skills: 'Habilidades',
    projects: 'Proyectos',
};

export function emptySelectedContent(): CvAdaptationSelectedContent {
    return {
        work_experiences: [],
        education_entries: [],
        certifications: [],
        skills: [],
        projects: [],
    };
}

export function selectedContentFromProposal(
    proposal: CvAdaptationProposal,
): CvAdaptationSelectedContent {
    if (proposal.selected_content === null) {
        return emptySelectedContent();
    }

    return {
        work_experiences: [...proposal.selected_content.work_experiences],
        education_entries: [...proposal.selected_content.education_entries],
        certifications: [...proposal.selected_content.certifications],
        skills: [...proposal.selected_content.skills],
        projects: [...proposal.selected_content.projects],
    };
}
