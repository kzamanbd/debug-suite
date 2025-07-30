/**
 * Email Log - Main page component for viewing email logs.
 *
 * @since 1.0.0
 */
import { useCallback, useState } from '@wordpress/element';
import { EmailDetail, EmailLogControls, EmailLogSkeleton, EmailLogViewer } from './components';
import { useEmailLogActions, useEmailLogEntries } from './hooks';
import type { BulkAction, EmailLogEntry } from './types';

const EmailLog = () => {
    const {
        entries,
        loading,
        filters,
        updateFilters,
        paginationInfo,
        refetch,
        selectedItems,
        onSelectAll,
        onSelectItem,
        onPageChange,
        emailStats
    } = useEmailLogEntries();

    const [currentSelections, setCurrentSelections] = useState<number[]>([]);
    const [modalState, setModalState] = useState<{
        isOpen: boolean;
        entry: EmailLogEntry | null;
    }>({
        isOpen: false,
        entry: null
    });

    // Update selections when they change
    const handleSelectionsChange = useCallback((selections: number[]) => {
        setCurrentSelections(selections);
    }, []);

    const { processing, handleBulkAction, handleItemAction } = useEmailLogActions(
        refetch,
        handleSelectionsChange,
        setModalState
    );

    // Handle filter changes
    const handleFilterChange = (newFilters: Partial<typeof filters>) => {
        updateFilters(newFilters);
    };

    // Handle bulk actions
    const handleBulkActionSubmit = async (action: BulkAction) => {
        await handleBulkAction(action);
    };

    // Handle individual item actions
    const handleItemActionSubmit = async (action: 'view' | 'resend' | 'delete', entry: EmailLogEntry) => {
        await handleItemAction(action, entry);
    };

    // Handle page changes
    const handlePageChange = (page: number) => {
        onPageChange(page);
    };

    // Handle refresh
    const handleRefresh = () => {
        refetch();
    };

    // Handle modal close
    const handleModalClose = () => {
        setModalState({ isOpen: false, entry: null });
    };

    // Show skeleton while initial data loads
    if (loading && entries.length === 0) {
        return <EmailLogSkeleton />;
    }

    return (
        <div className="flex h-full flex-col">
            <EmailLogControls
                filters={filters}
                onFiltersChange={handleFilterChange}
                onRefresh={handleRefresh}
                onBulkAction={handleBulkActionSubmit}
                loading={loading || processing}
                selectedItems={currentSelections.length > 0 ? currentSelections : selectedItems}
                paginationInfo={paginationInfo}
                emailStats={emailStats}
            />

            <EmailLogViewer
                entries={entries}
                filters={filters}
                onFiltersChange={handleFilterChange}
                loading={loading}
                selectedItems={currentSelections.length > 0 ? currentSelections : selectedItems}
                onSelectAll={onSelectAll}
                onSelectItem={onSelectItem}
                onItemAction={handleItemActionSubmit}
                paginationInfo={paginationInfo}
                onPageChange={handlePageChange}
            />

            <EmailDetail isOpen={modalState.isOpen} onClose={handleModalClose} entry={modalState.entry} />
        </div>
    );
};

export default EmailLog;
