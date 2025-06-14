/**
 * SettingsSkeleton component.
 *
 * Skeleton loader for the Settings page, matching the layout and style of the settings form.
 *
 * @since 1.0.0
 */
import { twMerge } from 'tailwind-merge';

/**
 * SettingsSkeletonProps interface.
 *
 * @since 1.0.0
 */
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
    const skeleton = (extra: string = '') => twMerge('bg-gray-200 dark:bg-gray-700 animate-pulse rounded', extra);

    return (
        <div className={twMerge('max-w-4xl mx-auto space-y-6', className)}>
            {/* Header Skeleton */}
            <div className="mb-6 sm:mb-8">
                <div className={skeleton('h-8 w-48 mb-2')}></div>
                <div className={skeleton('h-4 w-80')}></div>
            </div>
            {/* Settings Form Skeleton */}
            <div className="space-y-4 sm:space-y-6">
                {/* File Manager Settings Skeleton */}
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div className="border-l-4 border-l-blue-500 px-4 sm:px-6 py-3 sm:py-4 bg-blue-50 dark:bg-blue-950">
                        <div className={skeleton('h-6 w-64 mb-2')}></div>
                        <div className={skeleton('h-4 w-40')}></div>
                    </div>
                    <div className="p-4 sm:p-6 space-y-6">
                        {/* 3 rows for file manager settings */}
                        {[1, 2, 3].map((i) => (
                            <div key={i} className="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                <div>
                                    <div className={skeleton('h-4 w-32 mb-2')}></div>
                                    <div className={skeleton('h-3 w-24')}></div>
                                </div>
                                <div className="md:col-span-2">
                                    <div className={skeleton('h-10 w-full')}></div>
                                </div>
                            </div>
                        ))}
                        {/* View type radio skeleton */}
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                            <div>
                                <div className={skeleton('h-4 w-32 mb-2')}></div>
                                <div className={skeleton('h-3 w-24')}></div>
                            </div>
                            <div className="md:col-span-2 flex space-x-4">
                                <div className={skeleton('h-10 w-24')}></div>
                                <div className={skeleton('h-10 w-24')}></div>
                            </div>
                        </div>
                        {/* Toggle skeletons */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                            {[1, 2].map((i) => (
                                <div
                                    key={i}
                                    className={twMerge(
                                        'flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg',
                                        className
                                    )}
                                >
                                    <div>
                                        <div className={skeleton('h-4 w-32 mb-2')}></div>
                                        <div className={skeleton('h-3 w-24')}></div>
                                    </div>
                                    <div className={skeleton('h-6 w-12')}></div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
                {/* Debug Settings Skeleton */}
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div className="border-l-4 border-l-green-500 px-4 sm:px-6 py-3 sm:py-4 bg-green-50 dark:bg-green-950">
                        <div className={skeleton('h-6 w-64 mb-2')}></div>
                        <div className={skeleton('h-4 w-40')}></div>
                    </div>
                    <div className="p-4 sm:p-6 space-y-4">
                        {[1, 2, 3, 4, 5].map((i) => (
                            <div
                                key={i}
                                className={twMerge(
                                    'flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg',
                                    className
                                )}
                            >
                                <div>
                                    <div className={skeleton('h-4 w-32 mb-2')}></div>
                                    <div className={skeleton('h-3 w-24')}></div>
                                </div>
                                <div className={skeleton('h-6 w-12')}></div>
                            </div>
                        ))}
                    </div>
                </div>
                {/* Action Buttons Skeleton */}
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 sm:p-6 flex space-x-3">
                    <div className={skeleton('h-10 w-40')}></div>
                    <div className={skeleton('h-10 w-40')}></div>
                </div>
            </div>
        </div>
    );
};

export default SettingsSkeleton;
