/**
 * LogControls component - Consolidated controls for log filtering, search, pagination and actions.
 *
 * @since 1.0.0
 */
import Button from '@/components/ui/button';
import SearchableSelect from '@/components/ui/select';
import InputField from '@/components/ui/text-input';
import { useConfirm } from '@/hooks/useConfirm';
import { classNames } from '@/utils';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    CopyIcon,
    DownloadIcon,
    EyeIcon,
    FileTextIcon,
    RefreshCwIcon,
    SearchIcon,
    TrashIcon,
    XIcon
} from 'lucide-react';
import { exportOptions, levelOptions, perPageOptions, sortOptions } from '../constants';
import type { LogFile, LogFilters, RawFileContent, ViewMode } from '../types';

interface LogControlsProps {
    // File selection
    logFiles: LogFile[];
    selectedFile: string;
    onFileChange: (filePath: string) => void;

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
    onExport: (format: 'json' | 'csv' | 'txt') => void;
    clearing: boolean;
    filesLoading?: boolean;
    loading?: boolean;

    // Raw file content (only used in raw mode)
    rawContent?: RawFileContent | null;
}

const LogControls = ({
    logFiles,
    selectedFile,
    onFileChange,
    viewMode,
    onViewModeChange,
    filters,
    onFiltersChange,
    totalEntries,
    onRefresh,
    onClear,
    onExport,
    clearing,
    filesLoading = false,
    rawContent,
    loading = false
}: LogControlsProps) => {
    const confirm = useConfirm({
        type: 'confirm',
        showCancel: true,
        showOk: true,
        okText: __('Ok, Clear', 'debug-suite'),
        cancelText: __('Cancel', 'debug-suite')
    });
    const [copying, setCopying] = useState(false);

    const filteredLogFiles = logFiles.map((file) => ({
        value: file.path,
        label: file.name,
        ...file
    }));

    const selectedLogFile = () => {
        if (filesLoading) {
            return { value: '', label: __('Loading...', 'debug-suite') };
        }
        if (!selectedFile || !logFiles.length) {
            return null;
        }
        return {
            value: selectedFile,
            label: logFiles.find((f) => f.path === selectedFile)?.name || 'debug.log'
        };
    };

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

    const handleExport = (format: string) => {
        if (!format) {
            return;
        }
        onExport(format as 'json' | 'csv' | 'txt');
    };

    const handleCopy = async () => {
        if (!rawContent?.content) return;

        try {
            setCopying(true);
            await navigator.clipboard.writeText(rawContent.content);

            // Show temporary success feedback
            setTimeout(() => {
                setCopying(false);
            }, 2000);
        } catch (error) {
            console.error('Failed to copy content:', error);
            setCopying(false);
        }
    };

    const handleDownload = () => {
        if (!rawContent) return;

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

    return (
        <div className="divide divide-y">
            {/* Header with file selector */}
            <div className="bg-white py-4">
                <div className="flex flex-col flex-wrap gap-4 md:flex-row md:items-center md:justify-between">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                        <SearchableSelect
                            options={filteredLogFiles}
                            value={selectedLogFile()}
                            onChange={(option) => onFileChange(option?.value || '')}
                            isDisabled={filesLoading}
                            className="w-56"
                            placeholder={__('Select a log file', 'debug-suite')}
                        />

                        {/* View Mode Toggle */}
                        <nav className="flex gap-x-0.5 rounded-lg bg-gray-100 p-0.5 md:gap-x-1 dark:bg-neutral-800">
                            <button
                                type="button"
                                onClick={() => onViewModeChange('parsed')}
                                className={classNames(
                                    'flex items-center rounded-md border border-transparent px-1.5 py-2 text-xs font-medium transition-all duration-200 focus:outline-hidden sm:px-2 md:text-[13px]',
                                    viewMode === 'parsed'
                                        ? 'bg-white text-gray-800 shadow-sm hover:border-transparent focus:border-transparent'
                                        : 'text-gray-800 hover:border-gray-400 focus:border-gray-400 dark:text-neutral-200 dark:hover:border-neutral-500 dark:hover:text-white dark:focus:border-neutral-500 dark:focus:text-white'
                                )}
                            >
                                <EyeIcon className="mr-1 h-3.5 w-3.5 sm:mr-1.5" />
                                <span className="hidden sm:inline">{__('Parsed', 'debug-suite')}</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => onViewModeChange('raw')}
                                className={classNames(
                                    'flex items-center rounded-md border border-transparent px-1.5 py-2 text-xs font-medium transition-all duration-200 focus:outline-hidden sm:px-2 md:text-[13px]',
                                    viewMode === 'raw'
                                        ? 'bg-white text-gray-800 shadow-sm hover:border-transparent focus:border-transparent'
                                        : 'text-gray-800 hover:border-gray-400 focus:border-gray-400 dark:text-neutral-200 dark:hover:border-neutral-500 dark:hover:text-white dark:focus:border-neutral-500 dark:focus:text-white'
                                )}
                            >
                                <FileTextIcon className="mr-1 h-3.5 w-3.5 sm:mr-1.5" />
                                <span className="hidden sm:inline">{__('Raw File', 'debug-suite')}</span>
                            </button>
                        </nav>
                    </div>

                    <div className="flex items-center gap-2 md:gap-3">
                        {viewMode === 'parsed' && (
                            <div className="relative flex-1 md:flex-none">
                                <SearchIcon className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
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
                                        title={__('Clear search', 'debug-suite')}
                                    >
                                        <XIcon className="h-4 w-4" />
                                    </button>
                                )}
                            </div>
                        )}
                        {viewMode === 'raw' && rawContent && (
                            <>
                                <Button onClick={handleCopy} disabled={copying}>
                                    <CopyIcon className="mr-2 h-4 w-4" />
                                    {copying ? __('Copied!', 'debug-suite') : __('Copy', 'debug-suite')}
                                </Button>
                                <Button onClick={handleDownload}>
                                    <DownloadIcon className="mr-2 h-4 w-4" />
                                    {__('Download', 'debug-suite')}
                                </Button>
                            </>
                        )}
                        <Button onClick={onRefresh} className="shrink-0" disabled={loading}>
                            <RefreshCwIcon className={classNames('h-4 w-4', loading && 'animate-spin')} />
                            <span className="ml-2 hidden md:inline">{__('Refresh', 'debug-suite')}</span>
                        </Button>
                    </div>
                </div>
            </div>

            {/* Filters and actions - only show in parsed mode */}
            {viewMode === 'parsed' && (
                <div className="bg-white py-4">
                    <div className="flex flex-col flex-wrap gap-4 md:gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3 md:gap-4">
                                <SearchableSelect
                                    options={levelOptions}
                                    value={levelOptions.find((opt) => opt.value === filters.level) || levelOptions[0]}
                                    onChange={(option) => onFiltersChange({ level: option?.value || '' })}
                                />
                                <SearchableSelect
                                    options={sortOptions}
                                    value={sortOptions.find((opt) => opt.value === filters.sortBy) || sortOptions[0]}
                                    onChange={(option) => onFiltersChange({ sortBy: option?.value || '' })}
                                    className="w-[150px]"
                                />

                                <SearchableSelect
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
                            <SearchableSelect
                                options={exportOptions}
                                value={null}
                                onChange={(option) => handleExport(option?.value || '')}
                                placeholder={__('Export as...', 'debug-suite')}
                                className="w-[150px]"
                            />
                            <Button onClick={handleClear} variant="danger" disabled={clearing} className="shrink-0">
                                <TrashIcon className="h-4 w-4" />
                                <span className="ml-2 hidden md:inline">{__('Clear', 'debug-suite')}</span>
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default LogControls;
