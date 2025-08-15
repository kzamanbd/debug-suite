/**
 * Email Limport { RefreshCw, Search } from 'lucide-react';
import { useEmailLogAPI } from '../hooks';
import type { BulkAction, EmailEntry, EmailFilters } from '../types';ontrols - Filter and action controls component.
 *
 * @since 1.0.0
 */
import type { PaginationInfo } from '@/components/base';
import Button from '@/components/base/button';
import type { Option } from '@/components/base/select';
import SimpleSelect from '@/components/base/select';
import TextInput from '@/components/base/text-input';
import { useConfirm } from '@/hooks/use-confirm';
import { Fill } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { RefreshCw, Search } from 'lucide-react';
import useEmailLogAPI from '../hooks/use-api';
import type { BulkAction, EmailLogFilters, EmailLogStats } from '../types';

interface EmailLogControlsProps {
    filters: EmailLogFilters;
    onFiltersChange: (filters: Partial<EmailLogFilters>) => void;
    onRefresh: () => void;
    onBulkAction: (action: BulkAction) => void;
    loading?: boolean;
    selectedItems: number[];
    paginationInfo: PaginationInfo;
    emailStats: EmailLogStats;
}

const EmailLogControls = ({
    filters,
    onFiltersChange,
    onRefresh,
    onBulkAction,
    loading = false,
    selectedItems,
    paginationInfo,
    emailStats
}: EmailLogControlsProps) => {
    const confirm = useConfirm();
    const { fetchFilterOptions, clearAllEmails } = useEmailLogAPI();
    const [filterOptions, setFilterOptions] = useState<{
        receivers: Array<{ value: string; label: string }>;
        statuses: Array<{ value: string; label: string }>;
    }>({
        receivers: [],
        statuses: []
    });
    const [selectedBulkAction, setSelectedBulkAction] = useState<Option | null>(null);

    // Fetch filter options on component mount
    useEffect(() => {
        const loadFilterOptions = async () => {
            try {
                const options = await fetchFilterOptions();
                setFilterOptions(options);
            } catch (error) {
                console.error('Failed to fetch filter options:', error);
            }
        };
        void loadFilterOptions();
    }, [fetchFilterOptions]);

    // Filter options
    const statusOptions = [
        { value: 'all', label: __('All', 'debug-suite') },
        { value: 'success', label: __('Successful', 'debug-suite') },
        { value: 'failed', label: __('Failed', 'debug-suite') }
    ];

    const receiverOptions = [{ value: '', label: __('All Receivers', 'debug-suite') }, ...filterOptions.receivers];

    const bulkActionOptions = [
        { value: '', label: __('Bulk actions', 'debug-suite') },
        { value: 'delete', label: __('Delete', 'debug-suite') },
        { value: 'resend', label: __('Resend', 'debug-suite') }
    ];

    const handleBulkActionChange = (option: { value: string; label: string } | null) => {
        setSelectedBulkAction(option);
    };

    const handleReceiverChange = (option: { value: string; label: string } | null) => {
        onFiltersChange({ receiver: option?.value || '' });
    };

    const handleStatusChange = (option: { value: string; label: string } | null) => {
        onFiltersChange({ status: (option?.value || 'all') as EmailLogFilters['status'] });
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

    const clearEmail = async () => {
        try {
            if (
                !(await confirm(
                    __('Are you sure you want to clear all email logs? This action cannot be undone.', 'debug-suite')
                ))
            ) {
                return;
            }
            await clearAllEmails();
            onRefresh();
        } catch (error) {
            console.error('Failed to clear all emails:', error);
        }
    };

    const statusCounts = [
        {
            key: 'all',
            label: __('All', 'debug-suite'),
            count: emailStats.total_emails,
            color: 'blue'
        },
        {
            key: 'success',
            label: __('Successful', 'debug-suite'),
            count: emailStats.successful,
            color: 'green'
        },
        {
            key: 'failed',
            label: __('Failed', 'debug-suite'),
            count: emailStats.failed,
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
                                            onClick={() =>
                                                onFiltersChange({ status: status.key as EmailLogFilters['status'] })
                                            }>
                                            {status.label} ({status.count.toLocaleString()})
                                        </button>
                                    )}
                                </div>
                            );
                        })}
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

                {/* Receiver Filter */}
                <SimpleSelect
                    searchable
                    options={receiverOptions}
                    value={receiverOptions.find((opt) => opt.value === filters.receiver) || null}
                    onChange={handleReceiverChange}
                    placeholder={__('Receiver', 'debug-suite')}
                    className="w-48"
                />

                {/* Status Filter */}
                <SimpleSelect
                    options={statusOptions}
                    value={statusOptions.find((opt) => opt.value === filters.status) || null}
                    onChange={handleStatusChange}
                    placeholder={__('Status', 'debug-suite')}
                    className="w-40"
                />

                {/* Search Input */}
                <div className="relative max-w-sm min-w-64 flex-1">
                    <div className="absolute inset-y-0 left-0 flex items-center pl-3">
                        <Search className="h-4 w-4 text-gray-400" />
                    </div>
                    <TextInput
                        type="text"
                        placeholder={__('Search...', 'debug-suite')}
                        value={filters.search}
                        onChange={(e) => onFiltersChange({ search: e.target.value })}
                        className="pl-10"
                    />
                </div>

                {/* Refresh Button */}
                <Fill name="debug-suite-layout-header-right">
                    <div className="flex items-center space-x-2">
                        <Button size="md" onClick={clearEmail}>
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

export default EmailLogControls;
