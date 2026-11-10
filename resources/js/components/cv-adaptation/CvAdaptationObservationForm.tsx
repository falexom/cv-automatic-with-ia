import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    OBSERVATION_STATUS_LABELS,
    OBSERVATION_TYPE_LABELS,
    type CvAdaptationObservation,
    type CvAdaptationObservationStatus,
} from '@/lib/cv-adaptation';

export default function CvAdaptationObservationForm({
    profileId,
    adaptationId,
    observation,
    linkedTitle,
    linkedDetail,
}: {
    profileId: number;
    adaptationId: number;
    observation: CvAdaptationObservation;
    linkedTitle: string;
    linkedDetail?: string | null;
}) {
    const [status, setStatus] = useState<CvAdaptationObservationStatus>(
        observation.status,
    );
    const [note, setNote] = useState(observation.note ?? '');
    const [processing, setProcessing] = useState(false);

    const save = (nextNote: string = note) => {
        if (processing) {
            return;
        }

        setProcessing(true);

        router.patch(
            `/profiles/${profileId}/adaptations/${adaptationId}/observations/${observation.id}`,
            { status, note: nextNote },
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        save();
    };

    const clearNote = () => {
        setNote('');
        save('');
    };

    return (
        <div className="space-y-3 rounded-md border p-3">
            <div className="flex flex-wrap items-center gap-2">
                <Badge variant="secondary">
                    {OBSERVATION_TYPE_LABELS[observation.type]}
                </Badge>
                <span className="font-medium break-words">{linkedTitle}</span>
                <Badge variant="outline">
                    {OBSERVATION_STATUS_LABELS[observation.status]}
                </Badge>
            </div>

            {linkedDetail ? (
                <p className="text-sm break-words whitespace-pre-line text-muted-foreground">
                    {linkedDetail}
                </p>
            ) : null}

            <form onSubmit={submit} className="space-y-3">
                <div className="max-w-xs space-y-1.5">
                    <Label htmlFor={`observation-status-${observation.id}`}>
                        Estado
                    </Label>
                    <select
                        id={`observation-status-${observation.id}`}
                        className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                        value={status}
                        onChange={(event) =>
                            setStatus(
                                event.target
                                    .value as CvAdaptationObservationStatus,
                            )
                        }
                        disabled={processing}
                    >
                        {(
                            Object.keys(
                                OBSERVATION_STATUS_LABELS,
                            ) as CvAdaptationObservationStatus[]
                        ).map((value) => (
                            <option key={value} value={value}>
                                {OBSERVATION_STATUS_LABELS[value]}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor={`observation-note-${observation.id}`}>
                        Nota opcional
                    </Label>
                    <textarea
                        id={`observation-note-${observation.id}`}
                        className="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                        value={note}
                        onChange={(event) => setNote(event.target.value)}
                        maxLength={2000}
                        placeholder="Agregá una nota para recordar el seguimiento."
                        disabled={processing}
                    />
                    <p className="text-xs text-muted-foreground">
                        La nota es independiente de la respuesta original y de
                        la cobertura evaluada.
                    </p>
                </div>

                <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    {note.trim() !== '' ? (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={clearNote}
                            disabled={processing}
                        >
                            Borrar nota
                        </Button>
                    ) : null}
                    <Button type="submit" disabled={processing}>
                        Guardar observación
                    </Button>
                </div>
            </form>
        </div>
    );
}
