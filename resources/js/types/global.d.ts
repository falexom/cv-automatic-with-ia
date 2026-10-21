import type { VerificationResult } from '@/components/documents/VerificationReport';
import type { FlashToast } from '@/types/ui';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            flash: {
                duplicate_warning?: {
                    existing_id: number;
                    name: string;
                    institution: string | null;
                } | null;
                toast?: FlashToast | null;
                verification?: VerificationResult | null;
            };
            [key: string]: unknown;
        };
    }
}
