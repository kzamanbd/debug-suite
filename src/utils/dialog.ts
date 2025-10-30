import type { DialogOptions } from '@/types';

// Simple global dialog state
let dialogInstance: {
    show: (message: string, options?: DialogOptions) => Promise<boolean>;
} | null = null;

// Register dialog function from component
export const registerDialog = (showFn: (message: string, options?: DialogOptions) => Promise<boolean>) => {
    dialogInstance = { show: showFn };
};

// Unregister dialog function
export const unregisterDialog = () => {
    dialogInstance = null;
};

// Global dialog function
export const showDialog = (message: string, options?: DialogOptions): Promise<boolean> => {
    if (dialogInstance) {
        return dialogInstance.show(message, options);
    }
    // Fallback to native confirm
    return Promise.resolve(window.confirm(message));
};
