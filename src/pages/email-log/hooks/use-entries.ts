/**
 * useEmailLogEntries - Hook for managing email log entries data.
 *
 * @since 1.0.0
 */
import { useCallback, useEffect, useState } from '@wordpress/element';
import type { EmailLogEntry, EmailLogFilters, PaginationInfo } from '../types';

// Mock data for demonstration - matches the image content
const mockEmailLogEntries: EmailLogEntry[] = [
    {
        id: 1,
        time: '2025-07-25 22:35:30',
        receiver: 'kzamanbn@gmail.com',
        subject: '[My Dokan] Payment gateway "Direct bank transfer" enabled',
        status: 'success',
        created_at: '2025-07-25 22:35:30'
    },
    {
        id: 2,
        time: '2025-07-25 14:52:27',
        receiver: 'dummy_store3@dokan.com',
        subject: '[My Dokan] Your customer order is now complete (1140) - July 25, 2025',
        status: 'success',
        created_at: '2025-07-25 14:52:27'
    },
    {
        id: 3,
        time: '2025-07-25 14:52:27',
        receiver: 'dummy_store1@dokan.com',
        subject: 'Your order from My Dokan is on its way!',
        status: 'success',
        created_at: '2025-07-25 14:52:27'
    },
    {
        id: 4,
        time: '2025-07-25 14:52:27',
        receiver: 'dummy_store3@dokan.com',
        subject: '[My Dokan] New customer order (1140) - July 25, 2025',
        status: 'success',
        created_at: '2025-07-25 14:52:27'
    },
    {
        id: 5,
        time: '2025-07-25 14:52:27',
        receiver: 'dummy_store2@dokan.com',
        subject: '[My Dokan] New customer order (1139) - July 25, 2025',
        status: 'success',
        created_at: '2025-07-25 14:52:27'
    },
    {
        id: 6,
        time: '2025-07-25 14:52:27',
        receiver: 'dummy_store1@dokan.com',
        subject: 'Your My Dokan order has been received!',
        status: 'success',
        created_at: '2025-07-25 14:52:27'
    },
    {
        id: 7,
        time: '2025-07-25 14:52:27',
        receiver: 'kzamanbn@gmail.com',
        subject: "[My Dokan]: You've got a new order: #1138",
        status: 'success',
        created_at: '2025-07-25 14:52:27'
    },
    {
        id: 8,
        time: '2025-07-25 14:47:30',
        receiver: 'kzamanbn@gmail.com',
        subject: '[My Dokan] Payment gateway "Dokan Paystack" enabled',
        status: 'success',
        created_at: '2025-07-25 14:47:30'
    },
    {
        id: 9,
        time: '2025-07-25 14:46:40',
        receiver: 'kzamanbn@gmail.com',
        subject: '[My Dokan] Payment gateway "Dokan Paystack" enabled',
        status: 'success',
        created_at: '2025-07-25 14:46:40'
    }
];

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
}

