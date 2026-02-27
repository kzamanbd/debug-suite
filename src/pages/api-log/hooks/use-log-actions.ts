/**
 * useApiLogActions - Hook for managing API log actions.
 *
 * @since 1.2.0
 */
import { useCallback, useState } from '@wordpress/element';
import type { ApiLogEntry, BulkAction } from '../types';
import useApiLogAPI from './use-api';

interface UseApiLogActionsResult {
    processing: boolean;
    handleBulkAction: (action: BulkAction) => Promise<void>;
    handleItemAction: (action: 'view' | 'delete', entry: ApiLogEntry) => Promise<void>;
}

const useApiLogActions = (
    onRefresh: () => void,
    onSelectionsChange: (selections: number[]) => void,
    setModalState?: (state: { isOpen: boolean; entry: ApiLogEntry | null; loading: boolean }) => void
): UseApiLogActionsResult => {
    const [processing, setProcessing] = useState(false);
    const { deleteLogs, fetchApiLogDetail } = useApiLogAPI();

    const handleBulkAction = useCallback(
        async (action: BulkAction) => {
            setProcessing(true);

            try {
                switch (action.action) {
                    case 'delete':
                        await deleteLogs(action.selected_ids);
                        break;
                }

                onSelectionsChange([]);
                onRefresh();
            } catch (error) {
                console.error('Bulk action failed:', error);
                throw error;
            } finally {
                setProcessing(false);
            }
        },
        [deleteLogs, onRefresh, onSelectionsChange]
    );

    const handleItemAction = useCallback(
        async (action: 'view' | 'delete', entry: ApiLogEntry) => {
            try {
                switch (action) {
                    case 'view': {
                        if (setModalState) {
                            setModalState({ isOpen: true, entry: null, loading: true });
                            const detail = await fetchApiLogDetail(entry.id);
                            setModalState({ isOpen: true, entry: detail, loading: false });
                        }
                        break;
                    }
                    case 'delete':
                        setProcessing(true);
                        await deleteLogs([entry.id]);
                        onRefresh();
                        break;
                }
            } catch (error) {
                console.error('Item action failed:', error);
                if (action === 'view' && setModalState) {
                    setModalState({ isOpen: false, entry: null, loading: false });
                }
                throw error;
            } finally {
                if (action !== 'view') {
                    setProcessing(false);
                }
            }
        },
        [deleteLogs, fetchApiLogDetail, onRefresh, setModalState]
    );

    return {
        processing,
        handleBulkAction,
        handleItemAction
    };
};

export default useApiLogActions;
