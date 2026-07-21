/**
 * LogViewer component - Consolidated log table and entry display with infinite scroll.
 *
 * @since 1.0.0
 */
import { Button, DateTimeHtml } from '@/components/ui';
import { classNames } from '@/utils';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/react';
import { useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    Bug,
    CheckIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    ClipboardIcon,
    Info,
    LoaderCircle,
    OctagonAlert,
    RefreshCwIcon,
    TriangleAlert
} from 'lucide-react';
import type { ReactNode } from 'react';
import { levelColors } from '../constants';
import type { InfiniteScrollState, LogEntry, LogFilters } from '../types';

interface LogViewerProps {
    logs: LogEntry[];
    filters: LogFilters;
    loading: boolean;
    infiniteState: InfiniteScrollState;
    onLoadMore: () => void;

    onFiltersChange: (newFilters: Partial<LogFilters>) => void;
}

const levelIcons: Record<string, ReactNode> = {
    critical: <TriangleAlert className="size-3" />,
    error: <TriangleAlert className="size-3" />,
    warning: <TriangleAlert className="size-3" />,
    notice: <OctagonAlert className="size-3" />,
    info: <Info className="size-3" />,
    debug: <Bug className="size-3" />
};

const LogViewer = ({ logs, filters, loading, infiniteState, onFiltersChange, onLoadMore }: LogViewerProps) => {
    const tableRef = useRef<HTMLTableElement>(null);
    const [copiedId, setCopiedId] = useState<string | number | null>(null);

    const toggleSortOrder = () => {
        const newOrder = filters.sortOrder === 'asc' ? 'desc' : 'asc';
        onFiltersChange({ sortOrder: newOrder });
    };

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
        <div className="flex flex-1 flex-col rounded-lg border bg-white">
            <table ref={tableRef} className="min-w-full table-fixed divide-y divide-gray-200">
                <thead className="bg-gray-50">
                    <tr>
                        <th className="w-40 px-2 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                            {__('Date/Time', 'debug-suite')}
                        </th>
                        <th className="w-24 px-2 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                            {__('Severity', 'debug-suite')}
                        </th>
                        <th className="px-2 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                            {__('Message', 'debug-suite')}
                        </th>
                        <th className="w-20 px-2 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">
                            <div className="flex items-center justify-end gap-2">
                                <span className="inline-flex cursor-pointer" onClick={toggleSortOrder}>
                                    {filters.sortOrder === 'asc' ? (
                                        <ChevronUpIcon className="h-4 w-4" />
                                    ) : (
                                        <ChevronDownIcon className="h-4 w-4" />
                                    )}
                                </span>
                                <span>{__('#', 'debug-suite')}</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                    {logs.length === 0 ? (
                        <tr>
                            <td colSpan={4} className="h-[500px] px-6 py-12 text-center text-gray-500">
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
                            const onlyDumped = log.raw_line && !log.file_path && !log.line;
                            const showDisclosure = log.stack_trace || onlyDumped;
                            const showText = onlyDumped
                                ? __('Show Dump', 'debug-suite')
                                : __('Show Trace', 'debug-suite');
                            const hideText = onlyDumped
                                ? __('Hide Dump', 'debug-suite')
                                : __('Hide Trace', 'debug-suite');

                            return (
                                <Disclosure key={index}>
                                    {({ open }) => (
                                        <>
                                            <tr
                                                className={classNames(
                                                    'transition-colors hover:bg-gray-50',
                                                    open && 'bg-gray-50'
                                                )}>
                                                <td className="p-2 text-sm whitespace-nowrap text-gray-900">
                                                    <DateTimeHtml date={log.timestamp} />
                                                </td>
                                                <td className="p-2 whitespace-nowrap">
                                                    <span
                                                        className={classNames(
                                                            'inline-flex items-center rounded-full border px-2 py-px text-xs font-medium',
                                                            levelColors[log.level] || levelColors.debug
                                                        )}>
                                                        <span className="mr-1">
                                                            {levelIcons[log.level] || <Bug className="size-3" />}
                                                        </span>
                                                        {log.level.charAt(0).toUpperCase() + log.level.slice(1)}
                                                    </span>
                                                </td>
                                                <td className="w-full max-w-0 p-2 text-sm text-gray-900">
                                                    <div className="w-full text-left focus:outline-none">
                                                        <div className="line-clamp-3 flex-1 overflow-hidden pr-4 break-words">
                                                            {log.message}
                                                        </div>
                                                        {log.file_path && (
                                                            <div className="mt-1 text-xs text-gray-500">
                                                                <code className="rounded bg-gray-100 px-1 break-all">
                                                                    {log.file_path}
                                                                </code>
                                                                {log.line && <span>:{log.line}</span>}
                                                            </div>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="w-20 p-2 text-right text-sm whitespace-nowrap text-gray-500">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <div className="flex items-center gap-1">
                                                            {showDisclosure && (
                                                                <DisclosureButton className="hover:text-primary-600 p-1 text-gray-400 focus:outline-none">
                                                                    <span className="text-primary-600 text-xs">
                                                                        {open ? hideText : showText}
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
                                                                title={__('Copy message', 'debug-suite')}>
                                                                {copiedId === index ? (
                                                                    <CheckIcon className="h-4 w-4 text-green-500" />
                                                                ) : (
                                                                    <ClipboardIcon className="h-4 w-4" />
                                                                )}
                                                            </div>
                                                        </div>
                                                        <span>{entryNumber}</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            {showDisclosure && (
                                                <DisclosurePanel as="tr">
                                                    <td colSpan={4} className="border-t border-gray-100 bg-gray-50 p-2">
                                                        <div className="overflow-x-auto rounded-lg bg-gray-900 p-4 text-gray-100">
                                                            <pre className="font-mono text-xs whitespace-pre-wrap">
                                                                {onlyDumped
                                                                    ? log.raw_line
                                                                    : JSON.stringify(log.stack_trace, null, 2)}
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
                <div className="border-t border-gray-200 bg-white p-2">
                    {infiniteState.hasMore ? (
                        <div className="flex items-center justify-center gap-4">
                            <Button variant="outline" disabled={infiniteState.isLoadingMore} onClick={onLoadMore}>
                                {infiniteState.isLoadingMore && <LoaderCircle className="size-4 animate-spin" />}
                                <span>{__('Load More Entries', 'debug-suite')}</span>
                            </Button>
                            <span className="text-xs text-gray-400">
                                {__('Showing', 'debug-suite')} {logs.length} {__('entries', 'debug-suite')}
                            </span>
                        </div>
                    ) : (
                        <div className="flex items-center justify-center">
                            <span className="text-sm text-gray-500">{__('All entries loaded', 'debug-suite')}</span>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
};

export default LogViewer;
