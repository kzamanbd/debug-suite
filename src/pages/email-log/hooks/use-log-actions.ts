/**
 * useEmailLogActions - Hook for managing email log actions.
 *
 */
import { useCallback, useState } from '@wordpress/element';
import type { BulkAction, EmailLogEntry } from '../types';
import useEmailLogAPI from './use-api';

interface UseEmailLogActionsResult {
    processing: boolean;
    handleBulkAction: (action: BulkAction) => Promise<void>;
    handleItemAction: (action: 'view' | 'resend' | 'delete', entry: EmailLogEntry) => Promise<void>;
}

const useEmailLogActions = (
    onRefresh: () => void,
    onSelectionsChange: (selections: number[]) => void,
    setModalState?: (state: { isOpen: boolean; entry: EmailLogEntry | null }) => void
): UseEmailLogActionsResult => {
    const [processing, setProcessing] = useState(false);
    const { deleteEmails, resendEmail } = useEmailLogAPI();

    const handleBulkAction = useCallback(
        async (action: BulkAction) => {
            setProcessing(true);

            try {
                switch (action.action) {
                    case 'delete':
                        await deleteEmails(action.selected_ids);
                        break;
                    case 'resend':
                        // For bulk resend, we'd need to implement a bulk resend endpoint
                        // For now, resend emails one by one
                        for (const id of action.selected_ids) {
                            await resendEmail(id);
                        }
                        break;
                    case 'mark_read':
                        // This would need to be implemented in the API
                        console.warn('Mark as read not implemented yet');
                        break;
                }

                // Clear selections and refresh data
                onSelectionsChange([]);
                onRefresh();
            } catch (error) {
                console.error('Bulk action failed:', error);
                // Handle error (show toast, etc.)
                throw error;
            } finally {
                setProcessing(false);
            }
        },
        [deleteEmails, resendEmail, onRefresh, onSelectionsChange]
    );

    const handleItemAction = useCallback(
        async (action: 'view' | 'resend' | 'delete', entry: EmailLogEntry) => {
            try {
                switch (action) {
                    case 'view': {
                        if (setModalState) {
                            // Open modal directly with the existing entry data
                            // No need to fetch again as we already have all the data
                            setModalState({ isOpen: true, entry });
                        } else {
                            // Fallback if no modal state setter
                            console.warn('Email details:', entry);
                        }
                        break;
                    }
                    case 'resend':
                        setProcessing(true);
                        await resendEmail(entry.id);
                        onRefresh();
                        break;
                    case 'delete':
                        setProcessing(true);
                        await deleteEmails([entry.id]);
                        onRefresh();
                        break;
                }
            } catch (error) {
                console.error('Item action failed:', error);
                // Handle error (show toast, etc.)
                throw error;
            } finally {
                if (action !== 'view') {
                    setProcessing(false);
                }
            }
        },
        [deleteEmails, resendEmail, onRefresh, setModalState]
    );
    return {
        processing,
        handleBulkAction,
        handleItemAction
    };
};

export default useEmailLogActions;
