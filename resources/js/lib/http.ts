export class RequestError extends Error {
    status: number;
    code?: string;
    errors?: Record<string, string[]>;

    constructor(
        message: string,
        status: number,
        code?: string,
        errors?: Record<string, string[]>,
    ) {
        super(message);
        this.name = 'RequestError';
        this.status = status;
        this.code = code;
        this.errors = errors;
    }
}

function csrfToken(): string {
    return (
        document
            .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

async function requestJson<T>(url: string, init: RequestInit): Promise<T> {
    const { headers: initHeaders, ...rest } = init;
    const headers = new Headers(initHeaders);

    headers.set('Accept', 'application/json');
    headers.set('X-CSRF-TOKEN', csrfToken());
    headers.set('X-Requested-With', 'XMLHttpRequest');

    const response = await fetch(url, {
        credentials: 'same-origin',
        ...rest,
        headers,
    });

    const payload = (await response.json().catch(() => ({}))) as {
        message?: string;
        code?: string;
        errors?: Record<string, string[]>;
    };

    if (!response.ok) {
        const firstError = payload.errors
            ? Object.values(payload.errors).flat()[0]
            : undefined;

        throw new RequestError(
            firstError ??
                payload.message ??
                (response.status === 419
                    ? 'La sesión expiró. Recargá la página y volvé a intentar.'
                    : 'No se pudo completar la operación.'),
            response.status,
            payload.code,
            payload.errors,
        );
    }

    return payload as T;
}

export async function postJson<T>(
    url: string,
    body: unknown,
    signal?: AbortSignal,
): Promise<T> {
    return requestJson<T>(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(body),
        signal,
    });
}

export async function postForm<T>(
    url: string,
    body: FormData,
    signal?: AbortSignal,
): Promise<T> {
    // The browser sets the multipart boundary; do not force Content-Type.
    return requestJson<T>(url, {
        method: 'POST',
        body,
        signal,
    });
}
