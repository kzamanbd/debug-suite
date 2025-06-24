/**
 * LogControls component - Consolidated controls for log filtering, search, pagination and actions.
 *
 * @since 1.0.0
 */
import Button from '@/components/ui/button';
import Combobox from '@/components/ui/combobox';
import InputField from '@/components/ui/input-field';
import { classNames } from '@/utils';
import { __ } from '@wordpress/i18n';
import {
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ChevronUpIcon,
    DownloadIcon,
    EyeIcon,
    FileTextIcon,
    RefreshCwIcon,
    SearchIcon,
    TrashIcon,
    XIcon
} from 'lucide-react';
import { levelOptions, perPageOptions, sortOptions } from '../constants';
import type { LogFile, LogFilters, ViewMode } from '../types';

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

    // Pagination (only used in parsed mode)
    currentPage: number;
    totalPages: number;
    totalEntries: number;
    perPage: number;
    onPageChange: (page: number) => void;

    // Actions
    onRefresh: () => void;
    onClear: () => void;
    onExport: (format: 'json' | 'csv' | 'txt') => void;
    clearing: boolean;
    filesLoading?: boolean;
}

const LogControls = ({
    logFiles,
    selectedFile,
    onFileChange,
    viewMode,
    onViewModeChange,
    filters,
    onFiltersChange,
    currentPage,
    totalPages,
    totalEntries,
    perPage,
    onPageChange,
    onRefresh,
    onClear,
    onExport,
    clearing,
    filesLoading = false
}: LogControlsProps) => {
    const filteredLogFiles = logFiles.map((file) => ({
        value: file.path,
        label: file.name,
        meta: `${file.type} • ${file.size} • Modified: ${file.modified}`
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
            label: logFiles.find((f) => f.path === selectedFile)?.name || 'debug.log',
            meta: `${logFiles.find((f) => f.path === selectedFile)?.type || 'WordPress Debug'} • ${logFiles.find((f) => f.path === selectedFile)?.size || '0 B'}`
        };
    };

    const clearSearch = () => {
        onFiltersChange({ search: '' });
    };

    const toggleSortOrder = () => {
        const newOrder = filters.sortOrder === 'asc' ? 'desc' : 'asc';
        onFiltersChange({ sortOrder: newOrder });
    };

    const handleClear = () => {
        if (
            confirm(__('Are you sure you want to clear all log entries? This action cannot be undone.', 'debug-suite'))
        ) {
            onClear();
        }
    };

    // Pagination helpers
    const goToPage = (page: number) => {
        if (page >= 1 && page <= totalPages) {
            onPageChange(page);
        }
    };

    const RenderPagination = () => {
        if (totalPages <= 1) return null;

        const startEntry = (currentPage - 1) * perPage + 1;
        const endEntry = Math.min(currentPage * perPage, totalEntries);

        return (
            <div className="flex items-center justify-between border-gray-200 bg-white py-3">
                <div className="flex flex-1 justify-between sm:hidden">
                    <Button variant="light" onClick={() => goToPage(currentPage - 1)} disabled={currentPage === 1}>
                        {__('Previous', 'debug-suite')}
                    </Button>
                    <Button
                        variant="light"
                        onClick={() => goToPage(currentPage + 1)}
                        disabled={currentPage === totalPages}
                    >
                        {__('Next', 'debug-suite')}
                    </Button>
                </div>
                <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p className="text-sm text-gray-700">
                            {__('Showing', 'debug-suite')} <span className="font-medium">{startEntry}</span>{' '}
                            {__('to', 'debug-suite')} <span className="font-medium">{endEntry}</span>{' '}
                            {__('of', 'debug-suite')} <span className="font-medium">{totalEntries}</span>{' '}
                            {__('results', 'debug-suite')}
                        </p>
                    </div>
                    <div>
                        <nav className="isolate inline-flex gap-2" aria-label="Pagination">
                            <Button
                                variant="light"
                                onClick={() => goToPage(currentPage - 1)}
                                disabled={currentPage === 1}
                                className="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50"
                            >
                                <span className="sr-only">{__('Previous', 'debug-suite')}</span>
                                <ChevronLeftIcon className="h-5 w-5" aria-hidden="true" />
                            </Button>

                            {Array.from({ length: totalPages }, (_, i) => i + 1)
                                .filter((page) => {
                                    if (totalPages <= 7) return true;
                                    if (page === 1 || page === totalPages) return true;
                                    if (page >= currentPage - 1 && page <= currentPage + 1) return true;
                                    return false;
                                })
                                .map((page, index, array) => {
                                    const showEllipsis =
                                        index > 0 && array[index - 1] !== undefined && array[index - 1] < page - 1;

                                    return (
                                        <div className="flex gap-2" key={page}>
                                            {showEllipsis && (
                                                <Button
                                                    variant="light"
                                                    className="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50"
                                                    disabled
                                                    aria-label={__('More pages', 'debug-suite')}
                                                >
                                                    ...
                                                </Button>
                                            )}
                                            <Button
                                                variant={page === currentPage ? 'primary' : 'light'}
                                                onClick={() => goToPage(page)}
                                                className={classNames(
                                                    'relative inline-flex items-center border px-4 py-2 text-sm font-medium',
                                                    page === currentPage
                                                        ? 'border-primary-500 bg-primary-50 text-primary-600 z-10'
                                                        : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50'
                                                )}
                                            >
                                                {page}
                                            </Button>
                                        </div>
                                    );
                                })}

                            <Button
                                variant="light"
                                onClick={() => goToPage(currentPage + 1)}
                                disabled={currentPage === totalPages}
                                className="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50"
                            >
                                <span className="sr-only">{__('Next', 'debug-suite')}</span>
                                <ChevronRightIcon className="h-5 w-5" aria-hidden="true" />
                            </Button>
                        </nav>
                    </div>
                </div>
            </div>
        );
    };

    return (
        <div className="divide divide-y">
            {/* Header with file selector */}
            <div className="bg-white py-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Combobox
                            options={filteredLogFiles}
                            value={selectedLogFile()}
                            onChange={(option) => onFileChange(option?.value || '')}
                            isDisabled={filesLoading}
                            className="min-w-[250px]"
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
                                <EyeIcon className="mr-1.5 h-3.5 w-3.5" />
                                {__('Parsed', 'debug-suite')}
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
                                <FileTextIcon className="mr-1.5 h-3.5 w-3.5" />
                                {__('Raw File', 'debug-suite')}
                            </button>
                        </nav>
                    </div>

                    <div className="flex items-center space-x-2">
                        {viewMode === 'parsed' && (
                            <div className="relative">
                                <SearchIcon className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                                <InputField
                                    type="text"
                                    placeholder={__('Search in log...', 'debug-suite')}
                                    value={filters.search}
                                    onChange={(e) => onFiltersChange({ search: e.target.value })}
                                    className="w-64 pl-10"
                                />
                                {filters.search && (
                                    <button
                                        onClick={clearSearch}
                                        className="absolute top-1/2 right-3 -translate-y-1/2 transform"
                                    >
                                        <XIcon className="h-4 w-4 text-gray-400" />
                                    </button>
                                )}
                            </div>
                        )}
                        <Button variant="light" onClick={onRefresh}>
                            <RefreshCwIcon className="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>

            {/* Filters and actions - only show in parsed mode */}
            {viewMode === 'parsed' && (
                <div className="bg-white py-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-4">
                            <Combobox
                                options={levelOptions}
                                value={levelOptions.find((opt) => opt.value === filters.level) || levelOptions[0]}
                                onChange={(option) => onFiltersChange({ level: option?.value || '' })}
                            />
                            <Combobox
                                options={sortOptions}
                                value={sortOptions.find((opt) => opt.value === filters.sortBy) || sortOptions[0]}
                                onChange={(option) => onFiltersChange({ sortBy: option?.value || 'timestamp' })}
                            />
                            <Button variant="light" onClick={toggleSortOrder} className="flex items-center space-x-1">
                                {filters.sortOrder === 'asc' ? (
                                    <ChevronUpIcon className="h-4 w-4" />
                                ) : (
                                    <ChevronDownIcon className="h-4 w-4" />
                                )}
                                <span>
                                    {filters.sortOrder === 'asc'
                                        ? __('Ascending', 'debug-suite')
                                        : __('Descending', 'debug-suite')}
                                </span>
                            </Button>
                        </div>

                        <div className="flex items-center space-x-2">
                            <Combobox
                                options={perPageOptions}
                                value={
                                    perPageOptions.find((opt) => opt.value === filters.perPage.toString()) ||
                                    perPageOptions[1]
                                }
                                onChange={(option) => onFiltersChange({ perPage: parseInt(option?.value || '25') })}
                            />
                            <Button variant="light" onClick={() => onExport('json')}>
                                <DownloadIcon className="mr-2 h-4 w-4" />
                                {__('Export', 'debug-suite')}
                            </Button>
                            <Button variant="light" onClick={handleClear} disabled={clearing}>
                                <TrashIcon className="mr-2 h-4 w-4" />
                                {clearing ? __('Clearing...', 'debug-suite') : __('Clear', 'debug-suite')}
                            </Button>
                        </div>
                    </div>
                </div>
            )}

            {/* Pagination (rendered at bottom by parent) - only show in parsed mode */}
            {viewMode === 'parsed' && totalPages > 1 && <RenderPagination />}
        </div>
    );
};

export default LogControls;
