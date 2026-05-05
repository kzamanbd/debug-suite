/**
 * API Log - Main page component for viewing REST API logs.
 *
 * @since 1.2.0
 */
import { useCallback, useState } from '@wordpress/element';
import { ApiDetail, ApiLogControls, ApiLogSkeleton, ApiLogViewer } from './components';
import { useApiLogActions, useApiLogEntries } from './hooks';
import type { ApiLogEntry, BulkAction } from './types';

const ApiLog = () => {
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
        apiStats,
        filterOptions
    } = useApiLogEntries();

    const [currentSelections, setCurrentSelections] = useState<number[]>([]);
    const [modalState, setModalState] = useState<{
        isOpen: boolean;
        entry: ApiLogEntry | null;
        loading: boolean;
    }>({
        isOpen: false,
        entry: null,
        loading: false
    });

    const handleSelectionsChange = useCallback((selections: number[]) => {
        setCurrentSelections(selections);
    }, []);

    const { processing, handleBulkAction, handleItemAction } = useApiLogActions(
        refetch,
        handleSelectionsChange,
        setModalState
    );

    const handleFilterChange = (newFilters: Partial<typeof filters>) => {
        updateFilters(newFilters);
    };

    const handleBulkActionSubmit = async (action: BulkAction) => {
        await handleBulkAction(action);
    };

    const handleItemActionSubmit = async (action: 'view' | 'delete', entry: ApiLogEntry) => {
        await handleItemAction(action, entry);
    };

    const handlePageChange = (page: number) => {
        onPageChange(page);
    };

    const handleRefresh = () => {
        refetch();
    };

    const handleModalClose = () => {
        setModalState({ isOpen: false, entry: null, loading: false });
    };

    if (loading && entries.length === 0) {
        return <ApiLogSkeleton />;
    }

    return (
        <div className="flex h-full flex-col">
            <ApiLogControls
                filters={filters}
                onFiltersChange={handleFilterChange}
                onRefresh={handleRefresh}
                onBulkAction={handleBulkActionSubmit}
                loading={loading || processing}
                selectedItems={currentSelections.length > 0 ? currentSelections : selectedItems}
                paginationInfo={paginationInfo}
                apiStats={apiStats}
                filterOptions={filterOptions}
            />

            <ApiLogViewer
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

            <ApiDetail
                isOpen={modalState.isOpen}
                onClose={handleModalClose}
                entry={modalState.entry}
                loading={modalState.loading}
            />
        </div>
    );
};

export default ApiLog;
