/**
 * LogControls component - Consolidated controls for log filtering, search, pagination and actions.
 *
 * @since 1.0.0
 */
import { Button, SimpleSelect, TextInput as InputField } from '@/components/ui';
import type { Option } from '@/components/ui';
import { useConfirm } from '@/hooks/use-confirm';
import { classNames } from '@/utils';
import { Fill } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Download, EyeIcon, FileTextIcon, Filter, RefreshCw, SearchIcon, Trash2, XIcon } from 'lucide-react';
import { useState } from 'react';
import { levelOptions, perPageOptions, sortOptions } from '../constants';
import type { LogFilters, RawFileContent, ViewMode } from '../types';

interface LogControlsProps {
    // File selection
    selectedFile: Option | null;
    onFileChange: (file: Option | null) => void;

    // View mode
    viewMode: ViewMode;
    onViewModeChange: (mode: ViewMode) => void;

    // Filters (only used in parsed mode)
    filters: LogFilters;
    onFiltersChange: (newFilters: Partial<LogFilters>) => void;

    // Data stats
    totalEntries: number;

    // Actions
    onRefresh: () => void;
    onClear: () => void;
    clearing: boolean;
    loading?: boolean;

    // Raw file content (only used in raw mode)
    rawContent?: RawFileContent | null;
}

