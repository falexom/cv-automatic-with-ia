import { cn } from '@/lib/utils';
import type { CvPhotoData } from '@/lib/cv-preview';

type CvPhotoFrameProps = {
    photo: CvPhotoData;
    className?: string;
};

/**
 * Shared 1:1 photo frame. The same crop values drive the preview, the
 * print view and the PDF export.
 */
export default function CvPhotoFrame({ photo, className }: CvPhotoFrameProps) {
    const origin = `${photo.focus_x}% ${photo.focus_y}%`;

    return (
        <div
            data-testid="cv-photo-frame"
            className={cn(
                'h-[30mm] w-[30mm] shrink-0 overflow-hidden rounded-sm border bg-muted',
                className,
            )}
        >
            <img
                src={photo.data_uri}
                alt="Fotografía profesional"
                className="h-full w-full object-cover"
                style={{
                    objectPosition: origin,
                    transform: `scale(${photo.zoom / 100})`,
                    transformOrigin: origin,
                }}
            />
        </div>
    );
}
