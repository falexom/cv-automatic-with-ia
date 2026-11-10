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

export async function postJson<T>(url: string, body: unknown): Promise<T> {
    const token =
        document
            .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? '';

    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body),
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
