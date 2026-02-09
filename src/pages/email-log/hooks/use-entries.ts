/**
 * useEmailLogEntries - Hook for managing email log entries data.
 *
 * @since 1.0.0
 */
import type { PaginationInfo } from '@/components/base';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { EmailLogEntry, EmailLogFilters, EmailLogStats } from '../types';
import useEmailLogAPI from './use-api';

interface UseEmailLogEntriesResult {
    entries: EmailLogEntry[];
    loading: boolean;
    error: string | null;
    filters: EmailLogFilters;
    updateFilters: (newFilters: Partial<EmailLogFilters>) => void;
    paginationInfo: PaginationInfo;
    refetch: () => void;
    selectedItems: number[];
    onSelectAll: (selected: boolean) => void;
    onSelectItem: (id: number, selected: boolean) => void;
    onPageChange: (page: number) => void;
    emailStats: EmailLogStats;
    filterOptions: {
        receivers: Array<{ value: string; label: string }>;
        statuses: Array<{ value: string; label: string }>;
    };
}

const useEmailLogEntries = (): UseEmailLogEntriesResult => {
    const [entries, setEntries] = useState<EmailLogEntry[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [selectedItems, setSelectedItems] = useState<number[]>([]);
    const [emailStats, setEmailStats] = useState<EmailLogStats>({
        total_emails: 0,
        successful: 0,
        failed: 0,
        success_rate: '0%'
    });
    const [filters, setFilters] = useState<EmailLogFilters>({
        receiver: '',
        status: 'all',
        search: '',
        sortBy: 'sent_date',
        sortOrder: 'desc',
        perPage: 20
    });
    const [filterOptions, setFilterOptions] = useState<{
        receivers: Array<{ value: string; label: string }>;
        statuses: Array<{ value: string; label: string }>;
    }>({
        receivers: [],
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

    const { fetchEmailLogs } = useEmailLogAPI();

    // Fetch email logs from API
    const fetchEmailData = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetchEmailLogs(filters, currentPage);

            setEntries(response.entries);
            setEmailStats(response.stats);
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
            console.error('Failed to fetch email logs:', err);
            setError(__('Failed to load email logs.', 'debug-suite'));
            setEntries([]);
        } finally {
            setLoading(false);
        }
    }, [fetchEmailLogs, filters, currentPage]);

    // Effect to fetch data when filters or page change
    useEffect(() => {
        void fetchEmailData();
    }, [fetchEmailData]);

    const updateFilters = useCallback((newFilters: Partial<EmailLogFilters>) => {
        setFilters((prev) => ({ ...prev, ...newFilters }));
        setCurrentPage(1); // Reset to first page when filters change
        setSelectedItems([]); // Clear selections when filters change
    }, []);

    const refetch = useCallback(() => {
        void fetchEmailData();
    }, [fetchEmailData]);

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

    // Handle page change
    const onPageChange = useCallback((page: number) => {
        setCurrentPage(page);
        setSelectedItems([]); // Clear selections when page changes
    }, []);

    // Add page change to filters update
    const updateFiltersWithPage = useCallback(
        (newFilters: Partial<EmailLogFilters>) => {
            updateFilters(newFilters);
        },
        [updateFilters]
    );

    return {
        entries,
        loading,
        error,
        filters,
        updateFilters: updateFiltersWithPage,
        paginationInfo,
        refetch,
        selectedItems,
        onSelectAll,
        onSelectItem,
        onPageChange,
        emailStats,
        filterOptions
    };
};

export default useEmailLogEntries;
