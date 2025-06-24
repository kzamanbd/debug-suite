/**
 * API service hooks for Debug Log.
 *
 * @since 1.0.0
 */
import apiFetch from '@wordpress/api-fetch';
import { useCallback, useEffect, useState } from '@wordpress/element';
import type { InfiniteScrollState, LogFile, LogResponse, LogStats, RawFileContent } from './types';

export const useLogFiles = () => {
    const [logFiles, setLogFiles] = useState<LogFile[]>([]);
    const [selectedFile, setSelectedFile] = useState<string>('');
    const [loading, setLoading] = useState(true);

    const fetchLogFiles = async () => {
        try {
            setLoading(true);
            const response = await apiFetch<{ files: LogFile[]; current_file: string }>({
                path: '/debug-suite/v1/logs/supported-files'
            });
            setLogFiles(response.files);
            setSelectedFile(response.current_file);
        } catch (error) {
            console.error('Error fetching log files:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchLogFiles();
    }, []);

    return { logFiles, selectedFile, setSelectedFile, loading, refetch: fetchLogFiles };
};

export const useLogEntries = () => {
    const [allLogs, setAllLogs] = useState<LogResponse['entries']>([]);
    const [infiniteState, setInfiniteState] = useState<InfiniteScrollState>({
        page: 1,
        hasMore: true,
        isLoadingMore: false
    });
    const [loading, setLoading] = useState(true);
    const [totalEntries, setTotalEntries] = useState(0);
    const [currentFilters, setCurrentFilters] = useState<{
        level_filter?: string;
        search?: string;
        sort_by?: string;
        sort_order?: string;
        per_page?: number;
    }>({});

    const fetchLogs = useCallback(
        async (
            filters?: {
                page?: number;
                per_page?: number;
                level_filter?: string;
                search?: string;
                sort_by?: string;
                sort_order?: string;
            },
            append = false
        ) => {
            try {
                if (!append) {
                    setLoading(true);
                } else {
                    setInfiniteState((prev) => ({ ...prev, isLoadingMore: true }));
                }

                const params = new URLSearchParams();
                const page = filters?.page || 1;

                params.append('page', page.toString());
                if (filters?.per_page !== undefined) params.append('per_page', filters.per_page.toString());
                if (filters?.level_filter) params.append('level_filter', filters.level_filter);
                if (filters?.search) params.append('search', filters.search);
                if (filters?.sort_by) params.append('sort_by', filters.sort_by);
                if (filters?.sort_order) params.append('sort_order', filters.sort_order);

                const response = await apiFetch<LogResponse>({
                    path: `/debug-suite/v1/logs?${params.toString()}`
                });

                if (append) {
                    setAllLogs((prevLogs) => [...prevLogs, ...response.entries]);
                } else {
                    setAllLogs(response.entries);
                    setCurrentFilters(filters || {});
                }

                setTotalEntries(response.total);
                setInfiniteState((prev) => ({
                    ...prev,
                    page: response.current_page,
                    hasMore: response.has_more,
                    isLoadingMore: false
                }));
            } catch (error) {
                console.error('Error fetching logs:', error);
                if (!append) {
                    setAllLogs([]);
                    setTotalEntries(0);
                }
                setInfiniteState((prev) => ({
                    ...prev,
                    hasMore: false,
                    isLoadingMore: false
                }));
            } finally {
                setLoading(false);
            }
        },
        []
    );

    const loadMore = useCallback(() => {
        if (infiniteState.hasMore && !infiniteState.isLoadingMore) {
            const nextPage = infiniteState.page + 1;
            fetchLogs({ ...currentFilters, page: nextPage }, true);
        }
    }, [infiniteState.hasMore, infiniteState.isLoadingMore, infiniteState.page, currentFilters, fetchLogs]);

    const refetch = useCallback(() => {
        setInfiniteState({ page: 1, hasMore: true, isLoadingMore: false });
        setAllLogs([]);
        fetchLogs(currentFilters, false);
    }, [currentFilters, fetchLogs]);

    useEffect(() => {
        fetchLogs();
    }, [fetchLogs]);

    return {
        logs: allLogs,
        loading,
        infiniteState,
        totalEntries,
        fetchLogs: (filters?: any) => {
            setInfiniteState({ page: 1, hasMore: true, isLoadingMore: false });
            setAllLogs([]);
            fetchLogs(filters, false);
        },
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

    useEffect(() => {
        fetchStats();
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
            const response = await apiFetch<{
                data: string;
                filename: string;
                format: string;
            }>({
                path: `/debug-suite/v1/logs/export?format=${format}&limit=1000`
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
            const params = new URLSearchParams();
            params.append('file', path);

            const response = await apiFetch<RawFileContent>({
                path: `/debug-suite/v1/logs/raw?${params.toString()}`
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
