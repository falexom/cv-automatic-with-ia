import { useCallback, useEffect, useRef, useState } from 'react';
import {
    createImportConfirmationId,
    type CertificationImportLimits,
    type ImportAnalysis,
    type ImportPage,
    type ImportPreparation,
} from '@/lib/certification-import';
import { postForm, postJson, RequestError } from '@/lib/http';

export type CertificationImportError = {
    message: string;
    code?: string;
    errors?: Record<string, string[]>;
};

export const DEFAULT_IMPORT_LIMITS: CertificationImportLimits = {
    max_kilobytes: 20480,
    max_text_chars: 60000,
    max_pages: 30,
    max_page_chars: 20000,
};

export type CertificationImportFlow = {
    file: File | null;
    fileInputKey: number;
    objectUrl: string | null;
    fileError: string | null;
    fileInvalid: boolean;
    preparation: ImportPreparation | null;
    pages: ImportPage[];
    analysis: ImportAnalysis | null;
    consent: boolean;
    preparing: boolean;
    analyzing: boolean;
    manualMode: boolean;
    error: CertificationImportError | null;
    confirmationId: string;
    acceptedCategory: boolean;
    acceptedSkillIds: number[];
    totalChars: number;
    canAnalyze: boolean;
    selectFile: (file: File | null) => void;
    prepare: () => Promise<void>;
    analyze: (
        existingCategory: string,
        existingSkillIds: number[],
    ) => Promise<void>;
    setPageText: (page: number, text: string) => void;
    setConsent: (consent: boolean) => void;
    acceptCategory: (accepted: boolean) => void;
    acceptSkill: (id: number, accepted: boolean) => void;
    clearError: () => void;
    markFileInvalid: (message: string) => void;
    discardProposal: () => void;
    enterManualMode: () => void;
    cancel: () => void;
};

function isAbortError(error: unknown): boolean {
    return (
        (error instanceof DOMException && error.name === 'AbortError') ||
        (error instanceof Error && error.name === 'AbortError')
    );
}

function createObjectUrl(file: File): string | null {
    if (
        typeof URL === 'undefined' ||
        typeof URL.createObjectURL !== 'function'
    ) {
        return null;
    }

    return URL.createObjectURL(file);
}