const LogControls = ({
    selectedFile,
    onFileChange,
    viewMode,
    onViewModeChange,
    filters,
    onFiltersChange,
    totalEntries,
    onRefresh,
    onClear,
    clearing,
    rawContent,
    loading = false
}: LogControlsProps) => {
    const [showFilters, setShowFilters] = useState(false);
    const confirm = useConfirm({
        type: 'confirm',
        showCancel: true,
        showOk: true
    });

    const filteredLogFiles = window.debugSuite.logs.map((file) => ({
        value: file.path,
        label: file.name,
        ...file
    }));

    const clearSearch = () => {
        onFiltersChange({ search: '' });
    };

    const handleClear = async () => {
        if (
            await confirm(
                __('Are you sure you want to clear all log entries? This action cannot be undone.', 'debug-suite')
            )
        ) {
            onClear();
        }
    };

    const handleDownload = () => {
        if (!(viewMode === 'raw' && rawContent?.content)) return;

        const blob = new Blob([rawContent.content], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = rawContent.filename || 'debug.log';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    };

    const Toolbar = () => (
        <>
            <SimpleSelect
                options={filteredLogFiles}
                value={selectedFile}
                onChange={onFileChange}
                className="w-56 p-1.5"
                placeholder={__('Select a log file', 'debug-suite')}
            />
            {/* View Mode Toggle */}
            <nav className="flex gap-x-0.5 rounded-lg bg-gray-100 p-0.5 md:gap-x-1 dark:bg-neutral-800">
                <button
                    type="button"
                    onClick={() => onViewModeChange('parsed')}
                    className={classNames(
                        'flex items-center rounded-md border border-transparent p-1.5 text-xs font-medium transition-all duration-200 focus:outline-hidden sm:px-2 md:text-[13px]',
                        viewMode === 'parsed'
                            ? 'bg-white text-gray-800 shadow-sm hover:border-transparent focus:border-transparent'
                            : 'text-gray-800 hover:border-gray-400 focus:border-gray-400 dark:text-neutral-200 dark:hover:border-neutral-500 dark:hover:text-white dark:focus:border-neutral-500 dark:focus:text-white'
                    )}>
                    <EyeIcon className="size-4" />
                    <span className="hidden sm:inline">{__('Parsed', 'debug-suite')}</span>
                </button>
                <button
                    type="button"
                    onClick={() => onViewModeChange('raw')}
                    className={classNames(
                        'flex items-center rounded-md border border-transparent p-1.5 text-xs font-medium transition-all duration-200 focus:outline-hidden sm:px-2 md:text-[13px]',
                        viewMode === 'raw'
                            ? 'bg-white text-gray-800 shadow-sm hover:border-transparent focus:border-transparent'
                            : 'text-gray-800 hover:border-gray-400 focus:border-gray-400 dark:text-neutral-200 dark:hover:border-neutral-500 dark:hover:text-white dark:focus:border-neutral-500 dark:focus:text-white'
                    )}>
                    <FileTextIcon className="size-4" />
                    <span className="hidden sm:inline">{__('Raw', 'debug-suite')}</span>
                </button>
            </nav>

            <div className="flex items-center rounded-md border">
                <Button
                    variant="outline"
                    onClick={onRefresh}
                    disabled={loading}
                    className="group relative rounded-none rounded-l-md border-0 border-r p-1.5"
                    title="Refresh">
                    <RefreshCw className={classNames('size-4 shrink-0', loading && 'animate-spin')} />
                </Button>

                <Button
                    variant="outline"
                    onClick={() => setShowFilters(!showFilters)}
                    className={classNames(
                        'group relative rounded-none border-0 border-r p-1.5',
                        showFilters && 'bg-gray-100',
                        viewMode === 'raw' && 'hidden'
                    )}
                    title="Show/hide filters panel">
                    <Filter className="size-4 shrink-0" />
                </Button>
                <Button
                    variant="outline"
                    onClick={handleDownload}
                    className="group relative rounded-none border-0 border-r p-1.5"
                    title="Download logs">
                    <Download className="size-4 shrink-0" />
                </Button>
                <Button
                    variant="outline"
                    onClick={handleClear}
                    disabled={clearing}
                    className="group relative rounded-none rounded-r-md border-0 p-1.5"
                    title="Clear all logs">
                    <Trash2 className="size-4 shrink-0" />
                </Button>
            </div>
        </>
    );

    return (
        <>
            {/* Header with file selector */}
            <Fill name="debug-suite-layout-header-right">
                <Toolbar />
            </Fill>

            <Fill name="console-logs-actions">
                <Toolbar />
            </Fill>

            {/* Filters and actions - only show in parsed mode */}
            {viewMode === 'parsed' && showFilters && (
                <div className="border-t bg-white py-4">
                    <div className="flex flex-col flex-wrap gap-4 md:gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3 md:gap-4">
                                <SimpleSelect
                                    options={levelOptions}
                                    value={levelOptions.find((opt) => opt.value === filters.level) || levelOptions[0]}
                                    onChange={(option) => onFiltersChange({ level: option?.value || '' })}
                                />
                                <SimpleSelect
                                    options={sortOptions}
                                    value={sortOptions.find((opt) => opt.value === filters.sortBy) || sortOptions[0]}
                                    onChange={(option) => onFiltersChange({ sortBy: option?.value || '' })}
                                    className="w-[150px]"
                                />

                                <SimpleSelect
                                    options={perPageOptions}
                                    value={
                                        perPageOptions.find((opt) => opt.value === filters.perPage.toString()) ||
                                        perPageOptions[0]
                                    }
                                    onChange={(option) =>
                                        onFiltersChange({ perPage: parseInt(option?.value || '100', 10) })
                                    }
                                    className="w-[150px]"
                                />
                            </div>

                            {/* Total entries display */}
                            <div className="order-first text-sm text-gray-600 sm:order-none">
                                {filters.search || filters.level ? (
                                    <>
                                        {__('Showing:', 'debug-suite')}{' '}
                                        <span className="text-primary font-medium">{totalEntries}</span>
                                        {filters.search && (
                                            <span className="ml-1 text-xs text-gray-500">
                                                ({__('filtered', 'debug-suite')})
                                            </span>
                                        )}
                                    </>
                                ) : (
                                    <>
                                        {__('Total entries:', 'debug-suite')}{' '}
                                        <span className="font-medium">{totalEntries}</span>
                                    </>
                                )}
                            </div>
                        </div>

                        <div className="flex items-center gap-2 md:gap-3">
                            <div className="relative flex-1 md:flex-none">
                                <SearchIcon className="absolute top-1/2 left-3 size-4 -translate-y-1/2 transform text-gray-400" />
                                <InputField
                                    type="text"
                                    placeholder={__('Search in log...', 'debug-suite')}
                                    value={filters.search}
                                    onChange={(e) => onFiltersChange({ search: e.target.value })}
                                    className="w-full pl-10 md:w-48"
                                />
                                {filters.search && (
                                    <button
                                        onClick={clearSearch}
                                        className="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600"
                                        title={__('Clear search', 'debug-suite')}>
                                        <XIcon className="size-4" />
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
};

export default LogControls;
