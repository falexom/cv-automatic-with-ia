import '@testing-library/jest-dom/vitest';
import { cleanup } from '@testing-library/react';
import { afterEach } from 'vitest';

// jsdom no implementa ResizeObserver y los componentes Radix lo usan al montar.
globalThis.ResizeObserver = class {
    observe() {}
    unobserve() {}
    disconnect() {}
};

afterEach(() => {
    cleanup();
});
