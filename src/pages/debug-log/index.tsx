/**
 * Debug Log Viewer - Main page component.
 *
 * @since 1.0.0
 */
import { useEffect, useState } from '@wordpress/element';
import { LogControls, LogViewer, RawFileViewer } from './components';
import FileLogsSkeleton from './components/logs-skeleton';
import { useLogActions, useLogEntries, useRawFileContent } from './hooks';
import type { LogFilters, ViewMode } from './types';

const FileLogs = () => {
    const {
        logs,
        loading: logsLoading,
        infiniteState,
        totalEntries,
        filters,
        updateFilters,
        loadMore,
        refetch: refetchLogs,
        selectedFile,
        onFileChange
    } = useLogEntries();

    const { clearLogs, clearing } = useLogActions();
    const {
        content: rawContent,
        loading: rawLoading,
        refetch: refetchRawContent
    } = useRawFileContent(selectedFile?.value);

    // Local state for view mode only
    const [viewMode, setViewMode] = useState<ViewMode>('raw');

    // Handle focus change to refetch logs when tab becomes visible
    useEffect(() => {
        const handleFocus = () => {
            if (viewMode === 'parsed') {
                void refetchLogs();
            } else {
                void refetchRawContent();
            }
        };
        window.addEventListener('focus', handleFocus);

        return () => {
            window.removeEventListener('focus', handleFocus);
        };
    }, [refetchLogs, refetchRawContent, viewMode]);

    // Handle filter changes - now uses client-side filtering
    const handleFilterChange = (newFilters: Partial<LogFilters>) => {
        updateFilters(newFilters);
    };

    // Handle refresh based on current view mode
    const handleRefresh = () => {
        if (viewMode === 'parsed') {
            void refetchLogs();
        } else {
            void refetchRawContent();
        }
    };

    // Handle view mode change
    const handleViewModeChange = (mode: ViewMode) => {
        setViewMode(mode);
        if (mode === 'parsed') {
            void refetchLogs();
        } else {
            void refetchRawContent();
        }
    };

    // Handle clearing logs with automatic refresh
    const handleClearLogs = async () => {
        try {
            await clearLogs(selectedFile?.value);
            // Refresh logs after clearing
            handleRefresh();
        } catch (error) {
            console.error('Error clearing logs:', error);
        }
    };

    // Show skeleton while initial data loads
    if (rawLoading && !rawContent) {
        return <FileLogsSkeleton />;
    }

    return (
        <div className="flex h-full flex-col">
            <LogControls
                filters={filters}
                viewMode={viewMode}
                selectedFile={selectedFile}
                clearing={clearing}
                rawContent={rawContent}
                loading={rawLoading || logsLoading}
                onFileChange={onFileChange}
                onViewModeChange={handleViewModeChange}
                onFiltersChange={handleFilterChange}
                totalEntries={totalEntries}
                onRefresh={handleRefresh}
                onClear={handleClearLogs}
            />

            {viewMode === 'parsed' ? (
                <LogViewer
                    logs={logs}
                    filters={filters}
                    onLoadMore={loadMore}
                    loading={logsLoading}
                    infiniteState={infiniteState}
                    onFiltersChange={handleFilterChange}
                />
            ) : (
                <RawFileViewer content={rawContent} loading={rawLoading} onRefresh={refetchRawContent} />
            )}
        </div>
    );
};

export default FileLogs;
