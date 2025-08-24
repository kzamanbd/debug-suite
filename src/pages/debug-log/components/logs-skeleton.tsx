/**
 * FileLogsSkeleton component.
 *
 * Skeleton loader for the FileLogs page, matching the FileLogs layout and style.
 *
 * @since 1.0.0
 */

import { classNames } from '@/utils';

interface FileLogsSkeletonProps {
    className?: string;
}

const skeleton = (extra = '') => classNames('bg-gray-200 dark:bg-gray-700 animate-pulse rounded', extra);

const FileLogsSkeleton = ({ className = '' }: FileLogsSkeletonProps): JSX.Element => {
    return (
        <div className={classNames('space-y-6', className)}>
            {/* Table skeleton */}
            <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead className="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            {[1, 2, 3, 4].map((i) => (
                                <th key={i} className="px-4 py-3">
                                    <div className={skeleton('h-4 w-24')}></div>
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100 dark:divide-gray-900">
                        {Array.from({ length: 20 }).map((_, i) => (
                            <tr key={i}>
                                {[1, 2, 3, 4].map((j) => (
                                    <td key={j} className="px-4 py-2">
                                        <div className={skeleton('h-4 w-28')}></div>
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export default FileLogsSkeleton;
