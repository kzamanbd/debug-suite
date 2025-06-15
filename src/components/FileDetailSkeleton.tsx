/**
 * FileDetailSkeleton component.
 *
 * Animated skeleton for file detail view in a table layout.
 *
 * @since 1.0.0
 */
import { cn } from '@/utils/cn';
import { __ } from '@wordpress/i18n';

/**
 * FileDetailSkeleton component.
 *
 * Displays a table-style skeleton for loading file details.
 *
 * @since 1.0.0
 */
const FileDetailSkeleton = ({ className = '' }: { className?: string }): JSX.Element => {
    return (
        <div className={cn('w-full animate-pulse rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800', className)}>
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                {__('Name', 'debug-suite')}
                            </th>
                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                {__('Size', 'debug-suite')}
                            </th>
                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                {__('Last Modified', 'debug-suite')}
                            </th>
                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                {__('Actions', 'debug-suite')}
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100 dark:divide-gray-700">
                        {[...Array(15)].map((_, idx) => (
                            <tr key={idx}>
                                <td className="px-4 py-3">
                                    <div className="bg-primary-200 dark:bg-primary-800 h-4 w-32 rounded" />
                                </td>
                                <td className="px-4 py-3">
                                    <div className="h-4 w-16 rounded bg-gray-200 dark:bg-gray-700" />
                                </td>
                                <td className="px-4 py-3">
                                    <div className="h-4 w-24 rounded bg-gray-200 dark:bg-gray-700" />
                                </td>
                                <td className="flex gap-2 px-4 py-3">
                                    <div className="bg-primary-100 dark:bg-primary-900 h-4 w-8 rounded" />
                                    <div className="bg-primary-100 dark:bg-primary-900 h-4 w-8 rounded" />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <span className="sr-only">{__('Loading file details...', 'debug-suite')}</span>
        </div>
    );
};

export default FileDetailSkeleton;
