/**
 * LogViewer component - Consolidated log table and entry display with infinite scroll.
 *
 * @since 1.0.0
 */
import DateTimeHtml from '@/components/date-time';
import Button from '@/components/ui/button';
import { classNames } from '@/utils';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/react';
import { useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { CheckIcon, ChevronDownIcon, ClipboardIcon, RefreshCwIcon } from 'lucide-react';
import { levelColors, levelIcons } from '../constants';
import type { InfiniteScrollState, LogEntry } from '../types';

interface LogViewerProps {
    logs: LogEntry[];
    loading: boolean;
    infiniteState: InfiniteScrollState;
    onLoadMore: () => void;
}

const LogViewer = ({ logs, loading, infiniteState, onLoadMore }: LogViewerProps) => {
    const tableRef = useRef<HTMLTableElement>(null);
    const [copiedId, setCopiedId] = useState<string | number | null>(null);

    const handleCopy = async (text: string, id: string | number) => {
        try {
            await navigator.clipboard.writeText(text);
            setCopiedId(id);
            setTimeout(() => setCopiedId(null), 2000); // Reset after 2 seconds
        } catch (err) {
            console.error('Failed to copy text:', err);
        }
    };

    return (
        <div className="flex flex-1 flex-col overflow-hidden rounded-lg border bg-white">
            <div className="flex-1 overflow-y-auto">
                <table ref={tableRef} className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
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
                                const entryNumber = index + 1;

                                return (
                                    <Disclosure key={index}>
                                        {({ open }) => (
                                            <>
                                                <tr
                                                    className={classNames(
                                                        'transition-colors hover:bg-gray-50',
                                                        open && 'bg-gray-50'
                                                    )}
                                                >
                                                    <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                                        <DateTimeHtml date={log.timestamp} />
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
                                                        <div className="w-full text-left focus:outline-none">
                                                            <div className="flex items-center justify-between">
                                                                <span className="flex-1 pr-4">{log.message}</span>
                                                                <div className="flex items-center gap-2">
                                                                    {log.has_stack_trace && (
                                                                        <DisclosureButton className="hover:text-primary-600 p-1 text-gray-400 focus:outline-none">
                                                                            <span className="text-primary-600 text-xs">
                                                                                {open
                                                                                    ? __('Hide Trace', 'debug-suite')
                                                                                    : __('Show Trace', 'debug-suite')}
                                                                            </span>
                                                                        </DisclosureButton>
                                                                    )}
                                                                    <div
                                                                        role="button"
                                                                        tabIndex={0}
                                                                        onClick={(e) => {
                                                                            e.preventDefault();
                                                                            e.stopPropagation();
                                                                            void handleCopy(log.message, index);
                                                                        }}
                                                                        className="hover:text-primary-600 text-gray-400 focus:outline-none"
                                                                        title={__('Copy message', 'debug-suite')}
                                                                    >
                                                                        {copiedId === index ? (
                                                                            <CheckIcon className="h-4 w-4" />
                                                                        ) : (
                                                                            <ClipboardIcon className="h-4 w-4" />
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            {log.file && (
                                                                <div className="mt-1 text-xs text-gray-500">
                                                                    <code className="rounded bg-gray-100 px-1">
                                                                        {log.file}
                                                                    </code>
                                                                    {log.line && <span>:{log.line}</span>}
                                                                </div>
                                                            )}
                                                        </div>
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
                                                                    {JSON.stringify(log.stack_trace, null, 2)}
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

                {/* Load more section */}
                {logs.length > 0 && (
                    <div className="border-t border-gray-200 bg-white px-6 py-4">
                        {infiniteState.isLoadingMore ? (
                            <div className="flex items-center justify-center">
                                <RefreshCwIcon className="mr-2 h-4 w-4 animate-spin" />
                                <span className="text-sm text-gray-600">
                                    {__('Loading more entries...', 'debug-suite')}
                                </span>
                            </div>
                        ) : infiniteState.hasMore ? (
                            <div className="flex items-center justify-center">
                                <Button onClick={onLoadMore} className="flex items-center space-x-2">
                                    <ChevronDownIcon className="h-4 w-4" />
                                    <span>{__('Load More Entries', 'debug-suite')}</span>
                                </Button>
                            </div>
                        ) : (
                            <div className="flex items-center justify-center">
                                <span className="text-sm text-gray-500">{__('All entries loaded', 'debug-suite')}</span>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
};

export default LogViewer;
