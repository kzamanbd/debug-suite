/**
 * API service hooks for Debug Log.
 *
 * @since 1.0.0
 */
import type { Option } from '@/components/base/select';
import { useDebounce } from '@/hooks/use-debounce';
import apiFetch from '@wordpress/api-fetch';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import type { InfiniteScrollState, LogEntry, LogFile, LogFilters, LogResponse, RawFileContent } from './types';

export const useLogFiles = () => {
    const [logFiles, setLogFiles] = useState<LogFile[]>([]);
    const [loading, setLoading] = useState(true);

    const fetchLogFiles = async () => {
        try {
            setLoading(true);
            const response = await apiFetch<{ files: LogFile[] }>({
                path: '/debug-suite/v1/logs/supported-files'
            });
            setLogFiles(response.files);
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

    return { logFiles, loading, refetch: fetchLogFiles };
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
                log.file_path?.toLowerCase().includes(searchTerm) ||
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
                aValue = (a.file_path || '').toLowerCase();
                bValue = (b.file_path || '').toLowerCase();
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
    const [selectedFile, setSelectedFile] = useState<string>('');
    const [filters, setFilters] = useState<LogFilters>({
        level: '',
        search: '',
        sortBy: 'timestamp',
        sortOrder: 'desc',
        perPage: 100
    });

    const onFileChange = (file: Option | null) => {
        if (!file) {
            return;
        }
        setSelectedFile(file.value);
    };

    // Debounce search input to improve performance
    const debouncedSearch = useDebounce(filters.search, 300);

    // Fetch all logs without any server-side filtering
    const fetchAllLogs = useCallback(async () => {
        try {
            setLoading(true);

            // Fetch all logs with a high limit to get everything
            const apiPath = addQueryArgs('/debug-suite/v1/logs', {
                per_page: filters.perPage,
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
    }, [filters.perPage]);

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

    return {
        logs: paginatedLogs,
        allLogs: filteredLogs,
        loading,
        infiniteState,
        totalEntries: filteredLogs.length,
        filters,
        updateFilters,
        loadMore,
        refetch,
        selectedFile,
        onFileChange
    };
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

    return { clearLogs, clearing };
};

export const useRawFileContent = (filePath?: string) => {
    const [content, setContent] = useState<RawFileContent | null>(null);
    const [loading, setLoading] = useState(false);

    const fetchRawContent = useCallback(async () => {
        if (!filePath) return;

        try {
            setLoading(true);
            const apiPath = addQueryArgs('/debug-suite/v1/logs/raw', {
                file: filePath
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
    }, [filePath]);

    useEffect(() => {
        void fetchRawContent();
    }, [filePath, fetchRawContent]);

    return { content, loading, refetch: fetchRawContent };
};
