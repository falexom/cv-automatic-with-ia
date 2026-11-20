export type CertificationImportNotice = {
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
        note: string;
    };
};

export type CertificationImportLimits = {
    max_kilobytes: number;
    max_text_chars: number;
    max_pages: number;
    max_page_chars: number;
};

export type ImportPage = {
    page: number;
    text: string;
};

export type ImportPreparationStatus = 'ready' | 'insufficient' | 'too_long';

export type ImportPreparation = {
    preparation_token: string | null;
    checksum: string;
    original_filename: string;
    size_bytes: number;
    page_count: number;
    pages: ImportPage[];
    redactions: number;
    status: ImportPreparationStatus;
    message: string | null;
};

export type ImportFieldState = 'found' | 'suggested' | 'absent';

export type ImportField = {
    value: string | null;
    state: ImportFieldState;
    support: string | null;
    page: number | null;
    reason: string | null;
};

export type ImportDateField = {
    year: number | null;
    month: number | null;
    state: 'found' | 'absent';
    support: string | null;
    page: number | null;
    reason: null;
};

export type ImportSkill = {
    id: number;
    name: string;
    category: string;
    state: 'found' | 'suggested';
    support: string | null;
    page: number | null;
    reason: string | null;
};

export type ImportProposal = {
    schema_version: number;
    sufficiency: 'sufficient';
    sufficiency_note: string | null;
    fields: {
        name: ImportField;
        institution: ImportField;
        date: ImportDateField;
        duration: ImportField;
        code: ImportField;
        validation_url: ImportField;
        category: ImportField;
    };
    skills: ImportSkill[];
    ambiguities: string[];
};

export type ImportAnalysis = {
    proposal: ImportProposal;
    confirmation_token: string;
};

export type ImportFieldKey = keyof ImportProposal['fields'];

export const IMPORT_FIELD_LABELS: Record<
    Exclude<ImportFieldKey, 'date'>,
    string
> = {
    name: 'Nombre',
    institution: 'Institución',
    duration: 'Duración',
    code: 'Código o identificador',
    validation_url: 'URL de validación',
    category: 'Categoría',
};

export function importFieldLabel(field: ImportFieldKey): string {
    return field === 'date' ? 'Fecha' : IMPORT_FIELD_LABELS[field];
}

export function fieldStateLabel(state: ImportFieldState): string {
    switch (state) {
        case 'found':
            return 'Encontrado';
        case 'suggested':
            return 'Sugerencia';
        default:
            return 'No encontrado';
    }
}

export function formatImportPage(page: number | null): string {
    return page === null ? 'Sin página localizada' : `Página ${page}`;
}

export function formatImportDate(date: ImportDateField): string {
    if (date.year === null) {
        return '';
    }

    return date.month === null
        ? String(date.year)
        : `${String(date.month).padStart(2, '0')}/${date.year}`;
}

export function createImportConfirmationId(): string {
    if (
        typeof crypto !== 'undefined' &&
        typeof crypto.randomUUID === 'function'
    ) {
        return crypto.randomUUID();
    }

    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(
        /[xy]/g,
        (character) => {
            const random = Math.floor(Math.random() * 16);
            const value = character === 'x' ? random : (random & 0x3) | 0x8;

            return value.toString(16);
        },
    );
}
