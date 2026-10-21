export type DocumentAssociation = {
    type: 'certification' | 'education' | 'experience';
    id: number;
    label: string;
};

export type DocumentRecord = {
    id: number;
    display_name: string;
    original_filename: string;
    title: string | null;
    description: string | null;
    size_bytes: number;
    uploaded_at: string | null;
    is_available: boolean;
    last_verified_at: string | null;
    associations: DocumentAssociation[];
};

export type EntityOption = {
    id: number;
    label: string;
};

export type DocumentAssociationOptions = {
    certifications: EntityOption[];
    education: EntityOption[];
    experiences: EntityOption[];
};

export const ASSOCIATION_TYPE_LABELS: Record<
    DocumentAssociation['type'],
    string
> = {
    certification: 'Certificación',
    education: 'Educación',
    experience: 'Experiencia',
};

export function optionsForType(
    options: DocumentAssociationOptions,
    type: string,
): EntityOption[] {
    switch (type) {
        case 'certification':
            return options.certifications;
        case 'education':
            return options.education;
        case 'experience':
            return options.experiences;
        default:
            return [];
    }
}

export function formatBytes(bytes: number): string {
    if (!Number.isFinite(bytes) || bytes <= 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    let value = bytes;
    let unit = 0;

    while (value >= 1024 && unit < units.length - 1) {
        value /= 1024;
        unit += 1;
    }

    const decimals = unit === 0 || value >= 10 ? 0 : 1;

    return `${value.toFixed(decimals)} ${units[unit]}`;
}

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return date.toLocaleDateString('es', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return date.toLocaleString('es', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
