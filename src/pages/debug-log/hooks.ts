/**
 * API service hooks for Debug Log.
 *
 * @since 1.0.0
 */
import { useDebounce } from '@/utils/use-debounce';
import apiFetch from '@wordpress/api-fetch';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import type {
    InfiniteScrollState,
    LogEntry,
    LogFile,
    LogFilters,
    LogResponse,
    LogStats,
    RawFileContent
} from './types';

export const useLogFiles = () => {
    const [logFiles, setLogFiles] = useState<LogFile[]>([]);
    const [selectedFile, setSelectedFile] = useState<string>('');
    const [loading, setLoading] = useState(true);

    const fetchLogFiles = async () => {
        try {
            setLoading(true);
            const response = await apiFetch<{ files: LogFile[] }>({
                path: '/debug-suite/v1/logs/supported-files'
            });
            setLogFiles(response.files);
            setSelectedFile(response.files[0].path);
        } catch (error) {
            console.error('Error fetching log files:', error);
        } finally {
            setLoading(false);
        }
    };

    // Fetch log files on component mount
    useEffect(() => {
        void fetchLogFiles();
    }, []);

    return { logFiles, selectedFile, setSelectedFile, loading, refetch: fetchLogFiles };
};

/**
 * Client-side filtering function for log entries
 */
const filterLogEntries = (logs: LogEntry[], filters: LogFilters): LogEntry[] => {
    let filtered = [...logs];

    // Filter by level
    if (filters.level && filters.level !== 'all') {
        filtered = filtered.filter((log) => log.level === filters.level);
    }

    // Filter by search term
    if (filters.search.trim()) {
        const searchTerm = filters.search.toLowerCase().trim();
        filtered = filtered.filter(
            (log) =>
                log.message.toLowerCase().includes(searchTerm) ||
                log.file?.toLowerCase().includes(searchTerm) ||
                log.level.toLowerCase().includes(searchTerm)
        );
    }

    // Sort the entries
    filtered.sort((a, b) => {
        let aValue: string | number, bValue: string | number;
        const levelOrder = { critical: 6, error: 5, warning: 4, notice: 3, info: 2, debug: 1 };
        switch (filters.sortBy) {
            case 'level':
                // Define level hierarchy for sorting

                aValue = levelOrder[a.level] || 0;
                bValue = levelOrder[b.level] || 0;
                break;
            case 'message':
                aValue = a.message.toLowerCase();
                bValue = b.message.toLowerCase();
                break;
            case 'file':
                aValue = (a.file || '').toLowerCase();
                bValue = (b.file || '').toLowerCase();
                break;
            case 'timestamp':
            default:
                aValue = new Date(a.timestamp).getTime();
                bValue = new Date(b.timestamp).getTime();
                break;
        }

        if (filters.sortOrder === 'asc') {
            return aValue < bValue ? -1 : aValue > bValue ? 1 : 0;
        } else {
            return aValue > bValue ? -1 : aValue < bValue ? 1 : 0;
        }
    });

    return filtered;
};

