import type { CvProfileLink, CvProfilePersonalData } from '@/lib/cv-profiles';
import type { DocumentAssociation } from '@/lib/documents';

// Helpers de presentación compartidos: se reexportan para que la vista previa
// los consuma sin duplicar su lógica.
export { formatRange, formatYearMonth } from '@/lib/dates';
export { SKILL_CATEGORY_LABELS } from '@/lib/skill-categories';

export type CvGeneratorStatus =
    | 'no_active_profiles'
    | 'selection_required'
    | 'unavailable'
    | 'ready'
    | 'error';

export type CvProfileOption = {
    id: number;
    name: string;
    professional_title: string | null;
};

export type CvPreviewWorkExperience = {
    id: number;
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
};

export type CvPreviewEducationEntry = {
    id: number;
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

export type CvPreviewCertification = {
    id: number;
    name: string;
    institution: string | null;
    date_year: number | null;
    date_month: number | null;
    duration: string | null;
    category: string | null;
    code: string | null;
    validation_url: string | null;
};

export type CvPreviewSkill = {
    id: number;
    name: string;
    category: string;
};

export type CvPreviewProject = {
    id: number;
    name: string;
    role: string | null;
    start_year: number | null;
    start_month: number | null;
    end_year: number | null;
    end_month: number | null;
    description: string | null;
    results: string | null;
    url: string | null;
};

export type CvPreviewSections = {
    work_experiences: CvPreviewWorkExperience[];
    education_entries: CvPreviewEducationEntry[];
    certifications: CvPreviewCertification[];
    skills: CvPreviewSkill[];
    projects: CvPreviewProject[];
};

export type CvPreviewLink = CvProfileLink;

export type CvPhotoData = {
    data_uri: string;
    focus_x: number;
    focus_y: number;
    zoom: number;
};

export type CvPreviewPersonalData = CvProfilePersonalData & {
    photo: CvPhotoData | null;
};

export type CvPreviewSourceLinks = {
    profile: string;
    personal_data: string;
    work_experiences: string;
    education_entries: string;
    certifications: string;
    skills: string;
    projects: string;
};

export type CvPreviewData = {
    personal_data: CvPreviewPersonalData;
    professional_title: string | null;
    summary: string | null;
    content: CvPreviewSections;
    source_links: CvPreviewSourceLinks;
    has_data: boolean;
};

export type CvEvidence = {
    id: number;
    display_name: string;
    is_available: boolean;
    last_verified_at: string | null;
    associations: DocumentAssociation[];
};

export type CvGeneratorPageProps = {
    profiles: CvProfileOption[];
    status: CvGeneratorStatus;
    selected_profile_id: number | null;
    preview: CvPreviewData | null;
    evidences?: CvEvidence[];
};
