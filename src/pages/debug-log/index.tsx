/**
 * Debug Log Viewer - Main page component.
 *
 * @since 1.0.0
 */
import FileLogsSkeleton from '@/pages/debug-log/components/logs-skeleton';
import { useState } from '@wordpress/element';
import { LogControls, LogViewer, RawFileViewer } from './components';
import { useLogActions, useLogEntries, useLogFiles, useRawFileContent } from './hooks';
import type { LogFilters, ViewMode } from './types';

const FileLogs = () => {
    // Custom hooks for data management
    const { logFiles, selectedFile, setSelectedFile, loading: filesLoading } = useLogFiles();
    const {
        logs,
        loading: logsLoading,
        infiniteState,
        totalEntries,
        fetchLogs,
        loadMore,
        refetch: refetchLogs
    } = useLogEntries();
    const { clearLogs, exportLogs, clearing } = useLogActions();
    const { content: rawContent, loading: rawLoading, refetch: refetchRawContent } = useRawFileContent(selectedFile);

    // Local state for view mode and filters
    const [viewMode, setViewMode] = useState<ViewMode>('parsed');
    const [filters, setFilters] = useState<LogFilters>({
        level: '',
        search: '',
        sortBy: 'timestamp',
        sortOrder: 'desc',
        perPage: 100
    });

    // Show skeleton while initial data loads
    if (filesLoading) {
        return <FileLogsSkeleton />;
    }

    // Handle filter changes
    const handleFilterChange = (newFilters: Partial<LogFilters>) => {
        const updatedFilters = { ...filters, ...newFilters };
        setFilters(updatedFilters);

        // Trigger API call with updated filters
        fetchLogs({
            per_page: updatedFilters.perPage,
            level_filter: updatedFilters.level,
            search: updatedFilters.search,
            sort_by: updatedFilters.sortBy,
            sort_order: updatedFilters.sortOrder
        });
    };

    // Handle refresh based on current view mode
    const handleRefresh = () => {
        if (viewMode === 'parsed') {
            refetchLogs();
        } else {
            refetchRawContent();
        }
    };

    // Handle view mode change
    const handleViewModeChange = (mode: ViewMode) => {
        setViewMode(mode);
        if (mode !== 'parsed') {
            // If switching to raw view, fetch the raw content for the selected file
            if (!rawContent) {
                refetchRawContent();
            }
        }
    };

    // Handle export with format selection
    const handleExport = (format: 'json' | 'csv' | 'txt') => {
        exportLogs(format);
    };

    return (
        <div className="flex h-full flex-col">
            <LogControls
                logFiles={logFiles}
                selectedFile={selectedFile}
                onFileChange={setSelectedFile}
                viewMode={viewMode}
                onViewModeChange={handleViewModeChange}
                filters={filters}
                onFiltersChange={handleFilterChange}
                totalEntries={totalEntries}
                onRefresh={handleRefresh}
                onClear={clearLogs}
                onExport={handleExport}
                clearing={clearing}
                filesLoading={filesLoading}
            />

            {viewMode === 'parsed' ? (
                <LogViewer logs={logs} loading={logsLoading} infiniteState={infiniteState} onLoadMore={loadMore} />
            ) : (
                <RawFileViewer content={rawContent} loading={rawLoading} onRefresh={refetchRawContent} />
            )}
        </div>
    );
};

export default FileLogs;
