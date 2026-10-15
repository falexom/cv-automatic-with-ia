import type { ReactNode } from 'react';

export type AppLayoutProps = {
    children: ReactNode;
    title?: string;
};

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};
