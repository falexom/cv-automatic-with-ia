import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

export function useFlashToast(): void {
    const { flash } = usePage().props;

    useEffect(() => {
        const data = flash?.toast;

        if (!data) {
            return;
        }

        toast[data.type](data.message);
    }, [flash]);
}
