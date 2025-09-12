import type { DialogOptions } from '@/types';
import { showDialog } from '@/utils/dialog';

export const useConfirm = (defaultOptions?: DialogOptions) => {
    return (message: string, options?: DialogOptions) => {
        return showDialog(message, { type: 'confirm', ...defaultOptions, ...options });
    };
};
