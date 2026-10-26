import CvPreview from '@/components/CvPreview';
import type { CvPreviewData } from '@/lib/cv-preview';

type PrintProps = {
    preview: CvPreviewData;
};

export default function Print({ preview }: PrintProps) {
    return <CvPreview preview={preview} />;
}
