/**
 * Email Log Viewer - Table display component for email logs.
 *
 * @since 1.0.0
 */
import type { PaginationInfo } from '@/components/base';
import { DateTimeHtml, Pagination } from '@/components/base';
import Checkbox from '@/components/base/checkbox';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Eye, RotateCcw, Trash2 } from 'lucide-react';
import type { EmailLogEntry, EmailLogFilters } from '../types';

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
        key: 'sent_date',
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

    if (loading && entries.length === 0) {
        return (
            <div className="flex-1 rounded-lg border border-gray-200 bg-white">
                <div className="animate-pulse">
                    <div className="border-b border-gray-200 px-6 py-4">
                        <div className="h-4 w-1/4 rounded bg-gray-200"></div>
                    </div>
                    {Array.from({ length: 10 }, (_, i) => (
                        <div key={`skeleton-${i}`} className="border-b border-gray-100 px-6 py-4">
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
        <div className="flex flex-1 flex-col rounded-lg border bg-white">
            <table className="min-w-full table-fixed divide-y divide-gray-200">
                <thead className="bg-gray-50">
                    <tr>
                        <th className="w-8 px-2 py-3 text-left">
                            <Checkbox checked={isAllSelected} onChange={handleSelectAll} />
                        </th>
                        <th
                            className="w-36 cursor-pointer px-2 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase hover:bg-gray-100"
                            onClick={() => handleSort('sent_date')}>
                            <div className="flex items-center space-x-1">
                                <span>{__('Sent Date', 'debug-suite')}</span>
                                {sortConfig.key === 'sent_date' && (
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
                                    <DateTimeHtml date={entry.sent_date} />
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
                                            <Eye className="size-4" />
                                        </button>
                                        <button
                                            onClick={() => onItemAction('resend', entry)}
                                            className="p-1 text-gray-400 hover:text-blue-600"
                                            title={__('Resend', 'debug-suite')}>
                                            <RotateCcw className="size-4" />
                                        </button>
                                        <button
                                            onClick={() => onItemAction('delete', entry)}
                                            className="p-1 text-gray-400 hover:text-red-600"
                                            title={__('Delete', 'debug-suite')}>
                                            <Trash2 className="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))
                    )}
                </tbody>
            </table>

            {/* Pagination */}
            <Pagination paginationInfo={paginationInfo} onPageChange={onPageChange} />
        </div>
    );
};

export default EmailLogViewer;