export function useCertificationImport(
    limits: CertificationImportLimits = DEFAULT_IMPORT_LIMITS,
): CertificationImportFlow {
    const [file, setFile] = useState<File | null>(null);
    const [fileInputKey, setFileInputKey] = useState(0);
    const [objectUrl, setObjectUrl] = useState<string | null>(null);
    const [fileError, setFileError] = useState<string | null>(null);
    const [fileInvalid, setFileInvalid] = useState(false);
    const [preparation, setPreparation] = useState<ImportPreparation | null>(
        null,
    );
    const [pages, setPages] = useState<ImportPage[]>([]);
    const [analysis, setAnalysis] = useState<ImportAnalysis | null>(null);
    const [consent, setConsentState] = useState(false);
    const [preparing, setPreparing] = useState(false);
    const [analyzing, setAnalyzing] = useState(false);
    const [manualMode, setManualMode] = useState(false);
    const [error, setError] = useState<CertificationImportError | null>(null);
    const [confirmationId, setConfirmationId] = useState(() =>
        createImportConfirmationId(),
    );
    const [acceptedCategory, setAcceptedCategory] = useState(false);
    const [acceptedSkillIds, setAcceptedSkillIds] = useState<number[]>([]);

    const abortRef = useRef<AbortController | null>(null);
    const generationRef = useRef(0);
    const objectUrlRef = useRef<string | null>(null);
    const preparingRef = useRef(false);
    const analyzingRef = useRef(false);

    const abortRequest = useCallback(() => {
        abortRef.current?.abort();
        abortRef.current = null;
        generationRef.current += 1;
        preparingRef.current = false;
        analyzingRef.current = false;
    }, []);

    const revokeObjectUrl = useCallback(() => {
        if (
            objectUrlRef.current !== null &&
            typeof URL.revokeObjectURL === 'function'
        ) {
            URL.revokeObjectURL(objectUrlRef.current);
            objectUrlRef.current = null;
        }
    }, []);

    const resetDerivedState = useCallback(() => {
        preparingRef.current = false;
        analyzingRef.current = false;
        setPreparation(null);
        setPages([]);
        setAnalysis(null);
        setConsentState(false);
        setAcceptedCategory(false);
        setAcceptedSkillIds([]);
        setManualMode(false);
        setPreparing(false);
        setAnalyzing(false);
        setError(null);
    }, []);

    useEffect(() => {
        return () => {
            abortRef.current?.abort();
            revokeObjectUrl();
        };
    }, [revokeObjectUrl]);

    const selectFile = useCallback(
        (nextFile: File | null) => {
            abortRequest();
            revokeObjectUrl();
            resetDerivedState();
            setFileInvalid(false);
            setFileError(null);
            setFile(nextFile);
            setFileInputKey((key) => key + 1);
            setConfirmationId(createImportConfirmationId());

            if (nextFile === null) {
                setObjectUrl(null);

                return;
            }

            const looksLikePdf =
                nextFile.type === 'application/pdf' ||
                nextFile.name.toLowerCase().endsWith('.pdf');
            const tooLarge = nextFile.size > limits.max_kilobytes * 1024;

            if (!looksLikePdf) {
                setFileError('Seleccioná un único archivo PDF.');
                setFileInvalid(true);
                setObjectUrl(null);

                return;
            }

            if (tooLarge) {
                setFileError(
                    `El PDF supera el límite de ${Math.round(limits.max_kilobytes / 1024)} MB.`,
                );
                setFileInvalid(true);
                setObjectUrl(null);

                return;
            }

            const url = createObjectUrl(nextFile);
            objectUrlRef.current = url;
            setObjectUrl(url);
        },
        [
            abortRequest,
            limits.max_kilobytes,
            resetDerivedState,
            revokeObjectUrl,
        ],
    );

    const prepare = useCallback(async () => {
        if (file === null || fileInvalid || preparingRef.current) {
            return;
        }

        abortRequest();
        preparingRef.current = true;
        const controller = new AbortController();
        abortRef.current = controller;
        const generation = generationRef.current;

        setPreparing(true);
        setManualMode(false);
        setError(null);

        try {
            const formData = new FormData();
            formData.append('file', file);

            const response = await postForm<ImportPreparation>(
                '/certifications/import/prepare',
                formData,
                controller.signal,
            );

            if (generation !== generationRef.current) {
                return;
            }

            setPreparation(response);
            setPages(response.pages.map((page) => ({ ...page })));
            setAnalysis(null);
            setConsentState(false);
            setAcceptedCategory(false);
            setAcceptedSkillIds([]);
            setManualMode(false);
            setFileInvalid(false);
        } catch (caught) {
            if (isAbortError(caught) || generation !== generationRef.current) {
                return;
            }

            if (caught instanceof RequestError) {
                const invalidFile =
                    caught.code === 'pdf_invalid' ||
                    caught.code === 'pdf_missing' ||
                    Boolean(caught.errors?.file);

                setFileInvalid(invalidFile);
                setError({
                    message: caught.message,
                    code: caught.code,
                    errors: caught.errors,
                });
            } else {
                setError({
                    message:
                        'No se pudo preparar el texto del PDF. No se guardó nada.',
                });
            }

            setPreparation(null);
            setPages([]);
            setAnalysis(null);
            setConsentState(false);
        } finally {
            if (generation === generationRef.current) {
                setPreparing(false);
            }

            if (abortRef.current === controller) {
                abortRef.current = null;
                preparingRef.current = false;
            }
        }
    }, [abortRequest, file, fileInvalid]);

    const analyze = useCallback(
        async (existingCategory: string, existingSkillIds: number[]) => {
            const token = preparation?.preparation_token;

            if (
                token === null ||
                token === undefined ||
                pages.length === 0 ||
                !consent ||
                analyzingRef.current
            ) {
                return;
            }

            abortRequest();
            analyzingRef.current = true;
            const controller = new AbortController();
            abortRef.current = controller;
            const generation = generationRef.current;

            setAnalyzing(true);
            setError(null);

            try {
                const response = await postJson<ImportAnalysis>(
                    '/certifications/import/analyze',
                    {
                        preparation_token: token,
                        consent: true,
                        pages,
                        existing_category:
                            existingCategory.trim() === ''
                                ? null
                                : existingCategory,
                        existing_skill_ids: existingSkillIds,
                    },
                    controller.signal,
                );

                if (generation !== generationRef.current) {
                    return;
                }

                setAnalysis(response);
                setConsentState(false);
                setAcceptedCategory(false);
                setAcceptedSkillIds([]);
                setManualMode(false);
                setPreparation((current) =>
                    current === null
                        ? current
                        : {
                              ...current,
                              status: 'ready',
                              message: null,
                          },
                );
            } catch (caught) {
                if (
                    isAbortError(caught) ||
                    generation !== generationRef.current
                ) {
                    return;
                }

                setAnalysis(null);
                setConsentState(false);

                if (caught instanceof RequestError) {
                    setError({
                        message: caught.message,
                        code: caught.code,
                        errors: caught.errors,
                    });
                } else {
                    setError({
                        message:
                            'No se pudo completar el análisis. No se guardó nada.',
                    });
                }
            } finally {
                if (generation === generationRef.current) {
                    setAnalyzing(false);
                }

                if (abortRef.current === controller) {
                    abortRef.current = null;
                    analyzingRef.current = false;
                }
            }
        },
        [abortRequest, consent, pages, preparation],
    );

    const setPageText = useCallback((page: number, text: string) => {
        setPages((current) =>
            current.map((item) =>
                item.page === page ? { ...item, text } : item,
            ),
        );
        setAnalysis(null);
        setConsentState(false);
        setAcceptedCategory(false);
        setAcceptedSkillIds([]);
        setManualMode(false);
    }, []);

    const setConsent = useCallback((nextConsent: boolean) => {
        setConsentState(nextConsent);
    }, []);

    const acceptCategory = useCallback((accepted: boolean) => {
        setAcceptedCategory(accepted);
    }, []);

    const acceptSkill = useCallback((id: number, accepted: boolean) => {
        setAcceptedSkillIds((current) =>
            accepted
                ? [...new Set([...current, id])]
                : current.filter((skillId) => skillId !== id),
        );
    }, []);

    const clearError = useCallback(() => {
        setError(null);
    }, []);

    const markFileInvalid = useCallback((message: string) => {
        setFileInvalid(true);
        setFileError(message);
    }, []);

    const discardProposal = useCallback(() => {
        abortRequest();
        preparingRef.current = false;
        analyzingRef.current = false;
        setAnalysis(null);
        setPreparation(null);
        setPages([]);
        setConsentState(false);
        setAcceptedCategory(false);
        setAcceptedSkillIds([]);
        setManualMode(false);
        setPreparing(false);
        setAnalyzing(false);
        setError(null);
    }, [abortRequest]);

    const enterManualMode = useCallback(() => {
        abortRequest();
        preparingRef.current = false;
        analyzingRef.current = false;
        setAnalysis(null);
        setPreparation(null);
        setPages([]);
        setConsentState(false);
        setAcceptedCategory(false);
        setAcceptedSkillIds([]);
        setManualMode(true);
        setPreparing(false);
        setAnalyzing(false);
        setError(null);
    }, [abortRequest]);

    const cancel = useCallback(() => {
        abortRequest();
        revokeObjectUrl();
        resetDerivedState();
        setFile(null);
        setObjectUrl(null);
        setFileError(null);
        setFileInvalid(false);
        setFileInputKey((key) => key + 1);
        setConfirmationId(createImportConfirmationId());
    }, [abortRequest, resetDerivedState, revokeObjectUrl]);

    const totalChars = pages.reduce(
        (total, page) => total + page.text.length,
        0,
    );
    const canAnalyze =
        preparation?.preparation_token !== null &&
        preparation?.preparation_token !== undefined &&
        preparation.status !== 'insufficient' &&
        pages.length > 0 &&
        pages.every((page) => page.text.length <= limits.max_page_chars) &&
        consent &&
        !preparing &&
        !analyzing &&
        totalChars <= limits.max_text_chars;

    return {
        file,
        fileInputKey,
        objectUrl,
        fileError,
        fileInvalid,
        preparation,
        pages,
        analysis,
        consent,
        preparing,
        analyzing,
        manualMode,
        error,
        confirmationId,
        acceptedCategory,
        acceptedSkillIds,
        totalChars,
        canAnalyze,
        selectFile,
        prepare,
        analyze,
        setPageText,
        setConsent,
        acceptCategory,
        acceptSkill,
        clearError,
        markFileInvalid,
        discardProposal,
        enterManualMode,
        cancel,
    };
}
