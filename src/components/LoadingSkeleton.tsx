/**
 * LoadingSkeleton component.
 *
 * Animated skeleton for loading states, with i18n support.
 *
 * @since 1.0.0
 */
import { cn } from '@/utils/cn';
import { __ } from '@wordpress/i18n';

const LoadingSkeleton = () => {
    return (
        <div role="status" className={cn('w-full animate-pulse cursor-wait')}>
            <div className="mb-2.5 h-6 rounded-sm bg-gray-200 dark:bg-gray-700"></div>
            <span className="sr-only">{__('Loading...', 'debug-suite')}</span>
        </div>
    );
};

export default LoadingSkeleton;
