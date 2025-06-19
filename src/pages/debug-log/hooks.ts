/**
 * API service hooks for Debug Log.
 *
 * @since 1.0.0
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import type { LogFile, LogResponse, LogStats } from './types';

export const useLogFiles = () => {
    const [logFiles, setLogFiles] = useState<LogFile[]>([]);
    const [selectedFile, setSelectedFile] = useState<string>('');
    const [loading, setLoading] = useState(true);

    const fetchLogFiles = async () => {
        try {
            setLoading(true);
            const response = await apiFetch<{ files: LogFile[]; current_file: string }>({
                path: '/debug-suite/v1/logs/files'
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
    const [logs, setLogs] = useState<LogResponse>({
        entries: [],
        total: 0,
        total_pages: 1,
        current_page: 1,
        per_page: 25,
        has_more: false
    });
    const [loading, setLoading] = useState(true);

    const fetchLogs = async (filters?: {
        page?: number;
        per_page?: number;
        level_filter?: string;
        search?: string;
        sort_by?: string;
        sort_order?: string;
    }) => {
        try {
            setLoading(true);
            const params = new URLSearchParams();

            if (filters?.page !== undefined) params.append('page', filters.page.toString());
            if (filters?.per_page !== undefined) params.append('per_page', filters.per_page.toString());
            if (filters?.level_filter) params.append('level_filter', filters.level_filter);
            if (filters?.search) params.append('search', filters.search);
            if (filters?.sort_by) params.append('sort_by', filters.sort_by);
            if (filters?.sort_order) params.append('sort_order', filters.sort_order);

            const response = await apiFetch<LogResponse>({
                path: `/debug-suite/v1/logs?${params.toString()}`
            });

            setLogs(response);
        } catch (error) {
            console.error('Error fetching logs:', error);
            setLogs({
                entries: [],
                total: 0,
                total_pages: 1,
                current_page: 1,
                per_page: 25,
                has_more: false
            });
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchLogs();
    }, []);

    return { logs, loading, fetchLogs, refetch: () => fetchLogs() };
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
