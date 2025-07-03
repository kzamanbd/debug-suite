import { DialogContext } from '@/components/dialog-provider';
import type { DialogOptions } from '@/types';
import { useContext } from 'react';

export const useConfirm = (defaultOptions?: DialogOptions) => {
    const confirmDialog = useContext(DialogContext);
    return (message: string, options?: DialogOptions) => confirmDialog(message, { ...defaultOptions, ...options });
};