export const useLogEntries = () => {
    const [allLogs, setAllLogs] = useState<LogEntry[]>([]);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState<LogFilters>({
        level: '',
        search: '',
        sortBy: 'timestamp',
        sortOrder: 'desc',
        perPage: 100
    });

    // Debounce search input to improve performance
    const debouncedSearch = useDebounce(filters.search, 300);

    // Fetch all logs without any server-side filtering
    const fetchAllLogs = useCallback(async () => {
        try {
            setLoading(true);

            // Fetch all logs with a high limit to get everything
            const apiPath = addQueryArgs('/debug-suite/v1/logs', {
                per_page: 10000,
                page: 1
            });

            const response = await apiFetch<LogResponse>({
                path: apiPath
            });

            setAllLogs(response.entries);
        } catch (error) {
            console.error('Error fetching logs:', error);
            setAllLogs([]);
        } finally {
            setLoading(false);
        }
    }, []);

    // Apply client-side filtering and pagination with debounced search
    const filteredLogs = useMemo(() => {
        const filtersWithDebouncedSearch = {
            ...filters,
            search: debouncedSearch
        };
        return filterLogEntries(allLogs, filtersWithDebouncedSearch);
    }, [allLogs, filters, debouncedSearch]);

    // Paginated logs for display
    const paginatedLogs = useMemo(() => {
        const startIndex = 0;
        const endIndex = filters.perPage;
        return filteredLogs.slice(startIndex, endIndex);
    }, [filteredLogs, filters.perPage]);

    // Infinite scroll state for compatibility
    const [infiniteState, setInfiniteState] = useState<InfiniteScrollState>({
        page: 1,
        hasMore: false,
        isLoadingMore: false
    });

    // Update infinite scroll state based on pagination
    useEffect(() => {
        const hasMore = filteredLogs.length > filters.perPage;
        setInfiniteState((prev) => ({
            ...prev,
            hasMore,
            isLoadingMore: false
        }));
    }, [filteredLogs.length, filters.perPage]);

    // Load more entries (increase perPage)
    const loadMore = useCallback(() => {
        if (filteredLogs.length > filters.perPage) {
            setFilters((prev) => ({
                ...prev,
                perPage: prev.perPage + 100
            }));
        }
    }, [filteredLogs.length, filters.perPage]);

    // Update filters
    const updateFilters = useCallback((newFilters: Partial<LogFilters>) => {
        setFilters((prev) => ({
            ...prev,
            ...newFilters,
            // Reset pagination when filters change (except perPage)
            ...(Object.keys(newFilters).some((key) => key !== 'perPage') ? { perPage: 100 } : {})
        }));
    }, []);

    // Refetch all logs
    const refetch = useCallback(() => {
        void fetchAllLogs();
    }, [fetchAllLogs]);

    // Fetch all logs on component mount
    useEffect(() => {
        void fetchAllLogs();
    }, [fetchAllLogs]);

    // Handle visibility change to refetch logs when tab becomes visible
    useEffect(() => {
        const handleVisibilityChange = () => {
            if (document.visibilityState === 'visible') {
                refetch();
            }
        };

        document.addEventListener('visibilitychange', handleVisibilityChange);

        return () => {
            document.removeEventListener('visibilitychange', handleVisibilityChange);
        };
    }, [refetch]);

    return {
        logs: paginatedLogs,
        allLogs: filteredLogs,
        loading,
        infiniteState,
        totalEntries: filteredLogs.length,
        filters,
        updateFilters,
        loadMore,
        refetch
    };
};

export const useLogStats = () => {
    const [stats, setStats] = useState<LogStats | null>(null);
    const [loading, setLoading] = useState(true);

    const fetchStats = async () => {
        try {
            setLoading(true);
            const response = await apiFetch<LogStats>({
                path: '/debug-suite/v1/logs/stats'
            });
            setStats(response);
        } catch (error) {
            console.error('Error fetching stats:', error);
        } finally {
            setLoading(false);
        }
    };

    // Fetch stats on component mount
    useEffect(() => {
        void fetchStats();
    }, []);

    return { stats, loading, refetch: fetchStats };
};

export const useLogActions = () => {
    const [clearing, setClearing] = useState(false);

    const clearLogs = async () => {
        try {
            setClearing(true);
            await apiFetch({
                path: '/debug-suite/v1/logs/clear',
                method: 'DELETE'
            });
        } catch (error) {
            console.error('Error clearing logs:', error);
            throw error;
        } finally {
            setClearing(false);
        }
    };

    const exportLogs = async (format: 'json' | 'csv' | 'txt') => {
        try {
            const apiPath = addQueryArgs('/debug-suite/v1/logs/export', {
                format,
                limit: 1000
            });

            const response = await apiFetch<{
                data: string;
                filename: string;
                format: string;
            }>({
                path: apiPath
            });

            // Create download
            const blob = new Blob([response.data], {
                type: format === 'json' ? 'application/json' : 'text/plain'
            });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = response.filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        } catch (error) {
            console.error('Error exporting logs:', error);
            throw error;
        }
    };

    return { clearLogs, exportLogs, clearing };
};

export const useRawFileContent = (filePath?: string) => {
    const [content, setContent] = useState<RawFileContent | null>(null);
    const [loading, setLoading] = useState(false);

    const fetchRawContent = async (path?: string) => {
        if (!path) return;

        try {
            setLoading(true);
            const apiPath = addQueryArgs('/debug-suite/v1/logs/raw', {
                file: path
            });

            const response = await apiFetch<RawFileContent>({
                path: apiPath
            });

            setContent(response);
        } catch (error) {
            console.error('Error fetching raw file content:', error);
            setContent(null);
        } finally {
            setLoading(false);
        }
    };

    return { content, loading, refetch: () => fetchRawContent(filePath) };
};
