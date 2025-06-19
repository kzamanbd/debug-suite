/**
 * LogViewer component - Consolidated log table and entry display.
 *
 * @since 1.0.0
 */
import { classNames } from '@/utils';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/react';
import { __ } from '@wordpress/i18n';
import { RefreshCwIcon } from 'lucide-react';
import { levelColors, levelIcons } from '../constants';
import type { LogEntry } from '../types';

interface LogViewerProps {
    logs: LogEntry[];
    loading: boolean;
    currentPage: number;
    perPage: number;
}

const LogViewer = ({ logs, loading, currentPage, perPage }: LogViewerProps) => {
    return (
        <div className="flex-1 overflow-y-auto bg-white">
            <table className="min-w-full divide-y divide-gray-200">
                <thead className="sticky top-0 bg-gray-50">
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
                        logs.map((log, index) => {
                            const entryNumber = (currentPage - 1) * perPage + index + 1;

                            return (
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
                                                        <span className="mr-1">{levelIcons[log.level] || '📝'}</span>
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
                                                    {entryNumber}
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
                            );
                        })
                    )}
                </tbody>
            </table>
        </div>
    );
};

export default LogViewer;
