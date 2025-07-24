import { classNames } from '@/utils';
import { __ } from '@wordpress/i18n';
import { TriangleAlert } from 'lucide-react';

/**
 * NotFound page component for 404 routes.
 *
 * @since 1.0.0
 */
const NotFound = (): JSX.Element => {
    return (
        <div
            className={classNames(
                'flex h-screen flex-col items-center justify-center py-12',
                'mx-auto max-w-lg rounded-lg bg-white text-center dark:bg-gray-900'
            )}>
            <div className="mb-6">
                <span className="inline-flex items-center justify-center rounded-full bg-red-100 p-4 dark:bg-red-900">
                    <TriangleAlert className="h-10 w-10 text-red-500" />
                </span>
            </div>
            <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                {__('404 – Page Not Found', 'debug-suite')}
            </h1>
            <p className="mb-6 text-lg text-gray-600 dark:text-gray-300">
                {__('Sorry, the page you are looking for does not exist or has been moved.', 'debug-suite')}
            </p>
            <a
                href="/wp-admin/admin.php?page=debug-suite"
                className="bg-primary-600 hover:bg-primary-700 focus:ring-primary-400 dark:bg-primary-500 dark:hover:bg-primary-400 inline-block rounded-lg px-6 py-2 text-base font-semibold text-white shadow transition focus:ring-2 focus:outline-none">
                {__('Go to Dashboard', 'debug-suite')}
            </a>
        </div>
    );
};

export default NotFound;
