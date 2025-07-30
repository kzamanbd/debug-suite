/**
 * useEmailLogAPI - Hook for email log API integration.
 *
 * @since 1.0.0
 */
import apiFetch from '@wordpress/api-fetch';
import { useCallback } from '@wordpress/element';
import type { EmailLogEntry, EmailLogFilters, EmailLogResponse, EmailLogStats } from '../types';

interface UseEmailLogAPIResult {
    fetchEmailLogs: (filters: EmailLogFilters, page?: number) => Promise<EmailLogResponse>;
    fetchEmailStats: () => Promise<EmailLogStats>;
    fetchFilterOptions: () => Promise<{
        receivers: Array<{ value: string; label: string }>;
        statuses: Array<{ value: string; label: string }>;
    }>;
    deleteEmails: (ids: number[]) => Promise<{ deleted_count: number; message: string }>;
    clearAllEmails: () => Promise<{ message: string }>;
    resendEmail: (id: number) => Promise<{ message: string }>;
}

const useEmailLogAPI = (): UseEmailLogAPIResult => {
    const fetchEmailLogs = useCallback(async (filters: EmailLogFilters, page = 1): Promise<EmailLogResponse> => {
        const params = new URLSearchParams({
            page: page.toString(),
            per_page: filters.perPage.toString(),
            sort_by: filters.sortBy,
            sort_order: filters.sortOrder,
            ...(filters.search && { search: filters.search }),
            ...(filters.status !== 'all' && { status: filters.status }),
            ...(filters.receiver && { receiver: filters.receiver })
        });

        const response = await apiFetch<{
            entries: EmailLogEntry[];
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
        }>({
            path: `/debug-suite/v1/email-logs?${params.toString()}`
        });

        return {
            entries: response.entries,
            total: response.total_count,
            total_pages: response.pagination.total_pages,
            current_page: response.pagination.current_page,
            per_page: response.pagination.per_page,
            has_more: response.pagination.has_more
        };
    }, []);

    const fetchEmailStats = useCallback(async (): Promise<EmailLogStats> => {
        return await apiFetch<EmailLogStats>({
            path: '/debug-suite/v1/email-logs/stats'
        });
    }, []);

    const fetchFilterOptions = useCallback(async () => {
        return await apiFetch<{
            receivers: Array<{ value: string; label: string }>;
            statuses: Array<{ value: string; label: string }>;
        }>({
            path: '/debug-suite/v1/email-logs/filters'
        });
    }, []);

    const deleteEmails = useCallback(async (ids: number[]) => {
        return await apiFetch<{ deleted_count: number; message: string }>({
            path: '/debug-suite/v1/email-logs/bulk',
            method: 'DELETE',
            data: { ids }
        });
    }, []);

    const clearAllEmails = useCallback(async () => {
        return await apiFetch<{ message: string }>({
            path: '/debug-suite/v1/email-logs/clear',
            method: 'DELETE'
        });
    }, []);

    const resendEmail = useCallback(async (id: number) => {
        return await apiFetch<{ message: string }>({
            path: `/debug-suite/v1/email-logs/${id}/resend`,
            method: 'POST'
        });
    }, []);

    return {
        fetchEmailLogs,
        fetchEmailStats,
        fetchFilterOptions,
        deleteEmails,
        clearAllEmails,
        resendEmail
    };
};

export default useEmailLogAPI;
