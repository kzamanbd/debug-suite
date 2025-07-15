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
import type { InfiniteScrollState, LogEntry, LogFilters, LogResponse, RawFileContent } from './types';

const defaultPerPage = 50; // Default items per page

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

const { logs } = window.debugSuite;
const defaultLog: Option = {
    label: logs.length > 0 ? logs[0].name : 'No logs available',
    value: logs.length > 0 ? logs[0].path : ''
};

export const useLogEntries = () => {
    const [allLogs, setAllLogs] = useState<LogEntry[]>([]);
    const [totalEntries, setTotalEntries] = useState(0);
    const [loading, setLoading] = useState(true);
    const [selectedFile, setSelectedFile] = useState<Option | null>(defaultLog);
    const [filters, setFilters] = useState<LogFilters>({
        level: '',
        search: '',
        sortBy: 'timestamp',
        sortOrder: 'desc',
        perPage: defaultPerPage
    });

    // Infinite scroll state
    const [infiniteState, setInfiniteState] = useState<InfiniteScrollState>({
        page: 1,
        hasMore: false,
        isLoadingMore: false
    });

    const onFileChange = (file: Option | null) => {
        if (!file) {
            return;
        }
        setSelectedFile(file);
    };

    // Debounce search input to improve performance
    const debouncedSearch = useDebounce(filters.search, 300);

    // Fetch logs function
    const fetchLogs = useCallback(
        async (perPageOverride?: number) => {
            try {
                setLoading(true);

                const currentPerPage = perPageOverride ?? filters.perPage;
                const apiPath = addQueryArgs('/debug-suite/v1/logs', {
                    per_page: currentPerPage,
                    page: 1
                });

                const response = await apiFetch<LogResponse>({
                    path: apiPath
                });

                setAllLogs(response.entries);
                setTotalEntries(response.total);

                // Update infinite scroll state
                setInfiniteState((prev) => ({
                    ...prev,
                    hasMore: response.total > currentPerPage,
                    isLoadingMore: false
                }));
            } catch (error) {
                console.error('Error fetching logs:', error);
                setAllLogs([]);
                setTotalEntries(0);
                setInfiniteState((prev) => ({ ...prev, hasMore: false, isLoadingMore: false }));
            } finally {
                setLoading(false);
            }
        },
        [filters.perPage]
    );

    // Apply client-side filtering with debounced search
    const filteredLogs = useMemo(() => {
        const filtersWithDebouncedSearch = {
            ...filters,
            search: debouncedSearch
        };
        return filterLogEntries(allLogs, filtersWithDebouncedSearch);
    }, [allLogs, filters, debouncedSearch]);

    // Load more entries
    const loadMore = useCallback(() => {
        if (totalEntries > filters.perPage) {
            setInfiniteState((prev) => ({ ...prev, isLoadingMore: true }));

            const newPerPage = filters.perPage + defaultPerPage;
            setFilters((prev) => ({ ...prev, perPage: newPerPage }));
            void fetchLogs(newPerPage);
        }
    }, [totalEntries, filters.perPage, fetchLogs]);

    // Update filters
    const updateFilters = useCallback((newFilters: Partial<LogFilters>) => {
        setFilters((prev) => ({
            ...prev,
            ...newFilters,
            // Reset pagination when filters change (except perPage)
            ...(Object.keys(newFilters).some((key) => key !== 'perPage') ? { perPage: defaultPerPage } : {})
        }));
    }, []);

    // Refetch logs
    const refetch = useCallback(() => {
        void fetchLogs();
    }, [fetchLogs]);

    // Initial fetch - runs only once on mount
    useEffect(() => {
        void fetchLogs(defaultPerPage);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return {
        logs: filteredLogs,
        loading,
        infiniteState,
        totalEntries,
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
