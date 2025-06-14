import { cn } from '@/utils/cn';

interface SettingsSkeletonProps {
    className?: string;
}

/**
 * SettingsSkeleton component.
 *
 * @since 1.0.0
 */
const SettingsSkeleton = ({ className = '' }: SettingsSkeletonProps): JSX.Element => {
    // Utility for skeleton blocks
    const skeleton = (extra: string = '') => cn('bg-gray-200 dark:bg-gray-700 animate-pulse rounded', extra);

    return (
        <>
            {/* Header Skeleton */}
            <div className="mb-6 sm:mb-8">
                <div className={skeleton('mb-2 h-8 w-48')}></div>
                <div className={skeleton('h-4 w-80')}></div>
            </div>
            {/* Settings Form Skeleton */}
            <div className="space-y-4 sm:space-y-6">
                {/* File Manager Settings Skeleton */}
                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div className="border-l-4 border-l-blue-500 bg-blue-50 px-4 py-3 sm:px-6 sm:py-4 dark:bg-blue-950">
                        <div className={skeleton('mb-2 h-6 w-64')}></div>
                        <div className={skeleton('h-4 w-40')}></div>
                    </div>
                    <div className="space-y-6 p-4 sm:p-6">
                        {/* 3 rows for file manager settings */}
                        {[1, 2, 3].map((i) => (
                            <div key={i} className="grid grid-cols-1 items-center gap-4 md:grid-cols-3">
                                <div>
                                    <div className={skeleton('mb-2 h-4 w-32')}></div>
                                    <div className={skeleton('h-3 w-24')}></div>
                                </div>
                                <div className="md:col-span-2">
                                    <div className={skeleton('h-10 w-full')}></div>
                                </div>
                            </div>
                        ))}
                        {/* View type radio skeleton */}
                        <div className="grid grid-cols-1 items-center gap-4 md:grid-cols-3">
                            <div>
                                <div className={skeleton('mb-2 h-4 w-32')}></div>
                                <div className={skeleton('h-3 w-24')}></div>
                            </div>
                            <div className="flex space-x-4 md:col-span-2">
                                <div className={skeleton('h-10 w-24')}></div>
                                <div className={skeleton('h-10 w-24')}></div>
                            </div>
                        </div>
                        {/* Toggle skeletons */}
                        <div className="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-2">
                            {[1, 2].map((i) => (
                                <div
                                    key={i}
                                    className={cn(
                                        'flex items-center justify-between rounded-lg bg-gray-50 p-4 dark:bg-gray-900',
                                        className
                                    )}
                                >
                                    <div>
                                        <div className={skeleton('mb-2 h-4 w-32')}></div>
                                        <div className={skeleton('h-3 w-24')}></div>
                                    </div>
                                    <div className={skeleton('h-6 w-12')}></div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
                {/* Debug Settings Skeleton */}
                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div className="border-l-4 border-l-green-500 bg-green-50 px-4 py-3 sm:px-6 sm:py-4 dark:bg-green-950">
                        <div className={skeleton('mb-2 h-6 w-64')}></div>
                        <div className={skeleton('h-4 w-40')}></div>
                    </div>
                    <div className="space-y-4 p-4 sm:p-6">
                        {[1, 2, 3, 4, 5].map((i) => (
                            <div
                                key={i}
                                className={cn(
                                    'flex items-center justify-between rounded-lg bg-gray-50 p-4 dark:bg-gray-900',
                                    className
                                )}
                            >
                                <div>
                                    <div className={skeleton('mb-2 h-4 w-32')}></div>
                                    <div className={skeleton('h-3 w-24')}></div>
                                </div>
                                <div className={skeleton('h-6 w-12')}></div>
                            </div>
                        ))}
                    </div>
                </div>
                {/* Action Buttons Skeleton */}
                <div className="flex space-x-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6 dark:border-gray-700 dark:bg-gray-800">
                    <div className={skeleton('h-10 w-40')}></div>
                    <div className={skeleton('h-10 w-40')}></div>
                </div>
            </div>
        </>
    );
};

export default SettingsSkeleton;
