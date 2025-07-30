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
    onSelectionsChange: (selections: number[]) => void
): UseEmailLogActionsResult => {
    const [processing, setProcessing] = useState(false);
    const { deleteEmails, resendEmail, getEmail } = useEmailLogAPI();

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
            setProcessing(true);

            try {
                switch (action) {
                    case 'view': {
                        // Get full email details and open modal
                        const emailDetails = await getEmail(entry.id);
                        console.warn('Email details:', emailDetails);
                        // In real implementation, this would open a modal
                        break;
                    }
                    case 'resend':
                        await resendEmail(entry.id);
                        break;
                    case 'delete':
                        await deleteEmails([entry.id]);
                        break;
                }

                if (action !== 'view') {
                    onRefresh();
                }
            } catch (error) {
                console.error('Item action failed:', error);
                // Handle error (show toast, etc.)
                throw error;
            } finally {
                setProcessing(false);
            }
        },
        [deleteEmails, resendEmail, getEmail, onRefresh]
    );

    return {
        processing,
        handleBulkAction,
        handleItemAction
    };
};

export default useEmailLogActions;
