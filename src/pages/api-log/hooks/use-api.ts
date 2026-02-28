/**
 * useApiLogAPI - Hook for API log API integration.
 *
 * @since 1.2.0
 */
import apiFetch from '@wordpress/api-fetch';
import { useCallback } from '@wordpress/element';
import type { ApiLogEntry, ApiLogFilters, ApiLogResponse, ApiLogStats } from '../types';

interface UseApiLogAPIResult {
    fetchApiLogs: (
        filters: ApiLogFilters,
        page?: number
    ) => Promise<
        ApiLogResponse & {
            stats: ApiLogStats;
            filter_options: {
                routes: Array<{ value: string; label: string }>;
                methods: Array<{ value: string; label: string }>;
                statuses: Array<{ value: string; label: string }>;
            };
        }
    >;
    fetchApiLogDetail: (id: number) => Promise<ApiLogEntry>;
    deleteLogs: (ids: number[]) => Promise<{ deleted_count: number; message: string }>;
    clearAllLogs: () => Promise<{ message: string }>;
}

const useApiLogAPI = (): UseApiLogAPIResult => {
    const fetchApiLogs = useCallback(async (filters: ApiLogFilters, page = 1) => {
        const params = new URLSearchParams({
            page: page.toString(),
            per_page: filters.perPage.toString(),
            sort_by: filters.sortBy,
            sort_order: filters.sortOrder,
            ...(filters.search && { search: filters.search }),
            ...(filters.method !== 'all' && { method: filters.method }),
            ...(filters.status !== 'all' && { status: filters.status }),
            ...(filters.route && { route: filters.route })
        });

        const response = await apiFetch<{
            entries: ApiLogEntry[];
            pagination: {
                current_page: number;
                total_pages: number;
                total_items: number;
                per_page: number;
                from: number;
                to: number;
                has_more: boolean;
            };
            total_count: number;
            stats: ApiLogStats;
            filter_options: {
                routes: Array<{ value: string; label: string }>;
                methods: Array<{ value: string; label: string }>;
                statuses: Array<{ value: string; label: string }>;
            };
        }>({
            path: `/debug-suite/v1/api-logs?${params.toString()}`
        });

        return {
            entries: response.entries,
            total: response.total_count,
            total_pages: response.pagination.total_pages,
            current_page: response.pagination.current_page,
            per_page: response.pagination.per_page,
            has_more: response.pagination.has_more,
            stats: response.stats,
            filter_options: response.filter_options
        };
    }, []);

    const fetchApiLogDetail = useCallback(async (id: number) => {
        return await apiFetch<ApiLogEntry>({
            path: `/debug-suite/v1/api-logs/${id}`
        });
    }, []);

    const deleteLogs = useCallback(async (ids: number[]) => {
        return await apiFetch<{ deleted_count: number; message: string }>({
            path: '/debug-suite/v1/api-logs/bulk',
            method: 'DELETE',
            data: { ids }
        });
    }, []);

    const clearAllLogs = useCallback(async () => {
        return await apiFetch<{ message: string }>({
            path: '/debug-suite/v1/api-logs/clear',
            method: 'DELETE'
        });
    }, []);

    return {
        fetchApiLogs,
        fetchApiLogDetail,
        deleteLogs,
        clearAllLogs
    };
};

export default useApiLogAPI;
