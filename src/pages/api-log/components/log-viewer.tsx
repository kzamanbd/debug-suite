/**
 * API Log Viewer - Table display component for API logs.
 *
 * @since 1.2.0
 */
import type { PaginationInfo } from '@/components/base';
import { DateTimeHtml, Pagination } from '@/components/base';
import Checkbox from '@/components/base/checkbox';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Eye, Trash2 } from 'lucide-react';
import type { ApiLogEntry, ApiLogFilters } from '../types';

interface ApiLogViewerProps {
    entries: ApiLogEntry[];
    filters: ApiLogFilters;
    onFiltersChange: (filters: Partial<ApiLogFilters>) => void;
    loading?: boolean;
    selectedItems: number[];
    onSelectAll: (selected: boolean) => void;
    onSelectItem: (id: number, selected: boolean) => void;
    onItemAction: (action: 'view' | 'delete', entry: ApiLogEntry) => void;
    paginationInfo: PaginationInfo;
    onPageChange: (page: number) => void;
}

const methodColors: Record<string, string> = {
    GET: 'bg-green-100 text-green-800 border-green-200',
    POST: 'bg-blue-100 text-blue-800 border-blue-200',
    PUT: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    PATCH: 'bg-purple-100 text-purple-800 border-purple-200',
    DELETE: 'bg-red-100 text-red-800 border-red-200'
};

const getStatusColor = (status: number): string => {
    if (status >= 200 && status < 300) return 'bg-green-100 text-green-800 border-green-200';
    if (status >= 300 && status < 400) return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    if (status >= 400 && status < 500) return 'bg-orange-100 text-orange-800 border-orange-200';
    if (status >= 500) return 'bg-red-100 text-red-800 border-red-200';
    return 'bg-gray-100 text-gray-800 border-gray-200';
};

const ApiLogViewer = ({
    entries,
    onFiltersChange,
    loading = false,
    selectedItems,
    onSelectAll,
    onSelectItem,
    onItemAction,
    paginationInfo,
    onPageChange
}: ApiLogViewerProps) => {
    const [sortConfig, setSortConfig] = useState<{
        key: keyof ApiLogEntry;
        direction: 'asc' | 'desc';
    }>({
        key: 'created_at',
        direction: 'desc'
    });

    const isAllSelected = entries.length > 0 && selectedItems.length === entries.length;

    const handleSort = (key: keyof ApiLogEntry) => {
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
                                <div className="h-5 w-14 rounded bg-gray-200"></div>
                                <div className="h-4 w-48 rounded bg-gray-200"></div>
                                <div className="h-5 w-10 rounded bg-gray-200"></div>
                                <div className="h-4 w-16 rounded bg-gray-200"></div>
                                <div className="h-4 w-32 rounded bg-gray-200"></div>
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
                        <th className="w-20 px-2 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                            {__('Method', 'debug-suite')}
                        </th>
                        <th className="px-2 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                            {__('Route', 'debug-suite')}
                        </th>
                        <th
                            className="w-20 cursor-pointer px-2 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase hover:bg-gray-100"
                            onClick={() => handleSort('response_status')}>
                            <div className="flex items-center space-x-1">
                                <span>{__('Status', 'debug-suite')}</span>
                                {sortConfig.key === 'response_status' && (
                                    <span className="text-primary">{sortConfig.direction === 'asc' ? '↑' : '↓'}</span>
                                )}
                            </div>
                        </th>
                        <th
                            className="w-24 cursor-pointer px-2 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase hover:bg-gray-100"
                            onClick={() => handleSort('duration')}>
                            <div className="flex items-center space-x-1">
                                <span>{__('Duration', 'debug-suite')}</span>
                                {sortConfig.key === 'duration' && (
                                    <span className="text-primary">{sortConfig.direction === 'asc' ? '↑' : '↓'}</span>
                                )}
                            </div>
                        </th>
                        <th className="w-28 px-2 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                            {__('Source', 'debug-suite')}
                        </th>
                        <th
                            className="w-36 cursor-pointer px-2 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase hover:bg-gray-100"
                            onClick={() => handleSort('created_at')}>
                            <div className="flex items-center space-x-1">
                                <span>{__('Date', 'debug-suite')}</span>
                                {sortConfig.key === 'created_at' && (
                                    <span className="text-primary">{sortConfig.direction === 'asc' ? '↑' : '↓'}</span>
                                )}
                            </div>
                        </th>
                        <th className="w-16 px-2 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">
                            {__('Actions', 'debug-suite')}
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                    {entries.length === 0 ? (
                        <tr>
                            <td colSpan={8} className="px-6 py-12 text-center text-gray-500">
                                {loading ? (
                                    <div className="flex items-center justify-center">
                                        <span className="mr-2">{__('Loading API logs...', 'debug-suite')}</span>
                                    </div>
                                ) : (
                                    __('No API log entries found matching your criteria.', 'debug-suite')
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
                                <td className="px-2 py-3 whitespace-nowrap">
                                    <span
                                        className={`inline-flex items-center rounded border px-1.5 py-0.5 text-xs font-semibold ${methodColors[entry.method] || 'bg-gray-100 text-gray-800 border-gray-200'}`}>
                                        {entry.method}
                                    </span>
                                </td>
                                <td className="w-full max-w-0 px-2 py-3 text-sm text-gray-900">
                                    <div className="truncate font-mono text-xs" title={entry.route}>
                                        {entry.route}
                                    </div>
                                </td>
                                <td className="px-2 py-3 whitespace-nowrap">
                                    <span
                                        className={`inline-flex items-center rounded border px-1.5 py-0.5 text-xs font-medium ${getStatusColor(entry.response_status)}`}>
                                        {entry.response_status}
                                    </span>
                                </td>
                                <td className="px-2 py-3 text-sm whitespace-nowrap text-gray-600">
                                    {entry.duration.toFixed(1)}ms
                                </td>
                                <td className="px-2 py-3 text-xs whitespace-nowrap text-gray-500">
                                    <div className="max-w-[7rem] truncate" title={entry.source}>
                                        {entry.source}
                                    </div>
                                </td>
                                <td className="px-2 py-3 text-sm whitespace-nowrap text-gray-900">
                                    <DateTimeHtml date={entry.created_at} />
                                </td>
                                <td className="px-2 py-3 text-right">
                                    <div className="flex items-center justify-end gap-1">
                                        <button
                                            onClick={() => onItemAction('view', entry)}
                                            className="hover:text-primary-600 p-1 text-gray-400"
                                            title={__('View Details', 'debug-suite')}>
                                            <Eye className="size-4" />
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

export default ApiLogViewer;
