import { render } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { Toaster } from '@/components/ui/sonner';

describe('Toaster', () => {
    it('renders outside the Inertia page context without errors', () => {
        expect(() => render(<Toaster />)).not.toThrow();
    });
});
