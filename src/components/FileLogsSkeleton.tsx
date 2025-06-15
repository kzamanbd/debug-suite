/**
 * FileLogsSkeleton component.
 *
 * Skeleton loader for the FileLogs page, matching the FileLogs layout and style.
 *
 * @since 1.0.0
 */
import { cn } from '@/utils/cn';

interface FileLogsSkeletonProps {
    className?: string;
}

const skeleton = (extra: string = '') => cn('bg-gray-200 dark:bg-gray-700 animate-pulse rounded', extra);

const FileLogsSkeleton = ({ className = '' }: FileLogsSkeletonProps): JSX.Element => {
    return (
        <div className={cn('space-y-6', className)}>
            {/* Header skeleton */}
            <div className={skeleton('mb-4 h-5 w-80')}></div>
            {/* Action buttons skeleton */}
            <div className="mb-8 flex flex-wrap gap-4">
                <div className={skeleton('h-10 w-32')}></div>
                <div className={skeleton('h-10 w-36')}></div>
            </div>
            {/* Filter and controls skeleton */}
            <div className="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div className={skeleton('h-6 w-48')}></div>
                <div className="flex items-center gap-3">
                    <div className={skeleton('h-10 w-32')}></div>
                    <div className={skeleton('h-10 w-24')}></div>
                </div>
            </div>
            {/* Table skeleton */}
            <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
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
                        {[...Array(6)].map((_, i) => (
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
