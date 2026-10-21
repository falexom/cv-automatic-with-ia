import { AlertTriangle, ShieldCheck } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

export type VerificationProblem = {
    id: number;
    display_name: string;
    reason: 'missing' | 'altered';
};

export type VerificationResult = {
    checked: number;
    ok: number;
    unavailable: number;
    problems: VerificationProblem[];
};

const REASON_LABELS: Record<VerificationProblem['reason'], string> = {
    missing: 'Archivo ausente',
    altered: 'Archivo alterado',
};

export default function VerificationReport({
    result,
}: {
    result: VerificationResult | null;
}) {
    if (!result) {
        return null;
    }

    const hasProblems = result.unavailable > 0;

    return (
        <Alert variant={hasProblems ? 'destructive' : 'default'}>
            {hasProblems ? <AlertTriangle /> : <ShieldCheck />}
            <AlertTitle>Verificación de integridad</AlertTitle>
            <AlertDescription>
                <p>
                    {result.checked} documento(s) verificados: {result.ok}{' '}
                    correctos, {result.unavailable} con problemas.
                </p>
                {result.problems.length > 0 ? (
                    <ul className="mt-2 list-disc space-y-1 pl-4">
                        {result.problems.map((problem) => (
                            <li key={problem.id}>
                                {problem.display_name} —{' '}
                                {REASON_LABELS[problem.reason]}
                            </li>
                        ))}
                    </ul>
                ) : null}
            </AlertDescription>
        </Alert>
    );
}
