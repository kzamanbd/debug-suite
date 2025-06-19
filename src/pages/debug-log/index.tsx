import FileLogsSkeleton from '@/components/logs-skeleton';
import Button from '@/components/ui/button';
import InputField from '@/components/ui/input-field';
import SearchableSelect from '@/components/ui/searchable-select';
import { classNames } from '@/utils';
import useDebounce from '@/utils/use-debounce';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/react';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ChevronUpIcon,
    DownloadIcon,
    RefreshCwIcon,
    SearchIcon,
    TrashIcon,
    XIcon
} from 'lucide-react';
import { Link } from 'react-router-dom';

interface LogEntry {
    id: number;
    timestamp: string;
    level: 'critical' | 'error' | 'warning' | 'notice' | 'info' | 'debug';
    message: string;
    file?: string;
    line?: number;
    stack_trace?: string;
    has_stack_trace?: boolean;
}

interface LogFile {
    name: string;
    path: string;
    size: string;
    size_bytes: number;
    modified: string;
    type: string;
    is_current: boolean;
}

interface LogResponse {
    entries: LogEntry[];
    total: number;
    total_pages: number;
    current_page: number;
    per_page: number;
    has_more: boolean;
}

interface LogStats {
    total_entries: number;
    file_size: string;
    last_modified: string;
    level_counts: Record<string, number>;
}

const levelColors: Record<string, string> = {
    critical: 'bg-red-200 text-red-900 border-red-300',
    error: 'bg-red-100 text-red-800 border-red-200',
    warning: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    notice: 'bg-blue-100 text-blue-800 border-blue-200',
    info: 'bg-primary-100 text-primary-800 border-primary-200',
    debug: 'bg-gray-100 text-gray-800 border-gray-200'
};

const levelIcons: Record<string, string> = {
    critical: '🔴',
    error: '❌',
    warning: '⚠️',
    notice: 'ℹ️',
    info: '📝',
    debug: '🐛'
};

const levelOptions = [
    { value: '', label: __('All Levels', 'debug-suite') },
    { value: 'critical', label: __('Critical', 'debug-suite') },
    { value: 'error', label: __('Error', 'debug-suite') },
    { value: 'warning', label: __('Warning', 'debug-suite') },
    { value: 'notice', label: __('Notice', 'debug-suite') },
    { value: 'info', label: __('Info', 'debug-suite') },
    { value: 'debug', label: __('Debug', 'debug-suite') }
];

const perPageOptions = [
    { value: '10', label: '10 items per page' },
    { value: '25', label: '25 items per page' },
    { value: '50', label: '50 items per page' },
    { value: '100', label: '100 items per page' }
];

const sortOptions = [
    { value: 'timestamp', label: __('Sort by Date', 'debug-suite') },
    { value: 'level', label: __('Sort by Level', 'debug-suite') },
    { value: 'message', label: __('Sort by Message', 'debug-suite') }
];

