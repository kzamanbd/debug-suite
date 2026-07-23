/**
 * Pagination Component - Reusable pagination component for tables and lists.
 *
 * @since 1.0.0
 *
 * @example
 * ```tsx
 * import { Pagination } from '@/components/ui';
 * import type { PaginationInfo } from '@/components/ui';
 *
 * const paginationInfo: PaginationInfo = {
 *     current_page: 1,
 *     total_pages: 10,
 *     from: 1,
 *     to: 20,
 *     total_items: 200,
 *     per_page: 20
 * };
 *
 * <Pagination
 *     paginationInfo={paginationInfo}
 *     onPageChange={(page) => setCurrentPage(page)}
 *     showInfo={true}
 *     showPages={5}
 * />
 * ```
 */
import { classNames } from '@/utils';
import { __ } from '@wordpress/i18n';
import { ChevronLeft, ChevronRight } from 'lucide-react';

export interface PaginationInfo {
    current_page: number;
    total_pages: number;
    from: number;
    to: number;
    total_items: number;
    per_page?: number; // Optional for backwards compatibility
}

interface PaginationProps {
    paginationInfo: PaginationInfo;
    onPageChange: (page: number) => void;
    showInfo?: boolean;
    showPages?: number;
    className?: string;
}

const Pagination = ({
    paginationInfo,
    onPageChange,
    showInfo = true,
    showPages = 5,
    className = ''
}: PaginationProps) => {
    const { current_page, total_pages, from, to, total_items } = paginationInfo;

    if (total_pages <= 1) return null;

    const pages = [];
    let startPage = Math.max(1, current_page - Math.floor(showPages / 2));
    const endPage = Math.min(total_pages, startPage + showPages - 1);

    if (endPage - startPage + 1 < showPages) {
        startPage = Math.max(1, endPage - showPages + 1);
    }

    // First page and ellipsis
    if (startPage > 1) {
        pages.push(
            <button
                key="page-1"
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
                key={`page-${i}`}
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
                key={`page-${total_pages}`}
                onClick={() => onPageChange(total_pages)}
                className="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50">
                {total_pages}
            </button>
        );
    }

    return (
        <div
            className={classNames(
                'flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6',
                className
            )}>
            {/* Mobile pagination */}
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

            {/* Desktop pagination */}
            <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                {showInfo && (
                    <div>
                        <p className="text-sm text-gray-700">
                            {__('Showing', 'debug-suite')} <span className="font-medium">{from}</span>{' '}
                            {__('to', 'debug-suite')} <span className="font-medium">{to}</span>{' '}
                            {__('of', 'debug-suite')}{' '}
                            <span className="font-medium">{total_items.toLocaleString()}</span>{' '}
                            {__('results', 'debug-suite')}
                        </p>
                    </div>
                )}
                <div>
                    <nav className="relative z-0 inline-flex -space-x-px rounded-md" aria-label="Pagination">
                        {/* Previous button */}
                        <button
                            onClick={() => current_page > 1 && onPageChange(current_page - 1)}
                            disabled={current_page <= 1}
                            className="relative inline-flex items-center rounded-l-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">
                            <span className="sr-only">{__('Previous', 'debug-suite')}</span>
                            <ChevronLeft className="size-4" />
                        </button>

                        {/* Page numbers */}
                        {pages}

                        {/* Next button */}
                        <button
                            onClick={() => current_page < total_pages && onPageChange(current_page + 1)}
                            disabled={current_page >= total_pages}
                            className="relative inline-flex items-center rounded-r-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">
                            <span className="sr-only">{__('Next', 'debug-suite')}</span>
                            <ChevronRight className="size-4" />
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    );
};

export default Pagination;
