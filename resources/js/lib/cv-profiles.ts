export type Relevance = 'alta' | 'media' | 'baja';

export const RELEVANCE_LABELS: Record<Relevance, string> = {
    alta: 'Alta',
    media: 'Media',
    baja: 'Baja',
};

export const RELEVANCE_OPTIONS: { value: Relevance; label: string }[] = [
    { value: 'alta', label: RELEVANCE_LABELS.alta },
    { value: 'media', label: RELEVANCE_LABELS.media },
    { value: 'baja', label: RELEVANCE_LABELS.baja },
];

export type CvProfileOwnData = {
    id: number;
    name: string;
    professional_title: string | null;
    summary: string | null;
    is_active: boolean;
};

export type CvProfileSummary = {
    id: number;
    name: string;
    professional_title: string | null;
    is_active: boolean;
};

export type CvProfileWorkExperienceOption = {
    id: number;
    company: string;
    position: string;
    start_year: number | null;
    start_month: number | null;
    end_year: number | null;
    end_month: number | null;
    is_current: boolean;
};

export type CvProfileEducationOption = {
    id: number;
    institution: string;
    program: string;
    degree_level: string | null;
    start_year: number | null;
    start_month: number | null;
    end_year: number | null;
    end_month: number | null;
    is_in_progress: boolean;
};

export type CvProfileCertificationOption = {
    id: number;
    name: string;
    institution: string | null;
    date_year: number | null;
    date_month: number | null;
};

export type CvProfileSkillOption = {
    id: number;
    name: string;
    category: string;
};

export type CvProfileProjectOption = {
    id: number;
    name: string;
    role: string | null;
    start_year: number | null;
    start_month: number | null;
    end_year: number | null;
    end_month: number | null;
};

export type CvProfileOptions = {
    work_experiences: CvProfileWorkExperienceOption[];
    education_entries: CvProfileEducationOption[];
    certifications: CvProfileCertificationOption[];
    skills: CvProfileSkillOption[];
    projects: CvProfileProjectOption[];
};

export type CvProfileSelectionItem = {
    id: number;
    relevance: Relevance;
};

export type CvProfileSelections = {
    work_experiences: CvProfileSelectionItem[];
    education_entries: CvProfileSelectionItem[];
    certifications: CvProfileSelectionItem[];
    skills: CvProfileSelectionItem[];
    projects: CvProfileSelectionItem[];
};

export type CvProfileContentItemBase = {
    id: number;
    relevance: Relevance;
    master_path: string;
};

export type CvProfileWorkExperienceContent = CvProfileWorkExperienceOption &
    CvProfileContentItemBase & { description: string | null };

export type CvProfileEducationContent = CvProfileEducationOption &
    CvProfileContentItemBase & { description: string | null };

export type CvProfileCertificationContent = CvProfileCertificationOption &
    CvProfileContentItemBase & {
        duration: string | null;
        category: string | null;
        code: string | null;
        validation_url: string | null;
    };

export type CvProfileSkillContent = CvProfileSkillOption &
    CvProfileContentItemBase;

export type CvProfileProjectContent = CvProfileProjectOption &
    CvProfileContentItemBase & {
        url: string | null;
        description: string | null;
    };

export type CvProfileContent = {
    work_experiences: CvProfileWorkExperienceContent[];
    education_entries: CvProfileEducationContent[];
    certifications: CvProfileCertificationContent[];
    skills: CvProfileSkillContent[];
    projects: CvProfileProjectContent[];
};

export type CvProfileLink = {
    id: number;
    label: string;
    url: string;
};

export type CvProfilePersonalData = {
    full_name: string | null;
    phone: string | null;
    email: string | null;
    location: string | null;
    linkedin_url: string | null;
    github_url: string | null;
    website_url: string | null;
    links: CvProfileLink[];
    has_data: boolean;
};

export type CvProfileShowProps = {
    profile: CvProfileOwnData;
    personal_data: CvProfilePersonalData;
    content: CvProfileContent;
};

export type CvProfileFormData = {
    name: string;
    professional_title: string;
    summary: string;
    work_experiences: CvProfileSelectionItem[];
    education_entries: CvProfileSelectionItem[];
    certifications: CvProfileSelectionItem[];
    skills: CvProfileSelectionItem[];
    projects: CvProfileSelectionItem[];
};

export function emptyCvProfileSelections(): CvProfileSelections {
    return {
        work_experiences: [],
        education_entries: [],
        certifications: [],
        skills: [],
        projects: [],
    };
}

export function emptyCvProfileFormData(): CvProfileFormData {
    return {
        name: '',
        professional_title: '',
        summary: '',
        ...emptyCvProfileSelections(),
    };
}