const FileLogs = () => {
    // State management
    const [logs, setLogs] = useState<LogEntry[]>([]);
    const [logFiles, setLogFiles] = useState<LogFile[]>([]);
    const [stats, setStats] = useState<LogStats | null>(null);
    const [loading, setLoading] = useState(true);
    const [filesLoading, setFilesLoading] = useState(true);
    const [clearing, setClearing] = useState(false);

    // Filter and pagination state
    const [currentPage, setCurrentPage] = useState(1);
    const [perPage, setPerPage] = useState(25);
    const [totalPages, setTotalPages] = useState(1);
    const [totalEntries, setTotalEntries] = useState(0);
    const [selectedLevel, setSelectedLevel] = useState('');
    const [searchTerm, setSearchTerm] = useState('');
    const [sortBy, setSortBy] = useState('timestamp');
    const [sortOrder, setSortOrder] = useState('desc');
    const [selectedFile, setSelectedFile] = useState<string>('');

    // Debounce search term to avoid excessive API calls
    const debouncedSearchTerm = useDebounce(searchTerm, 500);

    // Fetch log files for sidebar
    const fetchLogFiles = async () => {
        try {
            setFilesLoading(true);
            const response = await apiFetch<{ files: LogFile[]; current_file: string }>({
                path: '/debug-suite/v1/logs/files'
            });
            setLogFiles(response.files);
            setSelectedFile(response.current_file);
        } catch (error) {
            console.error('Error fetching log files:', error);
        } finally {
            setFilesLoading(false);
        }
    };

    // Fetch log entries with current filters
    const fetchLogs = async (resetPage = false) => {
        try {
            setLoading(true);
            const page = resetPage ? 1 : currentPage;

            const params = new URLSearchParams({
                page: page.toString(),
                per_page: perPage.toString(),
                sort_by: sortBy,
                sort_order: sortOrder
            });

            if (selectedLevel) params.append('level_filter', selectedLevel);
            if (debouncedSearchTerm) params.append('search', debouncedSearchTerm);

            const response = await apiFetch<LogResponse>({
                path: `/debug-suite/v1/logs?${params.toString()}`
            });

            setLogs(response.entries);
            setTotalPages(response.total_pages);
            setTotalEntries(response.total);
            if (resetPage) setCurrentPage(1);
        } catch (error) {
            console.error('Error fetching logs:', error);
            setLogs([]);
        } finally {
            setLoading(false);
        }
    };

    // Fetch log statistics
    const fetchStats = async () => {
        try {
            const response = await apiFetch<LogStats>({
                path: '/debug-suite/v1/logs/stats'
            });
            setStats(response);
        } catch (error) {
            console.error('Error fetching stats:', error);
        }
    };

    // Clear log file
    const clearLogs = async () => {
        if (
            !confirm(__('Are you sure you want to clear all log entries? This action cannot be undone.', 'debug-suite'))
        ) {
            return;
        }

        try {
            setClearing(true);
            await apiFetch({
                path: '/debug-suite/v1/logs/clear',
                method: 'DELETE'
            });
            fetchLogs(true);
            fetchStats();
        } catch (error) {
            console.error('Error clearing logs:', error);
        } finally {
            setClearing(false);
        }
    };

    // Export logs
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
        }
    };

    // Initial load
    useEffect(() => {
        fetchLogFiles();
        fetchStats();
    }, []);

    // Fetch logs when filters change or file selection changes
    useEffect(() => {
        fetchLogs(true);
    }, [selectedLevel, debouncedSearchTerm, perPage, sortBy, sortOrder, selectedFile]);

    // Fetch logs when page changes
    useEffect(() => {
        if (currentPage > 1) {
            fetchLogs();
        }
    }, [currentPage]);

    // Handle search with debounce
    const handleSearch = (value: string) => {
        setSearchTerm(value);
    };

    // Clear search
    const clearSearch = () => {
        setSearchTerm('');
    };

    // Pagination helpers
    const goToPage = (page: number) => {
        if (page >= 1 && page <= totalPages) {
            setCurrentPage(page);
        }
    };

    const renderPagination = () => {
        const startEntry = (currentPage - 1) * perPage + 1;
        const endEntry = Math.min(currentPage * perPage, totalEntries);

        return (
            <div className="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                <div className="flex flex-1 justify-between sm:hidden">
                    <Button variant="light" onClick={() => goToPage(currentPage - 1)} disabled={currentPage === 1}>
                        {__('Previous', 'debug-suite')}
                    </Button>
                    <Button
                        variant="light"
                        onClick={() => goToPage(currentPage + 1)}
                        disabled={currentPage === totalPages}
                    >
                        {__('Next', 'debug-suite')}
                    </Button>
                </div>
                <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p className="text-sm text-gray-700">
                            {__('Showing', 'debug-suite')} <span className="font-medium">{startEntry}</span>{' '}
                            {__('to', 'debug-suite')} <span className="font-medium">{endEntry}</span>{' '}
                            {__('of', 'debug-suite')} <span className="font-medium">{totalEntries}</span>{' '}
                            {__('results', 'debug-suite')}
                        </p>
                    </div>
                    <div>
                        <nav className="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <Button
                                variant="light"
                                onClick={() => goToPage(currentPage - 1)}
                                disabled={currentPage === 1}
                                className="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-gray-300 ring-inset hover:bg-gray-50 focus:outline-offset-0"
                            >
                                <ChevronLeftIcon className="h-5 w-5" />
                            </Button>

                            {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
                                const page = i + 1;
                                return (
                                    <Button
                                        key={page}
                                        variant={currentPage === page ? 'primary' : 'light'}
                                        onClick={() => goToPage(page)}
                                        className="relative inline-flex items-center px-4 py-2 text-sm font-semibold"
                                    >
                                        {page}
                                    </Button>
                                );
                            })}

                            <Button
                                variant="light"
                                onClick={() => goToPage(currentPage + 1)}
                                disabled={currentPage === totalPages}
                                className="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-gray-300 ring-inset hover:bg-gray-50 focus:outline-offset-0"
                            >
                                <ChevronRightIcon className="h-5 w-5" />
                            </Button>
                        </nav>
                    </div>
                </div>
            </div>
        );
    };

    if (loading && logs.length === 0) {
        return <FileLogsSkeleton />;
    }

    return (
        <div className="flex h-screen overflow-hidden bg-gray-50">
            {/* Main Content - Full Width */}
            <div className="flex flex-1 flex-col overflow-hidden">
                {/* Top Bar */}
                <div className="border-b border-gray-200 bg-white p-4">
                    <div className="mb-4 flex items-center justify-between">
                        <div className="flex items-center space-x-4">
                            {/* Back Button */}
                            <Link
                                to="/"
                                className="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-800 transition-colors hover:bg-gray-200"
                            >
                                ← {__('Overview', 'debug-suite')}
                            </Link>

                            {/* File Selection Dropdown */}
                            <div className="flex items-center space-x-2">
                                <span className="text-sm font-medium text-gray-700">
                                    {__('Log File:', 'debug-suite')}
                                </span>
                                <SearchableSelect
                                    options={logFiles.map((file) => ({
                                        value: file.path,
                                        label: file.name,
                                        meta: `${file.type} • ${file.size} • Modified: ${new Date(file.modified).toLocaleDateString()}`
                                    }))}
                                    value={
                                        logFiles.length > 0
                                            ? {
                                                  value: selectedFile,
                                                  label:
                                                      logFiles.find((f) => f.path === selectedFile)?.name ||
                                                      'debug.log',
                                                  meta: `${logFiles.find((f) => f.path === selectedFile)?.type || 'WordPress Debug'} • ${logFiles.find((f) => f.path === selectedFile)?.size || '0 B'}`
                                              }
                                            : { value: '', label: __('Loading...', 'debug-suite') }
                                    }
                                    onChange={(option) => setSelectedFile(option?.value || '')}
                                    isDisabled={filesLoading}
                                    className="min-w-[250px]"
                                    formatOptionLabel={(option: any) => (
                                        <div className="flex flex-col">
                                            <div className="text-sm font-medium">{option.label}</div>
                                            {option.meta && <div className="text-xs text-gray-500">{option.meta}</div>}
                                        </div>
                                    )}
                                />
                            </div>

                            <div className="flex items-center space-x-2">
                                <span className="text-sm font-medium text-gray-700">
                                    {totalEntries} {__('entries in', 'debug-suite')}
                                </span>
                                <SearchableSelect
                                    options={levelOptions}
                                    value={levelOptions.find((opt) => opt.value === selectedLevel) || levelOptions[0]}
                                    onChange={(option) => setSelectedLevel(option?.value || '')}
                                />
                            </div>
                        </div>

                        <div className="flex items-center space-x-2">
                            <div className="relative">
                                <SearchIcon className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                                <InputField
                                    type="text"
                                    placeholder={__('Search in log...', 'debug-suite')}
                                    value={searchTerm}
                                    onChange={(e) => handleSearch(e.target.value)}
                                    className="w-64 pl-10"
                                />
                                {searchTerm && (
                                    <button
                                        onClick={clearSearch}
                                        className="absolute top-1/2 right-3 -translate-y-1/2 transform"
                                    >
                                        <XIcon className="h-4 w-4 text-gray-400" />
                                    </button>
                                )}
                            </div>
                            <Button variant="light" onClick={() => fetchLogs(true)}>
                                <RefreshCwIcon className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-4">
                            <SearchableSelect
                                options={sortOptions}
                                value={sortOptions.find((opt) => opt.value === sortBy) || sortOptions[0]}
                                onChange={(option) => setSortBy(option?.value || 'timestamp')}
                            />
                            <Button
                                variant="light"
                                onClick={() => setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc')}
                                className="flex items-center space-x-1"
                            >
                                {sortOrder === 'asc' ? (
                                    <ChevronUpIcon className="h-4 w-4" />
                                ) : (
                                    <ChevronDownIcon className="h-4 w-4" />
                                )}
                                <span>
                                    {sortOrder === 'asc'
                                        ? __('Ascending', 'debug-suite')
                                        : __('Descending', 'debug-suite')}
                                </span>
                            </Button>
                        </div>

                        <div className="flex items-center space-x-2">
                            <SearchableSelect
                                options={perPageOptions}
                                value={
                                    perPageOptions.find((opt) => opt.value === perPage.toString()) || perPageOptions[1]
                                }
                                onChange={(option) => setPerPage(parseInt(option?.value || '25'))}
                            />
                            <Button variant="light" onClick={() => exportLogs('json')}>
                                <DownloadIcon className="mr-2 h-4 w-4" />
                                {__('Export', 'debug-suite')}
                            </Button>
                            <Button variant="light" onClick={clearLogs} disabled={clearing}>
                                <TrashIcon className="mr-2 h-4 w-4" />
                                {clearing ? __('Clearing...', 'debug-suite') : __('Clear', 'debug-suite')}
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Log Entries */}
                <div className="flex-1 overflow-y-auto">
                    <div className="bg-white">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="sticky top-0 z-10 bg-gray-50">
                                <tr>
                                    <th className="w-40 px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                        {__('Date/Time', 'debug-suite')}
                                    </th>
                                    <th className="w-24 px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                        {__('Severity', 'debug-suite')}
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                        {__('Message', 'debug-suite')}
                                    </th>
                                    <th className="w-12 px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">
                                        {__('#', 'debug-suite')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {logs.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-6 py-12 text-center text-gray-500">
                                            {loading ? (
                                                <div className="flex items-center justify-center">
                                                    <RefreshCwIcon className="mr-2 h-5 w-5 animate-spin" />
                                                    {__('Loading logs...', 'debug-suite')}
                                                </div>
                                            ) : (
                                                __('No log entries found matching your criteria.', 'debug-suite')
                                            )}
                                        </td>
                                    </tr>
                                ) : (
                                    logs.map((log, index) => (
                                        <Disclosure key={log.id || index}>
                                            {({ open }) => (
                                                <>
                                                    <tr
                                                        className={classNames(
                                                            'transition-colors hover:bg-gray-50',
                                                            open && 'bg-gray-50'
                                                        )}
                                                    >
                                                        <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                                            {new Date(log.timestamp).toLocaleString()}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <span
                                                                className={classNames(
                                                                    'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                                                    levelColors[log.level] || levelColors.debug
                                                                )}
                                                            >
                                                                <span className="mr-1">
                                                                    {levelIcons[log.level] || '📝'}
                                                                </span>
                                                                {log.level.charAt(0).toUpperCase() + log.level.slice(1)}
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4 text-sm text-gray-900">
                                                            <DisclosureButton className="w-full text-left focus:outline-none">
                                                                <div className="flex items-center justify-between">
                                                                    <span className="flex-1 pr-4">{log.message}</span>
                                                                    {log.has_stack_trace && (
                                                                        <span className="text-primary-600 text-xs">
                                                                            {open
                                                                                ? __('Hide Trace', 'debug-suite')
                                                                                : __('Show Trace', 'debug-suite')}
                                                                        </span>
                                                                    )}
                                                                </div>
                                                                {log.file && (
                                                                    <div className="mt-1 text-xs text-gray-500">
                                                                        <code className="rounded bg-gray-100 px-1">
                                                                            {log.file}
                                                                        </code>
                                                                        {log.line && <span>:{log.line}</span>}
                                                                    </div>
                                                                )}
                                                            </DisclosureButton>
                                                        </td>
                                                        <td className="px-6 py-4 text-right text-sm whitespace-nowrap text-gray-500">
                                                            {(currentPage - 1) * perPage + index + 1}
                                                        </td>
                                                    </tr>
                                                    {log.stack_trace && (
                                                        <DisclosurePanel as="tr">
                                                            <td
                                                                colSpan={4}
                                                                className="border-t border-gray-100 bg-gray-50 px-6 py-4"
                                                            >
                                                                <div className="overflow-x-auto rounded-lg bg-gray-900 p-4 text-gray-100">
                                                                    <pre className="font-mono text-xs whitespace-pre-wrap">
                                                                        {log.stack_trace}
                                                                    </pre>
                                                                </div>
                                                            </td>
                                                        </DisclosurePanel>
                                                    )}
                                                </>
                                            )}
                                        </Disclosure>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {totalPages > 1 && renderPagination()}
                </div>

                {/* Stats Footer */}
                {stats && (
                    <div className="border-t border-gray-200 bg-white px-6 py-3">
                        <div className="flex items-center justify-between text-xs text-gray-500">
                            <div>
                                {__('Memory:', 'debug-suite')} {stats.file_size} • {__('Duration:', 'debug-suite')} - •{' '}
                                {__('Version:', 'debug-suite')} v1.0.0
                            </div>
                            <div className="flex items-center space-x-4">
                                <Link to="/file-logs/manage" className="text-primary-600 hover:text-primary-800">
                                    {__('Manage Logs', 'debug-suite')} →
                                </Link>
                                <span>🍪 Buy me a coffee</span>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default FileLogs;
