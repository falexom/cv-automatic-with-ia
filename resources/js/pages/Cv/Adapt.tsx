import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useRef, useState } from 'react';
import CvAdaptationOfferForm from '@/components/cv-adaptation/CvAdaptationOfferForm';
import CvAdaptationReview from '@/components/cv-adaptation/CvAdaptationReview';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout';
import {
    type CvAdaptationAnalysisResponse,
    type CvAdaptationPageProps,
    type CvAdaptationStrategy,
} from '@/lib/cv-adaptation';
import { postJson, RequestError } from '@/lib/http';

export default function Adapt({ data_notice, summary }: CvAdaptationPageProps) {
    const [offer, setOffer] = useState('');
    const [strategy, setStrategy] = useState<CvAdaptationStrategy>('auto');
    const [analysis, setAnalysis] =
        useState<CvAdaptationAnalysisResponse | null>(null);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const processingRef = useRef(false);

    const analyze = async (consent: boolean) => {
        if (processingRef.current) {
            return;
        }

        processingRef.current = true;
        setProcessing(true);
        setError(null);

        try {
            const response = await postJson<CvAdaptationAnalysisResponse>(
                '/cv/adapt/analyze',
                {
                    offer,
                    consent,
                    strategy,
                },
            );

            setAnalysis(response);
        } catch (caught) {
            setError(
                caught instanceof RequestError
                    ? caught.message
                    : 'No se pudo completar el análisis. No se envió ni guardó ningún cambio.',
            );
        } finally {
            processingRef.current = false;
            setProcessing(false);
        }
    };

    const cancelReview = () => {
        setAnalysis(null);
        setError(null);
    };

    return (
        <AppLayout title="Adaptar CV a una oferta">
            <Head title="Adaptar CV a una oferta" />

            <div className="mb-4 flex justify-end">
                <Button asChild variant="ghost" size="sm">
                    <Link href="/cv">
                        <ArrowLeft /> Volver al generador
                    </Link>
                </Button>
            </div>

            {analysis ? (
                <CvAdaptationReview
                    key={analysis.ref_nonce}
                    analysis={analysis}
                    dataNotice={data_notice}
                    onCancel={cancelReview}
                />
            ) : (
                <CvAdaptationOfferForm
                    offer={offer}
                    onOfferChange={setOffer}
                    strategy={strategy}
                    onStrategyChange={setStrategy}
                    dataNotice={data_notice}
                    summary={summary}
                    processing={processing}
                    error={error}
                    onAnalyze={analyze}
                />
            )}
        </AppLayout>
    );
}