const useEmailLogEntries = (): UseEmailLogEntriesResult => {
    const [entries, setEntries] = useState<EmailLogEntry[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [selectedItems, setSelectedItems] = useState<number[]>([]);
    const [filters, setFilters] = useState<EmailLogFilters>({
        receiver: '',
        status: 'all',
        search: '',
        sortBy: 'time',
        sortOrder: 'desc',
        perPage: 20
    });
    const [currentPage, setCurrentPage] = useState(1);

    // Simulate data fetching
    const fetchEmailLogs = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            // Simulate API delay
            await new Promise((resolve) => setTimeout(resolve, 500));

            // Filter and sort mock data
            let filteredEntries = [...mockEmailLogEntries];

            // Apply receiver filter
            if (filters.receiver) {
                filteredEntries = filteredEntries.filter((entry) =>
                    entry.receiver.toLowerCase().includes(filters.receiver.toLowerCase())
                );
            }

            // Apply status filter
            if (filters.status !== 'all') {
                filteredEntries = filteredEntries.filter((entry) => entry.status === filters.status);
            }

            // Apply search filter
            if (filters.search) {
                filteredEntries = filteredEntries.filter(
                    (entry) =>
                        entry.subject.toLowerCase().includes(filters.search.toLowerCase()) ||
                        entry.receiver.toLowerCase().includes(filters.search.toLowerCase())
                );
            }

            // Apply sorting
            filteredEntries.sort((a, b) => {
                let aValue: string | number = a[filters.sortBy as keyof EmailLogEntry] as string;
                let bValue: string | number = b[filters.sortBy as keyof EmailLogEntry] as string;

                // Convert to comparable values
                if (filters.sortBy === 'time') {
                    aValue = new Date(a.time).getTime();
                    bValue = new Date(b.time).getTime();
                } else {
                    aValue = String(aValue).toLowerCase();
                    bValue = String(bValue).toLowerCase();
                }

                if (filters.sortOrder === 'asc') {
                    return aValue < bValue ? -1 : aValue > bValue ? 1 : 0;
                } else {
                    return aValue > bValue ? -1 : aValue < bValue ? 1 : 0;
                }
            });

            // Apply pagination
            const _total = filteredEntries.length;
            const startIndex = (currentPage - 1) * filters.perPage;
            const endIndex = startIndex + filters.perPage;
            const paginatedEntries = filteredEntries.slice(startIndex, endIndex);

            setEntries(paginatedEntries);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'An error occurred');
        } finally {
            setLoading(false);
        }
    }, [filters, currentPage]);

    // Effect to fetch data when filters or page change
    useEffect(() => {
        void fetchEmailLogs();
    }, [fetchEmailLogs]);

    // Calculate pagination info
    const totalFiltered = mockEmailLogEntries.filter((entry) => {
        let matches = true;

        if (filters.receiver) {
            matches = entry.receiver.toLowerCase().includes(filters.receiver.toLowerCase());
        }

        if (filters.status !== 'all') {
            matches = matches && entry.status === filters.status;
        }

        if (filters.search) {
            matches =
                matches &&
                (entry.subject.toLowerCase().includes(filters.search.toLowerCase()) ||
                    entry.receiver.toLowerCase().includes(filters.search.toLowerCase()));
        }

        return matches;
    }).length;

    const paginationInfo: PaginationInfo = {
        current_page: currentPage,
        total_pages: Math.ceil(totalFiltered / filters.perPage),
        total_items: totalFiltered,
        per_page: filters.perPage,
        from: Math.min((currentPage - 1) * filters.perPage + 1, totalFiltered),
        to: Math.min(currentPage * filters.perPage, totalFiltered)
    };

    const updateFilters = useCallback((newFilters: Partial<EmailLogFilters>) => {
        setFilters((prev) => ({ ...prev, ...newFilters }));
        setCurrentPage(1); // Reset to first page when filters change
        setSelectedItems([]); // Clear selections when filters change
    }, []);

    const refetch = useCallback(() => {
        void fetchEmailLogs();
    }, [fetchEmailLogs]);

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
        setSelectedItems((prev) => {
            if (selected) {
                return prev.indexOf(id) === -1 ? [...prev, id] : prev;
            } else {
                return prev.filter((itemId) => itemId !== id);
            }
        });
    }, []);

    // Handle page change
    const onPageChange = useCallback((page: number) => {
        setCurrentPage(page);
        setSelectedItems([]); // Clear selections when page changes
    }, []);

    // Add page change to filters update
    const updateFiltersWithPage = useCallback(
        (newFilters: Partial<EmailLogFilters>) => {
            if ('perPage' in newFilters) {
                updateFilters(newFilters);
            } else {
                setFilters((prev) => ({ ...prev, ...newFilters }));
            }
        },
        [updateFilters]
    );

    return {
        entries,
        loading,
        error,
        filters,
        updateFilters: updateFiltersWithPage,
        paginationInfo: {
            ...paginationInfo,
            current_page: currentPage,
            total_pages: Math.ceil(totalFiltered / filters.perPage)
        },
        refetch,
        selectedItems,
        onSelectAll,
        onSelectItem,
        onPageChange
    };
};

export default useEmailLogEntries;
