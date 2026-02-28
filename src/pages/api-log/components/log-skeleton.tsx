/**
 * API Log Skeleton - Loading skeleton component for API logs.
 *
 * @since 1.2.0
 */

const ApiLogSkeleton = () => {
    return (
        <div className="flex h-full flex-col space-y-6">
            {/* Controls Skeleton */}
            <div className="space-y-4">
                {/* Status and Count Info */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <div className="h-4 w-24 animate-pulse rounded bg-gray-200"></div>
                        <div className="h-4 w-4 animate-pulse rounded bg-gray-200"></div>
                        <div className="h-4 w-24 animate-pulse rounded bg-gray-200"></div>
                        <div className="h-4 w-4 animate-pulse rounded bg-gray-200"></div>
                        <div className="h-4 w-24 animate-pulse rounded bg-gray-200"></div>
                    </div>
                    <div className="h-4 w-24 animate-pulse rounded bg-gray-200"></div>
                </div>

                {/* Filter Controls */}
                <div className="flex flex-wrap items-center gap-4">
                    <div className="h-10 w-40 animate-pulse rounded bg-gray-200"></div>
                    <div className="h-10 w-16 animate-pulse rounded bg-gray-200"></div>
                    <div className="h-10 w-36 animate-pulse rounded bg-gray-200"></div>
                    <div className="h-10 w-44 animate-pulse rounded bg-gray-200"></div>
                    <div className="h-10 w-64 animate-pulse rounded bg-gray-200"></div>
                </div>
            </div>

            {/* Table Skeleton */}
            <div className="flex-1 overflow-hidden rounded-lg border border-gray-200 bg-white">
                <div className="animate-pulse">
                    {/* Table Header */}
                    <div className="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <div className="flex items-center space-x-6">
                            <div className="h-4 w-4 rounded bg-gray-200"></div>
                            <div className="h-4 w-14 rounded bg-gray-200"></div>
                            <div className="h-4 w-48 rounded bg-gray-200"></div>
                            <div className="h-4 w-10 rounded bg-gray-200"></div>
                            <div className="h-4 w-16 rounded bg-gray-200"></div>
                            <div className="h-4 w-20 rounded bg-gray-200"></div>
                            <div className="h-4 w-32 rounded bg-gray-200"></div>
                            <div className="h-4 w-12 rounded bg-gray-200"></div>
                        </div>
                    </div>

                    {/* Table Rows */}
                    {Array.from({ length: 10 }, (_, i) => (
                        <div key={i} className="border-b border-gray-100 px-6 py-4">
                            <div className="flex items-center space-x-6">
                                <div className="h-4 w-4 rounded bg-gray-200"></div>
                                <div className="h-5 w-14 rounded bg-gray-200"></div>
                                <div className="h-4 max-w-md flex-1 rounded bg-gray-200"></div>
                                <div className="h-5 w-10 rounded bg-gray-200"></div>
                                <div className="h-4 w-16 rounded bg-gray-200"></div>
                                <div className="h-4 w-20 rounded bg-gray-200"></div>
                                <div className="h-4 w-32 rounded bg-gray-200"></div>
                                <div className="flex items-center space-x-2">
                                    <div className="h-4 w-4 rounded bg-gray-200"></div>
                                    <div className="h-4 w-4 rounded bg-gray-200"></div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Pagination Skeleton */}
                <div className="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                    <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div className="h-4 w-48 animate-pulse rounded bg-gray-200"></div>
                        <div className="flex items-center space-x-2">
                            <div className="h-8 w-8 animate-pulse rounded bg-gray-200"></div>
                            <div className="h-8 w-8 animate-pulse rounded bg-gray-200"></div>
                            <div className="h-8 w-8 animate-pulse rounded bg-gray-200"></div>
                            <div className="h-8 w-8 animate-pulse rounded bg-gray-200"></div>
                            <div className="h-8 w-8 animate-pulse rounded bg-gray-200"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ApiLogSkeleton;
