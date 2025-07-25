/**
 * Email Log Viewer - Table display component for email logs.
 *
 * @since 1.0.0
 */
import Checkbox from '@/components/base/checkbox';
import { classNames } from '@/utils';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Eye, RotateCcw, Trash2 } from 'lucide-react';
import type { EmailLogEntry, EmailLogFilters, PaginationInfo } from '../types';

interface EmailLogViewerProps {
    entries: EmailLogEntry[];
    filters: EmailLogFilters;
    onFiltersChange: (filters: Partial<EmailLogFilters>) => void;
    loading?: boolean;
    selectedItems: number[];
    onSelectAll: (selected: boolean) => void;
    onSelectItem: (id: number, selected: boolean) => void;
    onItemAction: (action: 'view' | 'resend' | 'delete', entry: EmailLogEntry) => void;
    paginationInfo: PaginationInfo;
    onPageChange: (page: number) => void;
}

const EmailLogViewer = ({
    entries,
    onFiltersChange,
    loading = false,
    selectedItems,
    onSelectAll,
    onSelectItem,
    onItemAction,
    paginationInfo,
    onPageChange
}: EmailLogViewerProps) => {
    const [sortConfig, setSortConfig] = useState<{
        key: keyof EmailLogEntry;
        direction: 'asc' | 'desc';
    }>({
        key: 'time',
        direction: 'desc'
    });

    const isAllSelected = entries.length > 0 && selectedItems.length === entries.length;

    const handleSort = (key: keyof EmailLogEntry) => {
        const direction = sortConfig.key === key && sortConfig.direction === 'asc' ? 'desc' : 'asc';
        setSortConfig({ key, direction });
        onFiltersChange({
            sortBy: key as string,
            sortOrder: direction
        });
    };

    const handleSelectAll = () => {
        onSelectAll(!isAllSelected);
    };

    const formatTime = (timeString: string) => {
        try {
            const date = new Date(timeString);
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        } catch {
            return timeString;
        }
    };

    const renderPagination = () => {
        const { current_page, total_pages, from, to, total_items } = paginationInfo;

        if (total_pages <= 1) return null;

        const pages = [];
        const showPages = 5;
        let startPage = Math.max(1, current_page - Math.floor(showPages / 2));
        const endPage = Math.min(total_pages, startPage + showPages - 1);

        if (endPage - startPage + 1 < showPages) {
            startPage = Math.max(1, endPage - showPages + 1);
        }

        // First page and ellipsis
        if (startPage > 1) {
            pages.push(
                <button
                    key={1}
                    onClick={() => onPageChange(1)}
                    className="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50">
                    1
                </button>
            );
            if (startPage > 2) {
                pages.push(
                    <span
                        key="ellipsis-start"
                        className="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700">
                        ...
                    </span>
                );
            }
        }

        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            pages.push(
                <button
                    key={i}
                    onClick={() => onPageChange(i)}
                    className={classNames(
                        'relative inline-flex items-center border px-4 py-2 text-sm font-medium',
                        current_page === i
                            ? 'bg-primary border-primary z-10 text-white'
                            : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50'
                    )}>
                    {i}
                </button>
            );
        }

        // Last page and ellipsis
        if (endPage < total_pages) {
            if (endPage < total_pages - 1) {
                pages.push(
                    <span
                        key="ellipsis-end"
                        className="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700">
                        ...
                    </span>
                );
            }
            pages.push(
                <button
                    key={total_pages}
                    onClick={() => onPageChange(total_pages)}
                    className="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50">
                    {total_pages}
                </button>
            );
        }

        return (
            <div className="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                <div className="flex flex-1 justify-between sm:hidden">
                    <button
                        onClick={() => current_page > 1 && onPageChange(current_page - 1)}
                        disabled={current_page <= 1}
                        className="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">
                        {__('Previous', 'debug-suite')}
                    </button>
                    <button
                        onClick={() => current_page < total_pages && onPageChange(current_page + 1)}
                        disabled={current_page >= total_pages}
                        className="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">
                        {__('Next', 'debug-suite')}
                    </button>
                </div>
                <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p className="text-sm text-gray-700">
                            {__('Showing', 'debug-suite')} <span className="font-medium">{from}</span>{' '}
                            {__('to', 'debug-suite')} <span className="font-medium">{to}</span>{' '}
                            {__('of', 'debug-suite')}{' '}
                            <span className="font-medium">{total_items.toLocaleString()}</span>{' '}
                            {__('results', 'debug-suite')}
                        </p>
                    </div>
                    <div>
                        <nav
                            className="relative z-0 inline-flex -space-x-px rounded-md shadow-sm"
                            aria-label="Pagination">
                            <button
                                onClick={() => current_page > 1 && onPageChange(current_page - 1)}
                                disabled={current_page <= 1}
                                className="relative inline-flex items-center rounded-l-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">
                                <span className="sr-only">{__('Previous', 'debug-suite')}</span>
                                <svg
                                    className="h-5 w-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    aria-hidden="true">
                                    <path
                                        fillRule="evenodd"
                                        d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                        clipRule="evenodd"
                                    />
                                </svg>
                            </button>
                            {pages}
                            <button
                                onClick={() => current_page < total_pages && onPageChange(current_page + 1)}
                                disabled={current_page >= total_pages}
                                className="relative inline-flex items-center rounded-r-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">
                                <span className="sr-only">{__('Next', 'debug-suite')}</span>
                                <svg
                                    className="h-5 w-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    aria-hidden="true">
                                    <path
                                        fillRule="evenodd"
                                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                        clipRule="evenodd"
                                    />
                                </svg>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        );
    };

    if (loading && entries.length === 0) {
        return (
            <div className="flex-1 rounded-lg border border-gray-200 bg-white">
                <div className="animate-pulse">
                    <div className="border-b border-gray-200 px-6 py-4">
                        <div className="h-4 w-1/4 rounded bg-gray-200"></div>
                    </div>
                    {Array.from({ length: 10 }, (_, i) => (
                        <div key={i} className="border-b border-gray-100 px-6 py-4">
                            <div className="flex items-center space-x-4">
                                <div className="h-4 w-4 rounded bg-gray-200"></div>
                                <div className="h-4 w-32 rounded bg-gray-200"></div>
                                <div className="h-4 w-48 rounded bg-gray-200"></div>
                                <div className="h-4 flex-1 rounded bg-gray-200"></div>
                                <div className="h-4 w-16 rounded bg-gray-200"></div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        );
    }

    return (
        <div className="flex flex-1 flex-col overflow-hidden rounded-lg border bg-white">
            <table className="min-w-full table-fixed divide-y divide-gray-200">
                <thead className="bg-gray-50">
                    <tr>
                        <th className="w-8 px-2 py-3 text-left">
                            <Checkbox checked={isAllSelected} onChange={handleSelectAll} />
                        </th>
                        <th
                            className="w-36 cursor-pointer px-2 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase hover:bg-gray-100"
                            onClick={() => handleSort('time')}>
                            <div className="flex items-center space-x-1">
                                <span>{__('Time', 'debug-suite')}</span>
                                {sortConfig.key === 'time' && (
                                    <span className="text-primary">{sortConfig.direction === 'asc' ? '↑' : '↓'}</span>
                                )}
                            </div>
                        </th>
                        <th
                            className="w-48 cursor-pointer px-2 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase hover:bg-gray-100"
                            onClick={() => handleSort('receiver')}>
                            <div className="flex items-center space-x-1">
                                <span>{__('Receiver', 'debug-suite')}</span>
                                {sortConfig.key === 'receiver' && (
                                    <span className="text-primary">{sortConfig.direction === 'asc' ? '↑' : '↓'}</span>
                                )}
                            </div>
                        </th>
                        <th className="px-2 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                            {__('Subject', 'debug-suite')}
                        </th>
                        <th className="w-16 px-2 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                            {__('Status', 'debug-suite')}
                        </th>
                        <th className="w-24 px-2 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">
                            {__('Actions', 'debug-suite')}
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                    {entries.length === 0 ? (
                        <tr>
                            <td colSpan={6} className="px-6 py-12 text-center text-gray-500">
                                {loading ? (
                                    <div className="flex items-center justify-center">
                                        <span className="mr-2">{__('Loading emails...', 'debug-suite')}</span>
                                    </div>
                                ) : (
                                    __('No email entries found matching your criteria.', 'debug-suite')
                                )}
                            </td>
                        </tr>
                    ) : (
                        entries.map((entry) => (
                            <tr key={entry.id} className="transition-colors hover:bg-gray-50">
                                <td className="px-2 py-3">
                                    <Checkbox
                                        checked={selectedItems.indexOf(entry.id) !== -1}
                                        onChange={(e) => onSelectItem(entry.id, e.target.checked)}
                                    />
                                </td>
                                <td className="px-2 py-3 text-sm whitespace-nowrap text-gray-900">
                                    {formatTime(entry.time)}
                                </td>
                                <td className="px-2 py-3 text-sm text-gray-900">
                                    <div className="truncate" title={entry.receiver}>
                                        {entry.receiver}
                                    </div>
                                </td>
                                <td className="w-full max-w-0 px-2 py-3 text-sm text-gray-900">
                                    <div className="truncate" title={entry.subject}>
                                        {entry.subject}
                                    </div>
                                </td>
                                <td className="px-2 py-3 whitespace-nowrap">
                                    {entry.error ? (
                                        <span className="inline-flex items-center rounded-full border border-red-200 bg-red-100 px-2 py-px text-xs font-medium text-red-800">
                                            {__('Error', 'debug-suite')}
                                        </span>
                                    ) : (
                                        <span className="inline-flex items-center rounded-full border border-green-200 bg-green-100 px-2 py-px text-xs font-medium text-green-800">
                                            {__('Success', 'debug-suite')}
                                        </span>
                                    )}
                                </td>
                                <td className="px-2 py-3 text-right">
                                    <div className="flex items-center justify-end gap-1">
                                        <button
                                            onClick={() => onItemAction('view', entry)}
                                            className="hover:text-primary-600 p-1 text-gray-400"
                                            title={__('View', 'debug-suite')}>
                                            <Eye className="h-4 w-4" />
                                        </button>
                                        <button
                                            onClick={() => onItemAction('resend', entry)}
                                            className="p-1 text-gray-400 hover:text-blue-600"
                                            title={__('Resend', 'debug-suite')}>
                                            <RotateCcw className="h-4 w-4" />
                                        </button>
                                        <button
                                            onClick={() => onItemAction('delete', entry)}
                                            className="p-1 text-gray-400 hover:text-red-600"
                                            title={__('Delete', 'debug-suite')}>
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))
                    )}
                </tbody>
            </table>

            {/* Pagination */}
            {renderPagination()}
        </div>
    );
};

export default EmailLogViewer;
