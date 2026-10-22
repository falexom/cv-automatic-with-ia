import { renderHook } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { useCurrentUrl } from '../use-current-url';

const page = vi.hoisted(() => ({ url: '/profile' }));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ url: page.url }),
}));

describe('useCurrentUrl', () => {
    it('keeps /profile exact and does not treat /profiles as its child', () => {
        page.url = '/profile';

        const { result } = renderHook(() => useCurrentUrl());

        expect(result.current.isCurrentUrl('/profile')).toBe(true);
        expect(result.current.isCurrentOrParentUrl('/profile')).toBe(true);
        expect(result.current.isCurrentUrl('/profiles')).toBe(false);
        expect(result.current.isCurrentOrParentUrl('/profiles')).toBe(false);
    });

    it('treats /profiles as parent of its own subpaths only', () => {
        page.url = '/profiles/1/edit';

        const { result } = renderHook(() => useCurrentUrl());

        expect(result.current.isCurrentOrParentUrl('/profiles')).toBe(true);
        expect(result.current.isCurrentOrParentUrl('/profiles/1/edit')).toBe(
            true,
        );
        expect(result.current.isCurrentOrParentUrl('/profile')).toBe(false);
    });

    it('treats /profiles as parent of /profiles/create', () => {
        page.url = '/profiles/create';

        const { result } = renderHook(() => useCurrentUrl());

        expect(result.current.isCurrentOrParentUrl('/profiles')).toBe(true);
    });

    it('keeps existing section subpaths working', () => {
        page.url = '/experiences/1/edit';

        const { result } = renderHook(() => useCurrentUrl());

        expect(result.current.isCurrentOrParentUrl('/experiences')).toBe(true);
        expect(result.current.isCurrentOrParentUrl('/education')).toBe(false);
    });

    it('compares absolute URLs by pathname', () => {
        page.url = '/profiles/2/edit';

        const { result } = renderHook(() => useCurrentUrl());

        expect(
            result.current.isCurrentOrParentUrl('http://localhost/profiles'),
        ).toBe(true);
    });
});
