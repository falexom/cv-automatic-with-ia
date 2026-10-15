import { Link } from '@inertiajs/react';
import { Inbox } from 'lucide-react';
import { Button } from '@/components/ui/button';

type EmptyStateProps = {
    title: string;
    description: string;
    actionLabel?: string;
    actionHref?: string;
};

export default function EmptyState({
    title,
    description,
    actionLabel,
    actionHref,
}: EmptyStateProps) {
    return (
        <div
            data-testid="empty-state"
            className="flex flex-col items-center gap-3 rounded-lg border border-dashed bg-background p-10 text-center"
        >
            <Inbox
                aria-hidden="true"
                className="size-8 text-muted-foreground"
            />
            <div className="space-y-1">
                <h2 className="text-base font-medium">{title}</h2>
                <p className="text-sm text-muted-foreground">{description}</p>
            </div>
            {actionLabel && actionHref ? (
                <Button asChild size="sm">
                    <Link href={actionHref}>{actionLabel}</Link>
                </Button>
            ) : null}
        </div>
    );
}
