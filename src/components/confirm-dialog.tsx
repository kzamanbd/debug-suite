import type { DialogOptions, DialogState } from '@/types';
import { registerDialog, unregisterDialog } from '@/utils/dialog';
import React, { useCallback, useEffect, useState } from 'react';
import DialogModal from './base/dialog-modal';

// Confirm Dialog component (no context needed)
const ConfirmDialog: React.FC = () => {
    const [state, setState] = useState<DialogState>({
        open: false,
        message: '',
        options: {
            type: 'confirm'
        }
    });

    const openDialog = useCallback((message: string, options?: DialogOptions) => {
        return new Promise<boolean>((resolve, reject) => {
            setState((prev) => ({
                ...prev,
                open: true,
                title: options?.title,
                message,
                options: { ...options },
                resolve,
                reject
            }));
        });
    }, []);

    const handleClose = (confirmed: boolean) => {
        setState((prev) => {
            if (confirmed) {
                prev.resolve?.(true);
            } else {
                prev.resolve?.(false);
            }
            return { ...prev, open: false };
        });
    };

    // Register/unregister with global dialog system
    useEffect(() => {
        registerDialog(openDialog);
        return () => unregisterDialog();
    }, [openDialog]);

    return (
        <DialogModal
            open={state.open}
            title={state.title}
            message={state.message}
            onClose={handleClose}
            options={state.options}
        />
    );
};

export default ConfirmDialog;
