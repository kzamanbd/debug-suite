/**
 * API Log Controls - Filter and action controls component.
 *
 * @since 1.2.0
 */
import type { PaginationInfo } from '@/components/base';
import Button from '@/components/base/button';
import type { Option } from '@/components/base/select';
import SimpleSelect from '@/components/base/select';
import TextInput from '@/components/base/text-input';
import { useConfirm } from '@/hooks/use-confirm';
import { Fill } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { RefreshCw, Search } from 'lucide-react';
import useApiLogAPI from '../hooks/use-api';
import type { ApiLogFilters, ApiLogStats, BulkAction } from '../types';

interface ApiLogControlsProps {
    filters: ApiLogFilters;
    onFiltersChange: (filters: Partial<ApiLogFilters>) => void;
    onRefresh: () => void;
    onBulkAction: (action: BulkAction) => void;
    loading?: boolean;
    selectedItems: number[];
    paginationInfo: PaginationInfo;
    apiStats: ApiLogStats;
    filterOptions: {
        routes: Array<{ value: string; label: string }>;
        methods: Array<{ value: string; label: string }>;
        statuses: Array<{ value: string; label: string }>;
    };
}

const ApiLogControls = ({
    filters,
    onFiltersChange,
    onRefresh,
    onBulkAction,
    loading = false,
    selectedItems,
    paginationInfo,
    apiStats,
    filterOptions
}: ApiLogControlsProps) => {
    const confirm = useConfirm();
    const { clearAllLogs } = useApiLogAPI();
    const [selectedBulkAction, setSelectedBulkAction] = useState<Option | null>(null);

    const methodOptions = filterOptions.methods.length > 0
        ? filterOptions.methods
        : [
            { value: 'all', label: __('All Methods', 'debug-suite') },
            { value: 'GET', label: 'GET' },
            { value: 'POST', label: 'POST' },
            { value: 'PUT', label: 'PUT' },
            { value: 'DELETE', label: 'DELETE' },
            { value: 'PATCH', label: 'PATCH' }
        ];

    const statusOptions = filterOptions.statuses.length > 0
        ? filterOptions.statuses
        : [
            { value: 'all', label: __('All Statuses', 'debug-suite') },
            { value: 'success', label: __('2xx Success', 'debug-suite') },
            { value: 'redirect', label: __('3xx Redirect', 'debug-suite') },
            { value: 'client_error', label: __('4xx Client Error', 'debug-suite') },
            { value: 'server_error', label: __('5xx Server Error', 'debug-suite') }
        ];

    const bulkActionOptions = [
        { value: '', label: __('Bulk actions', 'debug-suite') },
        { value: 'delete', label: __('Delete', 'debug-suite') }
    ];

    const handleBulkActionChange = (option: { value: string; label: string } | null) => {
        setSelectedBulkAction(option);
    };

    const handleMethodChange = (option: { value: string; label: string } | null) => {
        onFiltersChange({ method: option?.value || 'all' });
    };

    const handleStatusChange = (option: { value: string; label: string } | null) => {
        onFiltersChange({ status: option?.value || 'all' });
    };

    const applyChanges = async () => {
        if (!(await confirm(__('Are you sure you want to apply the selected bulk action?', 'debug-suite')))) {
            return;
        }
        if (selectedBulkAction?.value && selectedItems.length > 0) {
            onBulkAction({
                action: selectedBulkAction.value as BulkAction['action'],
                selected_ids: selectedItems
            });
        }
    };

    const clearLogs = async () => {
        try {
            if (
                !(await confirm(
                    __('Are you sure you want to clear all API logs? This action cannot be undone.', 'debug-suite')
                ))
            ) {
                return;
            }
            await clearAllLogs();
            onRefresh();
        } catch (error) {
            console.error('Failed to clear all API logs:', error);
        }
    };

    const statusCounts = [
        {
            key: 'all',
            label: __('All', 'debug-suite'),
            count: apiStats.total_requests,
            color: 'blue'
        },
        {
            key: 'success',
            label: __('Success', 'debug-suite'),
            count: apiStats.successful,
            color: 'green'
        },
        {
            key: 'client_error',
            label: __('Failed', 'debug-suite'),
            count: apiStats.failed,
            color: 'red'
        }
    ];

    return (
        <div className="mb-6 space-y-4">
            {/* Status and Count Info */}
            <div className="flex items-center justify-between">
                <div className="flex items-center space-x-4">
                    <div className="flex items-center text-sm text-gray-600">
                        {statusCounts.map((status, index) => {
                            const isActive = filters.status === status.key;
                            const baseClasses = `font-medium text-${status.color}-600`;
                            const buttonClasses = `${baseClasses} underline-offset-2 hover:text-${status.color}-800 hover:underline`;

                            return (
                                <div key={status.key} className="flex items-center">
                                    {index > 0 && <span className="mx-2 text-gray-400">|</span>}
                                    {isActive ? (
                                        <span className={baseClasses}>
                                            {status.label} ({status.count.toLocaleString()})
                                        </span>
                                    ) : (
                                        <button
                                            type="button"
                                            className={buttonClasses}
                                            onClick={() => onFiltersChange({ status: status.key })}>
                                            {status.label} ({status.count.toLocaleString()})
                                        </button>
                                    )}
                                </div>
                            );
                        })}
                        {apiStats.avg_duration > 0 && (
                            <>
                                <span className="mx-2 text-gray-400">|</span>
                                <span className="text-sm text-gray-500">
                                    {__('Avg:', 'debug-suite')} {apiStats.avg_duration.toFixed(1)}ms
                                </span>
                            </>
                        )}
                    </div>
                </div>
                <div className="text-sm text-gray-500">
                    {paginationInfo.total_items.toLocaleString()} {__('items', 'debug-suite')}
                </div>
            </div>

            {/* Filter Controls */}
            <div className="flex flex-wrap items-center gap-4">
                {/* Bulk Actions */}
                <div className="flex items-center space-x-2">
                    <SimpleSelect
                        options={bulkActionOptions}
                        value={selectedBulkAction}
                        onChange={handleBulkActionChange}
                        placeholder={__('Bulk actions', 'debug-suite')}
                        className="w-40"
                        isDisabled={selectedItems.length === 0}
                    />
                    <Button variant="default" size="md" disabled={selectedItems.length === 0} onClick={applyChanges}>
                        {__('Apply', 'debug-suite')}
                    </Button>
                </div>

                {/* Method Filter */}
                <SimpleSelect
                    options={methodOptions}
                    value={methodOptions.find((opt) => opt.value === filters.method) || null}
                    onChange={handleMethodChange}
                    placeholder={__('Method', 'debug-suite')}
                    className="w-36"
                />

                {/* Status Filter */}
                <SimpleSelect
                    options={statusOptions}
                    value={statusOptions.find((opt) => opt.value === filters.status) || null}
                    onChange={handleStatusChange}
                    placeholder={__('Status', 'debug-suite')}
                    className="w-44"
                />

                {/* Search Input */}
                <div className="relative max-w-sm min-w-64 flex-1">
                    <div className="absolute inset-y-0 left-0 flex items-center pl-3">
                        <Search className="h-4 w-4 text-gray-400" />
                    </div>
                    <TextInput
                        type="text"
                        placeholder={__('Search routes...', 'debug-suite')}
                        value={filters.search}
                        onChange={(e) => onFiltersChange({ search: e.target.value })}
                        className="pl-10"
                    />
                </div>

                {/* Refresh Button */}
                <Fill name="debug-suite-layout-header-right">
                    <div className="flex items-center space-x-2">
                        <Button size="md" onClick={clearLogs}>
                            {__('Clear All', 'debug-suite')}
                        </Button>
                        <Button
                            variant="default"
                            loading={loading}
                            onClick={onRefresh}
                            icon={<RefreshCw className="h-4 w-4" />}>
                            {__('Refresh', 'debug-suite')}
                        </Button>
                    </div>
                </Fill>
            </div>
        </div>
    );
};

export default ApiLogControls;
