/**
 * Email Log Controls - Filter and action controls component.
 *
 * @since 1.0.0
 */
import Button from '@/components/base/button';
import SearchableSelect from '@/components/base/select';
import TextInput from '@/components/base/text-input';
import { Fill } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { RefreshCw, Search } from 'lucide-react';
import type { BulkAction, EmailLogFilters } from '../types';

interface EmailLogControlsProps {
    filters: EmailLogFilters;
    onFiltersChange: (filters: Partial<EmailLogFilters>) => void;
    onRefresh: () => void;
    onBulkAction: (action: BulkAction) => void;
    loading?: boolean;
    selectedItems: number[];
    totalItems: number;
    paginationInfo: {
        current_page: number;
        total_pages: number;
        total_items: number;
        per_page: number;
        from: number;
        to: number;
    };
}

const EmailLogControls = ({
    filters,
    onFiltersChange,
    onRefresh,
    onBulkAction,
    loading = false,
    selectedItems,
    totalItems,
    paginationInfo
}: EmailLogControlsProps) => {
    // Filter options
    const statusOptions = [
        { value: 'all', label: __('All', 'debug-suite') },
        { value: 'success', label: __('Successful', 'debug-suite') },
        { value: 'failed', label: __('Failed', 'debug-suite') }
    ];

    const receiverOptions = [
        { value: '', label: __('All Receivers', 'debug-suite') },
        { value: 'kzamanbn@gmail.com', label: 'kzamanbn@gmail.com' },
        { value: 'dummy_store1@dokan.com', label: 'dummy_store1@dokan.com' },
        { value: 'dummy_store2@dokan.com', label: 'dummy_store2@dokan.com' },
        { value: 'dummy_store3@dokan.com', label: 'dummy_store3@dokan.com' }
    ];

    const bulkActionOptions = [
        { value: '', label: __('Bulk actions', 'debug-suite') },
        { value: 'delete', label: __('Delete', 'debug-suite') }
    ];

    const handleBulkActionChange = (option: { value: string; label: string } | null) => {
        if (option?.value && selectedItems.length > 0) {
            onBulkAction({
                action: option.value as BulkAction['action'],
                selected_ids: selectedItems
            });
        }
    };

    const handleReceiverChange = (option: { value: string; label: string } | null) => {
        onFiltersChange({ receiver: option?.value || '' });
    };

    const handleStatusChange = (option: { value: string; label: string } | null) => {
        onFiltersChange({ status: (option?.value || 'all') as EmailLogFilters['status'] });
    };

    return (
        <div className="mb-6 space-y-4">
            {/* Status and Count Info */}
            <div className="flex items-center justify-between">
                <div className="flex items-center space-x-4">
                    <span className="text-sm text-gray-600">
                        {filters.status === 'all' && (
                            <>
                                <span className="font-medium text-blue-600">
                                    {__('All', 'debug-suite')} ({totalItems.toLocaleString()})
                                </span>
                                <span className="mx-2 text-gray-400">|</span>
                                <span className="font-medium text-green-600">
                                    {__('Successful', 'debug-suite')} ({totalItems.toLocaleString()})
                                </span>
                                <span className="mx-2 text-gray-400">|</span>
                                <span className="font-medium text-red-600">{__('Failed', 'debug-suite')} (0)</span>
                            </>
                        )}
                        {filters.status === 'success' && (
                            <span className="font-medium text-green-600">
                                {__('Successful', 'debug-suite')} ({totalItems.toLocaleString()})
                            </span>
                        )}
                        {filters.status === 'failed' && (
                            <span className="font-medium text-red-600">{__('Failed', 'debug-suite')} (0)</span>
                        )}
                    </span>
                </div>
                <div className="text-sm text-gray-500">
                    {paginationInfo.total_items.toLocaleString()} {__('items', 'debug-suite')}
                </div>
            </div>

            {/* Filter Controls */}
            <div className="flex flex-wrap items-center gap-4">
                {/* Bulk Actions */}
                <div className="flex items-center space-x-2">
                    <SearchableSelect
                        options={bulkActionOptions}
                        value={null}
                        onChange={handleBulkActionChange}
                        placeholder={__('Bulk actions', 'debug-suite')}
                        className="w-40"
                        isDisabled={selectedItems.length === 0}
                    />
                    <Button
                        variant="default"
                        size="md"
                        disabled={selectedItems.length === 0}
                        onClick={() => {
                            // This would be handled by the bulk action select
                        }}>
                        {__('Apply', 'debug-suite')}
                    </Button>
                </div>

                {/* Receiver Filter */}
                <SearchableSelect
                    options={receiverOptions}
                    value={receiverOptions.find((opt) => opt.value === filters.receiver) || null}
                    onChange={handleReceiverChange}
                    placeholder={__('Receiver', 'debug-suite')}
                    className="w-48"
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

                {/* Search Button */}
                <Button
                    variant="primary"
                    size="md"
                    onClick={() => {
                        // Trigger search - already handled by input change
                    }}>
                    {__('Search', 'debug-suite')}
                </Button>

                {/* Refresh Button */}
                <Fill name="debug-suite-layout-header-right">
                    <Button
                        variant="default"
                        loading={loading}
                        onClick={onRefresh}
                        icon={<RefreshCw className="h-4 w-4" />}>
                        {__('Refresh', 'debug-suite')}
                    </Button>
                </Fill>
            </div>
        </div>
    );
};

export default EmailLogControls;
