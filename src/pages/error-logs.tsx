import FileLogsSkeleton from '@/components/logs-skeleton';
import Button from '@/components/ui/button';
import SearchableSelect from '@/components/ui/searchable-select';
import { classNames } from '@/utils';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/react';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { RefreshCwIcon } from 'lucide-react';
import { Link } from 'react-router-dom';

interface LogEntry {
    id: number;
    timestamp: string;
    level: 'error' | 'warning' | 'info' | 'debug';
    message: string;
    file?: string;
    line?: number;
    trace?: string;
}

const levelColors: Record<string, string> = {
    error: 'bg-red-100 text-red-700',
    warning: 'bg-yellow-100 text-yellow-800',
    info: 'bg-primary-100 text-primary-700',
    debug: 'bg-gray-100 text-gray-700'
};

const labels = [
    { value: 'all', label: __('All Levels', 'debug-suite') },
    { value: 'error', label: __('Error', 'debug-suite') },
    { value: 'warning', label: __('Warning', 'debug-suite') },
    { value: 'info', label: __('Info', 'debug-suite') },
    { value: 'debug', label: __('Debug', 'debug-suite') }
];

const FileLogs = () => {
    const [selectedLevel, setSelectedLevel] = useState<string>('all');

    const [logs, setLogs] = useState<LogEntry[]>([]);
    const [loading, setLoading] = useState(true);

    const fetchLogs = async () => {
        try {
            setLoading(true);
            const response = await apiFetch<{
                entries: LogEntry[];
            }>({
                path: '/debug-suite/v1/logs'
            });
            setLogs(response.entries);
        } catch (error) {
            console.error('Error fetching logs:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchLogs();
    }, []);

    const filteredLogs = selectedLevel === 'all' ? logs : logs.filter((log) => log.level === selectedLevel);

    if (loading) {
        return <FileLogsSkeleton />;
    }

    return (
        <>
            <p className="mb-6 text-gray-600">
                {__('Welcome to the Error Logs section. Choose an option below to get started.', 'debug-suite')}
            </p>

            <div className="mb-4 flex justify-between">
                <div className="flex items-center gap-3">
                    <SearchableSelect
                        options={labels}
                        value={labels.find((opt) => opt.value === selectedLevel) || labels[0]}
                        onChange={(option) => setSelectedLevel(option?.value || 'administrator')}
                    />

                    <Button variant="light">{__('Clear Logs', 'debug-suite')}</Button>
                    <Button variant="light" onClick={fetchLogs}>
                        <RefreshCwIcon className="mr-2 inline-block h-4 w-4" />
                        {__('Refresh', 'debug-suite')}
                    </Button>
                </div>

                <div className="flex gap-3">
                    <Link
                        to="/"
                        className="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 font-medium text-gray-800 transition-colors hover:bg-gray-200"
                    >
                        {'\u2190'} {__('Back to Overview', 'debug-suite')}
                    </Link>
                    <Link
                        to="/file-logs/manage"
                        className="bg-primary-50 text-primary-700 hover:bg-primary-100 inline-flex items-center gap-2 rounded-lg px-4 py-2 font-medium transition-colors"
                    >
                        {'\ud83d\udcc4'} {__('Manage File Logs', 'debug-suite')}
                    </Link>
                </div>
            </div>

            <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">
                                {__('Timestamp', 'debug-suite')}
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">
                                {__('Level', 'debug-suite')}
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">
                                {__('Message', 'debug-suite')}
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">
                                {__('File', 'debug-suite')}
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {filteredLogs.length === 0 ? (
                            <tr>
                                <td colSpan={4} className="py-8 text-center text-sm text-gray-400">
                                    {__('No logs found for the selected level.', 'debug-suite')}
                                </td>
                            </tr>
                        ) : (
                            filteredLogs.map((log) => (
                                <Disclosure key={log.id}>
                                    {(args: { open: boolean }) => {
                                        const { open } = args;
                                        return (
                                            <>
                                                <tr
                                                    className={classNames(
                                                        'transition-colors hover:bg-gray-50',
                                                        open && 'bg-gray-50'
                                                    )}
                                                >
                                                    <td className="px-4 py-2 text-sm whitespace-nowrap text-gray-900">
                                                        {log.timestamp}
                                                    </td>
                                                    <td className="px-4 py-2 whitespace-nowrap">
                                                        <span
                                                            className={`inline-block rounded px-2 py-0.5 text-xs font-semibold ${levelColors[log.level]}`}
                                                        >
                                                            {log.level.toUpperCase()}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-2 text-sm text-gray-800">
                                                        <DisclosureButton className="w-full text-left focus:outline-none">
                                                            {log.message}
                                                            {log.trace && (
                                                                <span className="text-primary-500 ml-2 text-xs">
                                                                    {open
                                                                        ? __('Hide Trace', 'debug-suite')
                                                                        : __('Show Trace', 'debug-suite')}
                                                                </span>
                                                            )}
                                                        </DisclosureButton>
                                                    </td>
                                                    <td className="px-4 py-2 text-sm text-gray-600">
                                                        {log.file && (
                                                            <span>
                                                                <code className="rounded bg-gray-100 px-1 text-xs">
                                                                    {log.file}
                                                                </code>
                                                                {log.line && (
                                                                    <span className="ml-1 text-gray-400">
                                                                        :{log.line}
                                                                    </span>
                                                                )}
                                                            </span>
                                                        )}
                                                    </td>
                                                </tr>
                                                {log.trace && (
                                                    <DisclosurePanel as="tr">
                                                        <td
                                                            colSpan={4}
                                                            className="border-t border-gray-100 bg-gray-50 px-6 py-4 text-xs text-gray-700"
                                                        >
                                                            <pre className="font-mono text-xs break-all whitespace-pre-wrap text-gray-700 dark:text-gray-200">
                                                                {log.trace}
                                                            </pre>
                                                        </td>
                                                    </DisclosurePanel>
                                                )}
                                            </>
                                        );
                                    }}
                                </Disclosure>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </>
    );
};

export default FileLogs;
