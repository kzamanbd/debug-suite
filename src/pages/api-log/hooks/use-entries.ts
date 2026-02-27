/**
 * useApiLogEntries - Hook for managing API log entries data.
 *
 * @since 1.2.0
 */
import type { PaginationInfo } from '@/components/base';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { ApiLogEntry, ApiLogFilters, ApiLogStats } from '../types';
import useApiLogAPI from './use-api';

interface UseApiLogEntriesResult {
    entries: ApiLogEntry[];
    loading: boolean;
    error: string | null;
    filters: ApiLogFilters;
    updateFilters: (newFilters: Partial<ApiLogFilters>) => void;
    paginationInfo: PaginationInfo;
    refetch: () => void;
    selectedItems: number[];
    onSelectAll: (selected: boolean) => void;
    onSelectItem: (id: number, selected: boolean) => void;
    onPageChange: (page: number) => void;
    apiStats: ApiLogStats;
    filterOptions: {
        routes: Array<{ value: string; label: string }>;
        methods: Array<{ value: string; label: string }>;
        statuses: Array<{ value: string; label: string }>;
    };
}

const useApiLogEntries = (): UseApiLogEntriesResult => {
    const [entries, setEntries] = useState<ApiLogEntry[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [selectedItems, setSelectedItems] = useState<number[]>([]);
    const [apiStats, setApiStats] = useState<ApiLogStats>({
        total_requests: 0,
        successful: 0,
        failed: 0,
        avg_duration: 0
    });
    const [filters, setFilters] = useState<ApiLogFilters>({
        method: 'all',
        status: 'all',
        route: '',
        search: '',
        sortBy: 'created_at',
        sortOrder: 'desc',
        perPage: 20
    });
    const [filterOptions, setFilterOptions] = useState<{
        routes: Array<{ value: string; label: string }>;
        methods: Array<{ value: string; label: string }>;
        statuses: Array<{ value: string; label: string }>;
    }>({
        routes: [],
        methods: [],
        statuses: []
    });
    const [currentPage, setCurrentPage] = useState(1);
    const [paginationInfo, setPaginationInfo] = useState<PaginationInfo>({
        current_page: 1,
        total_pages: 1,
        total_items: 0,
        per_page: 20,
        from: 0,
        to: 0
    });

    const { fetchApiLogs } = useApiLogAPI();

    const fetchData = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetchApiLogs(filters, currentPage);

            setEntries(response.entries);
            setApiStats(response.stats);
            setPaginationInfo({
                current_page: response.current_page,
                total_pages: response.total_pages,
                total_items: response.total,
                per_page: response.per_page,
                from: Math.min((response.current_page - 1) * response.per_page + 1, response.total),
                to: Math.min(response.current_page * response.per_page, response.total)
            });
            setFilterOptions(response.filter_options);
        } catch (err) {
            console.error('Failed to fetch API logs:', err);
            setError(__('Failed to load API logs.', 'debug-suite'));
            setEntries([]);
        } finally {
            setLoading(false);
        }
    }, [fetchApiLogs, filters, currentPage]);

    useEffect(() => {
        void fetchData();
    }, [fetchData]);

    const updateFilters = useCallback((newFilters: Partial<ApiLogFilters>) => {
        setFilters((prev) => ({ ...prev, ...newFilters }));
        setCurrentPage(1);
        setSelectedItems([]);
    }, []);

    const refetch = useCallback(() => {
        void fetchData();
    }, [fetchData]);

    const onSelectAll = useCallback(
        (selected: boolean) => {
            if (selected) {
                setSelectedItems(entries.map((entry) => entry.id));
            } else {
                setSelectedItems([]);
            }
        },
        [entries]
    );

    const onSelectItem = useCallback((id: number, selected: boolean) => {
        setSelectedItems((prev) => (selected ? [...prev, id] : prev.filter((itemId) => itemId !== id)));
    }, []);

    const onPageChange = useCallback((page: number) => {
        setCurrentPage(page);
        setSelectedItems([]);
    }, []);

    return {
        entries,
        loading,
        error,
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
    };
};

export default useApiLogEntries;
