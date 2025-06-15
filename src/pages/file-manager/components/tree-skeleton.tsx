/**
 * FileTreeLoadingSkeleton component.
 *
 * Animated skeleton for loading file tree view, visually representing a hierarchical structure.
 *
 * @since 1.0.0
 */
import { cn } from '@/utils/cn';
import { __ } from '@wordpress/i18n';

/**
 * FileTreeLoadingSkeleton component.
 *
 * Displays a multi-level skeleton for the file tree loading state.
 *
 * @since 1.0.0
 */
const FileTreeLoadingSkeleton = (): JSX.Element => {
    return (
        <div role="status" aria-busy="true" className={cn('w-full animate-pulse cursor-wait select-none')}>
            {/* Root folder skeleton */}
            <div className={cn('mb-2 flex h-5 items-center gap-2')}>
                <div className={cn('bg-primary-300 dark:bg-primary-700 h-4 w-4 rounded')} />
                <div className={cn('h-4 w-32 rounded bg-gray-200 dark:bg-gray-700')} />
            </div>
            {/* Child file/folder skeletons */}
            <div className="ml-6 space-y-2">
                <div className={cn('flex h-4 items-center gap-2')}>
                    <div className={cn('bg-primary-200 dark:bg-primary-800 h-3 w-3 rounded')} />
                    <div className={cn('h-3 w-24 rounded bg-gray-200 dark:bg-gray-700')} />
                </div>
                <div className={cn('flex h-4 items-center gap-2')}>
                    <div className={cn('bg-primary-200 dark:bg-primary-800 h-3 w-3 rounded')} />
                    <div className={cn('h-3 w-20 rounded bg-gray-200 dark:bg-gray-700')} />
                </div>
                {/* Nested folder */}
                <div className="ml-6 space-y-2">
                    <div className={cn('flex h-4 items-center gap-2')}>
                        <div className={cn('bg-primary-100 dark:bg-primary-900 h-3 w-3 rounded')} />
                        <div className={cn('h-3 w-16 rounded bg-gray-200 dark:bg-gray-700')} />
                    </div>
                    <div className={cn('flex h-4 items-center gap-2')}>
                        <div className={cn('bg-primary-100 dark:bg-primary-900 h-3 w-3 rounded')} />
                        <div className={cn('h-3 w-14 rounded bg-gray-200 dark:bg-gray-700')} />
                    </div>
                </div>
            </div>
            <span className="sr-only">{__('Loading file tree...', 'debug-suite')}</span>
        </div>
    );
};

export default FileTreeLoadingSkeleton;
